<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ShiftSwapRequest;
use App\Services\EffectiveScheduleService;
use App\Services\ShiftSwapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftSwapController extends Controller
{
    public function __construct(
        protected ShiftSwapService $swapService,
        protected EffectiveScheduleService $effectiveService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status', 'pending_admin')->toString();
        $employeeId = $request->integer('employee_id');
        $date = $request->string('date')->toString();

        $query = ShiftSwapRequest::with([
            'requester', 'target', 'requesterShift', 'targetShift', 'adminUser',
        ]);

        if ($status !== 'all') {
            if ($status === 'pending') {
                $query->whereIn('status', [ShiftSwapRequest::STATUS_PENDING_TARGET, ShiftSwapRequest::STATUS_PENDING_ADMIN]);
            } elseif ($status === 'rejected') {
                $query->whereIn('status', [ShiftSwapRequest::STATUS_REJECTED_BY_TARGET, ShiftSwapRequest::STATUS_REJECTED_BY_ADMIN]);
            } else {
                $query->where('status', $status);
            }
        }

        if ($employeeId > 0) {
            $query->where(fn ($q) => $q->where('requester_employee_id', $employeeId)->orWhere('target_employee_id', $employeeId));
        }

        if ($date !== '') {
            $query->where(fn ($q) => $q->whereDate('requester_work_date', $date)->orWhereDate('target_work_date', $date));
        }

        $swaps = $query->latest()->paginate(20)->withQueryString();

        $pendingAdminCount = ShiftSwapRequest::where('status', ShiftSwapRequest::STATUS_PENDING_ADMIN)->count();

        return view('admin.shift_swaps.index', [
            'swaps' => $swaps,
            'statusFilter' => $status,
            'employeeFilter' => $employeeId,
            'dateFilter' => $date,
            'employees' => Employee::orderBy('full_name')->get(),
            'pendingAdminCount' => $pendingAdminCount,
        ]);
    }

    public function show(ShiftSwapRequest $swap): View
    {
        $swap->load(['requester', 'target', 'requesterShift', 'targetShift', 'adminUser']);

        $reqCurrentEffective = $this->effectiveService->resolve($swap->requester, $swap->requester_work_date->format('Y-m-d'));
        $targetCurrentEffective = $this->effectiveService->resolve($swap->target, $swap->target_work_date->format('Y-m-d'));

        return view('admin.shift_swaps.show', [
            'swap' => $swap,
            'reqCurrentEffective' => $reqCurrentEffective,
            'targetCurrentEffective' => $targetCurrentEffective,
        ]);
    }

    public function approve(Request $request, ShiftSwapRequest $swap): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $this->swapService->respondByAdmin($swap, $request->user(), 'approve', $validated['reason'] ?? null);

            return redirect()->route('admin.shift-swaps.index')
                ->with('success', 'Permintaan tukar jadwal berhasil disetujui dan jadwal efektif kedua karyawan telah ditukar secara otomatis.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, ShiftSwapRequest $swap): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5'],
        ]);

        try {
            $this->swapService->respondByAdmin($swap, $request->user(), 'reject', $validated['reason']);

            return redirect()->route('admin.shift-swaps.index')
                ->with('success', 'Permintaan tukar jadwal telah ditolak.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
