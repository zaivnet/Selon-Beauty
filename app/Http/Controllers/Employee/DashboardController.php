<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(protected AttendanceService $attendanceService) {}

    public function index(Request $request): View
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
        $todayOvertime = null;
        $correctedAttendance = null;

        if ($employee) {
            if ($request->filled('attendance')) {
                $correctedAttendance = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereKey($request->integer('attendance'))
                    ->first();
            }
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

            $todayOvertime = OvertimeRequest::with('session')
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where(function ($query) use ($todayStr) {
                    $query->whereDate('work_date', $todayStr)
                        ->orWhereHas('session', fn ($session) => $session->where('status', 'active'));
                })
                ->first();
        }

        return view('employee.dashboard', [
            'user' => $user,
            'employee' => $employee,
            'today' => $todayFormatted,
            'todaySchedule' => $todaySchedule,
            'todayAttendance' => $todayAttendance,
            'todayLeave' => $todayLeave,
            'activeLocation' => $activeLocation,
            'requireSelfie' => (bool) AppSetting::get('attendance_require_selfie', true),
            'todayOvertime' => $todayOvertime,
            'correctedAttendance' => $correctedAttendance,
        ]);
    }
}
