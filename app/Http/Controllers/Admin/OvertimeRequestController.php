<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSession;
use App\Services\OutletScopeService;
use App\Services\OvertimeRequestService;
use App\Services\OvertimeSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeRequestController extends Controller
{
    public function __construct(
        protected OvertimeRequestService $overtimeService,
        protected OvertimeSessionService $sessionService,
        protected OutletScopeService $outletScopeService,
    ) {}

    public function index(Request $request): View
    {
        $statusFilter = $request->input('status', 'pending');
        $employeeIdFilter = $request->input('employee_id');
        $startDateFilter = $request->input('start_date');
        $endDateFilter = $request->input('end_date');

        $query = OvertimeRequest::with(['employee.jobTitle', 'reviewer', 'session'])
            ->orderBy('created_at', 'desc');

        $query = $this->outletScopeService->scopeQueryFor($request->user(), $query);

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($employeeIdFilter) {
            $query->where('employee_id', $employeeIdFilter);
        }

        if ($startDateFilter) {
            $query->where('work_date', '>=', $startDateFilter);
        }

        if ($endDateFilter) {
            $query->where('work_date', '<=', $endDateFilter);
        }

        $requests = $query->paginate(15)->withQueryString();

        // Collect pairs of (employee_id, work_date) to fetch attendance & schedule context
        $pairs = $requests->map(fn ($r) => [
            'employee_id' => $r->employee_id,
            'work_date' => $r->work_date->format('Y-m-d'),
        ]);

        $employeeIds = $requests->pluck('employee_id')->unique()->toArray();
        $workDates = $requests->pluck('work_date')->map(fn ($d) => $d->format('Y-m-d'))->unique()->toArray();

        $attendances = AttendanceRecord::whereIn('employee_id', $employeeIds)
            ->whereIn('work_date', $workDates)
            ->get()
            ->keyBy(fn ($a) => $a->employee_id.'_'.$a->work_date->format('Y-m-d'));

        $schedules = EmployeeSchedule::whereIn('employee_id', $employeeIds)
            ->whereIn('work_date', $workDates)
            ->with('shift')
            ->get()
            ->keyBy(fn ($s) => $s->employee_id.'_'.$s->work_date->format('Y-m-d'));

        $employeesQuery = Employee::whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();
        $employeesQuery = $this->outletScopeService->scopeEmployeesFor($request->user(), $employeesQuery);

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();

        $filters = [
            'status' => $statusFilter,
            'employee_id' => $employeeIdFilter,
            'start_date' => $startDateFilter,
            'end_date' => $endDateFilter,
        ];

        return view('admin.overtime_requests.index', [
            'requests' => $requests,
            'employees' => $employees,
            'attendances' => $attendances,
            'schedules' => $schedules,
            'filters' => $filters,
        ]);
    }

    public function approve(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $user = Auth::user();
        $this->outletScopeService->ensureCanManageOvertime($user, $overtimeRequest);
        $validated = $request->validate([
            'approved_minutes' => ['required', 'integer', 'min:0'],
            'reviewer_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'approved_minutes.required' => 'Durasi lembur disetujui wajib diisi.',
            'approved_minutes.min' => 'Durasi lembur disetujui tidak boleh negatif.',
        ]);

        $this->overtimeService->approveRequest(
            $overtimeRequest,
            $user,
            (int) $validated['approved_minutes'],
            $request->input('reviewer_note')
        );

        return redirect()->back()
            ->with('success', 'Pengajuan lembur berhasil disetujui.');
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $user = Auth::user();
        $this->outletScopeService->ensureCanManageOvertime($user, $overtimeRequest);
        $request->validate([
            'reviewer_note' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reviewer_note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $this->overtimeService->rejectRequest($overtimeRequest, $user, $request->input('reviewer_note'));

        return redirect()->back()
            ->with('success', 'Pengajuan lembur telah ditolak.');
    }

    public function forceFinish(Request $request, OvertimeSession $overtimeSession): RedirectResponse
    {
        $this->outletScopeService->ensureCanManageOvertimeSession($request->user(), $overtimeSession);
        $validated = $request->validate([
            'finish_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        $this->sessionService->forceFinish($request->user(), $overtimeSession, $validated['finish_at'], $validated['reason']);

        return back()->with('success', 'Sesi lembur berhasil diselesaikan oleh admin.');
    }

    public function cancelSession(Request $request, OvertimeSession $overtimeSession): RedirectResponse
    {
        $this->outletScopeService->ensureCanManageOvertimeSession($request->user(), $overtimeSession);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        $this->sessionService->cancel($request->user(), $overtimeSession, $validated['reason']);

        return back()->with('success', 'Sesi lembur dibatalkan tanpa menghapus histori.');
    }

    public function correctSession(Request $request, OvertimeSession $overtimeSession): RedirectResponse
    {
        $this->outletScopeService->ensureCanManageOvertimeSession($request->user(), $overtimeSession);
        $validated = $request->validate([
            'check_in_at' => ['required', 'date'],
            'check_out_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        $this->sessionService->correctCompleted(
            $request->user(),
            $overtimeSession,
            $validated['check_in_at'],
            $validated['check_out_at'],
            $validated['reason'],
        );

        return back()->with('success', 'Sesi lembur berhasil dikoreksi.');
    }
}
