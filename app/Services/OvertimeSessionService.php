<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Models\User;
use App\Notifications\AdminCorrectionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OvertimeSessionService
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected SelfieService $selfieService,
        protected ?EffectiveScheduleService $effectiveScheduleService = null,
    ) {
        $this->effectiveScheduleService = $effectiveScheduleService ?? new EffectiveScheduleService;
    }

    public function start(User $actor, int $requestId, array $evidence): OvertimeSession
    {
        $newSelfiePath = null;

        try {
            return DB::transaction(function () use ($actor, $requestId, $evidence, &$newSelfiePath) {
                $employee = $this->activeEmployee($actor);
                Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();

                $request = OvertimeRequest::with('session')->lockForUpdate()->findOrFail($requestId);
                if ($request->employee_id !== $employee->id) {
                    throw ValidationException::withMessages(['overtime' => 'Pengajuan lembur ini bukan milik Anda.']);
                }
                if ($request->status !== 'approved' || (int) $request->approved_minutes <= 0) {
                    throw ValidationException::withMessages(['overtime' => 'Hanya pengajuan lembur yang sudah disetujui yang dapat dimulai.']);
                }
                if ($request->session) {
                    throw ValidationException::withMessages(['overtime' => 'Sesi lembur untuk pengajuan ini sudah pernah dimulai.']);
                }
                if (OvertimeSession::where('employee_id', $employee->id)->where('status', 'active')->exists()) {
                    throw ValidationException::withMessages(['overtime' => 'Anda masih memiliki sesi lembur aktif.']);
                }

                $effective = $this->effectiveScheduleService->resolve($employee, $request->work_date);
                if ($effective['source'] === 'none') {
                    throw ValidationException::withMessages(['overtime' => 'Tanggal lembur belum memiliki konteks jadwal atau kalender kerja.']);
                }
                $schedule = $this->effectiveScheduleService->scheduleContext($effective);
                $this->ensureRequestDateIsValid($request, $schedule);

                $attendance = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereDate('work_date', $request->work_date)
                    ->lockForUpdate()
                    ->first();
                if ($effective['is_working_day'] && ! $attendance?->check_out_at) {
                    throw ValidationException::withMessages(['overtime' => 'Selesaikan absensi kerja reguler terlebih dahulu.']);
                }

                $gps = $this->validatedGps($evidence, 'mulai lembur');
                $newSelfiePath = $this->storeSelfie($evidence, $employee->id, 'check_in');
                $now = Carbon::now(config('app.timezone'));

                $session = OvertimeSession::create([
                    'overtime_request_id' => $request->id,
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $schedule?->exists ? $schedule->id : null,
                    'work_date' => $request->work_date,
                    'status' => 'active',
                    'check_in_at' => $now,
                    'started_at' => $now,
                    'check_in_latitude' => $gps['latitude'],
                    'check_in_longitude' => $gps['longitude'],
                    'check_in_accuracy_meters' => $gps['accuracy'],
                    'check_in_distance_meters' => $gps['distance'],
                    'check_in_selfie_path' => $newSelfiePath,
                ]);

                AuditLog::log('overtime.session_started', $session, null, $session->toArray(), $actor);

                return $session;
            });
        } catch (\Throwable $exception) {
            if ($newSelfiePath) {
                Storage::disk('local')->delete($newSelfiePath);
            }
            throw $exception;
        }
    }

    public function finish(User $actor, int $sessionId, array $evidence): OvertimeSession
    {
        $newSelfiePath = null;

        try {
            return DB::transaction(function () use ($actor, $sessionId, $evidence, &$newSelfiePath) {
                $employee = $this->activeEmployee($actor);
                Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
                $session = OvertimeSession::with('overtimeRequest')->lockForUpdate()->findOrFail($sessionId);

                if ($session->employee_id !== $employee->id) {
                    throw ValidationException::withMessages(['overtime' => 'Sesi lembur ini bukan milik Anda.']);
                }
                if (! $session->isActive() || $session->check_out_at) {
                    throw ValidationException::withMessages(['overtime' => 'Sesi lembur ini sudah selesai atau tidak aktif.']);
                }

                $gps = $this->validatedGps($evidence, 'selesai lembur');
                $newSelfiePath = $this->storeSelfie($evidence, $employee->id, 'check_out');
                $now = Carbon::now(config('app.timezone'));
                $actualMinutes = max(0, (int) floor($session->check_in_at->diffInMinutes($now, false)));
                $creditedMinutes = min($actualMinutes, (int) $session->overtimeRequest->approved_minutes);
                $before = $session->toArray();

                $session->update([
                    'status' => 'completed',
                    'check_out_at' => $now,
                    'completed_at' => $now,
                    'completed_by_user_id' => $actor->id,
                    'completion_source' => 'employee',
                    'check_out_latitude' => $gps['latitude'],
                    'check_out_longitude' => $gps['longitude'],
                    'check_out_accuracy_meters' => $gps['accuracy'],
                    'check_out_distance_meters' => $gps['distance'],
                    'check_out_selfie_path' => $newSelfiePath,
                    'actual_minutes' => $actualMinutes,
                    'credited_minutes' => $creditedMinutes,
                ]);

                AuditLog::log('overtime.session_completed', $session, $before, $session->fresh()->toArray(), $actor);

                return $session->fresh();
            });
        } catch (\Throwable $exception) {
            if ($newSelfiePath) {
                Storage::disk('local')->delete($newSelfiePath);
            }
            throw $exception;
        }
    }

    public function forceFinish(User $actor, OvertimeSession $session, string $finishAt, string $reason): OvertimeSession
    {
        $this->ensureAdmin($actor);
        $this->ensureReason($reason);

        return DB::transaction(function () use ($actor, $session, $finishAt, $reason) {
            $session = OvertimeSession::with(['overtimeRequest', 'employee.user'])->lockForUpdate()->findOrFail($session->id);
            if (! $session->isActive() || $session->check_out_at) {
                throw ValidationException::withMessages(['session' => 'Sesi lembur sudah tidak aktif.']);
            }

            $finish = $this->parseAdminTimestamp($finishAt, $session->work_date->format('Y-m-d'));
            if (! $session->check_in_at || $finish->lessThan($session->check_in_at)) {
                throw ValidationException::withMessages(['finish_at' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.']);
            }

            $before = $session->getAttributes();
            $actual = max(0, (int) floor($session->check_in_at->diffInMinutes($finish, false)));
            $session->update([
                'status' => 'completed',
                'check_out_at' => $finish,
                'completed_at' => $finish,
                'actual_minutes' => $actual,
                'credited_minutes' => min($actual, (int) $session->overtimeRequest->approved_minutes),
                'corrected_at' => now(config('app.timezone')),
                'corrected_by' => $actor->id,
                'completed_by_user_id' => $actor->id,
                'completion_source' => 'admin_force_finish',
            ]);

            $this->auditAndNotify(
                actor: $actor,
                session: $session,
                action: 'overtime.force_finished',
                reason: $reason,
                before: $before,
                title: 'Sesi lembur Anda diselesaikan',
                message: 'Waktu selesai lembur Anda dilengkapi oleh admin.',
            );

            return $session->fresh();
        });
    }

    public function cancel(User $actor, OvertimeSession $session, string $reason): OvertimeSession
    {
        $this->ensureAdmin($actor);
        $this->ensureReason($reason);

        return DB::transaction(function () use ($actor, $session, $reason) {
            $session = OvertimeSession::with(['overtimeRequest', 'employee.user'])->lockForUpdate()->findOrFail($session->id);
            if (! $session->isActive()) {
                throw ValidationException::withMessages(['session' => 'Hanya sesi lembur aktif yang dapat dibatalkan.']);
            }

            $before = $session->getAttributes();
            $session->update([
                'status' => 'cancelled',
                'actual_minutes' => 0,
                'credited_minutes' => 0,
                'completed_at' => now(config('app.timezone')),
                'corrected_at' => now(config('app.timezone')),
                'corrected_by' => $actor->id,
                'completed_by_user_id' => $actor->id,
                'completion_source' => 'admin_cancelled',
            ]);

            $this->auditAndNotify(
                actor: $actor,
                session: $session,
                action: 'overtime.cancelled',
                reason: $reason,
                before: $before,
                title: 'Sesi lembur Anda dibatalkan',
                message: 'Sesi lembur dinyatakan tidak valid oleh admin dan kredit lembur menjadi 0 menit.',
            );

            return $session->fresh();
        });
    }

    public function correctCompleted(
        User $actor,
        OvertimeSession $session,
        string $checkInAt,
        string $checkOutAt,
        string $reason,
    ): OvertimeSession {
        $this->ensureAdmin($actor);
        $this->ensureReason($reason);

        return DB::transaction(function () use ($actor, $session, $checkInAt, $checkOutAt, $reason) {
            $session = OvertimeSession::with(['overtimeRequest', 'employee.user'])->lockForUpdate()->findOrFail($session->id);
            if (! $session->isCompleted()) {
                throw ValidationException::withMessages(['session' => 'Hanya sesi lembur completed yang dapat dikoreksi.']);
            }

            $start = $this->parseAdminTimestamp($checkInAt, $session->work_date->format('Y-m-d'));
            $finish = $this->parseAdminTimestamp($checkOutAt, $session->work_date->format('Y-m-d'));
            if ($finish->lessThan($start)) {
                throw ValidationException::withMessages(['check_out_at' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.']);
            }
            $actual = max(0, (int) floor($start->diffInMinutes($finish, false)));
            if ($session->check_in_at?->equalTo($start) && $session->check_out_at?->equalTo($finish)) {
                return $session;
            }

            $before = $session->getAttributes();
            $session->update([
                'check_in_at' => $start,
                'started_at' => $start,
                'check_out_at' => $finish,
                'completed_at' => $finish,
                'actual_minutes' => $actual,
                'credited_minutes' => min($actual, (int) $session->overtimeRequest->approved_minutes),
                'corrected_at' => now(config('app.timezone')),
                'corrected_by' => $actor->id,
                'completed_by_user_id' => $actor->id,
                'completion_source' => 'admin_corrected',
            ]);

            $this->auditAndNotify(
                actor: $actor,
                session: $session,
                action: 'overtime.corrected',
                reason: $reason,
                before: $before,
                title: 'Data lembur Anda dikoreksi',
                message: 'Waktu mulai atau selesai lembur Anda diperbarui oleh admin.',
            );

            return $session->fresh();
        });
    }

    protected function ensureAdmin(User $actor): void
    {
        if (! in_array($actor->role, ['admin', 'owner', 'superadmin'], true)) {
            throw ValidationException::withMessages(['authorization' => 'Akses tindakan admin ditolak.']);
        }
    }

    protected function ensureReason(string $reason): void
    {
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi minimal 5 karakter.']);
        }
    }

    protected function parseAdminTimestamp(string $value, string $workDate): Carbon
    {
        $value = trim($value);
        if ($value === '') {
            throw ValidationException::withMessages(['time' => 'Waktu wajib diisi.']);
        }

        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)
            ? Carbon::parse($workDate.' '.$value, config('app.timezone'))
            : Carbon::parse($value, config('app.timezone'));
    }

    /** @param array<string, mixed> $before */
    protected function auditAndNotify(
        User $actor,
        OvertimeSession $session,
        string $action,
        string $reason,
        array $before,
        string $title,
        string $message,
    ): void {
        AuditLog::log(
            action: $action,
            model: $session,
            before: $before,
            after: $session->fresh()->getAttributes(),
            user: $actor,
            reason: $reason,
            metadata: [
                'employee_id' => $session->employee_id,
                'overtime_request_id' => $session->overtime_request_id,
                'source' => 'admin',
            ],
        );

        $session->employee?->user?->notify(new AdminCorrectionNotification(
            type: str_replace('.', '_', $action),
            title: $title,
            message: $message,
            targetUrl: route('employee.overtime-requests.index', ['highlight' => $session->overtime_request_id]).'#overtime-'.$session->overtime_request_id,
        ));
    }

    protected function activeEmployee(User $actor): Employee
    {
        $employee = $actor->employee;
        if (! $actor->is_active || ! $employee || $employee->status !== 'active') {
            throw ValidationException::withMessages(['overtime' => 'Akun karyawan Anda tidak aktif atau tidak terhubung.']);
        }
        if (! $employee->isCurrentAttendanceWorkforceMember()) {
            throw ValidationException::withMessages(['overtime' => 'Akun Anda tidak terdaftar sebagai peserta sistem kehadiran.']);
        }

        return $employee;
    }

    protected function ensureRequestDateIsValid(OvertimeRequest $request, ?EmployeeSchedule $schedule): void
    {
        if (! $request->isStartDateValid($schedule)) {
            throw ValidationException::withMessages(['overtime' => 'Sesi lembur hanya dapat dimulai pada tanggal kerja yang disetujui.']);
        }
    }

    /** @return array{latitude: float, longitude: float, accuracy: float, distance: float} */
    protected function validatedGps(array $evidence, string $action): array
    {
        $latitude = $evidence['latitude'] ?? null;
        $longitude = $evidence['longitude'] ?? null;
        $accuracy = $evidence['accuracy'] ?? null;
        if (! is_numeric($latitude) || ! is_numeric($longitude) || ! is_numeric($accuracy)) {
            throw ValidationException::withMessages(['gps' => "Data GPS wajib disertakan untuk {$action}."]);
        }

        $result = $this->attendanceService->validateGeofence((float) $latitude, (float) $longitude, (float) $accuracy);

        return [
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'accuracy' => (float) $accuracy,
            'distance' => (float) $result['distance'],
        ];
    }

    protected function storeSelfie(array $evidence, int $employeeId, string $type): ?string
    {
        $input = $evidence['selfie'] ?? $evidence['selfie_base64'] ?? request()->file('selfie');
        if (! (bool) AppSetting::get('attendance_require_selfie', true) && empty($input)) {
            return null;
        }

        return $this->selfieService->processAndStore($input, $employeeId, $type, 'overtime');
    }
}
