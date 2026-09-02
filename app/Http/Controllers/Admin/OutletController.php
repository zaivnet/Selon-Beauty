<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OutletAccessMode;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\User;
use App\Services\GeofenceService;
use App\Services\OutletModeService;
use App\Services\OutletScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function __construct(
        protected OutletScopeService $outletScopeService,
        protected GeofenceService $geofenceService,
        protected OutletModeService $outletModeService,
    ) {}

    public function index(Request $request)
    {
        $actor = $request->user();

        if ($this->outletScopeService->isGlobalScope($actor)) {
            $query = Outlet::query();
        } else {
            $allowedIds = $this->outletScopeService->allowedOutletIds($actor);
            $query = Outlet::query()->whereIn('id', $allowedIds)->where('is_active', true);
        }

        $outlets = $query->withCount([
            'employees' => fn ($q) => $q->where('status', 'active'),
            'assignedAdmins as users_count' => fn ($q) => $q->where('role', 'admin')->where('is_active', true),
        ])
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get();
        $allOutletAdminCount = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->where('outlet_access_mode', OutletAccessMode::ALL->value)
            ->count();
        $outlets->each(fn (Outlet $outlet) => $outlet->setAttribute('users_count', $outlet->users_count + $allOutletAdminCount));

        $testResult = null;
        if ($request->hasAny(['test_outlet_id', 'test_lat', 'test_lon', 'test_accuracy'])) {
            $validated = $request->validate([
                'test_outlet_id' => ['required', 'integer'],
                'test_lat' => ['required', 'numeric', 'between:-90,90'],
                'test_lon' => ['required', 'numeric', 'between:-180,180'],
                'test_accuracy' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            ], [
                'test_outlet_id.required' => 'Pilih outlet yang akan diuji.',
                'test_lat.required' => 'Latitude posisi karyawan wajib diisi.',
                'test_lat.numeric' => 'Latitude posisi karyawan harus berupa angka desimal.',
                'test_lat.between' => 'Latitude posisi karyawan harus bernilai antara -90 dan 90.',
                'test_lon.required' => 'Longitude posisi karyawan wajib diisi.',
                'test_lon.numeric' => 'Longitude posisi karyawan harus berupa angka desimal.',
                'test_lon.between' => 'Longitude posisi karyawan harus bernilai antara -180 dan 180.',
                'test_accuracy.numeric' => 'Akurasi GPS harus berupa angka.',
                'test_accuracy.min' => 'Akurasi GPS tidak boleh bernilai negatif.',
            ]);

            $selectedOutlet = $outlets->firstWhere('id', (int) $validated['test_outlet_id']);
            if ($selectedOutlet) {
                $testLat = (float) $validated['test_lat'];
                $testLon = (float) $validated['test_lon'];
                $testAccuracy = (float) ($validated['test_accuracy'] ?? 15.0);

                $testResult = $this->geofenceService->evaluateGeofence($testLat, $testLon, $testAccuracy, $selectedOutlet);
                $testResult['outlet'] = $selectedOutlet;
                $testResult['test_lat'] = $testLat;
                $testResult['test_lon'] = $testLon;
                $testResult['test_accuracy'] = $testAccuracy;
            } else {
                return redirect()->route('admin.outlets.index')
                    ->with('error', 'Outlet yang dipilih tidak ditemukan atau berada di luar cakupan izin Anda.');
            }
        }

        return view('admin.outlets.index', compact('outlets', 'testResult'));
    }

    public function create(Request $request)
    {
        $this->ensureGlobalScope($request);

        if ($this->outletModeService->isSingleOutlet()) {
            return redirect()->route('admin.outlets.index')
                ->with('error', 'Aplikasi berada dalam Mode Single Outlet. Tidak dapat menambah outlet baru.');
        }

        return view('admin.outlets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureGlobalScope($request);

        if ($this->outletModeService->isSingleOutlet()) {
            return redirect()->route('admin.outlets.index')
                ->with('error', 'Aplikasi berada dalam Mode Single Outlet. Tidak dapat menambah outlet baru.');
        }

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
        $actor = $request->user();

        if ($outlet->trashed()) {
            abort(403, 'Akses ditolak. Outlet ini sudah dihapus.');
        }

        $this->outletScopeService->ensureCanAccessOutlet($actor, $outlet);

        return view('admin.outlets.edit', compact('outlet'));
    }

    public function update(Request $request, Outlet $outlet): RedirectResponse
    {
        $actor = $request->user();

        if ($outlet->trashed()) {
            abort(403, 'Akses ditolak. Outlet ini sudah dihapus.');
        }

        $this->outletScopeService->ensureCanAccessOutlet($actor, $outlet);

        $isGlobal = $this->outletScopeService->isGlobalScope($actor);

        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:1', 'max:50000'],
            'max_accuracy_meters' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];

        if ($isGlobal) {
            $rules['code'] = ['required', 'string', 'max:50', 'uppercase', 'unique:outlets,code,'.$outlet->id];
            $rules['is_active'] = ['nullable', 'boolean'];
        }

        $messages = [
            'name.required' => 'Nama outlet wajib diisi.',
            'code.required' => 'Kode outlet wajib diisi.',
            'code.unique' => 'Kode outlet tersebut sudah digunakan.',
            'latitude.required' => 'Latitude GPS wajib diisi.',
            'longitude.required' => 'Longitude GPS wajib diisi.',
            'radius_meters.required' => 'Radius absensi wajib diisi.',
        ];

        $validated = $request->validate($rules, $messages);

        if ($isGlobal) {
            $validated['code'] = strtoupper(trim($validated['code']));
            $validated['is_active'] = $request->boolean('is_active', $outlet->is_active);
        } else {
            unset($validated['code'], $validated['is_active']);
        }

        $validated['max_accuracy_meters'] = $validated['max_accuracy_meters'] ?? 100;

        $outlet->update($validated);

        \App\Models\AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'outlet.updated',
            'description' => "Memperbarui konfigurasi operasional outlet {$outlet->name} ({$outlet->code})",
            'metadata' => [
                'outlet_id' => $outlet->id,
                'changed_fields' => array_keys($validated),
            ],
        ]);

        return redirect()->route('admin.outlets.index')
            ->with('success', "Data outlet {$outlet->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, Outlet $outlet): RedirectResponse
    {
        $this->ensureGlobalScope($request);

        if ($this->outletModeService->isSingleOutlet()) {
            return redirect()->back()
                ->with('error', 'Aplikasi berada dalam Mode Single Outlet. Status operasional outlet tunggal tidak dapat dinonaktifkan.');
        }

        $newStatus = ! $outlet->is_active;

        if (! $newStatus) {
            $activeEmployeesCount = $outlet->employees()->where('status', 'active')->count();
            $activeAdminsCount = $outlet->assignedAdmins()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->count()
                + User::query()
                    ->where('role', 'admin')
                    ->where('is_active', true)
                    ->where('outlet_access_mode', OutletAccessMode::ALL->value)
                    ->count();
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

        if ($this->outletModeService->isSingleOutlet()) {
            return redirect()->back()
                ->with('error', 'Aplikasi berada dalam Mode Single Outlet. Outlet operasional utama tidak dapat dihapus.');
        }

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
