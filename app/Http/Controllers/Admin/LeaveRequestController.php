<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use App\Services\OutletScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveService,
        protected OutletScopeService $outletScopeService
    ) {}

    public function index(Request $request): View
    {
        $statusFilter = $request->input('status', 'pending');
        $typeFilter = $request->input('type');
        $employeeIdFilter = $request->input('employee_id');
        $startDateFilter = $request->input('start_date');
        $endDateFilter = $request->input('end_date');

        $query = LeaveRequest::with(['employee.jobTitle', 'reviewer'])
            ->orderBy('created_at', 'desc');

        $inputOutletId = $request->has('outlet_id') ? (int) $request->input('outlet_id') : null;
        $query = $this->outletScopeService->scopeByRequestedOutlet($request->user(), $query, $inputOutletId);

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter && $typeFilter !== 'all') {
            $query->where('type', $typeFilter);
        }

        if ($employeeIdFilter) {
            $query->where('employee_id', $employeeIdFilter);
        }

        if ($startDateFilter) {
            $query->where('start_date', '>=', $startDateFilter);
        }

        if ($endDateFilter) {
            $query->where('end_date', '<=', $endDateFilter);
        }

        $requests = $query->paginate(15)->withQueryString();

        $employeesQuery = Employee::whereNull('deleted_at')
            ->where('status', 'active')
            ->currentAttendanceWorkforce();
        $employeesQuery = $this->outletScopeService->scopeByRequestedOutlet($request->user(), $employeesQuery, $inputOutletId);

        $employees = $employeesQuery->orderBy('full_name', 'asc')->get();

        $filters = [
            'status' => $statusFilter,
            'type' => $typeFilter,
            'employee_id' => $employeeIdFilter,
            'start_date' => $startDateFilter,
            'end_date' => $endDateFilter,
        ];

        return view('admin.leave_requests.index', [
            'requests' => $requests,
            'employees' => $employees,
            'filters' => $filters,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = Auth::user();
        $this->outletScopeService->ensureCanManageLeave($user, $leaveRequest);
        $this->leaveService->approveRequest($leaveRequest, $user, $request->input('reviewer_note'));

        return redirect()->back()
            ->with('success', 'Pengajuan izin/cuti berhasil disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = Auth::user();
        $this->outletScopeService->ensureCanManageLeave($user, $leaveRequest);
        $request->validate([
            'reviewer_note' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reviewer_note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $this->leaveService->rejectRequest($leaveRequest, $user, $request->input('reviewer_note'));

        return redirect()->back()
            ->with('success', 'Pengajuan izin/cuti telah ditolak.');
    }
}
