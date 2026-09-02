<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OutletModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceSettingController extends Controller
{
    public function __construct(
        protected OutletModeService $outletModeService,
    ) {}

    public function index(Request $request): View
    {
        // Fetch settings from app_settings
        $settingsRaw = DB::table('app_settings')->get()->keyBy('key');

        $settings = [
            'timezone' => $settingsRaw->get('timezone')?->value ?? 'Asia/Jakarta',
            'require_checkout_geofence' => ($settingsRaw->get('attendance_require_checkout_geofence')?->value ?? '1') === '1',
            'require_selfie' => ($settingsRaw->get('attendance_require_selfie')?->value ?? '1') === '1',
            'outlet_mode' => $this->outletModeService->getMode(),
        ];

        return view('admin.settings.attendance', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'timezone' => ['required', 'string', 'max:50'],
            'require_checkout_geofence' => ['nullable', 'boolean'],
            'require_selfie' => ['nullable', 'boolean'],
            'outlet_mode' => ['nullable', 'in:single,multi'],
        ]);

        $actor = $request->user();
        $timezone = $request->input('timezone', 'Asia/Jakarta');
        $requireCheckout = $request->boolean('require_checkout_geofence') ? '1' : '0';
        $requireSelfie = $request->boolean('require_selfie') ? '1' : '0';

        // Manage Outlet Mode update (Restricted to Superadmin / Owner)
        if ($request->filled('outlet_mode') && in_array($actor?->role, ['superadmin', 'owner'], true)) {
            $requestedMode = $request->input('outlet_mode');
            try {
                $this->outletModeService->setMode($requestedMode, $actor);
            } catch (\InvalidArgumentException $e) {
                return redirect()->route('admin.settings.attendance')
                    ->withInput()
                    ->with('error', $e->getMessage());
            }
        }

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'timezone'],
            ['value' => $timezone, 'type' => 'string', 'is_public' => true, 'updated_at' => now()]
        );

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'attendance_require_checkout_geofence'],
            ['value' => $requireCheckout, 'type' => 'boolean', 'is_public' => true, 'updated_at' => now()]
        );

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'attendance_require_selfie'],
            ['value' => $requireSelfie, 'type' => 'boolean', 'is_public' => true, 'updated_at' => now()]
        );

        return redirect()->route('admin.settings.attendance')
            ->with('success', 'Pengaturan absensi berhasil disimpan.');
    }
}
