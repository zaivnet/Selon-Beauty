<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class AttendanceMonitoringService
{
    public function __construct(
        protected ?AttendanceStatusResolver $statusResolver = null,
        protected ?EffectiveScheduleService $effectiveScheduleService = null,
    ) {
        $this->statusResolver = $statusResolver ?? new AttendanceStatusResolver;
        $this->effectiveScheduleService = $effectiveScheduleService ?? new EffectiveScheduleService;
    }

    /**
     * Get KPI summary metrics for a given date in business timezone (Asia/Jakarta).
     */
    public function getSummaryMetrics(?string $dateStr = null): array
    {
        $targetDate = $dateStr ?: Carbon::now(config('app.timezone'))->toDateString();

        $items = $this->getAttendanceMonitoringList(['date' => $targetDate]);
        $collection = collect($items);

        $totalEmployees = Employee::whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce()
            ->count();

        $presentToday = $collection->filter(fn ($i) => in_array($i['status_key'], ['present', 'late'], true))->count();
        $lateToday = $collection->filter(fn ($i) => $i['status_key'] === 'late')->count();
        $pendingCheckInToday = $collection->filter(fn ($i) => $i['status_key'] === 'pending')->count();
        $absentToday = $collection->filter(fn ($i) => $i['status_key'] === 'absent')->count();
        $leaveToday = $collection->filter(fn ($i) => in_array($i['status_key'], ['permission', 'sick', 'leave'], true))->count();

        return [
            'target_date' => $targetDate,
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'late_today' => $lateToday,
            'pending_check_in_today' => $pendingCheckInToday,
            'absent_today' => $absentToday,
            'leave_today' => $leaveToday,
        ];
    }

    /**
     * Get real-time filterable attendance monitoring items for a specific date.
     */
    public function getAttendanceMonitoringList(array $filters = [], ?Carbon $nowServerTime = null): array
    {
        $targetDate = $filters['date'] ?? Carbon::now(config('app.timezone'))->toDateString();
        $filterEmployeeId = ! empty($filters['employee_id']) ? (int) $filters['employee_id'] : null;
        $filterStatus = ! empty($filters['status']) ? strtolower($filters['status']) : null;

        // Fetch active employees with their job titles
        $employeesQuery = Employee::with(['jobTitle'])
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();

        if ($filterEmployeeId) {
            $employeesQuery->where('id', $filterEmployeeId);
        }

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();

        // Fetch schedules for target date with shifts
        $schedules = EmployeeSchedule::with(['shift'])
            ->whereDate('work_date', $targetDate)
            ->get()
            ->keyBy('employee_id');
        $overrides = EmployeeScheduleOverride::with('shift')->whereDate('date', $targetDate)->get()->keyBy('employee_id');
        $calendarDay = Holiday::whereDate('date', $targetDate)->first();

        // Fetch attendance records for target date with location
        $records = AttendanceRecord::with(['location'])
            ->whereDate('work_date', $targetDate)
            ->get()
            ->keyBy('employee_id');

        // Fetch approved leave requests for target date
        $approvedLeaves = LeaveRequest::whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->where('status', 'approved')
            ->get()
            ->keyBy('employee_id');

        $items = [];

        foreach ($employees as $emp) {
            $schedule = $schedules->get($emp->id);
            $record = $records->get($emp->id);
            $approvedLeave = $approvedLeaves->get($emp->id);

            $effective = $this->effectiveScheduleService->resolveFromModels(
                $emp, $targetDate, $schedule, $overrides->get($emp->id), $calendarDay,
            );
            $resolved = $this->statusResolver->resolveEffective($effective, $record, $approvedLeave, $nowServerTime);
            $statusKey = $resolved['key'];
            $statusLabel = $resolved['label'];
            $badgeClass = $resolved['badge_class'];

            // Apply status filter if set
            if ($filterStatus && $filterStatus !== 'all') {
                if ($filterStatus === 'pending' && $statusKey !== 'pending') {
                    continue;
                }
                if ($filterStatus === 'absent' && $statusKey !== 'absent') {
                    continue;
                }
                if ($filterStatus === 'present' && ! in_array($statusKey, ['present', 'late'], true)) {
                    continue;
                }
                if ($filterStatus === 'late' && $statusKey !== 'late') {
                    continue;
                }
                if ($filterStatus === 'leave' && ! in_array($statusKey, ['permission', 'sick', 'leave'], true)) {
                    continue;
                }
                if ($filterStatus === 'off' && $statusKey !== 'off') {
                    continue;
                }
                if ($filterStatus === 'holiday' && $statusKey !== 'holiday') {
                    continue;
                }
                if ($filterStatus === 'not_started' && $statusKey !== 'not_started') {
                    continue;
                }
            }

            $items[] = [
                'employee' => $emp,
                'schedule' => $schedule,
                'effective_schedule' => $effective,
                'record' => $record,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'badge_class' => $badgeClass,
            ];
        }

        return $items;
    }

    /**
     * Get past week trend data for admin dashboard chart.
     */
    public function getPastWeekTrendData(): array
    {
        $today = Carbon::now(config('app.timezone'));
        $dates = [];
        $hasData = false;

        for ($i = 6; $i >= 0; $i--) {
            $date = (clone $today)->subDays($i)->toDateString();
            $metrics = $this->getSummaryMetrics($date);
            $totalPresent = $metrics['present_today'];
            if ($totalPresent > 0) {
                $hasData = true;
            }

            $dates[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->locale('id')->isoFormat('ddd'),
                'total' => $totalPresent,
                'present' => $metrics['present_today'],
                'late' => $metrics['late_today'],
                'pending' => $metrics['pending_check_in_today'],
                'absent' => $metrics['absent_today'] ?? 0,
                'leave' => $metrics['leave_today'],
            ];
        }

        return [
            'has_data' => $hasData,
            'data' => $dates,
        ];
    }
}
