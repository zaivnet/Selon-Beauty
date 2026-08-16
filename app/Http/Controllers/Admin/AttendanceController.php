<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\AttendanceMonitoringService;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected AttendanceMonitoringService $monitoringService) {}

    public function index(Request $request): View
    {
        $todayDefault = Carbon::now(config('app.timezone'))->toDateString();
        $selectedDate = $request->input('date', $todayDefault);
        $selectedEmployeeId = $request->input('employee_id');
        $selectedStatus = $request->input('status');

        $filters = [
            'date' => $selectedDate,
            'employee_id' => $selectedEmployeeId,
            'status' => $selectedStatus,
        ];

        $actor = $request->user();
        $metrics = $this->monitoringService->getSummaryMetrics($selectedDate, $actor);
        $attendanceItems = $this->monitoringService->getAttendanceMonitoringList($filters, null, $actor);

        $employeesQuery = Employee::whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();

        if ($actor->role === 'admin') {
            $adminOutletId = $actor->outlet_id ?? $actor->employee?->outlet_id;
            $employeesQuery->where('outlet_id', $adminOutletId);
        }

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();

        return view('admin.attendance.index', [
            'metrics' => $metrics,
            'attendanceItems' => $attendanceItems,
            'employees' => $employees,
            'filters' => $filters,
            'selectedDateFormatted' => Carbon::parse($selectedDate, config('app.timezone'))->locale('id')->isoFormat('dddd, D MMMM YYYY'),
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
                'correction_url' => route('admin.attendance.correct', $attendance),
                'check_in_local' => $attendance->check_in_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
                'check_out_local' => $attendance->check_out_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            ]);
        }

        return view('admin.attendance.show', [
            'attendance' => $attendance,
        ]);
    }

    public function correct(Request $request, AttendanceRecord $attendance, AttendanceService $attendanceService)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
            'check_in_at' => 'nullable|string',
            'check_out_at' => 'nullable|string',
            'internal_note' => 'nullable|string|max:2000',
        ], [
            'reason.required' => 'Alasan koreksi wajib diisi.',
            'reason.min' => 'Alasan koreksi minimal 5 karakter.',
        ]);

        try {
            $attendanceService->correctAttendanceRecord(
                record: $attendance,
                checkInStr: $request->exists('check_in_at')
                    ? $request->input('check_in_at')
                    : $attendance->check_in_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
                checkOutStr: $request->exists('check_out_at')
                    ? $request->input('check_out_at')
                    : $attendance->check_out_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
                reason: $request->input('reason'),
                actor: $request->user(),
                internalNote: $request->input('internal_note'),
            );

            return redirect()->back()->with('success', 'Presensi berhasil dikoreksi secara manual oleh Admin/Owner.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
