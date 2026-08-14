<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\AdminCorrectionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceService
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected SelfieService $selfieService,
        protected AttendanceStatusResolver $statusResolver,
    ) {}

    /**
     * Resolve the active work schedule for an employee considering timezone and cross-midnight shifts.
     */
    public function resolveActiveSchedule(Employee $employee): ?EmployeeSchedule
    {
        $openAttendance = AttendanceRecord::with('schedule.shift')
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->latest('check_in_at')
            ->first();

        if ($openAttendance?->schedule?->shift) {
            return $openAttendance->schedule;
        }

        $now = Carbon::now($this->timezone());
        $todayDateStr = $now->format('Y-m-d');

        // 1. Query schedule for today's date
        $schedule = EmployeeSchedule::with(['shift'])
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $todayDateStr)
            ->first();

        if ($schedule) {
            return $schedule;
        }

        // 2. Cross-midnight shift resolver: check if yesterday's night shift is still active in early morning
        $yesterdayDateStr = (clone $now)->subDay()->format('Y-m-d');
        $yesterdaySchedule = EmployeeSchedule::with(['shift'])
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $yesterdayDateStr)
            ->where('schedule_type', 'work')
            ->first();

        if ($yesterdaySchedule && $yesterdaySchedule->shift && $yesterdaySchedule->shift->crosses_midnight) {
            $currentTime = $now->format('H:i');
            $endTime = substr($yesterdaySchedule->shift->end_time, 0, 5);
            if ($currentTime <= $endTime) {
                return $yesterdaySchedule;
            }
        }

        return null;
    }

    /**
     * Validate GPS geofence position against active AttendanceLocation server-side.
     *
     * @return array{location: AttendanceLocation, distance: float}
     */
    public function validateGeofence(float $latitude, float $longitude, float $accuracy): array
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new \InvalidArgumentException('Nilai Latitude GPS tidak valid.');
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new \InvalidArgumentException('Nilai Longitude GPS tidak valid.');
        }

        if ($accuracy <= 0.0) {
            throw new \InvalidArgumentException('Nilai Akurasi GPS tidak valid.');
        }

        $activeLocation = AttendanceLocation::where('is_active', true)->first();
        if (! $activeLocation) {
            throw new \InvalidArgumentException('Lokasi absensi toko belum dikonfigurasi atau tidak aktif.');
        }

        // Accuracy Check
        if (! $this->geofenceService->isAccuracyAcceptable($accuracy, $activeLocation)) {
            $currAcc = round($accuracy);
            $maxAcc = (int) $activeLocation->max_accuracy_meters;
            throw new \InvalidArgumentException("Akurasi lokasi belum cukup baik. Akurasi saat ini ±{$currAcc} meter, sedangkan maksimum yang diperbolehkan ±{$maxAcc} meter.");
        }

        // Distance & Radius Check
        $distance = $this->geofenceService->calculateDistanceMeters(
            $latitude,
            $longitude,
            (float) $activeLocation->latitude,
            (float) $activeLocation->longitude
        );

        if ($distance > $activeLocation->radius_meters) {
            $distRound = round($distance);
            $radius = (int) $activeLocation->radius_meters;
            throw new \InvalidArgumentException("Anda berada di luar area absensi {$activeLocation->name}. Jarak Anda: {$distRound}m, Radius: {$radius}m.");
        }

        return [
            'location' => $activeLocation,
            'distance' => $distance,
        ];
    }

    /**
     * Core Check-In Action with server timestamping, geofence validation, late minutes, and duplicate protection.
     */
    public function checkIn(User $actor, array $evidence = []): AttendanceRecord
    {
        $newSelfiePath = null;

        try {
            return DB::transaction(function () use ($actor, $evidence, &$newSelfiePath) {
                $employee = $actor->employee;
                if (! $employee || $employee->status !== 'active' || ! $actor->is_active) {
                    throw new \InvalidArgumentException('Akun karyawan Anda tidak aktif atau tidak terhubung.');
                }

                $schedule = $this->resolveActiveSchedule($employee);
                if (! $schedule || $schedule->schedule_type !== 'work' || ! $schedule->shift) {
                    throw new \InvalidArgumentException('Anda tidak memiliki jadwal kerja aktif untuk melakukan absensi.');
                }

                $workDateStr = is_string($schedule->work_date) ? substr($schedule->work_date, 0, 10) : $schedule->work_date->format('Y-m-d');

                // Check duplicate check-in for this work date
                $existingRecord = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereDate('work_date', $workDateStr)
                    ->lockForUpdate()
                    ->first();

                if ($existingRecord) {
                    throw new \InvalidArgumentException('Anda sudah melakukan absensi masuk untuk jadwal kerja ini.');
                }

                if (LeaveRequest::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereIn('type', ['permission', 'sick', 'leave'])
                    ->whereDate('start_date', '<=', $workDateStr)
                    ->whereDate('end_date', '>=', $workDateStr)
                    ->exists()) {
                    throw new \InvalidArgumentException('Check-in tidak dapat dilakukan karena izin, sakit, atau cuti untuk jadwal ini sudah disetujui.');
                }

                $serverNow = Carbon::now($this->timezone());
                [$shiftStart] = $this->shiftDatetimes($schedule);
                $checkInOpen = (clone $shiftStart)->subMinutes((int) $schedule->shift->check_in_open_minutes_before);
                $checkInClose = (clone $shiftStart)->addMinutes((int) $schedule->shift->check_in_close_minutes_after);

                if ($serverNow->lessThan($checkInOpen)) {
                    throw new \InvalidArgumentException('Check-in belum dibuka untuk jadwal kerja ini.');
                }

                if ($serverNow->greaterThan($checkInClose)) {
                    throw new \InvalidArgumentException('Check-in sudah ditutup untuk jadwal kerja ini.');
                }

                // Extract & Validate GPS evidence (server recalculates distance and validates geofence)
                $rawLat = $evidence['check_in_latitude'] ?? $evidence['latitude'] ?? null;
                $rawLng = $evidence['check_in_longitude'] ?? $evidence['longitude'] ?? null;
                $rawAcc = $evidence['check_in_accuracy_meters'] ?? $evidence['accuracy'] ?? null;

                if ($rawLat === null || $rawLng === null || $rawAcc === null || ! is_numeric($rawLat) || ! is_numeric($rawLng) || ! is_numeric($rawAcc)) {
                    throw new \InvalidArgumentException('Data lokasi GPS (Latitude, Longitude, Akurasi) wajib disertakan untuk melakukan absensi masuk.');
                }

                $lat = (float) $rawLat;
                $lng = (float) $rawLng;
                $acc = (float) $rawAcc;

                $geofenceResult = $this->validateGeofence($lat, $lng, $acc);
                $location = $geofenceResult['location'];
                $distance = $geofenceResult['distance'];

                $selfieInput = $evidence['selfie'] ?? $evidence['check_in_selfie'] ?? $evidence['selfie_base64'] ?? request()->file('selfie') ?? request()->file('check_in_selfie');
                $newSelfiePath = $this->storeSelfieIfRequired($selfieInput, $employee->id, 'check_in');

                $graceMins = (int) $schedule->shift->grace_period_minutes;
                $graceLimit = (clone $shiftStart)->addMinutes($graceMins);

                if ($serverNow->greaterThan($graceLimit)) {
                    $lateMins = (int) floor($graceLimit->diffInMinutes($serverNow, false));
                    $lateMins = max(0, $lateMins);
                    $status = 'late';
                } else {
                    $lateMins = 0;
                    $status = 'present';
                }

                $record = AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $schedule->id,
                    'work_date' => $workDateStr,
                    'attendance_location_id' => $location->id,
                    'status' => $status,
                    'check_in_at' => $serverNow,
                    'check_in_latitude' => $lat,
                    'check_in_longitude' => $lng,
                    'check_in_accuracy_meters' => $acc,
                    'check_in_distance_meters' => $distance,
                    'check_in_selfie_path' => $newSelfiePath,
                    'check_in_ip' => request()->ip(),
                    'check_in_user_agent' => request()->userAgent(),
                    'late_minutes' => $lateMins,
                    'notes' => $evidence['notes'] ?? null,
                ]);

                AuditLog::log(
                    action: 'attendance.check_in',
                    model: $record,
                    before: null,
                    after: $record->toArray(),
                    user: $actor
                );

                return $record;
            });
        } catch (\Throwable $e) {
            if ($newSelfiePath !== null) {
                Storage::disk('local')->delete($newSelfiePath);
            }
            throw $e;
        }
    }

    /**
     * Core Check-Out Action with server timestamping, geofence policy check, worked minutes, early leave, and overtime.
     */
    public function checkOut(User $actor, array $evidence = []): AttendanceRecord
    {
        $newSelfiePath = null;

        try {
            return DB::transaction(function () use ($actor, $evidence, &$newSelfiePath) {
                $employee = $actor->employee;
                if (! $employee || $employee->status !== 'active' || ! $actor->is_active) {
                    throw new \InvalidArgumentException('Akun karyawan Anda tidak aktif atau tidak terhubung.');
                }

                $schedule = $this->resolveActiveSchedule($employee);
                if (! $schedule || ! $schedule->shift) {
                    throw new \InvalidArgumentException('Jadwal kerja aktif tidak ditemukan.');
                }

                $record = AttendanceRecord::where('employee_id', $employee->id)
                    ->where('work_schedule_id', $schedule->id)
                    ->lockForUpdate()
                    ->first();

                if (! $record || ! $record->check_in_at) {
                    throw new \InvalidArgumentException('Anda belum melakukan absensi masuk.');
                }

                if ($record->check_out_at !== null) {
                    throw new \InvalidArgumentException('Anda sudah melakukan absensi keluar untuk jadwal kerja ini.');
                }

                // Fetch checkout geofence setting from app_settings
                $settingRaw = DB::table('app_settings')->where('key', 'attendance_require_checkout_geofence')->first();
                $requireCheckoutGeofence = ($settingRaw?->value ?? '1') === '1';

                $rawLat = $evidence['check_out_latitude'] ?? $evidence['latitude'] ?? null;
                $rawLng = $evidence['check_out_longitude'] ?? $evidence['longitude'] ?? null;
                $rawAcc = $evidence['check_out_accuracy_meters'] ?? $evidence['accuracy'] ?? null;

                $outLat = null;
                $outLng = null;
                $outAcc = null;
                $outDist = null;

                if ($requireCheckoutGeofence) {
                    if ($rawLat === null || $rawLng === null || $rawAcc === null || ! is_numeric($rawLat) || ! is_numeric($rawLng) || ! is_numeric($rawAcc)) {
                        throw new \InvalidArgumentException('Data lokasi GPS (Latitude, Longitude, Akurasi) wajib disertakan untuk melakukan absensi keluar.');
                    }

                    $lat = (float) $rawLat;
                    $lng = (float) $rawLng;
                    $acc = (float) $rawAcc;

                    $geofenceResult = $this->validateGeofence($lat, $lng, $acc);
                    $outLat = $lat;
                    $outLng = $lng;
                    $outAcc = $acc;
                    $outDist = $geofenceResult['distance'];
                } else {
                    // Checkout geofence optional: if coordinates provided, calculate distance for evidence
                    if ($rawLat !== null && $rawLng !== null && $rawAcc !== null && is_numeric($rawLat) && is_numeric($rawLng) && is_numeric($rawAcc)) {
                        $lat = (float) $rawLat;
                        $lng = (float) $rawLng;
                        $acc = (float) $rawAcc;

                        $activeLocation = AttendanceLocation::where('is_active', true)->first();
                        if ($activeLocation) {
                            $outDist = $this->geofenceService->calculateDistanceMeters($lat, $lng, (float) $activeLocation->latitude, (float) $activeLocation->longitude);
                        }
                        $outLat = $lat;
                        $outLng = $lng;
                        $outAcc = $acc;
                    }
                }

                $workDateStr = is_string($schedule->work_date) ? substr($schedule->work_date, 0, 10) : $schedule->work_date->format('Y-m-d');
                $serverNow = Carbon::now($this->timezone());
                [, $shiftEnd] = $this->shiftDatetimes($schedule);
                $checkOutOpen = (clone $shiftEnd)->subMinutes((int) $schedule->shift->check_out_open_minutes_before);

                if ($serverNow->lessThan($checkOutOpen)) {
                    throw new \InvalidArgumentException('Check-out belum dibuka untuk jadwal kerja ini.');
                }

                $selfieInput = $evidence['selfie'] ?? $evidence['check_out_selfie'] ?? $evidence['selfie_base64'] ?? request()->file('selfie') ?? request()->file('check_out_selfie');
                $newSelfiePath = $this->storeSelfieIfRequired($selfieInput, $employee->id, 'check_out');

                // Align checkInAt wall clock time to the configured business timezone.
                $checkInTime = Carbon::createFromFormat('Y-m-d H:i:s', $record->check_in_at->format('Y-m-d H:i:s'), $this->timezone());

                // Gross worked minutes
                $grossMinutes = (int) floor($checkInTime->diffInMinutes($serverNow, false));
                $grossMinutes = max(0, $grossMinutes);

                // Net worked minutes (minus break)
                $breakMins = (int) $schedule->shift->break_minutes;
                $workedMinutes = max(0, $grossMinutes - $breakMins);

                // Early Leave Minutes
                if ($serverNow->lessThan($shiftEnd)) {
                    $earlyLeaveMins = (int) floor($serverNow->diffInMinutes($shiftEnd, false));
                    $earlyLeaveMins = max(0, $earlyLeaveMins);
                } else {
                    $earlyLeaveMins = 0;
                }

                // Overtime Candidate Minutes
                if ($serverNow->greaterThan($shiftEnd)) {
                    $overtimeMins = (int) floor($shiftEnd->diffInMinutes($serverNow, false));
                    $overtimeMins = max(0, $overtimeMins);
                } else {
                    $overtimeMins = 0;
                }

                $beforeData = $record->toArray();

                $record->update([
                    'check_out_at' => $serverNow,
                    'check_out_latitude' => $outLat,
                    'check_out_longitude' => $outLng,
                    'check_out_accuracy_meters' => $outAcc,
                    'check_out_distance_meters' => $outDist,
                    'check_out_selfie_path' => $newSelfiePath,
                    'check_out_ip' => request()->ip(),
                    'check_out_user_agent' => request()->userAgent(),
                    'worked_minutes' => $workedMinutes,
                    'early_leave_minutes' => $earlyLeaveMins,
                    'overtime_minutes' => $overtimeMins,
                ]);

                AuditLog::log(
                    action: 'attendance.check_out',
                    model: $record,
                    before: $beforeData,
                    after: $record->fresh()->toArray(),
                    user: $actor
                );

                return $record;
            });
        } catch (\Throwable $e) {
            if ($newSelfiePath !== null) {
                Storage::disk('local')->delete($newSelfiePath);
            }
            throw $e;
        }
    }

    protected function timezone(): string
    {
        return (string) config('app.timezone', 'Asia/Jakarta');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function shiftDatetimes(EmployeeSchedule $schedule): array
    {
        $workDate = $schedule->work_date instanceof \DateTimeInterface
            ? $schedule->work_date->format('Y-m-d')
            : substr((string) $schedule->work_date, 0, 10);
        $shiftStart = Carbon::parse($workDate.' '.$schedule->shift->start_time, $this->timezone());
        $shiftEnd = Carbon::parse($workDate.' '.$schedule->shift->end_time, $this->timezone());

        if ($schedule->shift->crosses_midnight || $shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        return [$shiftStart, $shiftEnd];
    }

    protected function storeSelfieIfRequired(mixed $input, int $employeeId, string $type): ?string
    {
        $required = filter_var(
            AppSetting::get('attendance_require_selfie', true),
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $required && ($input === null || $input === '')) {
            return null;
        }

        return $this->selfieService->processAndStore($input, $employeeId, $type);
    }

    /**
     * Correct an attendance record manually by Owner/Admin.
     */
    public function correctAttendanceRecord(
        AttendanceRecord $record,
        ?string $checkInStr,
        ?string $checkOutStr,
        string $reason,
        User $actor,
        ?string $internalNote = null,
    ): AttendanceRecord {
        if (! in_array($actor->role, ['admin', 'owner', 'superadmin'], true)) {
            throw new \InvalidArgumentException('Anda tidak berwenang melakukan koreksi absensi.');
        }
        if (empty(trim($reason)) || strlen(trim($reason)) < 5) {
            throw new \InvalidArgumentException('Alasan koreksi absensi wajib diisi (minimal 5 karakter).');
        }

        return DB::transaction(function () use ($record, $checkInStr, $checkOutStr, $reason, $actor, $internalNote) {
            $record = AttendanceRecord::with(['schedule.shift', 'employee.user'])
                ->lockForUpdate()
                ->findOrFail($record->id);
            $beforeData = $record->getAttributes();
            $workDateStr = is_string($record->work_date) ? substr($record->work_date, 0, 10) : $record->work_date->format('Y-m-d');
            $timezone = $this->timezone();
            $checkInAt = $this->parseCorrectionTimestamp($checkInStr, $workDateStr, $timezone);
            $checkOutAt = $this->parseCorrectionTimestamp($checkOutStr, $workDateStr, $timezone);

            if ($checkInAt && $checkInAt->toDateString() !== $workDateStr) {
                throw new \InvalidArgumentException('Jam masuk harus tetap berada pada tanggal kerja record.');
            }
            if ($checkInAt && $checkOutAt && $checkOutAt->lessThan($checkInAt)) {
                if ($record->schedule?->shift?->crosses_midnight && strlen((string) $checkOutStr) <= 8) {
                    $checkOutAt->addDay();
                } else {
                    throw new \InvalidArgumentException('Jam pulang tidak boleh lebih awal dari jam masuk.');
                }
            }
            if ($checkOutAt && ! in_array($checkOutAt->toDateString(), [$workDateStr, Carbon::parse($workDateStr, $timezone)->addDay()->toDateString()], true)) {
                throw new \InvalidArgumentException('Jam pulang harus tetap terkait dengan tanggal kerja record.');
            }
            if ($checkOutAt && $checkOutAt->toDateString() !== $workDateStr && ! $record->schedule?->shift?->crosses_midnight) {
                throw new \InvalidArgumentException('Jam pulang lintas hari hanya valid untuk shift cross-midnight.');
            }

            $metrics = $this->calculateAttendanceMetrics($record, $checkInAt, $checkOutAt);
            $materialBefore = $this->attendanceSnapshot($record);
            $candidate = clone $record;
            $candidate->check_in_at = $checkInAt;
            $candidate->check_out_at = $checkOutAt;
            $candidate->late_minutes = $metrics['late_minutes'];
            $candidate->status = $checkInAt ? ($metrics['late_minutes'] > 0 ? 'late' : 'present') : 'absent';
            $resolvedStatus = $this->statusResolver->resolve($record->schedule, $candidate)['key'];
            if (! in_array($resolvedStatus, ['present', 'late', 'absent'], true)) {
                $resolvedStatus = $candidate->status;
            }

            $materialAfter = [
                'check_in_at' => $checkInAt?->format('Y-m-d H:i:s'),
                'check_out_at' => $checkOutAt?->format('Y-m-d H:i:s'),
                'status' => $resolvedStatus,
                ...$metrics,
            ];
            if ($materialBefore === $materialAfter) {
                return $record;
            }

            $wasMissingCheckout = $record->check_out_at === null && $checkOutAt !== null;
            $record->update([
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'status' => $resolvedStatus,
                ...$metrics,
                'is_manually_adjusted' => true,
                'corrected_at' => now($timezone),
                'corrected_by' => $actor->id,
            ]);

            $afterData = $record->fresh()->getAttributes();

            // Record in attendance_corrections table
            AttendanceCorrection::create([
                'attendance_record_id' => $record->id,
                'requested_by' => $actor->id,
                'approved_by' => $actor->id,
                'reason' => trim($reason),
                'before_data' => $beforeData,
                'after_data' => $afterData,
                'status' => 'approved',
                'reviewed_at' => now(),
            ]);

            // Audit Log
            AuditLog::log(
                action: $wasMissingCheckout ? 'attendance.checkout_recovered' : 'attendance.corrected',
                model: $record,
                before: $beforeData,
                after: $afterData,
                user: $actor,
                reason: $reason,
                metadata: [
                    'employee_id' => $record->employee_id,
                    'internal_note' => $internalNote,
                    'source' => 'admin',
                    'changed_fields' => array_keys(array_diff_assoc($materialAfter, $materialBefore)),
                ],
            );

            $record->employee?->user?->notify(new AdminCorrectionNotification(
                type: $wasMissingCheckout ? 'attendance_checkout_recovered' : 'attendance_corrected',
                title: 'Absensi Anda dikoreksi',
                message: $wasMissingCheckout
                    ? 'Jam pulang Anda dilengkapi oleh admin.'
                    : 'Data absensi Anda diperbarui oleh admin.',
                targetUrl: route('employee.dashboard', ['attendance' => $record->id]),
            ));

            return $record->fresh();
        });
    }

    protected function parseCorrectionTimestamp(?string $value, string $workDate, string $timezone): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)
            ? Carbon::parse($workDate.' '.$value, $timezone)
            : Carbon::parse($value, $timezone);
    }

    /** @return array{late_minutes:int,worked_minutes:int,early_leave_minutes:int,overtime_minutes:int} */
    protected function calculateAttendanceMetrics(AttendanceRecord $record, ?Carbon $checkInAt, ?Carbon $checkOutAt): array
    {
        $metrics = ['late_minutes' => 0, 'worked_minutes' => 0, 'early_leave_minutes' => 0, 'overtime_minutes' => 0];
        $shift = $record->schedule?->shift;
        if (! $shift || ! $checkInAt) {
            return $metrics;
        }

        $window = $this->statusResolver->calculateCheckInWindow($record->work_date->format('Y-m-d'), $shift);
        $graceTime = $window['start_time']->copy()->addMinutes((int) $shift->grace_period_minutes);
        $metrics['late_minutes'] = $checkInAt->greaterThan($graceTime)
            ? max(0, (int) floor($graceTime->diffInMinutes($checkInAt, false))) : 0;

        if ($checkOutAt) {
            $metrics['worked_minutes'] = max(0, (int) floor($checkInAt->diffInMinutes($checkOutAt, false)) - (int) $shift->break_minutes);
            if ($checkOutAt->lessThan($window['end_time'])) {
                $metrics['early_leave_minutes'] = max(0, (int) floor($checkOutAt->diffInMinutes($window['end_time'], false)));
            } elseif ($checkOutAt->greaterThan($window['end_time'])) {
                $metrics['overtime_minutes'] = max(0, (int) floor($window['end_time']->diffInMinutes($checkOutAt, false)));
            }
        }

        return $metrics;
    }

    /** @return array<string, mixed> */
    protected function attendanceSnapshot(AttendanceRecord $record): array
    {
        return [
            'check_in_at' => $record->check_in_at?->format('Y-m-d H:i:s'),
            'check_out_at' => $record->check_out_at?->format('Y-m-d H:i:s'),
            'status' => $record->status,
            'late_minutes' => (int) $record->late_minutes,
            'worked_minutes' => (int) $record->worked_minutes,
            'early_leave_minutes' => (int) $record->early_leave_minutes,
            'overtime_minutes' => (int) $record->overtime_minutes,
        ];
    }
}
