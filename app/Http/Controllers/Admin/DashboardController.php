<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceMonitoringService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(protected AttendanceMonitoringService $monitoringService) {}

    public function index(): View
    {
        $todayStr = Carbon::now('Asia/Jakarta')->toDateString();

        $metrics = $this->monitoringService->getSummaryMetrics($todayStr);
        $attendanceItems = $this->monitoringService->getAttendanceMonitoringList([
            'date' => $todayStr,
        ]);
        $trendData = $this->monitoringService->getPastWeekTrendData();

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'attendanceItems' => $attendanceItems,
            'trendData' => $trendData,
            'todayFormatted' => Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM YYYY'),
        ]);
    }
}
