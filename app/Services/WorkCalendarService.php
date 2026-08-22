<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\ScheduleOverrideNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkCalendarService
{
    public function __construct(protected ?AttendancePeriodService $periodService = null, protected ?OutletScopeService $outletScopeService = null)
    {
        $this->periodService = $periodService ?? new AttendancePeriodService;
        $this->outletScopeService = $outletScopeService ?? new OutletScopeService;
    }

    public function createCalendarDay(array $data, User $actor): Holiday
    {
        $this->ensureManager($actor);

        return DB::transaction(function () use ($data, $actor) {
            $this->periodService->assertPeriodOpen($data['date']);
            if (Holiday::whereDate('date', $data['date'])->lockForUpdate()->exists()) {
                throw new \InvalidArgumentException('Tanggal tersebut sudah memiliki event kalender kerja.');
            }
            $day = Holiday::create([
                'date' => $data['date'], 'type' => $data['type'], 'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'is_working_day' => $data['type'] === 'special_working_day',
                'applies_to_all_employees' => true, 'created_by' => $actor->id,
            ]);
            $this->audit('work_calendar.created', $day, null, $day->getAttributes(), $data['audit_reason'], $actor, ['date' => $day->date->format('Y-m-d')]);

            return $day;
        });
    }

    public function updateCalendarDay(Holiday $day, array $data, User $actor): Holiday
    {
        $this->ensureManager($actor);

        return DB::transaction(function () use ($day, $data, $actor) {
            $this->periodService->assertPeriodOpen($day->date->format('Y-m-d'));
            $this->periodService->assertPeriodOpen($data['date']);
            $day = Holiday::lockForUpdate()->findOrFail($day->id);
            if (Holiday::whereDate('date', $data['date'])->where('id', '!=', $day->id)->exists()) {
                throw new \InvalidArgumentException('Tanggal tersebut sudah memiliki event kalender kerja.');
            }
            $before = $day->getAttributes();
            $changes = [
                'date' => $data['date'], 'type' => $data['type'], 'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'is_working_day' => $data['type'] === 'special_working_day',
            ];
            $day->fill($changes);
            if (! $day->isDirty()) {
                return $day;
            }
            $day->save();
            $this->audit('work_calendar.updated', $day, $before, $day->fresh()->getAttributes(), $data['audit_reason'], $actor, ['date' => $day->date->format('Y-m-d')]);

            return $day->fresh();
        });
    }

    public function deleteCalendarDay(Holiday $day, string $reason, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureReason($reason);
        DB::transaction(function () use ($day, $reason, $actor) {
            $this->periodService->assertPeriodOpen($day->date->format('Y-m-d'));
            $day = Holiday::lockForUpdate()->findOrFail($day->id);
            $before = $day->getAttributes();
            $metadata = ['date' => $day->date->format('Y-m-d')];
            $day->delete();
            $this->audit('work_calendar.deleted', $day, $before, null, $reason, $actor, $metadata);
        });
    }

    public function saveOverride(array $data, User $actor, ?EmployeeScheduleOverride $override = null): EmployeeScheduleOverride
    {
        $this->ensureManager($actor);
        $this->ensureReason($data['reason']);

        return DB::transaction(function () use ($data, $actor, $override) {
            $this->periodService->assertPeriodOpen($data['date']);
            $employee = Employee::findOrFail($data['employee_id']);
            $this->outletScopeService->ensureCanManageEmployee($actor, $employee);
            if (! $employee->isCurrentAttendanceWorkforceMember()) {
                throw new \InvalidArgumentException('Karyawan tidak terdaftar sebagai peserta sistem kehadiran.');
            }

            if ($override) {
                $this->periodService->assertPeriodOpen($override->date->format('Y-m-d'));
                $override = EmployeeScheduleOverride::with(['employee.user', 'shift', 'workOutlet'])->lockForUpdate()->findOrFail($override->id);
                // Authorize the persisted record as well as the requested employee below.
                // A crafted employee_id must not turn an authorized update into an update
                // of an override belonging to a different Home Outlet.
                $this->outletScopeService->ensureCanManageEmployee($actor, $override->employee);
                $conflict = EmployeeScheduleOverride::where('employee_id', $data['employee_id'])
                    ->whereDate('date', $data['date'])->where('id', '!=', $override->id)->exists();
                if ($conflict) {
                    throw new \InvalidArgumentException('Karyawan sudah memiliki override pada tanggal tersebut.');
                }
            } else {
                $existing = EmployeeScheduleOverride::where('employee_id', $data['employee_id'])
                    ->whereDate('date', $data['date'])->lockForUpdate()->first();
                if ($existing) {
                    throw new \InvalidArgumentException('Karyawan sudah memiliki override pada tanggal tersebut.');
                }
            }

            $shiftId = $data['override_type'] === 'work' ? ($data['shift_id'] ?? null) : null;
            if ($data['override_type'] === 'work') {
                $shift = Shift::whereKey($shiftId)->where('is_active', true)->first();
                if (! $shift) {
                    throw new \InvalidArgumentException('Override Masuk Kerja wajib memilih shift aktif.');
                }
            }
            $requestedWorkOutletId = array_key_exists('work_outlet_id', $data) && $data['work_outlet_id'] !== null
                ? (int) $data['work_outlet_id']
                : null;
            $workOutletId = $data['override_type'] === 'work'
                ? ($requestedWorkOutletId ?: ($override?->work_outlet_id ?: $employee->outlet_id))
                : null;
            if ($workOutletId && ! Outlet::query()->whereKey($workOutletId)->where('is_active', true)->exists()) {
                throw new \InvalidArgumentException('Outlet Kerja harus berupa outlet aktif yang valid.');
            }
            if ($workOutletId) {
                $this->outletScopeService->ensureCanAccessOutlet($actor, $workOutletId);
            }

            $before = $override?->getAttributes();
            $event = $override ? 'updated' : 'created';
            if ($override) {
                $override->fill([
                    'employee_id' => $data['employee_id'], 'date' => $data['date'],
                    'override_type' => $data['override_type'], 'shift_id' => $shiftId,
                    'work_outlet_id' => $workOutletId,
                    'reason' => trim($data['reason']),
                ]);
                if (! $override->isDirty()) {
                    return $override;
                }
                $override->save();
            } else {
                $override = EmployeeScheduleOverride::create([
                    'employee_id' => $data['employee_id'], 'date' => $data['date'],
                    'override_type' => $data['override_type'], 'shift_id' => $shiftId,
                    'work_outlet_id' => $workOutletId,
                    'reason' => trim($data['reason']), 'created_by' => $actor->id,
                ]);
            }
            $override->load(['employee.user', 'shift', 'workOutlet']);
            $this->audit(
                "schedule_override.{$event}", $override, $before, $override->getAttributes(),
                $data['reason'], $actor, ['employee_id' => $override->employee_id, 'date' => $override->date->format('Y-m-d')],
            );
            $override->employee?->user?->notify(new ScheduleOverrideNotification($override, $event));

            return $override;
        });
    }

    public function deleteOverride(EmployeeScheduleOverride $override, string $reason, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureReason($reason);
        DB::transaction(function () use ($override, $reason, $actor) {
            $this->periodService->assertPeriodOpen($override->date->format('Y-m-d'));
            $override = EmployeeScheduleOverride::with(['employee.user', 'shift', 'workOutlet'])->lockForUpdate()->findOrFail($override->id);
            $this->outletScopeService->ensureCanManageEmployee($actor, $override->employee);
            $before = $override->getAttributes();
            $metadata = ['employee_id' => $override->employee_id, 'date' => $override->date->format('Y-m-d')];
            $notificationSnapshot = clone $override;
            $override->delete();
            $this->audit('schedule_override.deleted', $override, $before, null, $reason, $actor, $metadata);
            $notificationSnapshot->employee?->user?->notify(new ScheduleOverrideNotification($notificationSnapshot, 'deleted'));
        });
    }

    protected function ensureManager(User $actor): void
    {
        if (! in_array($actor->role, ['admin', 'owner', 'superadmin'], true)) {
            throw new \InvalidArgumentException('Akses pengelolaan kalender kerja ditolak.');
        }
    }

    protected function ensureReason(string $reason): void
    {
        if (mb_strlen(trim($reason)) < 5) {
            throw new \InvalidArgumentException('Alasan wajib diisi minimal 5 karakter.');
        }
    }

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after @param array<string, mixed> $metadata */
    protected function audit(string $action, Model $model, ?array $before, ?array $after, string $reason, User $actor, array $metadata): void
    {
        $this->ensureReason($reason);
        AuditLog::log($action, $model, $before, $after, $actor, $reason, $metadata);
    }
}
