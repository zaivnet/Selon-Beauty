<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocation;
use App\Services\GeofenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceSettingController extends Controller
{
    public function __construct(protected GeofenceService $geofenceService) {}

    public function index(Request $request): View
    {
        $locations = AttendanceLocation::orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        $activeLocation = $locations->firstWhere('is_active', true);

        // Fetch settings from app_settings
        $settingsRaw = DB::table('app_settings')->get()->keyBy('key');

        $settings = [
            'timezone' => $settingsRaw->get('timezone')?->value ?? 'Asia/Jakarta',
            'require_checkout_geofence' => ($settingsRaw->get('attendance_require_checkout_geofence')?->value ?? '1') === '1',
            'require_selfie' => ($settingsRaw->get('attendance_require_selfie')?->value ?? '1') === '1',
        ];

        // Calculation test helper parameters if submitted via GET
        $testResult = null;
        if ($request->filled(['test_lat', 'test_lon']) && $activeLocation) {
            $testLat = (float) $request->input('test_lat');
            $testLon = (float) $request->input('test_lon');
            $testAccuracy = (float) $request->input('test_accuracy', 10.0);

            $testResult = $this->geofenceService->evaluateGeofence($testLat, $testLon, $testAccuracy, $activeLocation);
            $testResult['test_lat'] = $testLat;
            $testResult['test_lon'] = $testLon;
            $testResult['test_accuracy'] = $testAccuracy;
        }

        return view('admin.settings.attendance', compact('locations', 'activeLocation', 'settings', 'testResult'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'timezone' => ['required', 'string', 'max:50'],
            'require_checkout_geofence' => ['nullable', 'boolean'],
            'require_selfie' => ['nullable', 'boolean'],
        ]);

        $timezone = $request->input('timezone', 'Asia/Jakarta');
        $requireCheckout = $request->boolean('require_checkout_geofence') ? '1' : '0';
        $requireSelfie = $request->boolean('require_selfie') ? '1' : '0';

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
