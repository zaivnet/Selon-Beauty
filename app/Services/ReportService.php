<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReportService
{
    public function __construct(protected ?AttendanceStatusResolver $statusResolver = null)
    {
        $this->statusResolver = $statusResolver ?? new AttendanceStatusResolver();
    }

    /**
     * Generate comprehensive attendance report data based on filters.
     *
     * @param array $filters
     * @return array
     */
    public function generateAttendanceReport(array $filters): array
    {
        $startDateStr = $filters['start_date'] ?? now('Asia/Jakarta')->startOfMonth()->format('Y-m-d');
        $endDateStr = $filters['end_date'] ?? now('Asia/Jakarta')->endOfMonth()->format('Y-m-d');
        $employeeIdFilter = $filters['employee_id'] ?? null;
        $statusFilter = $filters['status'] ?? 'all';
        $jobTitleIdFilter = $filters['job_title_id'] ?? null;

        $startDate = Carbon::parse($startDateStr, 'Asia/Jakarta')->startOfDay();
        $endDate = Carbon::parse($endDateStr, 'Asia/Jakarta')->endOfDay();
        $todayStr = now('Asia/Jakarta')->format('Y-m-d');

        // 1. Fetch Employees
        $employeesQuery = Employee::with('jobTitle')->whereNull('deleted_at');

        if ($employeeIdFilter) {
            $employeesQuery->where('id', $employeeIdFilter);
        }

        if ($jobTitleIdFilter) {
            $employeesQuery->where('job_title_id', $jobTitleIdFilter);
        }

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();
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
            ->groupBy(fn ($s) => $s->employee_id . '_' . $s->work_date->format('Y-m-d'));

        $attendances = AttendanceRecord::whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->with('location')
            ->get()
            ->groupBy(fn ($a) => $a->employee_id . '_' . $a->work_date->format('Y-m-d'));

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
                $key = $lr->employee_id . '_' . $pDate->format('Y-m-d');
                $leaveMap[$key] = $lr;
            }
        }

        // Fetch Approved Overtime Requests
        $approvedOvertimes = OvertimeRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('work_date', '>=', $startDateStr)
            ->whereDate('work_date', '<=', $endDateStr)
            ->get()
            ->keyBy(fn ($o) => $o->employee_id . '_' . $o->work_date->format('Y-m-d'));

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
            'total_late_minutes' => 0,
            'total_worked_minutes' => 0,
            'total_early_leave_minutes' => 0,
            'total_approved_overtime_minutes' => 0,
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
                'total_late_minutes' => 0,
                'total_worked_minutes' => 0,
                'total_early_leave_minutes' => 0,
                'total_approved_overtime_minutes' => 0,
            ];

            foreach ($datePeriod as $currDate) {
                $dStr = $currDate->format('Y-m-d');
                $key = $emp->id . '_' . $dStr;

                $sch = $schedules->get($key)?->first();
                $att = $attendances->get($key)?->first();
                $leave = $leaveMap[$key] ?? null;
                $ovt = $approvedOvertimes->get($key);

                // Skip unscheduled days with no attendance, leave, or overtime
                if (! $sch && ! $att && ! $leave && ! $ovt) {
                    continue;
                }

                $scheduleType = $sch?->schedule_type ?? 'none'; // work, off, holiday, none
                $isWorkDay = ($scheduleType === 'work');

                $resolved = $this->statusResolver->resolve($sch, $att, $leave);
                $statusKey = $resolved['key'];
                $statusLabel = $resolved['label'];
                $statusBadgeClass = $resolved['badge_class'];

                $lateMinutes = $att ? (int) $att->late_minutes : 0;
                $workedMinutes = $att ? (int) $att->worked_minutes : 0;
                $earlyLeaveMinutes = $att ? (int) $att->early_leave_minutes : 0;
                $approvedOvertimeMinutes = $ovt ? (int) $ovt->approved_minutes : 0;

                if (! $isWorkDay) {
                    if ($scheduleType === 'off') {
                        $statusLabel = 'OFF Pekanan';
                    } elseif ($scheduleType === 'holiday') {
                        $statusLabel = 'Libur Toko';
                    } else {
                        $statusLabel = 'Tanpa Jadwal';
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
                    'date' => $dStr,
                    'date_str' => $dStr,
                    'day_name' => $currDate->locale('id')->isoFormat('dddd'),
                    'schedule' => $sch,
                    'shift' => $sch?->shift,
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
                ];
            }

            $employeeSummaries[$emp->id] = $empSummary;
        }

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
                'total_late_minutes' => 0,
                'total_worked_minutes' => 0,
                'total_early_leave_minutes' => 0,
                'total_approved_overtime_minutes' => 0,
            ],
            'filters' => $filters,
        ];
    }
}
