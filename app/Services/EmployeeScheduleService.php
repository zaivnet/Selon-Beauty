<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeScheduleService
{
    public function __construct(
        protected ?AttendancePeriodService $periodService = null,
        protected ?OutletScopeService $outletScopeService = null,
    ) {
        $this->periodService = $periodService ?? new AttendancePeriodService;
        $this->outletScopeService = $outletScopeService ?? new OutletScopeService;
    }

    /**
     * Assign or create a schedule entry for an employee on a specific date.
     */
    public function assignSchedule(array $data, User $actor): EmployeeSchedule
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $this->outletScopeService->ensureCanManageEmployee($actor, $employee);

        return DB::transaction(function () use ($data, $actor) {
            $this->periodService->assertPeriodOpen($data['work_date']);
            $this->ensureAttendanceParticipant((int) $data['employee_id']);
            $scheduleType = $data['schedule_type'] ?? 'work';

            if ($scheduleType === 'work') {
                if (empty($data['shift_id'])) {
                    throw new \InvalidArgumentException('Jadwal jenis Kerja wajib memilih Shift.');
                }

                $shift = Shift::find($data['shift_id']);
                if (! $shift || ! $shift->is_active) {
                    throw new \InvalidArgumentException('Shift yang dipilih tidak ditemukan atau dalam status nonaktif.');
                }
            } else {
                $data['shift_id'] = null;
            }

            $existing = EmployeeSchedule::where('employee_id', $data['employee_id'])
                ->whereDate('work_date', $data['work_date'])
                ->first();

            if ($existing) {
                throw new \InvalidArgumentException('Karyawan sudah memiliki jadwal pada tanggal ini.');
            }

            $schedule = EmployeeSchedule::create([
                'employee_id' => $data['employee_id'],
                'work_date' => $data['work_date'],
                'shift_id' => $data['shift_id'],
                'schedule_type' => $scheduleType,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            AuditLog::log(
                action: 'schedule.created',
                model: $schedule,
                before: null,
                after: $schedule->toArray(),
                user: $actor
            );

            return $schedule;
        });
    }

    /**
     * Update an existing schedule entry.
     */
    public function updateSchedule(EmployeeSchedule $schedule, array $data, User $actor): EmployeeSchedule
    {
        $this->outletScopeService->ensureCanManageEmployee($actor, $schedule->employee);
        if (isset($data['employee_id']) && (int) $data['employee_id'] !== (int) $schedule->employee_id) {
            $targetEmployee = Employee::findOrFail($data['employee_id']);
            $this->outletScopeService->ensureCanManageEmployee($actor, $targetEmployee);
        }

        return DB::transaction(function () use ($schedule, $data, $actor) {
            $this->periodService->assertPeriodOpen($data['work_date'] ?? $schedule->work_date);
            $this->ensureAttendanceParticipant((int) ($data['employee_id'] ?? $schedule->employee_id));
            $beforeData = $schedule->toArray();
            $scheduleType = $data['schedule_type'] ?? $schedule->schedule_type;

            // Check unique constraint if employee_id or work_date is being changed to another date
            if (isset($data['employee_id'], $data['work_date'])) {
                $conflict = EmployeeSchedule::where('employee_id', $data['employee_id'])
                    ->whereDate('work_date', $data['work_date'])
                    ->where('id', '!=', $schedule->id)
                    ->first();

                if ($conflict) {
                    throw new \InvalidArgumentException('Karyawan sudah memiliki jadwal lain pada tanggal tersebut.');
                }
            }

            if ($scheduleType === 'work') {
                $shiftId = $data['shift_id'] ?? $schedule->shift_id;
                if (! $shiftId) {
                    throw new \InvalidArgumentException('Jadwal jenis Kerja wajib memilih Shift.');
                }

                $shift = Shift::find($shiftId);
                if (! $shift) {
                    throw new \InvalidArgumentException('Shift tidak ditemukan.');
                }
                // Reject if changing to another inactive shift
                if (! $shift->is_active && $shiftId != $schedule->shift_id) {
                    throw new \InvalidArgumentException('Shift yang dipilih dalam status nonaktif.');
                }

                $data['shift_id'] = $shiftId;
            } else {
                $data['shift_id'] = null;
            }

            $schedule->update([
                'employee_id' => $data['employee_id'] ?? $schedule->employee_id,
                'work_date' => $data['work_date'] ?? $schedule->work_date,
                'shift_id' => $data['shift_id'],
                'schedule_type' => $scheduleType,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $schedule->notes,
                'updated_by' => $actor->id,
            ]);

            // Determine audit log action
            $action = 'schedule.updated';
            if ($beforeData['schedule_type'] !== 'off' && $scheduleType === 'off') {
                $action = 'schedule.changed_to_off';
            } elseif ($beforeData['schedule_type'] !== 'work' && $scheduleType === 'work') {
                $action = 'schedule.changed_to_work';
            }

            AuditLog::log(
                action: $action,
                model: $schedule,
                before: $beforeData,
                after: $schedule->fresh()->toArray(),
                user: $actor
            );

            return $schedule;
        });
    }

    /**
     * Quick action to mark an employee OFF for a specific date.
     */
    public function markOff(int $employeeId, string $workDate, ?string $notes, User $actor): EmployeeSchedule
    {
        $employee = Employee::findOrFail($employeeId);
        $this->outletScopeService->ensureCanManageEmployee($actor, $employee);

        return DB::transaction(function () use ($employeeId, $workDate, $notes, $actor) {
            $this->periodService->assertPeriodOpen($workDate);
            $existing = EmployeeSchedule::where('employee_id', $employeeId)
                ->whereDate('work_date', $workDate)
                ->first();

            if ($existing) {
                return $this->updateSchedule($existing, [
                    'schedule_type' => 'off',
                    'shift_id' => null,
                    'notes' => $notes ?? $existing->notes,
                ], $actor);
            }

            return $this->assignSchedule([
                'employee_id' => $employeeId,
                'work_date' => $workDate,
                'schedule_type' => 'off',
                'shift_id' => null,
                'notes' => $notes,
            ], $actor);
        });
    }

    /**
     * Safely delete a schedule if no attendance records exist.
     */
    public function deleteSchedule(EmployeeSchedule $schedule, User $actor): bool
    {
        $this->outletScopeService->ensureCanManageEmployee($actor, $schedule->employee);

        return DB::transaction(function () use ($schedule, $actor) {
            $this->periodService->assertPeriodOpen($schedule->work_date);
            // Check if linked attendance records exist
            $hasAttendance = DB::table('attendance_records')
                ->where('work_schedule_id', $schedule->id)
                ->orWhere(function ($q) use ($schedule) {
                    $q->where('employee_id', $schedule->employee_id)
                        ->whereDate('work_date', $schedule->work_date);
                })
                ->exists();

            if ($hasAttendance) {
                throw new \InvalidArgumentException('Jadwal ini sudah memiliki data absensi dan tidak dapat dihapus. Silakan lakukan koreksi melalui modul absensi.');
            }

            $beforeData = $schedule->toArray();
            $deleted = $schedule->delete();

            AuditLog::log(
                action: 'schedule.deleted',
                model: $schedule,
                before: $beforeData,
                after: null,
                user: $actor
            );

            return (bool) $deleted;
        });
    }

    /**
     * Preview copying previous week's schedule to the target week.
     */
    public function previewCopyPreviousWeek(string $targetWeekStartDate, ?User $actor = null): array
    {
        $targetStart = Carbon::parse($targetWeekStartDate)->startOfWeek();
        $targetEnd = (clone $targetStart)->endOfWeek();

        $prevStart = (clone $targetStart)->subWeek();
        $prevEnd = (clone $prevStart)->endOfWeek();

        $prevSchedulesQuery = EmployeeSchedule::with(['employee', 'shift'])
            ->whereHas('employee', function ($query) use ($actor) {
                $query->currentAttendanceWorkforce();
                if ($actor) {
                    $this->outletScopeService->scopeEmployeesFor($actor, $query);
                }
            })
            ->whereBetween('work_date', [$prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d')]);

        $prevSchedules = $prevSchedulesQuery->get();

        $existingTargetSchedules = EmployeeSchedule::whereBetween('work_date', [$targetStart->format('Y-m-d'), $targetEnd->format('Y-m-d')])
            ->get()
            ->keyBy(fn ($s) => $s->employee_id.'_'.$s->work_date->format('Y-m-d'));

        $items = [];
        $conflictCount = 0;

        foreach ($prevSchedules as $prev) {
            $dayOffset = (int) $prevStart->diffInDays($prev->work_date);
            $newTargetDate = (clone $targetStart)->addDays($dayOffset)->format('Y-m-d');
            $key = $prev->employee_id.'_'.$newTargetDate;

            $hasConflict = isset($existingTargetSchedules[$key]);
            if ($hasConflict) {
                $conflictCount++;
            }

            $items[] = [
                'employee_id' => $prev->employee_id,
                'employee_name' => $prev->employee?->full_name ?? 'Karyawan',
                'source_date' => $prev->work_date->format('Y-m-d'),
                'target_date' => $newTargetDate,
                'schedule_type' => $prev->schedule_type,
                'shift_id' => $prev->shift_id,
                'shift_code' => $prev->shift?->code ?? '-',
                'has_conflict' => $hasConflict,
            ];
        }

        return [
            'target_start' => $targetStart->format('Y-m-d'),
            'target_end' => $targetEnd->format('Y-m-d'),
            'prev_start' => $prevStart->format('Y-m-d'),
            'prev_end' => $prevEnd->format('Y-m-d'),
            'total_source_items' => count($items),
            'conflict_count' => $conflictCount,
            'items' => $items,
        ];
    }

    /**
     * Execute copying previous week's schedule to the target week.
     */
    public function executeCopyPreviousWeek(string $targetWeekStartDate, bool $overwriteConflicts, User $actor): array
    {
        $preview = $this->previewCopyPreviousWeek($targetWeekStartDate, $actor);
        $copied = 0;
        $skipped = 0;

        DB::transaction(function () use ($preview, $overwriteConflicts, $actor, &$copied, &$skipped) {
            foreach ($preview['items'] as $item) {
                $this->periodService->assertPeriodOpen($item['target_date']);
                $existing = EmployeeSchedule::where('employee_id', $item['employee_id'])
                    ->whereDate('work_date', $item['target_date'])
                    ->first();

                if ($existing) {
                    if ($overwriteConflicts) {
                        $this->updateSchedule($existing, [
                            'shift_id' => $item['shift_id'],
                            'schedule_type' => $item['schedule_type'],
                        ], $actor);
                        $copied++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $this->assignSchedule([
                        'employee_id' => $item['employee_id'],
                        'work_date' => $item['target_date'],
                        'shift_id' => $item['shift_id'],
                        'schedule_type' => $item['schedule_type'],
                    ], $actor);
                    $copied++;
                }
            }
        });

        return [
            'copied_count' => $copied,
            'skipped_count' => $skipped,
        ];
    }

    protected function ensureAttendanceParticipant(int $employeeId): void
    {
        $employee = Employee::findOrFail($employeeId);
        if (! $employee->isCurrentAttendanceWorkforceMember()) {
            throw new \InvalidArgumentException('Karyawan tidak terdaftar sebagai peserta sistem kehadiran.');
        }
    }
}
