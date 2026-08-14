<?php

namespace App\Services;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class MonthlyAttendanceRecapService
{
    public function __construct(
        protected AttendanceStatusResolver $statusResolver,
        protected EffectiveScheduleService $effectiveScheduleService,
    ) {}

    /**
     * Build deterministic payroll-ready attendance recaps without persisting a snapshot.
     *
     * @return array{period: array<string, mixed>, recaps: array<int, array<string, mixed>>}
     */
    public function generate(int $year, int $month, array $filters = []): array
    {
        $this->validatePeriod($year, $month);
        $timezone = config('app.timezone');
        $start = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $dates = collect(CarbonPeriod::create($start, $end))->map(fn (Carbon $date) => $date->copy());

        $employeesQuery = Employee::with('jobTitle')->whereNull('deleted_at');
        if (! empty($filters['employee_id'])) {
            $employeesQuery->whereKey((int) $filters['employee_id']);
        }
        if (! empty($filters['job_title_id'])) {
            $employeesQuery->where('job_title_id', (int) $filters['job_title_id']);
        }
        $employees = $employeesQuery->orderBy('full_name')->get();
        $employeeIds = $employees->pluck('id');

        $period = [
            'year' => $year,
            'month' => $month,
            'key' => $start->format('Y-m'),
            'label' => $start->locale('id')->translatedFormat('F Y'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'calendar_days' => $start->daysInMonth,
        ];

        if ($employeeIds->isEmpty()) {
            return ['period' => $period, 'recaps' => []];
        }

        $regularSchedules = EmployeeSchedule::with('shift')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $period['start_date'])
            ->whereDate('work_date', '<=', $period['end_date'])
            ->get()->keyBy(fn ($item) => $this->key($item->employee_id, $item->work_date));
        $overrides = EmployeeScheduleOverride::with('shift')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $period['start_date'])
            ->whereDate('date', '<=', $period['end_date'])
            ->get()->keyBy(fn ($item) => $this->key($item->employee_id, $item->date));
        $calendarDays = Holiday::whereDate('date', '>=', $period['start_date'])
            ->whereDate('date', '<=', $period['end_date'])
            ->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));
        $attendances = AttendanceRecord::with('location')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $period['start_date'])
            ->whereDate('work_date', '<=', $period['end_date'])
            ->get()->keyBy(fn ($item) => $this->key($item->employee_id, $item->work_date));
        $corrections = AttendanceCorrection::whereIn('attendance_record_id', $attendances->pluck('id'))
            ->get()->groupBy('attendance_record_id');
        $leaves = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $period['end_date'])
            ->whereDate('end_date', '>=', $period['start_date'])
            ->get();
        $leaveMap = $this->mapLeaves($leaves, $start, $end);
        $overtimeRequests = OvertimeRequest::with('session')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $period['start_date'])
            ->whereDate('work_date', '<=', $period['end_date'])
            ->whereIn('status', ['pending', 'approved'])
            ->get()->groupBy(fn ($item) => $this->key($item->employee_id, $item->work_date));

        $recaps = $employees->map(fn (Employee $employee) => $this->buildEmployeeRecap(
            $employee, $period, $dates, $regularSchedules, $overrides, $calendarDays,
            $attendances, $corrections, $leaveMap, $overtimeRequests,
        ))->values()->all();

        return ['period' => $period, 'recaps' => $recaps];
    }

    /** @return array<string, mixed> */
    public function forEmployee(Employee $employee, int $year, int $month): array
    {
        $data = $this->generate($year, $month, ['employee_id' => $employee->id]);

        return $data['recaps'][0] ?? $this->emptyEmployeeRecap($employee, $data['period']);
    }

    /** @param Collection<int, LeaveRequest> $leaves @return array<string, LeaveRequest> */
    protected function mapLeaves(Collection $leaves, Carbon $periodStart, Carbon $periodEnd): array
    {
        $map = [];
        foreach ($leaves as $leave) {
            $start = $leave->start_date->copy()->max($periodStart);
            $end = $leave->end_date->copy()->min($periodEnd);
            foreach (CarbonPeriod::create($start, $end) as $date) {
                $map[$this->key($leave->employee_id, $date)] = $leave;
            }
        }

        return $map;
    }

    /** @return array<string, mixed> */
    protected function buildEmployeeRecap(
        Employee $employee,
        array $period,
        Collection $dates,
        Collection $regularSchedules,
        Collection $overrides,
        Collection $calendarDays,
        Collection $attendances,
        Collection $corrections,
        array $leaveMap,
        Collection $overtimeRequests,
    ): array {
        $summary = $this->baseSummary($employee, $period);
        $daily = [];
        $today = Carbon::now(config('app.timezone'))->startOfDay();

        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');
            $key = $this->key($employee->id, $dateString);
            $regular = $regularSchedules->get($key);
            $override = $overrides->get($key);
            $calendar = $calendarDays->get($dateString);
            $attendance = $attendances->get($key);
            $leave = $leaveMap[$key] ?? null;
            $requests = $overtimeRequests->get($key, collect());
            $effective = $this->effectiveScheduleService->resolveFromModels(
                $employee, $dateString, $regular, $override, $calendar,
            );
            $resolved = $this->statusResolver->resolveEffective($effective, $attendance, $leave);
            $isWorkDay = (bool) $effective['is_working_day'];
            $isCorrected = (bool) ($attendance?->is_manually_adjusted || $attendance?->corrected_at || ($attendance && $corrections->has($attendance->id)));
            $reviewIssues = $this->reviewIssues($date, $today, $effective, $regular, $override, $attendance, $resolved, $requests);

            if ($isWorkDay) {
                $summary['effective_work_days']++;
                if ($resolved['key'] === 'present') {
                    $summary['present_days']++;
                } elseif ($resolved['key'] === 'late') {
                    $summary['present_days']++;
                    $summary['late_days']++;
                } elseif ($resolved['key'] === 'absent') {
                    $summary['absent_days']++;
                } elseif ($resolved['key'] === 'permission') {
                    $summary['permission_days']++;
                } elseif ($resolved['key'] === 'sick') {
                    $summary['sick_days']++;
                } elseif ($resolved['key'] === 'leave') {
                    $summary['leave_days']++;
                }
            } elseif ($this->isOffDay($effective, $regular, $override)) {
                $summary['off_days']++;
            } elseif ($this->isHoliday($effective, $regular)) {
                $summary['holiday_days']++;
            }

            $requestedMinutes = (int) $requests->sum('requested_minutes');
            $approvedMinutes = (int) $requests->where('status', 'approved')->sum(fn ($request) => (int) $request->approved_minutes);
            $sessions = $requests->pluck('session')->filter();
            $completedSessions = $sessions->filter(fn ($session) => $session->isCompleted());
            $actualMinutes = (int) $completedSessions->sum('actual_minutes');
            $creditedMinutes = (int) $completedSessions->sum('credited_minutes');
            $primarySession = $sessions->sortByDesc('id')->first();

            $summary['total_late_minutes'] += $isWorkDay ? (int) ($attendance?->late_minutes ?? 0) : 0;
            $summary['total_early_leave_minutes'] += $isWorkDay ? (int) ($attendance?->early_leave_minutes ?? 0) : 0;
            $summary['regular_worked_minutes'] += (int) ($attendance?->worked_minutes ?? 0);
            $summary['overtime_requested_minutes'] += $requestedMinutes;
            $summary['overtime_approved_minutes'] += $approvedMinutes;
            $summary['overtime_actual_minutes'] += $actualMinutes;
            $summary['overtime_credited_minutes'] += $creditedMinutes;
            $summary['completed_work_days'] += $attendance?->check_in_at && $attendance?->check_out_at ? 1 : 0;
            $summary['missing_checkout_count'] += $attendance?->check_in_at && ! $attendance?->check_out_at ? 1 : 0;
            $summary['corrected_attendance_count'] += $isCorrected ? 1 : 0;
            $summary['review_required_count'] += $reviewIssues === [] ? 0 : 1;

            $daily[] = [
                'date' => $date->copy(),
                'date_string' => $dateString,
                'day_name' => $date->locale('id')->isoFormat('dddd'),
                'effective_schedule' => $effective,
                'effective_schedule_label' => $effective['label'],
                'shift' => $effective['shift'],
                'status_key' => $resolved['key'],
                'status_label' => $resolved['label'],
                'status_badge_class' => $resolved['badge_class'],
                'attendance' => $attendance,
                'check_in_at' => $attendance?->check_in_at,
                'check_out_at' => $attendance?->check_out_at,
                'late_minutes' => $isWorkDay ? (int) ($attendance?->late_minutes ?? 0) : 0,
                'early_leave_minutes' => $isWorkDay ? (int) ($attendance?->early_leave_minutes ?? 0) : 0,
                'regular_worked_minutes' => (int) ($attendance?->worked_minutes ?? 0),
                'leave_type' => $isWorkDay ? $leave?->type : null,
                'leave_label' => $isWorkDay ? $leave?->type_label : null,
                'holiday_name' => $effective['holiday_name'],
                'schedule_source' => $effective['source'],
                'has_override' => $override !== null,
                'is_corrected' => $isCorrected,
                'overtime_requested_minutes' => $requestedMinutes,
                'overtime_approved_minutes' => $approvedMinutes,
                'overtime_start_at' => $primarySession?->check_in_at,
                'overtime_finish_at' => $primarySession?->check_out_at,
                'overtime_actual_minutes' => $actualMinutes,
                'overtime_credited_minutes' => $creditedMinutes,
                'overtime_session_status' => $primarySession?->status,
                'review_issues' => $reviewIssues,
                'needs_review' => $reviewIssues !== [],
            ];
        }

        $summary['attendance_rate'] = $summary['effective_work_days'] > 0
            ? round(($summary['present_days'] / $summary['effective_work_days']) * 100, 2)
            : 0.0;
        $summary['readiness_status'] = $summary['review_required_count'] > 0 ? 'NEEDS_REVIEW' : 'READY';
        $summary['readiness_label'] = $summary['readiness_status'] === 'READY' ? 'READY' : 'PERLU REVIEW';

        return ['employee' => $employee, 'period' => $period, 'summary' => $summary, 'daily' => $daily];
    }

    /** @return array<int, array{code: string, label: string}> */
    protected function reviewIssues(
        Carbon $date,
        Carbon $today,
        array $effective,
        ?EmployeeSchedule $regular,
        ?EmployeeScheduleOverride $override,
        ?AttendanceRecord $attendance,
        array $resolved,
        Collection $requests,
    ): array {
        $issues = [];
        if ($attendance?->check_in_at && ! $attendance?->check_out_at) {
            $issues[] = ['code' => 'missing_checkout', 'label' => 'Attendance belum checkout'];
        }
        if ($requests->pluck('session')->filter()->contains(fn ($session) => $session->isActive())) {
            $issues[] = ['code' => 'active_overtime', 'label' => 'Sesi lembur masih aktif'];
        }
        $missingShift = ($regular?->schedule_type === 'work' && ! $regular->shift)
            || ($override?->override_type === 'work' && ! $override->shift)
            || ($effective['source'] === 'special_working_day' && ! $effective['shift']);
        if ($missingShift) {
            $issues[] = ['code' => 'incomplete_schedule', 'label' => 'Jadwal kerja belum memiliki shift'];
        }
        if ($date->lt($today) && $effective['source'] === 'none') {
            $issues[] = ['code' => 'missing_schedule', 'label' => 'Jadwal belum ditetapkan'];
        }
        if ($date->lt($today) && $attendance && ! $attendance->check_in_at
            && ! in_array($resolved['key'], ['permission', 'sick', 'leave', 'holiday', 'off', 'absent'], true)) {
            $issues[] = ['code' => 'unresolved_attendance', 'label' => 'Status attendance belum terselesaikan'];
        }

        return $issues;
    }

    protected function isOffDay(array $effective, ?EmployeeSchedule $regular, ?EmployeeScheduleOverride $override): bool
    {
        return ($effective['source'] === 'employee_override' && $override?->override_type === 'off')
            || ($effective['source'] === 'regular_schedule' && $regular?->schedule_type === 'off');
    }

    protected function isHoliday(array $effective, ?EmployeeSchedule $regular): bool
    {
        return in_array($effective['source'], ['public_holiday', 'company_holiday'], true)
            || ($effective['source'] === 'regular_schedule' && $regular?->schedule_type === 'holiday');
    }

    /** @return array<string, mixed> */
    protected function baseSummary(Employee $employee, array $period): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'employee_code' => $employee->employee_code,
            'job_title' => $employee->jobTitle?->name,
            'period' => $period['key'],
            'calendar_days' => $period['calendar_days'],
            'effective_work_days' => 0,
            'holiday_days' => 0,
            'off_days' => 0,
            'present_days' => 0,
            'late_days' => 0,
            'absent_days' => 0,
            'permission_days' => 0,
            'sick_days' => 0,
            'leave_days' => 0,
            'total_late_minutes' => 0,
            'total_early_leave_minutes' => 0,
            'regular_worked_minutes' => 0,
            'overtime_requested_minutes' => 0,
            'overtime_approved_minutes' => 0,
            'overtime_actual_minutes' => 0,
            'overtime_credited_minutes' => 0,
            'completed_work_days' => 0,
            'missing_checkout_count' => 0,
            'corrected_attendance_count' => 0,
            'review_required_count' => 0,
            'attendance_rate' => 0.0,
            'readiness_status' => 'READY',
            'readiness_label' => 'READY',
        ];
    }

    /** @return array<string, mixed> */
    protected function emptyEmployeeRecap(Employee $employee, array $period): array
    {
        return ['employee' => $employee, 'period' => $period, 'summary' => $this->baseSummary($employee, $period), 'daily' => []];
    }

    protected function key(int $employeeId, mixed $date): string
    {
        $dateString = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : substr((string) $date, 0, 10);

        return $employeeId.'_'.$dateString;
    }

    protected function validatePeriod(int $year, int $month): void
    {
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Periode rekap bulanan tidak valid.');
        }
    }
}
