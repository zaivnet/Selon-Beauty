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
        protected AttendanceMonitoringService $monitoringService
    ) {}

    public function index(Request $request): View
    {
        $now = Carbon::now(config('app.timezone'));
        $todayStr = $now->toDateString();
        $includeBackupHealth = in_array($request->user()->role, ['owner', 'superadmin'], true);

        $exceptions = $this->exceptionService->generate($todayStr, [
            'include_backup_health' => $includeBackupHealth,
        ], $now);

        $metrics = $this->monitoringService->getSummaryMetrics($todayStr);
        $attendanceItems = $this->monitoringService->getAttendanceMonitoringList(['date' => $todayStr], $now);
        $trendData = $this->monitoringService->getPastWeekTrendData();
        $shifts = Shift::orderBy('name', 'asc')->get();

        return view('admin.dashboard', [
            'exceptions' => $exceptions,
            'metrics' => $metrics,
            'attendanceItems' => $attendanceItems,
            'trendData' => $trendData,
            'shifts' => $shifts,
            'todayFormatted' => $now->locale('id')->isoFormat('dddd, D MMMM YYYY'),
        ]);
    }
}
