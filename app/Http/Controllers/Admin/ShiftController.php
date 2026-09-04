<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShiftRequest;
use App\Http\Requests\Admin\UpdateShiftRequest;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status', '');

        $query = Shift::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $shifts = $query->orderBy('start_time')->paginate(10)->withQueryString();

        return view('admin.shifts.index', compact('shifts', 'search', 'status'));
    }

    public function create(): View
    {
        return view('admin.shifts.create');
    }

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $validated['crosses_midnight'] = ($endTime < $startTime);
        $validated['auto_checkout_enabled'] = $request->boolean('auto_checkout_enabled', true);
        $validated['auto_checkout_grace_minutes'] = (int) ($validated['auto_checkout_grace_minutes'] ?? 10);
        $validated['is_active'] = $request->boolean('is_active', true);

        $shift = Shift::create($validated);

        return redirect()->route('admin.shifts.index')
            ->with('success', "Shift kerja {$shift->name} ({$shift->code}) berhasil ditambahkan.");
    }

    public function show(Shift $shift): View
    {
        return view('admin.shifts.show', compact('shift'));
    }

    public function edit(Shift $shift): View
    {
        return view('admin.shifts.edit', compact('shift'));
    }

    public function update(UpdateShiftRequest $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validated();

        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $validated['crosses_midnight'] = ($endTime < $startTime);
        $validated['auto_checkout_enabled'] = $request->boolean('auto_checkout_enabled', false);
        $validated['auto_checkout_grace_minutes'] = (int) ($validated['auto_checkout_grace_minutes'] ?? 10);
        $validated['is_active'] = $request->boolean('is_active', $shift->is_active);

        $shift->update($validated);

        return redirect()->route('admin.shifts.index')
            ->with('success', "Shift kerja {$shift->name} ({$shift->code}) berhasil diperbarui.");
    }

    public function toggleStatus(Shift $shift): RedirectResponse
    {
        $newStatus = ! $shift->is_active;
        $shift->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Shift {$shift->name} berhasil {$statusText}.");
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $name = $shift->name;

        // Protection: If shift is assigned to schedules, do soft/inactive disable instead of hard delete
        if ($shift->hasSchedules()) {
            $shift->update(['is_active' => false]);

            return redirect()->route('admin.shifts.index')
                ->with('error', "Shift {$name} tidak dapat dihapus karena sudah terhubung ke jadwal karyawan. Status otomatis diubah menjadi Nonaktif.");
        }

        $shift->delete();

        return redirect()->route('admin.shifts.index')
            ->with('success', "Shift kerja {$name} berhasil dihapus.");
    }
}
