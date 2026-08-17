<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\OutletScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use App\Services\GeofenceService;

class OutletController extends Controller
{
    public function __construct(
        protected OutletScopeService $outletScopeService,
        protected GeofenceService $geofenceService,
    ) {
    }

    public function index(Request $request)
    {
        $this->ensureGlobalScope($request);

        $outlets = Outlet::withCount([
            'employees' => fn ($q) => $q->where('status', 'active'),
            'users' => fn ($q) => $q->where('role', 'admin')->where('is_active', true),
        ])
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $testResult = null;
        if ($request->filled(['test_outlet_id', 'test_lat', 'test_lon'])) {
            $selectedOutlet = $outlets->firstWhere('id', (int) $request->input('test_outlet_id'));
            if ($selectedOutlet) {
                $testLat = (float) $request->input('test_lat');
                $testLon = (float) $request->input('test_lon');
                $testAccuracy = (float) $request->input('test_accuracy', 15.0);

                $testResult = $this->geofenceService->evaluateGeofence($testLat, $testLon, $testAccuracy, $selectedOutlet);
                $testResult['outlet'] = $selectedOutlet;
                $testResult['test_lat'] = $testLat;
                $testResult['test_lon'] = $testLon;
                $testResult['test_accuracy'] = $testAccuracy;
            }
        }

        return view('admin.outlets.index', compact('outlets', 'testResult'));
    }

    public function create(Request $request)
    {
        $this->ensureGlobalScope($request);

        return view('admin.outlets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureGlobalScope($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'uppercase', 'unique:outlets,code'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:1', 'max:50000'],
            'max_accuracy_meters' => ['nullable', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama outlet wajib diisi.',
            'code.required' => 'Kode outlet wajib diisi.',
            'code.unique' => 'Kode outlet tersebut sudah digunakan.',
            'latitude.required' => 'Latitude GPS wajib diisi.',
            'longitude.required' => 'Longitude GPS wajib diisi.',
            'radius_meters.required' => 'Radius absensi wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['max_accuracy_meters'] = $validated['max_accuracy_meters'] ?? 100;
        $validated['is_active'] = $request->boolean('is_active', true);

        $outlet = Outlet::create($validated);

        return redirect()->route('admin.outlets.index')
            ->with('success', "Outlet {$outlet->name} ({$outlet->code}) berhasil ditambahkan.");
    }

    public function edit(Request $request, Outlet $outlet)
    {
        $this->ensureGlobalScope($request);

        return view('admin.outlets.edit', compact('outlet'));
    }

    public function update(Request $request, Outlet $outlet): RedirectResponse
    {
        $this->ensureGlobalScope($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'uppercase', 'unique:outlets,code,'.$outlet->id],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:1', 'max:50000'],
            'max_accuracy_meters' => ['nullable', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama outlet wajib diisi.',
            'code.required' => 'Kode outlet wajib diisi.',
            'code.unique' => 'Kode outlet tersebut sudah digunakan.',
            'latitude.required' => 'Latitude GPS wajib diisi.',
            'longitude.required' => 'Longitude GPS wajib diisi.',
            'radius_meters.required' => 'Radius absensi wajib diisi.',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['max_accuracy_meters'] = $validated['max_accuracy_meters'] ?? 100;
        $validated['is_active'] = $request->boolean('is_active', $outlet->is_active);

        $outlet->update($validated);

        return redirect()->route('admin.outlets.index')
            ->with('success', "Data outlet {$outlet->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, Outlet $outlet): RedirectResponse
    {
        $this->ensureGlobalScope($request);

        $newStatus = ! $outlet->is_active;

        if (! $newStatus) {
            $activeEmployeesCount = $outlet->employees()->where('status', 'active')->count();
            $activeAdminsCount = $outlet->users()->where('role', 'admin')->where('is_active', true)->count();
            if ($activeEmployeesCount > 0 || $activeAdminsCount > 0) {
                return redirect()->back()->with('error', "Outlet {$outlet->name} masih memiliki {$activeEmployeesCount} Karyawan dan {$activeAdminsCount} Admin aktif. Pindahkan pengguna ke outlet aktif lain sebelum menonaktifkan outlet ini.");
            }
        }

        $outlet->update(['is_active' => $newStatus]);
        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Outlet {$outlet->name} berhasil {$statusText}.");
    }

    public function destroy(Request $request, Outlet $outlet): RedirectResponse
    {
        $this->ensureGlobalScope($request);

        $activeEmployeesCount = $outlet->employees()->where('status', 'active')->count();
        if ($activeEmployeesCount > 0) {
            return redirect()->back()->with('error', "Outlet {$outlet->name} tidak dapat dihapus karena masih memiliki {$activeEmployeesCount} karyawan aktif.");
        }

        $name = $outlet->name;
        $outlet->delete();

        return redirect()->route('admin.outlets.index')
            ->with('success', "Outlet {$name} berhasil dihapus (soft delete).");
    }

    protected function ensureGlobalScope(Request $request): void
    {
        if (! $this->outletScopeService->isGlobalScope($request->user())) {
            abort(403, 'Akses ditolak. Pengelolaan outlet hanya dapat dilakukan oleh Owner atau Administrator.');
        }
    }
}
