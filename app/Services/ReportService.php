<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReportService
{
    public function __construct(
        protected ?AttendanceStatusResolver $statusResolver = null,
        protected ?EffectiveScheduleService $effectiveScheduleService = null,
        protected ?OutletScopeService $outletScopeService = null,
    ) {
        $this->statusResolver = $statusResolver ?? new AttendanceStatusResolver;
        $this->effectiveScheduleService = $effectiveScheduleService ?? new EffectiveScheduleService;
        $this->outletScopeService = $outletScopeService ?? app(OutletScopeService::class);
    }

    /**
     * Generate comprehensive attendance report data based on filters.
     */
    public function generateAttendanceReport(array $filters): array
    {
        $startDateStr = $filters['start_date'] ?? now(config('app.timezone'))->startOfMonth()->format('Y-m-d');
        $endDateStr = $filters['end_date'] ?? now(config('app.timezone'))->endOfMonth()->format('Y-m-d');
        $employeeIdFilter = $filters['employee_id'] ?? null;
        $statusFilter = $filters['status'] ?? 'all';
        $jobTitleIdFilter = $filters['job_title_id'] ?? null;

        $startDate = Carbon::parse($startDateStr, config('app.timezone'))->startOfDay();
        $endDate = Carbon::parse($endDateStr, config('app.timezone'))->endOfDay();
        $todayStr = now(config('app.timezone'))->format('Y-m-d');

        // 1. Fetch Employees
        $employeesQuery = Employee::with(['jobTitle', 'user'])->whereNull('deleted_at');

        if (! empty($filters['actor'])) {
            $employeesQuery = $this->outletScopeService->scopeByRequestedOutlet(
                $filters['actor'],
                $employeesQuery,
                isset($filters['outlet_id']) ? (int) $filters['outlet_id'] : null,
            );
        }

        if ($employeeIdFilter) {
            $employeesQuery->where('id', $employeeIdFilter);
        } else {
            $employeesQuery->currentAttendanceWorkforce();
        }

        if ($jobTitleIdFilter) {
            $employeesQuery->where('job_title_id', $jobTitleIdFilter);
        }

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();
        if ($employeeIdFilter) {
            $employees->each(function (Employee $employee): void {
                if ($employee->user?->role !== 'superadmin') {
                    $employee->setAttribute('attendance_enabled', true);
                }
            });
        }
        $employeeIds = $employees->pluck('id')->toArray();

        if (empty($employeeIds)) {
            return $this->emptyReportData($startDateStr, $endDateStr, $filters);
        }

        // 2. Fetch Bulk Data (N+1 Free)
        $schedules = EmployeeSchedule::whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->with('shift')
            ->get()
            ->groupBy(fn ($s) => $s->employee_id.'_'.$s->work_date->format('Y-m-d'));

        $attendances = AttendanceRecord::whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->with('location')
            ->get()
            ->groupBy(fn ($a) => $a->employee_id.'_'.$a->work_date->format('Y-m-d'));

        $overrides = EmployeeScheduleOverride::whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $startDateStr)->whereDate('date', '<=', $endDateStr)
            ->with('shift')->get()->keyBy(fn ($item) => $item->employee_id.'_'.$item->date->format('Y-m-d'));
        $calendarDays = Holiday::whereDate('date', '>=', $startDateStr)
            ->whereDate('date', '<=', $endDateStr)->get()->keyBy(fn ($item) => $item->date->format('Y-m-d'));

        // Fetch Approved Leave Requests overlapping date range
        $leaveRequests = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDateStr, $endDateStr) {
                $q->whereDate('start_date', '<=', $endDateStr)
                    ->whereDate('end_date', '>=', $startDateStr);
            })
            ->get();

        // Index Leave Requests per (employee_id, Y-m-d)
        $leaveMap = [];
        foreach ($leaveRequests as $lr) {
            $lStart = Carbon::parse($lr->start_date)->max($startDate);
            $lEnd = Carbon::parse($lr->end_date)->min($endDate);
            $lPeriod = CarbonPeriod::create($lStart, $lEnd);
            foreach ($lPeriod as $pDate) {
                $key = $lr->employee_id.'_'.$pDate->format('Y-m-d');
                $leaveMap[$key] = $lr;
            }
        }

        // Fetch Approved Overtime Requests
        $approvedOvertimes = OvertimeRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->with('session')
            ->get()
            ->keyBy(fn ($o) => $o->employee_id.'_'.$o->work_date->format('Y-m-d'));

        // 3. Build Daily Detail Matrix & Calculate Summaries
        $detailRows = [];
        $employeeSummaries = [];

        // Global Summary Accumulators
        $globalSummary = [
            'scheduled_work_days' => 0,
            'present_count' => 0,
            'late_count' => 0,
            'absent_count' => 0,
            'permission_count' => 0,
            'sick_count' => 0,
            'leave_count' => 0,
            'holiday_count' => 0,
            'total_late_minutes' => 0,
            'total_worked_minutes' => 0,
            'total_early_leave_minutes' => 0,
            'total_approved_overtime_minutes' => 0,
            'total_actual_overtime_minutes' => 0,
            'total_credited_overtime_minutes' => 0,
        ];

        $datePeriod = CarbonPeriod::create($startDate, $endDate);

        foreach ($employees as $emp) {
            $empSummary = [
                'employee' => $emp,
                'scheduled_work_days' => 0,
                'present_count' => 0,
                'late_count' => 0,
                'absent_count' => 0,
                'permission_count' => 0,
                'sick_count' => 0,
                'leave_count' => 0,
                'holiday_count' => 0,
                'total_late_minutes' => 0,
                'total_worked_minutes' => 0,
                'total_early_leave_minutes' => 0,
                'total_approved_overtime_minutes' => 0,
                'total_actual_overtime_minutes' => 0,
                'total_credited_overtime_minutes' => 0,
            ];

            foreach ($datePeriod as $currDate) {
                $dStr = $currDate->format('Y-m-d');
                $key = $emp->id.'_'.$dStr;

                $sch = $schedules->get($key)?->first();
                $att = $attendances->get($key)?->first();
                $leave = $leaveMap[$key] ?? null;
                $ovt = $approvedOvertimes->get($key);
                $effective = $this->effectiveScheduleService->resolveFromModels(
                    $emp, $dStr, $sch, $overrides->get($key), $calendarDays->get($dStr),
                );

                // Skip unscheduled days with no attendance, leave, or overtime
                if ($effective['source'] === 'none' && ! $att && ! $leave && ! $ovt) {
                    continue;
                }

                $isWorkDay = $effective['is_working_day'];

                $resolved = $this->statusResolver->resolveEffective($effective, $att, $leave);
                $statusKey = $resolved['key'];
                $statusLabel = $resolved['label'];
                $statusBadgeClass = $resolved['badge_class'];

                $lateMinutes = $isWorkDay && $att ? (int) $att->late_minutes : 0;
                $workedMinutes = $att ? (int) $att->worked_minutes : 0;
                $earlyLeaveMinutes = $att ? (int) $att->early_leave_minutes : 0;
                $approvedOvertimeMinutes = $ovt ? (int) $ovt->approved_minutes : 0;
                $actualOvertimeMinutes = $ovt?->session?->isCompleted() ? (int) $ovt->session->actual_minutes : 0;
                $creditedOvertimeMinutes = $ovt?->session?->isCompleted() ? (int) $ovt->session->credited_minutes : 0;

                if (! $isWorkDay) {
                    if ($effective['source'] !== 'none') {
                        $empSummary['holiday_count']++;
                        $globalSummary['holiday_count']++;
                    }
                } else {
                    // Scheduled WORK day
                    $empSummary['scheduled_work_days']++;
                    $globalSummary['scheduled_work_days']++;

                    if ($statusKey === 'permission') {
                        $empSummary['permission_count']++;
                        $globalSummary['permission_count']++;
                    } elseif ($statusKey === 'sick') {
                        $empSummary['sick_count']++;
                        $globalSummary['sick_count']++;
                    } elseif ($statusKey === 'leave') {
                        $empSummary['leave_count']++;
                        $globalSummary['leave_count']++;
                    } elseif ($statusKey === 'late') {
                        $empSummary['late_count']++;
                        $globalSummary['late_count']++;
                        $empSummary['present_count']++;
                        $globalSummary['present_count']++;
                    } elseif ($statusKey === 'present') {
                        $empSummary['present_count']++;
                        $globalSummary['present_count']++;
                    } elseif ($statusKey === 'absent') {
                        $empSummary['absent_count']++;
                        $globalSummary['absent_count']++;
                    }
                }

                // Accumulate Minutes
                $empSummary['total_late_minutes'] += $lateMinutes;
                $globalSummary['total_late_minutes'] += $lateMinutes;

                $empSummary['total_worked_minutes'] += $workedMinutes;
                $globalSummary['total_worked_minutes'] += $workedMinutes;

                $empSummary['total_early_leave_minutes'] += $earlyLeaveMinutes;
                $globalSummary['total_early_leave_minutes'] += $earlyLeaveMinutes;

                $empSummary['total_approved_overtime_minutes'] += $approvedOvertimeMinutes;
                $globalSummary['total_approved_overtime_minutes'] += $approvedOvertimeMinutes;
                $empSummary['total_actual_overtime_minutes'] += $actualOvertimeMinutes;
                $globalSummary['total_actual_overtime_minutes'] += $actualOvertimeMinutes;
                $empSummary['total_credited_overtime_minutes'] += $creditedOvertimeMinutes;
                $globalSummary['total_credited_overtime_minutes'] += $creditedOvertimeMinutes;

                // Status Filter Matching
                if ($statusFilter !== 'all') {
                    if ($statusFilter === 'present' && ! in_array($statusKey, ['present', 'late'], true)) {
                        continue;
                    }
                    if ($statusFilter === 'late' && $statusKey !== 'late') {
                        continue;
                    }
                    if ($statusFilter === 'absent' && $statusKey !== 'absent') {
                        continue;
                    }
                    if ($statusFilter === 'leave' && ! in_array($statusKey, ['permission', 'sick', 'leave'], true)) {
                        continue;
                    }
                    if ($statusFilter === 'off' && $statusKey !== 'off') {
                        continue;
                    }
                    if ($statusFilter === 'holiday' && $statusKey !== 'holiday') {
                        continue;
                    }
                }

                $detailRows[] = [
                    'employee' => $emp,
                    'date' => $currDate->copy(),
                    'date_str' => $dStr,
                    'day_name' => $currDate->locale('id')->isoFormat('dddd'),
                    'schedule' => $sch,
                    'shift' => $effective['shift'],
                    'effective_schedule' => $effective,
                    'attendance' => $att,
                    'leave_request' => $leave,
                    'overtime_request' => $ovt,
                    'status' => $statusKey,
                    'status_key' => $statusKey,
                    'status_label' => $statusLabel,
                    'status_badge_class' => $statusBadgeClass,
                    'check_in_at' => $att?->check_in_at,
                    'check_out_at' => $att?->check_out_at,
                    'late_minutes' => $lateMinutes,
                    'worked_minutes' => $workedMinutes,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                    'approved_overtime_minutes' => $approvedOvertimeMinutes,
                    'actual_overtime_minutes' => $actualOvertimeMinutes,
                    'credited_overtime_minutes' => $creditedOvertimeMinutes,
                    'overtime_session' => $ovt?->session,
                ];
            }

            $empSummary['attendance_rate'] = $empSummary['scheduled_work_days'] > 0
                ? round(($empSummary['present_count'] / $empSummary['scheduled_work_days']) * 100, 2)
                : 0.0;

            $employeeSummaries[$emp->id] = $empSummary;
        }

        $globalSummary['attendance_rate'] = $globalSummary['scheduled_work_days'] > 0
            ? round(($globalSummary['present_count'] / $globalSummary['scheduled_work_days']) * 100, 2)
            : 0.0;

        return [
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'detail_rows' => $detailRows,
            'employee_summaries' => array_values($employeeSummaries),
            'global_summary' => $globalSummary,
            'filters' => $filters,
        ];
    }

    protected function emptyReportData(string $startDateStr, string $endDateStr, array $filters): array
    {
        return [
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'detail_rows' => [],
            'employee_summaries' => [],
            'global_summary' => [
                'scheduled_work_days' => 0,
                'present_count' => 0,
                'late_count' => 0,
                'absent_count' => 0,
                'permission_count' => 0,
                'sick_count' => 0,
                'leave_count' => 0,
                'holiday_count' => 0,
                'attendance_rate' => 0.0,
                'total_late_minutes' => 0,
                'total_worked_minutes' => 0,
                'total_early_leave_minutes' => 0,
                'total_approved_overtime_minutes' => 0,
                'total_actual_overtime_minutes' => 0,
                'total_credited_overtime_minutes' => 0,
            ],
            'filters' => $filters,
        ];
    }
}
