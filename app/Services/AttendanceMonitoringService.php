<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
     * Accepts optional $items to avoid redundant getAttendanceMonitoringList calls.
     */
    public function getSummaryMetrics(?string $dateStr = null, ?User $actor = null, ?array $items = null, ?int $requestedOutletId = null): array
    {
        $targetDate = $dateStr ?: Carbon::now(config('app.timezone'))->toDateString();

        if ($items === null) {
            $items = $this->getAttendanceMonitoringList(['date' => $targetDate], null, $actor, $requestedOutletId);
        }

        $collection = collect($items);

        $employeesQuery = Employee::whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();

        $outletScopeService = app(OutletScopeService::class);
        $targetOutletId = $actor ? $outletScopeService->resolveRequestedOutlet($actor, $requestedOutletId) : $requestedOutletId;

        if ($targetOutletId !== null) {
            // Total Karyawan is an organizational (Home Outlet) KPI, unlike the
            // date-based attendance items that are filtered by Work Outlet.
            $employeesQuery->where('employees.outlet_id', $targetOutletId);
        } elseif ($actor) {
            $outletScopeService->scopeEmployeesFor($actor, $employeesQuery);
        }

        $totalEmployees = $employeesQuery->count();

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
    public function getAttendanceMonitoringList(array $filters = [], ?Carbon $nowServerTime = null, ?User $actor = null, ?int $requestedOutletId = null): array
    {
        $targetDate = $filters['date'] ?? Carbon::now(config('app.timezone'))->toDateString();
        $filterEmployeeId = ! empty($filters['employee_id']) ? (int) $filters['employee_id'] : null;
        $filterStatus = ! empty($filters['status']) ? strtolower($filters['status']) : null;

        // Fetch active employees with their job titles
        $employeesQuery = Employee::with(['jobTitle', 'outlet'])
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();

        $outletScopeService = app(OutletScopeService::class);
        $inputOutletId = $requestedOutletId ?? ($filters['outlet_id'] ?? null);
        $targetOutletId = $actor ? $outletScopeService->resolveRequestedOutlet($actor, $inputOutletId ? (int) $inputOutletId : null) : $inputOutletId;

        if ($actor) {
            // Home Outlet controls who an admin may manage. The requested outlet is
            // applied below to the resolved Work Outlet for this operational date.
            $outletScopeService->scopeEmployeesFor($actor, $employeesQuery);
        }

        if ($filterEmployeeId) {
            $employeesQuery->where('id', $filterEmployeeId);
        }

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();
        $employeeIds = $employees->pluck('id');

        // Fetch schedules for target date with shifts
        $schedules = EmployeeSchedule::with(['shift', 'workOutlet'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', $targetDate)
            ->get()
            ->keyBy('employee_id');
        $overrides = EmployeeScheduleOverride::with(['shift', 'workOutlet'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', $targetDate)->get()->keyBy('employee_id');
        $calendarDay = Holiday::whereDate('date', $targetDate)->first();

        // Fetch attendance records for target date with location
        $records = AttendanceRecord::with(['location'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', $targetDate)
            ->get()
            ->keyBy('employee_id');

        // Fetch approved leave requests for target date
        $approvedLeaves = LeaveRequest::whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->where('status', 'approved')
            ->whereIn('employee_id', $employeeIds)
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
            if ($targetOutletId !== null && (int) $effective['work_outlet_id'] !== $targetOutletId) {
                continue;
            }
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
     * Batches all 7-day data requests to prevent N+1 query loops.
     */
    public function getPastWeekTrendData(?User $actor = null, ?int $requestedOutletId = null): array
    {
        $today = Carbon::now(config('app.timezone'));
        $startDate = (clone $today)->subDays(6)->toDateString();
        $endDate = $today->toDateString();

        // 1. Fetch active employees scoped by actor & requested outlet
        $employeesQuery = Employee::with(['jobTitle', 'outlet'])
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();

        $outletScopeService = app(OutletScopeService::class);
        $targetOutletId = $actor ? $outletScopeService->resolveRequestedOutlet($actor, $requestedOutletId) : $requestedOutletId;

        if ($actor) {
            $outletScopeService->scopeEmployeesFor($actor, $employeesQuery);
        }

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();
        $empIds = $employees->pluck('id');

        if ($empIds->isEmpty()) {
            $dates = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = (clone $today)->subDays($i)->toDateString();
                $dates[] = [
                    'date' => $date,
                    'label' => Carbon::parse($date)->locale('id')->isoFormat('ddd'),
                    'total' => 0,
                    'present' => 0,
                    'late' => 0,
                    'pending' => 0,
                    'absent' => 0,
                    'leave' => 0,
                ];
            }

            return ['has_data' => false, 'data' => $dates];
        }

        // 2. Batch fetch schedules, overrides, holidays, attendance records, and approved leaves for the 7-day range
        $schedules = EmployeeSchedule::with(['shift', 'workOutlet'])
            ->whereIn('employee_id', $empIds)
            ->whereDate('work_date', '>=', $startDate)
            ->whereDate('work_date', '<=', $endDate)
            ->get()
            ->groupBy(fn ($item) => $item->employee_id.'_'.$item->work_date->format('Y-m-d'));

        $overrides = EmployeeScheduleOverride::with(['shift', 'workOutlet'])
            ->whereIn('employee_id', $empIds)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get()
            ->groupBy(fn ($item) => $item->employee_id.'_'.$item->date->format('Y-m-d'));

        $calendarDays = Holiday::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get()
            ->keyBy(fn ($item) => $item->date->format('Y-m-d'));

        $records = AttendanceRecord::with(['location'])
            ->whereIn('employee_id', $empIds)
            ->whereDate('work_date', '>=', $startDate)
            ->whereDate('work_date', '<=', $endDate)
            ->get()
            ->groupBy(fn ($item) => $item->employee_id.'_'.$item->work_date->format('Y-m-d'));

        $approvedLeaves = LeaveRequest::whereIn('employee_id', $empIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get();

        $leaveMap = [];
        foreach ($approvedLeaves as $leaveReq) {
            $lStart = $leaveReq->start_date->copy()->max(Carbon::parse($startDate));
            $lEnd = $leaveReq->end_date->copy()->min(Carbon::parse($endDate));
            foreach (CarbonPeriod::create($lStart, $lEnd) as $d) {
                $leaveMap[$leaveReq->employee_id.'_'.$d->format('Y-m-d')] = $leaveReq;
            }
        }

        $dates = [];
        $hasData = false;

        for ($i = 6; $i >= 0; $i--) {
            $date = (clone $today)->subDays($i)->toDateString();
            $calendarDay = $calendarDays->get($date);

            $presentCount = 0;
            $lateCount = 0;
            $pendingCount = 0;
            $absentCount = 0;
            $leaveCount = 0;

            foreach ($employees as $emp) {
                $key = $emp->id.'_'.$date;
                $schedule = $schedules->get($key)?->first();
                $override = $overrides->get($key)?->first();
                $record = $records->get($key)?->first();
                $approvedLeave = $leaveMap[$key] ?? null;

                $effective = $this->effectiveScheduleService->resolveFromModels(
                    $emp, $date, $schedule, $override, $calendarDay,
                );
                if ($targetOutletId !== null && (int) $effective['work_outlet_id'] !== $targetOutletId) {
                    continue;
                }
                $resolved = $this->statusResolver->resolveEffective($effective, $record, $approvedLeave, null);
                $statusKey = $resolved['key'];

                if (in_array($statusKey, ['present', 'late'], true)) {
                    $presentCount++;
                }
                if ($statusKey === 'late') {
                    $lateCount++;
                }
                if ($statusKey === 'pending') {
                    $pendingCount++;
                }
                if ($statusKey === 'absent') {
                    $absentCount++;
                }
                if (in_array($statusKey, ['permission', 'sick', 'leave'], true)) {
                    $leaveCount++;
                }
            }

            if ($presentCount > 0) {
                $hasData = true;
            }

            $dates[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->locale('id')->isoFormat('ddd'),
                'total' => $presentCount,
                'present' => $presentCount,
                'late' => $lateCount,
                'pending' => $pendingCount,
                'absent' => $absentCount,
                'leave' => $leaveCount,
            ];
        }

        return [
            'has_data' => $hasData,
            'data' => $dates,
        ];
    }
}
