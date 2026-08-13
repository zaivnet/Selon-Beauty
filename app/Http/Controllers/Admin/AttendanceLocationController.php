<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceLocationRequest;
use App\Http\Requests\Admin\UpdateAttendanceLocationRequest;
use App\Models\AttendanceLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AttendanceLocationController extends Controller
{
    public function store(StoreAttendanceLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        DB::transaction(function () use ($data) {
            if ($data['is_active']) {
                // Deactivate other locations if multi-location primary toggle is used
                AttendanceLocation::query()->update(['is_active' => false]);
            }

            AttendanceLocation::create($data);
        });

        return redirect()->route('admin.settings.attendance')
            ->with('success', 'Lokasi absensi toko berhasil ditambahkan.');
    }

    public function update(UpdateAttendanceLocationRequest $request, AttendanceLocation $location): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $location->is_active);

        DB::transaction(function () use ($location, $data) {
            if (! empty($data['is_active'])) {
                AttendanceLocation::where('id', '!=', $location->id)->update(['is_active' => false]);
            }

            $location->update($data);
        });

        return redirect()->route('admin.settings.attendance')
            ->with('success', "Lokasi absensi {$location->name} berhasil diperbarui.");
    }

    public function toggleStatus(AttendanceLocation $location): RedirectResponse
    {
        $newStatus = ! $location->is_active;

        DB::transaction(function () use ($location, $newStatus) {
            if ($newStatus) {
                AttendanceLocation::where('id', '!=', $location->id)->update(['is_active' => false]);
            }

            $location->update(['is_active' => $newStatus]);
        });

        $statusText = $newStatus ? 'diaktifkan sebagai lokasi utama' : 'dinonaktifkan';

        return redirect()->route('admin.settings.attendance')
            ->with('success', "Lokasi {$location->name} berhasil {$statusText}.");
    }

    public function destroy(AttendanceLocation $location): RedirectResponse
    {
        $name = $location->name;
        $location->delete();

        return redirect()->route('admin.settings.attendance')
            ->with('success', "Lokasi absensi {$name} berhasil dihapus.");
    }
}
