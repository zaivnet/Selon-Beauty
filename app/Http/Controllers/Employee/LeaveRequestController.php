<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveRequestController extends Controller
{
    public function __construct(protected LeaveRequestService $leaveService) {}

    public function index(): View|RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Profil karyawan belum terhubung.');
        }

        $requests = LeaveRequest::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('employee.leave_requests.index', [
            'employee' => $employee,
            'requests' => $requests,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Profil karyawan tidak ditemukan.');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:permission,sick,leave'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ]);

        $this->leaveService->submitRequest($employee, $validated, $request->file('attachment'));

        return redirect()->route('employee.leave-requests.index')
            ->with('success', 'Pengajuan berhasil dikirim.');
    }

    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = Auth::user();

        if ($leaveRequest->employee_id !== $user->employee_id) {
            abort(403, 'Akses ditolak. Pengajuan ini bukan milik Anda.');
        }

        $this->leaveService->cancelRequest($leaveRequest, $user);

        return redirect()->route('employee.leave-requests.index')
            ->with('success', 'Pengajuan berhasil dibatalkan.');
    }
}
