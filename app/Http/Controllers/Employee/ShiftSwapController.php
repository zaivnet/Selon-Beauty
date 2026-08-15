<?php

namespace App\Http\Controllers\Employee;

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
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Profil karyawan tidak ditemukan.');

        $tab = $request->string('tab', 'my_requests')->toString();

        $myRequests = ShiftSwapRequest::with(['target', 'requesterShift', 'targetShift', 'adminUser'])
            ->where('requester_employee_id', $employee->id)
            ->latest()
            ->paginate(15, ['*'], 'my_page');

        $incomingRequests = ShiftSwapRequest::with(['requester', 'requesterShift', 'targetShift', 'adminUser'])
            ->where('target_employee_id', $employee->id)
            ->latest()
            ->paginate(15, ['*'], 'in_page');

        return view('employee.shift_swaps.index', [
            'employee' => $employee,
            'tab' => $tab,
            'myRequests' => $myRequests,
            'incomingRequests' => $incomingRequests,
        ]);
    }

    public function create(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Profil karyawan tidak ditemukan.');

        $reqDate = $request->string('work_date', now(config('app.timezone'))->toDateString())->toString();
        $targetDate = $request->string('target_work_date', $reqDate)->toString();
        $targetId = $request->integer('target_employee_id');

        $eligibleTargets = Employee::where('id', '!=', $employee->id)
            ->where('status', 'active')
            ->where('attendance_enabled', true)
            ->orderBy('full_name')
            ->get();

        $reqEffective = $this->effectiveService->resolve($employee, $reqDate);

        $targetEffective = null;
        $selectedTarget = null;
        if ($targetId > 0) {
            $selectedTarget = Employee::find($targetId);
            if ($selectedTarget) {
                $targetEffective = $this->effectiveService->resolve($selectedTarget, $targetDate);
            }
        }

        return view('employee.shift_swaps.create', [
            'employee' => $employee,
            'eligibleTargets' => $eligibleTargets,
            'reqDate' => $reqDate,
            'targetDate' => $targetDate,
            'targetId' => $targetId,
            'selectedTarget' => $selectedTarget,
            'reqEffective' => $reqEffective,
            'targetEffective' => $targetEffective,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404, 'Profil karyawan tidak ditemukan.');

        $validated = $request->validate([
            'target_employee_id' => ['required', 'integer', 'exists:employees,id'],
            'requester_work_date' => ['required', 'date'],
            'target_work_date' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'min:5'],
        ]);

        try {
            $swap = $this->swapService->submitRequest($employee, $validated);

            return redirect()->route('employee.shift-swaps.index')
                ->with('success', 'Permintaan tukar jadwal berhasil diajukan dan menunggu persetujuan rekan kerja.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function respond(Request $request, ShiftSwapRequest $swap): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:accept,reject'],
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $this->swapService->respondByTarget($swap, $request->user(), $validated['action'], $validated['reason'] ?? null);

            $msg = $validated['action'] === 'accept'
                ? 'Anda menyetujui penukaran jadwal. Permintaan telah diteruskan ke Admin.'
                : 'Permintaan penukaran jadwal berhasil ditolak.';

            return redirect()->route('employee.shift-swaps.index', ['tab' => 'incoming'])->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, ShiftSwapRequest $swap): RedirectResponse
    {
        try {
            $this->swapService->cancelRequest($swap, $request->user());

            return redirect()->route('employee.shift-swaps.index')->with('success', 'Permintaan tukar jadwal berhasil dibatalkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
