<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeOutletTransfer;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleOverride;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Outlet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DashboardDutyRosterService
{
    public function __construct(
        protected OutletScopeService $outletScopeService,
        protected EffectiveScheduleService $effectiveScheduleService,
        protected AttendanceStatusResolver $statusResolver,
        protected EmployeeTransferService $transferService,
    ) {}

    /**
     * Generate the complete Multi-Outlet Duty Roster (Jadwal Piket) data.
     *
     * @param  User  $actor  The authenticated user viewing the dashboard
     * @param  string|null  $dateStr  Selected date in Y-m-d format (defaults to application-local today)
     * @param  int|string|null  $requestedOutletId  Specific outlet filter (or null/'all' for Semua Outlet)
     * @return array<string, mixed>
     *
     * @throws AccessDeniedHttpException
     */
    public function getRosterData(User $actor, ?string $dateStr = null, int|string|null $requestedOutletId = null): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($timezone);
        $todayStr = $now->toDateString();
        $tomorrowStr = (clone $now)->addDay()->toDateString();

        // 1. Resolve and validate target date
        $targetDate = $this->resolveDateString($dateStr, $todayStr, $timezone);
        $targetDateCarbon = Carbon::parse($targetDate, $timezone);

        $isToday = ($targetDate === $todayStr);
        $isTomorrow = ($targetDate === $tomorrowStr);
        $isFuture = ($targetDate > $todayStr);
        $isPast = ($targetDate < $todayStr);

        // 2. Resolve Authorized Active Outlets
        $authorizedOutlets = $this->getAuthorizedActiveOutlets($actor);

        if ($authorizedOutlets->isEmpty()) {
            return [
                'has_outlets' => false,
                'target_date' => $targetDate,
                'target_date_formatted' => $targetDateCarbon->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                'today_str' => $todayStr,
                'tomorrow_str' => $tomorrowStr,
                'is_today' => $isToday,
                'is_tomorrow' => $isTomorrow,
                'is_future' => $isFuture,
                'is_past' => $isPast,
                'authorized_outlets' => collect(),
                'selected_outlet_id' => null,
                'is_all_outlets' => true,
                'outlets' => [],
                'total_scheduled_duty' => 0,
            ];
        }

        $authorizedOutletIds = $authorizedOutlets->pluck('id')->all();

        // 3. Handle explicit outlet filter & IDOR protection
        $selectedOutletId = null;
        if ($requestedOutletId !== null && $requestedOutletId !== '' && $requestedOutletId !== 'all') {
            $selectedOutletId = (int) $requestedOutletId;

            if (! in_array($selectedOutletId, $authorizedOutletIds, true)) {
                throw new AccessDeniedHttpException('Akses outlet ditolak. Anda tidak berwenang melihat jadwal piket untuk outlet ini.');
            }
        }

        // Determine which outlets to render in the roster
        $targetOutlets = ($selectedOutletId !== null)
            ? $authorizedOutlets->where('id', $selectedOutletId)
            : $authorizedOutlets;

        $targetOutletIds = $targetOutlets->pluck('id')->all();

        // 4. Batch query workforce employees and related scheduling/attendance data
        $rosterItems = $this->fetchAndResolveRosterItems(
            $actor,
            $targetDate,
            $targetOutletIds,
            $isToday,
            $isFuture,
            $isPast,
            $now
        );

        // 5. Group roster items by Outlet -> Shift -> Employees
        $groupedOutlets = [];
        $totalScheduledDutyGlobal = 0;

        foreach ($targetOutlets as $outlet) {
            $itemsForOutlet = $rosterItems->get($outlet->id, collect());
            $dutyItems = $itemsForOutlet->filter(fn ($item) => $item['is_working_duty']);
            $offItems = $itemsForOutlet->filter(fn ($item) => ! $item['is_working_duty']);

            $totalScheduledDutyGlobal += $dutyItems->count();

            // Group duty employees by Shift (ordered by shift start_time)
            $shiftsGrouped = [];
            $shiftSummaryParts = [];

            $groupedByShift = $dutyItems->groupBy(fn ($item) => $item['shift']->id);

            foreach ($groupedByShift as $shiftId => $shiftItems) {
                $firstItem = $shiftItems->first();
                $shift = $firstItem['shift'];
                $startTime = substr((string) $shift->start_time, 0, 5);
                $endTime = substr((string) $shift->end_time, 0, 5);

                $shiftsGrouped[] = [
                    'shift' => $shift,
                    'shift_name' => $shift->name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'time_range' => "{$startTime} - {$endTime}",
                    'employee_count' => $shiftItems->count(),
                    'employees' => $shiftItems->values()->all(),
                ];

                $shiftSummaryParts[] = "{$shift->name} {$shiftItems->count()}";
            }

            // Sort shift groups chronologically by start_time
            usort($shiftsGrouped, fn ($a, $b) => strcmp($a['start_time'], $b['start_time']));

            $groupedOutlets[] = [
                'outlet' => $outlet,
                'total_duty_count' => $dutyItems->count(),
                'off_count' => $offItems->count(),
                'shift_summary' => implode(' • ', $shiftSummaryParts),
                'shifts' => $shiftsGrouped,
            ];
        }

        return [
            'has_outlets' => true,
            'target_date' => $targetDate,
            'target_date_formatted' => $targetDateCarbon->locale('id')->isoFormat('dddd, D MMMM YYYY'),
            'today_str' => $todayStr,
            'tomorrow_str' => $tomorrowStr,
            'is_today' => $isToday,
            'is_tomorrow' => $isTomorrow,
            'is_future' => $isFuture,
            'is_past' => $isPast,
            'authorized_outlets' => $authorizedOutlets,
            'selected_outlet_id' => $selectedOutletId,
            'is_all_outlets' => ($selectedOutletId === null),
            'outlets' => $groupedOutlets,
            'total_scheduled_duty' => $totalScheduledDutyGlobal,
        ];
    }

    /**
     * Batch fetch and resolve effective scheduling, attendance, and leave items.
     *
     * @param  array<int>  $targetOutletIds
     * @return Collection<int, Collection<int, array<string, mixed>>> Keyed by WORK Outlet ID
     */
    protected function fetchAndResolveRosterItems(
        User $actor,
        string $targetDate,
        array $targetOutletIds,
        bool $isToday,
        bool $isFuture,
        bool $isPast,
        Carbon $now
    ): Collection {
        // Query active workforce employees scoped by target outlets
        $employeesQuery = Employee::with(['jobTitle', 'outlet'])
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();

        if (! $this->outletScopeService->isGlobalScope($actor)) {
            $matchingOverrideEmpIds = EmployeeScheduleOverride::whereDate('date', $targetDate)
                ->whereIn('work_outlet_id', $targetOutletIds)
                ->pluck('employee_id')
                ->all();

            $matchingSchedEmpIds = EmployeeSchedule::whereDate('work_date', $targetDate)
                ->whereIn('work_outlet_id', $targetOutletIds)
                ->pluck('employee_id')
                ->all();

            $employeesQuery->where(function ($q) use ($targetOutletIds, $matchingOverrideEmpIds, $matchingSchedEmpIds) {
                $q->whereIn('employees.outlet_id', $targetOutletIds);
                if (! empty($matchingOverrideEmpIds)) {
                    $q->orWhereIn('employees.id', $matchingOverrideEmpIds);
                }
                if (! empty($matchingSchedEmpIds)) {
                    $q->orWhereIn('employees.id', $matchingSchedEmpIds);
                }
            });
        }

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();
        if ($employees->isEmpty()) {
            return collect();
        }

        $employeeIds = $employees->pluck('id')->all();

        if (empty($employeeIds)) {
            return collect();
        }

        // Batch load regular schedules, overrides, holidays, attendance records, leaves, and transfers
        $schedules = EmployeeSchedule::with(['shift', 'workOutlet'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', $targetDate)
            ->get()
            ->keyBy('employee_id');

        $overrides = EmployeeScheduleOverride::with(['shift', 'workOutlet'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', $targetDate)
            ->get()
            ->keyBy('employee_id');

        $calendarDay = Holiday::whereDate('date', $targetDate)->first();

        $records = AttendanceRecord::with(['location', 'outlet'])
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('work_date', $targetDate)
            ->get()
            ->keyBy('employee_id');

        $approvedLeaves = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->get()
            ->keyBy('employee_id');

        $transfersMap = EmployeeOutletTransfer::whereIn('employee_id', $employeeIds)
            ->with(['fromOutlet', 'toOutlet'])
            ->orderBy('effective_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('employee_id');

        $rosterItems = collect();

        foreach ($employees as $emp) {
            $schedule = $schedules->get($emp->id);
            $override = $overrides->get($emp->id);
            $record = $records->get($emp->id);
            $approvedLeave = $approvedLeaves->get($emp->id);

            $effective = $this->effectiveScheduleService->resolveFromModels(
                $emp,
                $targetDate,
                $schedule,
                $override,
                $calendarDay
            );

            $workOutletId = $effective['work_outlet_id'] ? (int) $effective['work_outlet_id'] : null;

            // If not assigned to any of the target outlets, skip
            if ($workOutletId === null || ! in_array($workOutletId, $targetOutletIds, true)) {
                continue;
            }

            // Resolve historical HOME Outlet on targetDate
            $historicalHomeOutlet = $this->transferService->resolveHistoricalHomeOutlet(
                $emp,
                $targetDate,
                $transfersMap->get($emp->id, collect())
            );

            $isTemporaryAssignment = (bool) (
                $workOutletId
                && $historicalHomeOutlet
                && $workOutletId !== (int) $historicalHomeOutlet->id
            );

            // Is the employee scheduled on active working duty?
            $isWorkingDuty = (bool) ($effective['is_working_day'] && $effective['shift'] !== null);

            if (! $isWorkingDuty) {
                // OFF or Holiday
                $rosterItems->push([
                    'work_outlet_id' => $workOutletId,
                    'is_working_duty' => false,
                    'employee' => $emp,
                    'shift' => null,
                ]);

                continue;
            }

            // Status Resolution based on Today vs Future vs Past
            $statusData = $this->resolveRosterItemStatus(
                $effective,
                $record,
                $approvedLeave,
                $isToday,
                $isFuture,
                $isPast,
                $now
            );

            $checkInTime = null;
            if ($record?->check_in_at) {
                $checkInTime = $record->check_in_at->timezone(config('app.timezone'))->format('H:i');
            }

            $initials = strtoupper(substr($emp->full_name, 0, 2));

            $rosterItems->push([
                'work_outlet_id' => $workOutletId,
                'is_working_duty' => true,
                'employee' => $emp,
                'employee_id' => $emp->id,
                'full_name' => $emp->full_name,
                'employee_code' => $emp->employee_code,
                'job_title' => $emp->jobTitle?->name,
                'profile_photo_path' => $emp->profile_photo_path,
                'initials' => $initials,
                'shift' => $effective['shift'],
                'record' => $record,
                'check_in_time' => $checkInTime,
                'status_key' => $statusData['key'],
                'status_label' => $statusData['label'],
                'badge_class' => $statusData['badge_class'],
                'is_temporary_assignment' => $isTemporaryAssignment,
                'home_outlet_name' => $historicalHomeOutlet?->name ?? $emp->outlet?->name,
            ]);
        }

        return $rosterItems->groupBy('work_outlet_id');
    }

    /**
     * Resolve the UI status label and badge class for a duty roster employee.
     *
     * @return array{key: string, label: string, badge_class: string}
     */
    protected function resolveRosterItemStatus(
        array $effective,
        ?AttendanceRecord $record,
        ?LeaveRequest $approvedLeave,
        bool $isToday,
        bool $isFuture,
        bool $isPast,
        Carbon $now
    ): array {
        // Priority 1: Approved Leave (Always show leave status across Today, Future, Past)
        if ($approvedLeave) {
            $type = strtolower($approvedLeave->type);
            $label = match ($type) {
                'sick' => 'SAKIT',
                'permission' => 'IZIN',
                'leave' => 'CUTI',
                default => strtoupper($type),
            };

            return [
                'key' => $type,
                'label' => $label,
                'badge_class' => 'ui-badge-indigo',
            ];
        }

        // Priority 2: Future Date
        if ($isFuture) {
            return [
                'key' => 'scheduled',
                'label' => 'TERJADWAL',
                'badge_class' => 'ui-badge-slate',
            ];
        }

        // Priority 3: Today
        if ($isToday) {
            $resolved = $this->statusResolver->resolveEffective($effective, $record, null, $now);
            $statusKey = $resolved['key'];

            $badgeClass = match ($statusKey) {
                'present' => 'ui-badge-emerald',
                'late' => 'ui-badge-amber',
                'pending' => 'ui-badge-rose',
                'absent' => 'ui-badge-rose',
                'not_started' => 'ui-badge-slate',
                default => 'ui-badge-slate',
            };

            return [
                'key' => $statusKey,
                'label' => $resolved['label'],
                'badge_class' => $badgeClass,
            ];
        }

        // Priority 4: Past Date
        if ($record) {
            if ($record->check_in_at !== null || in_array($record->status, ['present', 'late'], true)) {
                if ($record->status === 'late' || (int) $record->late_minutes > 0) {
                    return [
                        'key' => 'late',
                        'label' => 'TERLAMBAT',
                        'badge_class' => 'ui-badge-amber',
                    ];
                }

                return [
                    'key' => 'present',
                    'label' => 'HADIR',
                    'badge_class' => 'ui-badge-emerald',
                ];
            }

            if (in_array($record->status, ['sick', 'permission', 'leave'], true)) {
                $type = strtolower($record->status);

                return [
                    'key' => $type,
                    'label' => match ($type) {
                        'sick' => 'SAKIT',
                        'permission' => 'IZIN',
                        'leave' => 'CUTI',
                        default => strtoupper($type),
                    },
                    'badge_class' => 'ui-badge-indigo',
                ];
            }
        }

        // Past date with no attendance and no leave -> TIDAK HADIR
        return [
            'key' => 'absent',
            'label' => 'TIDAK HADIR',
            'badge_class' => 'ui-badge-rose',
        ];
    }

    /**
     * Get authorized active operational outlets for the current user.
     *
     * @return Collection<int, Outlet>
     */
    protected function getAuthorizedActiveOutlets(User $actor): Collection
    {
        if ($this->outletScopeService->isGlobalScope($actor)) {
            return Outlet::query()->where('is_active', true)->orderBy('name')->get();
        }

        if ($actor->role === 'admin') {
            return $this->outletScopeService->getAuthorizedActiveOutlets($actor);
        }

        return collect();
    }

    /**
     * Normalize and validate date string format Y-m-d.
     */
    protected function resolveDateString(?string $dateInput, string $defaultDate, string $timezone): string
    {
        if (empty($dateInput)) {
            return $defaultDate;
        }

        try {
            return Carbon::parse($dateInput, $timezone)->format('Y-m-d');
        } catch (\Throwable) {
            return $defaultDate;
        }
    }
}
