<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\AttendanceMonitoringService;
use App\Services\OperationalExceptionService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected OperationalExceptionService $exceptionService,
        protected AttendanceMonitoringService $monitoringService,
        protected \App\Services\MultiOutletDashboardService $multiOutletDashboardService,
        protected \App\Services\DashboardDutyRosterService $dutyRosterService,
    ) {}

    public function index(Request $request): View
    {
        $actor = $request->user();
        $now = Carbon::now(config('app.timezone'));
        $todayStr = $now->toDateString();
        $includeBackupHealth = in_array($actor->role, ['owner', 'superadmin'], true);

        $outletScopeService = app(\App\Services\OutletScopeService::class);
        $rawInput = $request->input('outlet_id');
        $inputOutletId = $request->has('outlet_id') ? ($rawInput === 'all' || $rawInput === '0' || $rawInput === 0 ? 0 : (int) $rawInput) : null;
        $requestedOutletId = $outletScopeService->resolveRequestedOutlet($actor, $inputOutletId);

        $rosterDate = $request->input('roster_date');
        $rosterOutletId = $request->has('roster_outlet_id')
            ? $request->input('roster_outlet_id')
            : ($request->has('outlet_id') ? $requestedOutletId : null);
        $rosterData = $this->dutyRosterService->getRosterData($actor, $rosterDate, $rosterOutletId);

        $exceptions = $this->exceptionService->generate($todayStr, [
            'include_backup_health' => $includeBackupHealth,
            'actor' => $actor,
            'outlet_id' => $requestedOutletId,
        ], $now);

        $attendanceItems = $this->monitoringService->getAttendanceMonitoringList(['date' => $todayStr], $now, $actor, $requestedOutletId);

        $authorizedOutlets = $outletScopeService->getAuthorizedActiveOutlets($actor);
        $isAllOutletsView = ($requestedOutletId === null && $authorizedOutlets->count() > 1);

        if ($isAllOutletsView) {
            $globalData = $this->multiOutletDashboardService->generateOverview($authorizedOutlets, $attendanceItems, $exceptions);

            return view('admin.dashboard_global', [
                'globalData' => $globalData,
                'exceptions' => $exceptions,
                'todayFormatted' => $now->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                'rosterData' => $rosterData,
            ]);
        }

        $metrics = $this->monitoringService->getSummaryMetrics($todayStr, $actor, $attendanceItems, $requestedOutletId);
        $trendData = $this->monitoringService->getPastWeekTrendData($actor, $requestedOutletId);
        $shifts = Shift::orderBy('name', 'asc')->get();

        return view('admin.dashboard', [
            'exceptions' => $exceptions,
            'metrics' => $metrics,
            'attendanceItems' => $attendanceItems,
            'trendData' => $trendData,
            'shifts' => $shifts,
            'todayFormatted' => $now->locale('id')->isoFormat('dddd, D MMMM YYYY'),
            'requestedOutletId' => $requestedOutletId,
            'rosterData' => $rosterData,
        ]);
    }
}
