<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OvertimeSession;
use App\Models\User;
use App\Notifications\AttendanceParticipationChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceParticipationService
{
    public function update(Employee $employee, bool $enabled, ?string $reason, User $actor): Employee
    {
        $reason = trim((string) $reason);

        return DB::transaction(function () use ($employee, $enabled, $reason, $actor): Employee {
            $locked = Employee::with('user')->lockForUpdate()->findOrFail($employee->id);
            if ($locked->user?->role === 'superadmin') {
                return $locked;
            }
            if ($locked->participatesInAttendance() === $enabled) {
                return $locked;
            }
            if (! $enabled) {
                if (mb_strlen($reason) < 5) {
                    throw ValidationException::withMessages([
                        'attendance_participation_reason' => 'Alasan menonaktifkan sistem kehadiran wajib diisi minimal 5 karakter.',
                    ]);
                }
                $this->ensureNoActiveWorkforceState($locked);
            }

            $before = ['attendance_enabled' => $locked->participatesInAttendance()];
            $locked->update(['attendance_enabled' => $enabled]);
            $after = ['attendance_enabled' => $locked->fresh()->participatesInAttendance()];
            AuditLog::log(
                action: $enabled ? 'employee.attendance_enabled' : 'employee.attendance_disabled',
                model: $locked,
                before: $before,
                after: $after,
                user: $actor,
                reason: $reason !== '' ? $reason : null,
                metadata: ['employee_id' => $locked->id],
            );
            $locked->user?->notify(new AttendanceParticipationChangedNotification($enabled));

            return $locked->fresh(['user']);
        });
    }

    protected function ensureNoActiveWorkforceState(Employee $employee): void
    {
        $hasOpenAttendance = AttendanceRecord::where('employee_id', $employee->id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->exists();
        $hasActiveOvertime = OvertimeSession::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->exists();

        if ($hasOpenAttendance || $hasActiveOvertime) {
            throw ValidationException::withMessages([
                'attendance_enabled' => 'Selesaikan absensi/lembur aktif terlebih dahulu sebelum menonaktifkan sistem kehadiran.',
            ]);
        }
    }
}
