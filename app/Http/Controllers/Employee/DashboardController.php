<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(protected AttendanceService $attendanceService) {}

    public function index(): View
    {
        $user = Auth::user();
        $employee = $user->employee;

        $now = Carbon::now('Asia/Jakarta');
        $todayStr = $now->toDateString();
        $todayFormatted = $now->locale('id')->isoFormat('dddd, D MMMM YYYY');

        $todaySchedule = null;
        $todayAttendance = null;
        $todayLeave = null;
        $activeLocation = AttendanceLocation::where('is_active', true)->first();

        if ($employee) {
            $todayLeave = LeaveRequest::where('employee_id', $employee->id)
                ->whereDate('start_date', '<=', $todayStr)
                ->whereDate('end_date', '>=', $todayStr)
                ->where('status', 'approved')
                ->first();

            $todaySchedule = $this->attendanceService->resolveActiveSchedule($employee);

            if ($todaySchedule) {
                $todayAttendance = AttendanceRecord::where('employee_id', $employee->id)
                    ->where('work_schedule_id', $todaySchedule->id)
                    ->first();
            }
        }

        return view('employee.dashboard', [
            'user' => $user,
            'employee' => $employee,
            'today' => $todayFormatted,
            'todaySchedule' => $todaySchedule,
            'todayAttendance' => $todayAttendance,
            'todayLeave' => $todayLeave,
            'activeLocation' => $activeLocation,
        ]);
    }
}
