<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperationalExceptionService
{
    public const CATEGORY_DEFINITIONS = [
        'pending_check_in' => ['label' => 'Belum Check-in', 'default_severity' => 'warning'],
        'late' => ['label' => 'Terlambat', 'default_severity' => 'warning'],
        'missing_checkout' => ['label' => 'Belum Check-out', 'default_severity' => 'warning'],
        'absent' => ['label' => 'Tidak Hadir', 'default_severity' => 'critical'],
        'attendance_needs_review' => ['label' => 'Attendance Perlu Review', 'default_severity' => 'warning'],
        'overtime_active' => ['label' => 'Lembur Aktif', 'default_severity' => 'info'],
        'overtime_approved_not_started' => ['label' => 'Lembur Disetujui Belum Dimulai', 'default_severity' => 'warning'],
        'pending_leave' => ['label' => 'Pengajuan Izin Pending', 'default_severity' => 'warning'],
        'pending_overtime' => ['label' => 'Pengajuan Lembur Pending', 'default_severity' => 'warning'],
        'schedule_override' => ['label' => 'Override Jadwal', 'default_severity' => 'info'],
        'recent_correction' => ['label' => 'Koreksi Attendance Terbaru', 'default_severity' => 'info'],
        'backup_scheduler_issue' => ['label' => 'Backup / Scheduler', 'default_severity' => 'critical'],
    ];

    public function __construct(
        protected AttendanceStatusResolver $statusResolver,
        protected EffectiveScheduleService $effectiveScheduleService,
        protected MonthlyAttendanceRecapService $monthlyRecapService,
        protected ?OutletScopeService $outletScopeService = null,
    ) {
        $this->outletScopeService = $outletScopeService ?? new OutletScopeService;
    }

    /** @return array<string, mixed> */
    public function generate(?string $date = null, array $filters = [], ?Carbon $now = null): array
    {
        $timezone = config('app.timezone');
        $actualNow = ($now ?? Carbon::now($timezone))->copy()->timezone($timezone);
        $target = Carbon::parse($date ?: $actualNow->toDateString(), $timezone)->startOfDay();
        $targetDate = $target->toDateString();
        $operationalNow = $target->isSameDay($actualNow)
            ? $actualNow
            : ($target->isBefore($actualNow) ? $target->copy()->endOfDay() : $actualNow);
        $lookbackStart = $target->copy()->subDays(max(1, (int) config('operations.review_lookback_days', 31)));

        $employeesQuery = Employee::with(['jobTitle', 'outlet'])->whereNull('deleted_at')->where('status', 'active')->currentAttendanceWorkforce();
        $targetOutletId = null;
        $allowedOutletIds = [];
        if (! empty($filters['actor'])) {
            $rawInput = $filters['outlet_id'] ?? null;
            $requestedOutletId = ($rawInput !== null && $rawInput !== '' && $rawInput !== 'all') ? (int) $rawInput : ($rawInput === 'all' || $rawInput === '0' || $rawInput === 0 ? 0 : null);
            $targetOutletId = $this->outletScopeService->resolveRequestedOutlet($filters['actor'], $requestedOutletId);
            $allowedOutletIds = $this->outletScopeService->allowedOutletIds($filters['actor']);

            if (! $this->outletScopeService->isGlobalScope($filters['actor'])) {
                $matchingOverrideEmpIds = EmployeeScheduleOverride::whereDate('date', '>=', $lookbackStart->toDateString())
                    ->whereDate('date', '<=', $targetDate)
                    ->whereIn('work_outlet_id', $allowedOutletIds)
                    ->pluck('employee_id')
                    ->all();

                $matchingSchedEmpIds = EmployeeSchedule::whereDate('work_date', '>=', $lookbackStart->toDateString())
                    ->whereDate('work_date', '<=', $targetDate)
                    ->whereIn('work_outlet_id', $allowedOutletIds)
                    ->pluck('employee_id')
                    ->all();

                $employeesQuery->where(function ($q) use ($allowedOutletIds, $matchingOverrideEmpIds, $matchingSchedEmpIds) {
                    $q->whereIn('employees.outlet_id', $allowedOutletIds);
                    if (! empty($matchingOverrideEmpIds)) {
                        $q->orWhereIn('employees.id', $matchingOverrideEmpIds);
                    }
                    if (! empty($matchingSchedEmpIds)) {
                        $q->orWhereIn('employees.id', $matchingSchedEmpIds);
                    }
                });
            }
        } elseif (! empty($filters['outlet_id'])) {
            $targetOutletId = (int) $filters['outlet_id'];
        }
        if (! empty($filters['employee_id'])) {
            $employeesQuery->whereKey((int) $filters['employee_id']);
        }
        if (! empty($filters['job_title_id'])) {
            $employeesQuery->where('job_title_id', (int) $filters['job_title_id']);
        }
        $employees = $employeesQuery->orderBy('full_name')->get();
        $employeeIds = $employees->pluck('id');
        $employeeMap = $employees->keyBy('id');

        $schedules = EmployeeSchedule::with(['shift', 'workOutlet'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $lookbackStart->toDateString())
            ->whereDate('work_date', '<=', $targetDate)
            ->get()->keyBy(fn ($model) => $this->key($model->employee_id, $model->work_date));
        $overrides = EmployeeScheduleOverride::with(['shift', 'workOutlet'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $lookbackStart->toDateString())
            ->whereDate('date', '<=', $targetDate)
            ->get()->keyBy(fn ($model) => $this->key($model->employee_id, $model->date));
        $calendar = Holiday::whereDate('date', '>=', $lookbackStart->toDateString())
            ->whereDate('date', '<=', $targetDate)->get()->keyBy(fn ($model) => $model->date->format('Y-m-d'));
        $attendances = AttendanceRecord::with('location')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', '>=', $lookbackStart->toDateString())
            ->whereDate('work_date', '<=', $targetDate)
            ->where(function (Builder $query) use ($targetDate) {
                $query->whereDate('work_date', $targetDate)
                    ->orWhere(function (Builder $open) {
                        $open->whereNotNull('check_in_at')->whereNull('check_out_at');
                    });
            })->get();
        $attendanceMap = $attendances->keyBy(fn ($model) => $this->key($model->employee_id, $model->work_date));
        $approvedLeaves = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->get()->keyBy('employee_id');
        $relevantOvertimeRequests = OvertimeRequest::with('session')
            ->whereIn('employee_id', $employeeIds)
            ->where(function (Builder $query) use ($targetDate, $target, $actualNow) {
                $query->whereDate('work_date', $targetDate);
                if ($target->isSameDay($actualNow)) {
                    $query->orWhere('status', 'pending');
                }
            })->get();
        $overtimeByDate = $relevantOvertimeRequests->groupBy(fn ($model) => $this->key($model->employee_id, $model->work_date));

        $groups = collect(array_keys(self::CATEGORY_DEFINITIONS))->mapWithKeys(fn ($key) => [$key => []])->all();

        foreach ($employees as $employee) {
            $key = $this->key($employee->id, $targetDate);
            $schedule = $schedules->get($key);
            $override = $overrides->get($key);
            $attendance = $attendanceMap->get($key);
            $leave = $approvedLeaves->get($employee->id);
            $effective = $this->effectiveScheduleService->resolveFromModels(
                $employee, $targetDate, $schedule, $override, $calendar->get($targetDate),
            );
            $workOutletId = $effective['work_outlet_id'] ? (int) $effective['work_outlet_id'] : null;
            if ($targetOutletId !== null) {
                if ($workOutletId !== $targetOutletId) {
                    continue;
                }
            } elseif (! empty($filters['actor']) && ! $this->outletScopeService->isGlobalScope($filters['actor'])) {
                if (! $workOutletId || ! in_array($workOutletId, $allowedOutletIds, true)) {
                    continue;
                }
            }
            $effectiveSchedule = $this->effectiveScheduleService->scheduleContext($effective);
            $resolved = $this->statusResolver->resolveEffective($effective, $attendance, $leave, $operationalNow);

            if ($resolved['key'] === 'pending') {
                $groups['pending_check_in'][] = $this->attendanceItem(
                    'pending_check_in', 'warning', $employee, $targetDate,
                    'Window check-in sudah dibuka', $effectiveSchedule, $attendance,
                );
            } elseif ($resolved['key'] === 'late' && $attendance) {
                $groups['late'][] = $this->attendanceItem(
                    'late', 'warning', $employee, $targetDate,
                    'Terlambat '.$attendance->late_minutes.' menit', $effectiveSchedule, $attendance,
                    ['late_minutes' => (int) $attendance->late_minutes, 'check_in_at' => $attendance->check_in_at],
                );
            } elseif ($resolved['key'] === 'absent') {
                $groups['absent'][] = $this->attendanceItem(
                    'absent', 'critical', $employee, $targetDate,
                    'Batas check-in telah lewat', $effectiveSchedule, $attendance,
                );
            }

            $reviewIssues = $this->monthlyRecapService->reviewIssuesForDay(
                $target->copy(), $actualNow->copy()->startOfDay(), $effective, $schedule, $override,
                $attendance, $resolved, $overtimeByDate->get($key, collect()),
            );
            if ($attendance?->check_in_at && ! $attendance->check_out_at && $effectiveSchedule?->shift) {
                $shiftWindow = $this->statusResolver->calculateCheckInWindow($targetDate, $effectiveSchedule->shift);
                $checkoutOpen = $shiftWindow['end_time']->copy()->subMinutes((int) $effectiveSchedule->shift->check_out_open_minutes_before);
                if ($operationalNow->lt($checkoutOpen)) {
                    $reviewIssues = array_values(array_filter(
                        $reviewIssues,
                        fn ($issue) => $issue['code'] !== 'missing_checkout',
                    ));
                }
            }
            if ($reviewIssues !== []) {
                $groups['attendance_needs_review'][] = $this->attendanceItem(
                    'attendance_needs_review', 'warning', $employee, $targetDate,
                    collect($reviewIssues)->pluck('label')->join(', '), $effectiveSchedule, $attendance,
                    ['issues' => $reviewIssues],
                );
            }
        }

        foreach ($attendances->filter(fn ($record) => $record->check_in_at && ! $record->check_out_at) as $attendance) {
            $dateString = $attendance->work_date->format('Y-m-d');
            $employee = $employeeMap->get($attendance->employee_id);
            $schedule = $schedules->get($this->key($attendance->employee_id, $dateString));
            if (! $employee) {
                continue;
            }
            $effective = $this->effectiveScheduleService->resolveFromModels(
                $employee,
                $dateString,
                $schedule,
                $overrides->get($this->key($attendance->employee_id, $dateString)),
                $calendar->get($dateString),
            );
            $attendanceOutletId = $attendance->outlet_id ? (int) $attendance->outlet_id : ($effective['work_outlet_id'] ? (int) $effective['work_outlet_id'] : null);
            if ($targetOutletId !== null) {
                if ($attendanceOutletId !== $targetOutletId) {
                    continue;
                }
            } elseif (! empty($filters['actor']) && ! $this->outletScopeService->isGlobalScope($filters['actor'])) {
                if (! $attendanceOutletId || ! in_array($attendanceOutletId, $allowedOutletIds, true)) {
                    continue;
                }
            }
            $effectiveSchedule = $this->effectiveScheduleService->scheduleContext($effective);
            if (! $effectiveSchedule?->shift) {
                continue;
            }
            $window = $this->statusResolver->calculateCheckInWindow($dateString, $effectiveSchedule->shift);
            $checkoutOpen = $window['end_time']->copy()->subMinutes((int) $effectiveSchedule->shift->check_out_open_minutes_before);
            if ($operationalNow->lt($checkoutOpen)) {
                continue;
            }
            $overdueMinutes = max(0, (int) floor($window['end_time']->diffInMinutes($operationalNow, false)));
            $criticalAfter = max(0, (int) config('operations.missing_checkout_critical_after_minutes', 120));
            $severity = $overdueMinutes > $criticalAfter ? 'critical' : 'warning';
            $groups['missing_checkout'][] = $this->attendanceItem(
                'missing_checkout', $severity, $employee, $dateString,
                $overdueMinutes > 0 ? "Melewati akhir shift {$overdueMinutes} menit" : 'Window check-out sudah dibuka',
                $effectiveSchedule, $attendance, ['overdue_minutes' => $overdueMinutes],
            );
        }

        $activeSessionsQuery = OvertimeSession::with('overtimeRequest')
            ->whereIn('employee_id', $employeeIds)->where('status', 'active');
        if (! $target->isSameDay($actualNow)) {
            $activeSessionsQuery->whereDate('work_date', $targetDate);
        }
        foreach ($activeSessionsQuery->get() as $session) {
            $employee = $employeeMap->get($session->employee_id);
            if (! $employee) {
                continue;
            }
            $elapsed = $session->runningMinutes($operationalNow);
            $approved = (int) ($session->overtimeRequest?->approved_minutes ?? 0);
            $severity = $elapsed > $approved ? 'critical' : 'info';
            $groups['overtime_active'][] = $this->item(
                'overtime_active', $severity, $employee,
                $elapsed > $approved ? 'Durasi lembur melewati persetujuan' : 'Sesi lembur sedang berjalan',
                route('admin.overtime-requests.index', [
                    'status' => 'approved', 'employee_id' => $employee->id,
                    'start_date' => $session->work_date->format('Y-m-d'),
                    'end_date' => $session->work_date->format('Y-m-d'),
                ]),
                'Buka recovery lembur', [
                    'work_date' => $session->work_date->format('Y-m-d'),
                    'start_at' => $session->check_in_at,
                    'elapsed_minutes' => $elapsed,
                    'approved_minutes' => $approved,
                    'remaining_minutes' => max(0, $approved - $elapsed),
                ], $session->id,
            );
        }

        foreach ($relevantOvertimeRequests->where('status', 'approved')->filter(fn ($request) => ! $request->session && $request->work_date->format('Y-m-d') === $targetDate) as $request) {
            $employee = $employeeMap->get($request->employee_id);
            if (! $employee) {
                continue;
            }
            $attendanceStatus = $this->targetAttendanceStatus(
                $employee, $targetDate, $schedules, $overrides, $calendar, $attendanceMap, $approvedLeaves, $operationalNow,
            );
            $groups['overtime_approved_not_started'][] = $this->item(
                'overtime_approved_not_started', 'warning', $employee,
                'Lembur disetujui tetapi belum dimulai',
                route('admin.overtime-requests.index', [
                    'status' => 'approved', 'employee_id' => $employee->id,
                    'start_date' => $targetDate, 'end_date' => $targetDate,
                ]),
                'Lihat lembur', [
                    'work_date' => $targetDate,
                    'requested_minutes' => (int) $request->requested_minutes,
                    'approved_minutes' => (int) $request->approved_minutes,
                    'approved_at' => $request->reviewed_at,
                    'attendance_status' => $attendanceStatus,
                ], $request->id,
            );
        }

        $pendingLeavesQuery = LeaveRequest::whereIn('employee_id', $employeeIds)->where('status', 'pending');
        if (! $target->isSameDay($actualNow)) {
            $pendingLeavesQuery->whereDate('start_date', '<=', $targetDate)->whereDate('end_date', '>=', $targetDate);
        }
        foreach ($pendingLeavesQuery->get()->sortBy(fn ($request) => [$request->start_date->diffInDays($target, false), $request->created_at]) as $request) {
            $employee = $employeeMap->get($request->employee_id);
            if (! $employee) {
                continue;
            }
            $ageHours = max(0, (int) floor($request->created_at->diffInHours($operationalNow, false)));
            $urgent = $request->start_date->lte($target->copy()->addDay()) || $ageHours >= 24;
            $groups['pending_leave'][] = $this->item(
                'pending_leave', $urgent ? 'warning' : 'info', $employee,
                $request->type_label.' menunggu persetujuan',
                route('admin.leave-requests.index', ['status' => 'pending', 'employee_id' => $employee->id]),
                'Tinjau pengajuan', [
                    'leave_type' => $request->type,
                    'start_date' => $request->start_date->format('Y-m-d'),
                    'end_date' => $request->end_date->format('Y-m-d'),
                    'submitted_at' => $request->created_at,
                    'age_hours' => $ageHours,
                ], $request->id,
            );
        }

        $pendingOvertime = $relevantOvertimeRequests->where('status', 'pending');
        if (! $target->isSameDay($actualNow)) {
            $pendingOvertime = $pendingOvertime->filter(fn ($request) => $request->work_date->format('Y-m-d') === $targetDate);
        }
        foreach ($pendingOvertime->sortBy(fn ($request) => [$request->work_date->format('Y-m-d') !== $targetDate, $request->created_at]) as $request) {
            $employee = $employeeMap->get($request->employee_id);
            if (! $employee) {
                continue;
            }
            $ageHours = max(0, (int) floor($request->created_at->diffInHours($operationalNow, false)));
            $urgent = $request->work_date->format('Y-m-d') === $targetDate || $ageHours >= 24;
            $groups['pending_overtime'][] = $this->item(
                'pending_overtime', $urgent ? 'warning' : 'info', $employee,
                'Permohonan lembur menunggu persetujuan',
                route('admin.overtime-requests.index', ['status' => 'pending', 'employee_id' => $employee->id]),
                'Tinjau pengajuan', [
                    'work_date' => $request->work_date->format('Y-m-d'),
                    'requested_minutes' => (int) $request->requested_minutes,
                    'submitted_at' => $request->created_at,
                    'age_hours' => $ageHours,
                ], $request->id,
            );
        }

        foreach ($overrides->filter(fn ($override) => $override->date->format('Y-m-d') === $targetDate) as $override) {
            $employee = $employeeMap->get($override->employee_id);
            if (! $employee) {
                continue;
            }
            $regular = $schedules->get($this->key($employee->id, $targetDate));
            $groups['schedule_override'][] = $this->item(
                'schedule_override', 'info', $employee,
                $override->override_type === 'work' ? 'Masuk kerja dengan jadwal khusus' : 'Libur khusus',
                route('admin.schedules.index', ['start_date' => $targetDate]), 'Buka jadwal', [
                    'date' => $targetDate,
                    'override_type' => $override->override_type,
                    'regular_schedule' => $regular?->schedule_type,
                    'regular_shift' => $regular?->shift?->name,
                    'effective_shift' => $override->shift?->name,
                    'reason' => $override->reason,
                ], $override->id,
            );
        }

        $correctionQuery = AuditLog::with('user')->whereIn('action', ['attendance.corrected', 'attendance.checkout_recovered'])
            ->whereDate('created_at', $targetDate)->latest('created_at')->limit(10);
        if ($employeeIds->isNotEmpty()) {
            $correctionQuery->whereIn('metadata->employee_id', $employeeIds);
        } else {
            $correctionQuery->whereRaw('1 = 0');
        }
        foreach ($correctionQuery->get() as $log) {
            $employeeId = (int) ($log->metadata['employee_id'] ?? 0);
            $employee = $employeeMap->get($employeeId);
            if (! $employee) {
                continue;
            }
            $groups['recent_correction'][] = $this->item(
                'recent_correction', 'info', $employee,
                $log->action === 'attendance.checkout_recovered' ? 'Checkout dipulihkan admin' : 'Attendance dikoreksi admin',
                route('admin.attendance.index', ['date' => $targetDate, 'employee_id' => $employee->id]),
                'Lihat attendance', [
                    'actor' => $log->user?->name,
                    'time' => $log->created_at,
                    'reason' => $log->reason,
                    'changed_fields' => $log->metadata['changed_fields'] ?? [],
                ], $log->id,
            );
        }

        $includeBackupHealth = (bool) ($filters['include_backup_health'] ?? false);
        $backupHealth = $includeBackupHealth ? $this->backupHealth($operationalNow) : [
            'available' => false,
            'severity' => 'info',
            'message' => 'Status backup hanya tersedia untuk Owner dan Superadmin',
        ];
        if ($includeBackupHealth && $backupHealth['severity'] === 'critical') {
            $groups['backup_scheduler_issue'][] = $this->item(
                'backup_scheduler_issue', 'critical', null, $backupHealth['message'],
                route('admin.settings.backups.index'), 'Buka pengaturan backup', $backupHealth,
            );
        }

        usort($groups['late'], fn ($a, $b) => ($b['data']['late_minutes'] ?? 0) <=> ($a['data']['late_minutes'] ?? 0));
        usort($groups['missing_checkout'], fn ($a, $b) => ($b['data']['overdue_minutes'] ?? 0) <=> ($a['data']['overdue_minutes'] ?? 0));

        return $this->finalize($target, $operationalNow, $groups, $filters, $backupHealth);
    }

    /** @return array<string, array{label:string,default_severity:string}> */
    public function categories(): array
    {
        return self::CATEGORY_DEFINITIONS;
    }

    protected function targetAttendanceStatus(
        Employee $employee,
        string $targetDate,
        Collection $schedules,
        Collection $overrides,
        Collection $calendar,
        Collection $attendance,
        Collection $leaves,
        Carbon $now,
    ): string {
        $key = $this->key($employee->id, $targetDate);
        $effective = $this->effectiveScheduleService->resolveFromModels(
            $employee, $targetDate, $schedules->get($key), $overrides->get($key), $calendar->get($targetDate),
        );

        return $this->statusResolver->resolveEffective($effective, $attendance->get($key), $leaves->get($employee->id), $now)['label'];
    }

    /** @return array<string, mixed> */
    protected function attendanceItem(
        string $category,
        string $severity,
        Employee $employee,
        string $date,
        string $message,
        ?EmployeeSchedule $schedule,
        ?AttendanceRecord $attendance,
        array $data = [],
    ): array {
        $url = route('admin.attendance.index', ['date' => $date, 'employee_id' => $employee->id]);

        return $this->item($category, $severity, $employee, $message, $url, 'Buka monitoring', [
            'work_date' => $date,
            'work_outlet_id' => $schedule?->work_outlet_id ?? $employee->outlet_id,
            'attendance_outlet_id' => $attendance?->outlet_id,
            'shift_name' => $schedule?->shift?->name,
            'shift_start' => $schedule?->shift?->start_time,
            'shift_end' => $schedule?->shift?->end_time,
            ...$data,
        ], $attendance?->id);
    }

    /** @return array<string, mixed> */
    protected function item(
        string $category,
        string $severity,
        ?Employee $employee,
        string $message,
        ?string $actionUrl,
        ?string $actionLabel,
        array $data = [],
        mixed $recordId = null,
    ): array {
        return [
            'category' => $category,
            'category_label' => self::CATEGORY_DEFINITIONS[$category]['label'],
            'severity' => $severity,
            'employee' => $employee,
            'employee_id' => $employee?->id,
            'employee_name' => $employee?->full_name,
            'employee_code' => $employee?->employee_code,
            'job_title' => $employee?->jobTitle?->name,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
            'data' => $data,
            'record_id' => $recordId,
        ];
    }

    /** @return array<string, mixed> */
    protected function backupHealth(Carbon $asOf): array
    {
        $settings = AppSetting::whereIn('key', [
            'backup_scheduled_enabled', 'backup_scheduled_frequency', 'backup_scheduled_time', 'backup_scheduled_day',
        ])->get()->keyBy('key');
        $enabled = filter_var($settings->get('backup_scheduled_enabled')?->value ?? false, FILTER_VALIDATE_BOOLEAN);
        $frequency = $settings->get('backup_scheduled_frequency')?->value ?? 'daily';
        $records = BackupRecord::where('status', '!=', 'deleted')->where('created_at', '<=', $asOf)
            ->latest('created_at')->limit(50)->get();
        $latest = $records->first();
        $lastSuccessful = $records->first(fn ($record) => $record->status === 'completed' && (! $enabled || $record->created_by === null));
        $lastFailure = $records->firstWhere('status', 'failed');
        $threshold = $frequency === 'weekly'
            ? max(1, (int) config('operations.backup_weekly_overdue_hours', 192))
            : max(1, (int) config('operations.backup_daily_overdue_hours', 36));

        $severity = 'info';
        $message = $enabled ? 'Backup terjadwal sehat' : 'Backup terjadwal tidak aktif';
        if ($latest?->status === 'failed') {
            $severity = 'critical';
            $message = 'Backup terbaru gagal';
        } elseif ($enabled && ! $lastSuccessful) {
            $severity = 'critical';
            $message = 'Belum ada backup terjadwal yang berhasil';
        } elseif ($enabled && $lastSuccessful && $lastSuccessful->created_at->diffInHours($asOf, false) > $threshold) {
            $severity = 'critical';
            $message = 'Backup terjadwal melewati batas waktu';
        }

        return [
            'available' => true,
            'enabled' => $enabled,
            'frequency' => $frequency,
            'scheduled_time' => $settings->get('backup_scheduled_time')?->value ?? '02:00',
            'severity' => $severity,
            'message' => $message,
            'threshold_hours' => $threshold,
            'last_successful_at' => $lastSuccessful?->created_at,
            'last_failure_at' => $lastFailure?->created_at,
            'latest_status' => $latest?->status,
        ];
    }

    /** @return array<string, mixed> */
    protected function finalize(Carbon $target, Carbon $now, array $rawGroups, array $filters, array $backupHealth): array
    {
        $categoryFilter = $filters['category'] ?? null;
        $severityFilter = $filters['severity'] ?? null;
        $groups = [];
        foreach ($rawGroups as $key => $items) {
            if ($categoryFilter && $categoryFilter !== $key) {
                continue;
            }
            if ($severityFilter) {
                $items = array_values(array_filter($items, fn ($item) => $item['severity'] === $severityFilter));
            }
            if ($items === []) {
                continue;
            }
            $groups[$key] = [
                'key' => $key,
                'label' => self::CATEGORY_DEFINITIONS[$key]['label'],
                'severity' => $this->highestSeverity($items),
                'count' => count($items),
                'items' => array_values($items),
            ];
        }
        $items = collect($groups)->pluck('items')->flatten(1);
        $severityRank = ['critical' => 0, 'warning' => 1, 'info' => 2];
        $sortedItems = $items->sortBy(fn ($item) => [
            $severityRank[$item['severity']] ?? 9,
            array_search($item['category'], array_keys(self::CATEGORY_DEFINITIONS), true),
        ])->values();

        return [
            'date' => $target->toDateString(),
            'date_label' => $target->locale('id')->isoFormat('dddd, D MMMM YYYY'),
            'generated_at' => $now,
            'is_today' => $target->isToday(),
            'summary' => [
                'critical' => $items->where('severity', 'critical')->count(),
                'warning' => $items->where('severity', 'warning')->count(),
                'info' => $items->where('severity', 'info')->count(),
                'pending_approval' => collect($groups)->only(['pending_leave', 'pending_overtime'])->sum('count'),
                'active_overtime' => $groups['overtime_active']['count'] ?? 0,
                'total' => $items->count(),
            ],
            'groups' => $groups,
            'items' => $sortedItems->all(),
            'backup_health' => $backupHealth,
            'filters' => $filters,
        ];
    }

    protected function highestSeverity(array $items): string
    {
        foreach (['critical', 'warning', 'info'] as $severity) {
            if (collect($items)->contains('severity', $severity)) {
                return $severity;
            }
        }

        return 'info';
    }

    protected function key(int $employeeId, mixed $date): string
    {
        $dateString = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : substr((string) $date, 0, 10);

        return $employeeId.'_'.$dateString;
    }
}
