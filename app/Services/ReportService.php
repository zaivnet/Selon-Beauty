<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReportService
{
    public function __construct(
        protected ?AttendanceStatusResolver $statusResolver = null,
        protected ?EffectiveScheduleService $effectiveScheduleService = null,
        protected ?OutletScopeService $outletScopeService = null,
        protected ?EmployeeTransferService $transferService = null,
    ) {
        $this->statusResolver = $statusResolver ?? new AttendanceStatusResolver;
        $this->effectiveScheduleService = $effectiveScheduleService ?? new EffectiveScheduleService;
        $this->outletScopeService = $outletScopeService ?? app(OutletScopeService::class);
        $this->transferService = $transferService ?? app(EmployeeTransferService::class);
    }

    /**
     * Get workforce employees relevant to the selected target outlets in the date range.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    public function getReportEmployees(User $actor, string $startDateStr, string $endDateStr, ?int $outletId = null): \Illuminate\Database\Eloquent\Collection
    {
        $allowedOutletIds = $this->outletScopeService->allowedOutletIds($actor);
        if (empty($allowedOutletIds)) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        if ($outletId !== null) {
            if (! in_array($outletId, $allowedOutletIds, true)) {
                return new \Illuminate\Database\Eloquent\Collection;
            }
            $targetOutletIds = [$outletId];
        } else {
            $targetOutletIds = $allowedOutletIds;
        }

        $overrideEmpIds = EmployeeScheduleOverride::whereDate('date', '>=', $startDateStr)
            ->whereDate('date', '<=', $endDateStr)
            ->whereIn('work_outlet_id', $targetOutletIds)
            ->pluck('employee_id')
            ->all();

        $scheduleEmpIds = EmployeeSchedule::whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->whereIn('work_outlet_id', $targetOutletIds)
            ->pluck('employee_id')
            ->all();

        $attendanceEmpIds = AttendanceRecord::whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->whereIn('outlet_id', $targetOutletIds)
            ->pluck('employee_id')
            ->all();

        return Employee::whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce()
            ->where(function ($q) use ($targetOutletIds, $overrideEmpIds, $scheduleEmpIds, $attendanceEmpIds) {
                $q->whereIn('outlet_id', $targetOutletIds);
                if (! empty($overrideEmpIds)) {
                    $q->orWhereIn('id', $overrideEmpIds);
                }
                if (! empty($scheduleEmpIds)) {
                    $q->orWhereIn('id', $scheduleEmpIds);
                }
                if (! empty($attendanceEmpIds)) {
                    $q->orWhereIn('id', $attendanceEmpIds);
                }
            })
            ->orderBy('full_name', 'asc')
            ->get();
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
        $actor = $filters['actor'] ?? null;

        $startDate = Carbon::parse($startDateStr, config('app.timezone'))->startOfDay();
        $endDate = Carbon::parse($endDateStr, config('app.timezone'))->endOfDay();
        $todayStr = now(config('app.timezone'))->format('Y-m-d');

        // Resolve Authorized Outlets
        $allowedOutletIds = $actor ? $this->outletScopeService->allowedOutletIds($actor) : \App\Models\Outlet::where('is_active', true)->pluck('id')->all();
        if (empty($allowedOutletIds)) {
            return $this->emptyReportData($startDateStr, $endDateStr, $filters);
        }

        $outletIdFilter = isset($filters['outlet_id']) && $filters['outlet_id'] !== null && $filters['outlet_id'] !== '' && $filters['outlet_id'] !== 'all'
            ? (int) $filters['outlet_id']
            : null;

        if ($outletIdFilter !== null) {
            if (! in_array($outletIdFilter, $allowedOutletIds, true)) {
                throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Akses outlet ditolak. Anda tidak berwenang melihat laporan untuk outlet ini.');
            }
            $targetOutletIds = [$outletIdFilter];
        } else {
            $targetOutletIds = $allowedOutletIds;
        }

        // 1. Fetch Candidate Employees
        $overrideEmpIds = EmployeeScheduleOverride::whereDate('date', '>=', $startDateStr)
            ->whereDate('date', '<=', $endDateStr)
            ->whereIn('work_outlet_id', $targetOutletIds)
            ->pluck('employee_id')
            ->all();

        $scheduleEmpIds = EmployeeSchedule::whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->whereIn('work_outlet_id', $targetOutletIds)
            ->pluck('employee_id')
            ->all();

        $attendanceEmpIds = AttendanceRecord::whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->whereIn('outlet_id', $targetOutletIds)
            ->pluck('employee_id')
            ->all();

        $employeesQuery = Employee::with(['jobTitle', 'user', 'outlet'])->whereNull('deleted_at');

        if ($employeeIdFilter) {
            $employeesQuery->where('id', $employeeIdFilter);
        } else {
            $employeesQuery->where('status', 'active')->currentAttendanceWorkforce();
        }

        $employeesQuery->where(function ($q) use ($targetOutletIds, $overrideEmpIds, $scheduleEmpIds, $attendanceEmpIds) {
            $q->whereIn('outlet_id', $targetOutletIds);
            if (! empty($overrideEmpIds)) {
                $q->orWhereIn('id', $overrideEmpIds);
            }
            if (! empty($scheduleEmpIds)) {
                $q->orWhereIn('id', $scheduleEmpIds);
            }
            if (! empty($attendanceEmpIds)) {
                $q->orWhereIn('id', $attendanceEmpIds);
            }
        });

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
            ->with(['shift', 'workOutlet'])
            ->get()
            ->groupBy(fn ($s) => $s->employee_id.'_'.$s->work_date->format('Y-m-d'));

        $attendances = AttendanceRecord::whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->with(['location', 'outlet'])
            ->get()
            ->groupBy(fn ($a) => $a->employee_id.'_'.$a->work_date->format('Y-m-d'));

        $overrides = EmployeeScheduleOverride::whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $startDateStr)->whereDate('date', '<=', $endDateStr)
            ->with(['shift', 'workOutlet'])->get()->keyBy(fn ($item) => $item->employee_id.'_'.$item->date->format('Y-m-d'));
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

        // Fetch Employee Outlet Transfers for historical home outlet resolution
        $transfersMap = \App\Models\EmployeeOutletTransfer::whereIn('employee_id', $employeeIds)
            ->with(['fromOutlet', 'toOutlet'])
            ->orderBy('effective_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('employee_id');

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

            $tempAssignmentDays = 0;
            $empMatchingDayCount = 0;

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

                // Resolve historical HOME Outlet on $dStr
                $historicalHomeOutlet = $this->transferService->resolveHistoricalHomeOutlet(
                    $emp,
                    $dStr,
                    $transfersMap->get($emp->id, collect())
                );
                $workOutlet = $att?->outlet ?? $effective['work_outlet'] ?? $historicalHomeOutlet ?? $emp->outlet;
                $workOutletId = $att?->outlet_id
                    ? (int) $att->outlet_id
                    : ($effective['work_outlet_id']
                        ? (int) $effective['work_outlet_id']
                        : ($historicalHomeOutlet?->id
                            ? (int) $historicalHomeOutlet->id
                            : (int) $emp->outlet_id));

                // Scope to target authorized outlets
                if (! in_array($workOutletId, $targetOutletIds, true)) {
                    continue;
                }

                $empMatchingDayCount++;

                $isTemporaryAssignment = (bool) (
                    $workOutletId
                    && $historicalHomeOutlet
                    && $workOutletId !== (int) $historicalHomeOutlet->id
                );

                if ($isTemporaryAssignment) {
                    $tempAssignmentDays++;
                }

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
                    'work_outlet' => $workOutlet,
                    'historical_home_outlet' => $historicalHomeOutlet,
                    'is_temporary_assignment' => $isTemporaryAssignment,
                    'leave_request' => $leave,
                    'overtime_request' => $ovt,
                    'status' => $statusKey,
                    'status_key' => $statusKey,
                    'status_label' => $statusLabel,
                    'status_badge_class' => $statusBadgeClass,
                    'check_in_at' => $att?->check_in_at,
                    'check_out_at' => $att?->check_out_at,
                    'checkout_source' => $att?->checkout_source,
                    'late_minutes' => $lateMinutes,
                    'worked_minutes' => $workedMinutes,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                    'approved_overtime_minutes' => $approvedOvertimeMinutes,
                    'actual_overtime_minutes' => $actualOvertimeMinutes,
                    'credited_overtime_minutes' => $creditedOvertimeMinutes,
                    'overtime_session' => $ovt?->session,
                ];
            }

            if ($empMatchingDayCount > 0) {
                $empSummary['attendance_rate'] = $empSummary['scheduled_work_days'] > 0
                    ? round(($empSummary['present_count'] / $empSummary['scheduled_work_days']) * 100, 2)
                    : 0.0;

                $empSummary['temporary_assignment_days'] = $tempAssignmentDays;
                $empSummary['notice'] = $tempAssignmentDays > 0 ? "Memiliki {$tempAssignmentDays} hari penugasan di outlet lain pada periode ini." : null;

                $employeeSummaries[$emp->id] = $empSummary;
            }
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
