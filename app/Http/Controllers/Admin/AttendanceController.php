<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\AttendanceMonitoringService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceMonitoringService $monitoringService) {}

    public function index(Request $request): View
    {
        $todayDefault = Carbon::now('Asia/Jakarta')->toDateString();
        $selectedDate = $request->input('date', $todayDefault);
        $selectedEmployeeId = $request->input('employee_id');
        $selectedStatus = $request->input('status');

        $filters = [
            'date' => $selectedDate,
            'employee_id' => $selectedEmployeeId,
            'status' => $selectedStatus,
        ];

        $metrics = $this->monitoringService->getSummaryMetrics($selectedDate);
        $attendanceItems = $this->monitoringService->getAttendanceMonitoringList($filters);

        $employees = Employee::whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('full_name', 'asc')
            ->get();

        return view('admin.attendance.index', [
            'metrics' => $metrics,
            'attendanceItems' => $attendanceItems,
            'employees' => $employees,
            'filters' => $filters,
            'selectedDateFormatted' => Carbon::parse($selectedDate, 'Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM YYYY'),
        ]);
    }

    public function show(Request $request, AttendanceRecord $attendance): View|JsonResponse
    {
        $attendance->load([
            'employee.jobTitle',
            'schedule.shift',
            'location',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $attendance,
                'check_in_selfie_url' => $attendance->check_in_selfie_path ? route('attendance.selfie', ['record' => $attendance->id, 'type' => 'check_in']) : null,
                'check_out_selfie_url' => $attendance->check_out_selfie_path ? route('attendance.selfie', ['record' => $attendance->id, 'type' => 'check_out']) : null,
            ]);
        }

        return view('admin.attendance.show', [
            'attendance' => $attendance,
        ]);
    }

    public function correct(Request $request, AttendanceRecord $attendance, \App\Services\AttendanceService $attendanceService)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
            'check_in_at' => 'nullable|string',
            'check_out_at' => 'nullable|string',
            'status' => 'nullable|string|in:present,late,absent,permission,sick,leave',
        ], [
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'reason.min' => 'Alasan koreksi minimal 5 karakter.',
        ]);

        try {
            $attendanceService->correctAttendanceRecord(
                record: $attendance,
                checkInStr: $request->input('check_in_at'),
                checkOutStr: $request->input('check_out_at'),
                status: $request->input('status'),
                reason: $request->input('reason'),
                actor: $request->user()
            );

            return redirect()->back()->with('success', 'Presensi berhasil dikoreksi secara manual oleh Admin/Owner.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
