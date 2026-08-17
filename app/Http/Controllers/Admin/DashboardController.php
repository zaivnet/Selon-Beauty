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
        $actor = $request->user();
        $now = Carbon::now(config('app.timezone'));
        $todayStr = $now->toDateString();
        $includeBackupHealth = in_array($actor->role, ['owner', 'superadmin'], true);

        $exceptions = $this->exceptionService->generate($todayStr, [
            'include_backup_health' => $includeBackupHealth,
            'actor' => $actor,
        ], $now);

        $metrics = $this->monitoringService->getSummaryMetrics($todayStr, $actor);
        $attendanceItems = $this->monitoringService->getAttendanceMonitoringList(['date' => $todayStr], $now, $actor);
        $trendData = $this->monitoringService->getPastWeekTrendData($actor);
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
