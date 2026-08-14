<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use Carbon\Carbon;

class EffectiveScheduleService
{
    /** @return array<string, mixed> */
    public function resolve(Employee $employee, string|Carbon $workDate): array
    {
        $date = $this->dateString($workDate);

        if (! $employee->participatesInAttendance()) {
            return $this->result(
                employee: $employee, date: $date, working: false, source: 'attendance_disabled',
                regular: null, override: null, calendar: null, shift: null,
                label: 'TIDAK IKUT SISTEM KEHADIRAN', reason: null,
            );
        }
        $regular = EmployeeSchedule::with('shift')
            ->where('employee_id', $employee->id)->whereDate('work_date', $date)->first();
        $override = EmployeeScheduleOverride::with('shift')
            ->where('employee_id', $employee->id)->whereDate('date', $date)->first();
        $calendar = Holiday::whereDate('date', $date)->first();

        return $this->resolveFromModels($employee, $date, $regular, $override, $calendar);
    }

    /** @return array<string, mixed> */
    public function resolveFromModels(
        Employee $employee,
        string|Carbon $workDate,
        ?EmployeeSchedule $regular,
        ?EmployeeScheduleOverride $override,
        ?Holiday $calendar,
    ): array {
        $date = $this->dateString($workDate);

        if (! $employee->participatesInAttendance()) {
            return $this->result(
                employee: $employee, date: $date, working: false, source: 'attendance_disabled',
                regular: $regular, override: $override, calendar: $calendar, shift: null,
                label: 'TIDAK IKUT SISTEM KEHADIRAN', reason: null,
            );
        }

        if ($override) {
            $working = $override->override_type === 'work' && $override->shift !== null;

            return $this->result(
                employee: $employee, date: $date, working: $working, source: 'employee_override',
                regular: $regular, override: $override, calendar: $calendar,
                shift: $working ? $override->shift : null,
                label: $working ? 'JADWAL KHUSUS' : 'LIBUR KHUSUS', reason: $override->reason,
            );
        }

        if ($calendar && ! $calendar->is_working_day) {
            return $this->result(
                employee: $employee, date: $date, working: false, source: $calendar->type,
                regular: $regular, override: null, calendar: $calendar, shift: null,
                label: 'LIBUR', reason: $calendar->description ?: $calendar->name,
            );
        }

        if ($calendar?->type === 'special_working_day') {
            $working = $regular?->schedule_type === 'work' && $regular->shift !== null;

            return $this->result(
                employee: $employee, date: $date, working: $working, source: 'special_working_day',
                regular: $regular, override: null, calendar: $calendar,
                shift: $working ? $regular->shift : null,
                label: $working ? 'JADWAL KHUSUS' : 'HARI KERJA KHUSUS — SHIFT BELUM DITETAPKAN',
                reason: $calendar->description ?: $calendar->name,
            );
        }

        if ($regular) {
            $working = $regular->schedule_type === 'work' && $regular->shift !== null;
            $label = $working ? 'JADWAL REGULER' : ($regular->schedule_type === 'holiday' ? 'LIBUR' : 'OFF');

            return $this->result(
                employee: $employee, date: $date, working: $working, source: 'regular_schedule',
                regular: $regular, override: null, calendar: null,
                shift: $working ? $regular->shift : null, label: $label, reason: $regular->notes,
            );
        }

        return $this->result(
            employee: $employee, date: $date, working: false, source: 'none',
            regular: null, override: null, calendar: null, shift: null,
            label: 'BELUM DITETAPKAN', reason: null,
        );
    }

    public function scheduleContext(array $effective): ?EmployeeSchedule
    {
        if (! $effective['is_working_day'] || ! $effective['shift']) {
            return null;
        }
        if ($effective['source'] === 'regular_schedule' || $effective['source'] === 'special_working_day') {
            return $effective['regular_schedule'];
        }

        $schedule = new EmployeeSchedule([
            'employee_id' => $effective['employee']->id,
            'work_date' => $effective['date'],
            'shift_id' => $effective['shift']->id,
            'schedule_type' => 'work',
            'notes' => $effective['reason'],
        ]);
        $schedule->setRelation('shift', $effective['shift']);

        return $schedule;
    }

    public function displaySchedule(array $effective): EmployeeSchedule
    {
        $schedule = $effective['regular_schedule'] ?? new EmployeeSchedule([
            'employee_id' => $effective['employee']->id,
            'work_date' => $effective['date'],
            'schedule_type' => $effective['is_working_day'] ? 'work' : 'holiday',
            'shift_id' => $effective['shift']?->id,
        ]);
        $schedule->setRelation('shift', $effective['shift']);
        $schedule->setAttribute('effective_source', $effective['source']);
        $schedule->setAttribute('effective_label', $effective['label']);
        $schedule->setAttribute('effective_is_working_day', $effective['is_working_day']);
        $schedule->setAttribute('holiday_name', $effective['holiday_name']);

        return $schedule;
    }

    protected function dateString(string|Carbon $date): string
    {
        return $date instanceof Carbon
            ? $date->copy()->timezone(config('app.timezone'))->format('Y-m-d')
            : Carbon::parse($date, config('app.timezone'))->format('Y-m-d');
    }

    /** @return array<string, mixed> */
    protected function result(
        Employee $employee,
        string $date,
        bool $working,
        string $source,
        ?EmployeeSchedule $regular,
        ?EmployeeScheduleOverride $override,
        ?Holiday $calendar,
        mixed $shift,
        string $label,
        ?string $reason,
    ): array {
        return [
            'employee' => $employee, 'date' => $date, 'is_working_day' => $working,
            'participates_in_attendance' => $employee->participatesInAttendance(),
            'source' => $source, 'regular_schedule' => $regular, 'override' => $override,
            'calendar_day' => $calendar, 'shift' => $shift, 'holiday_name' => $calendar?->name,
            'label' => $label, 'reason' => $reason,
        ];
    }
}
