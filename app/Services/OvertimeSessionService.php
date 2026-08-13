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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OvertimeSessionService
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected SelfieService $selfieService,
    ) {}

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

                $schedule = EmployeeSchedule::with('shift')
                    ->where('employee_id', $employee->id)
                    ->whereDate('work_date', $request->work_date)
                    ->first();
                $this->ensureRequestDateIsValid($request, $schedule);

                $attendance = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereDate('work_date', $request->work_date)
                    ->lockForUpdate()
                    ->first();
                if (! $attendance?->check_out_at) {
                    throw ValidationException::withMessages(['overtime' => 'Selesaikan absensi kerja reguler terlebih dahulu.']);
                }

                $gps = $this->validatedGps($evidence, 'mulai lembur');
                $newSelfiePath = $this->storeSelfie($evidence, $employee->id, 'check_in');
                $now = Carbon::now(config('app.timezone'));

                $session = OvertimeSession::create([
                    'overtime_request_id' => $request->id,
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $schedule?->id,
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

    protected function activeEmployee(User $actor): Employee
    {
        $employee = $actor->employee;
        if (! $actor->is_active || ! $employee || $employee->status !== 'active') {
            throw ValidationException::withMessages(['overtime' => 'Akun karyawan Anda tidak aktif atau tidak terhubung.']);
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
