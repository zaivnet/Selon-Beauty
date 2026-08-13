<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceLocation;
use App\Models\User;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceLocationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected GeofenceService $geofenceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->geofenceService = new GeofenceService();

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    public function test_valid_coordinates_accepted(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/settings/locations', [
            'name' => 'SELON BEAUTY Mall',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'radius_meters' => 50,
            'max_accuracy_meters' => 100,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/settings/attendance');

        $this->assertDatabaseHas('attendance_locations', [
            'name' => 'SELON BEAUTY Mall',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'radius_meters' => 50,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);
    }

    public function test_invalid_latitude_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/settings/locations', [
            'name' => 'Invalid Lat Store',
            'latitude' => 95.0, // Invalid lat > 90
            'longitude' => 106.827153,
            'radius_meters' => 50,
            'max_accuracy_meters' => 100,
        ]);

        $response->assertSessionHasErrors('latitude');
    }

    public function test_invalid_longitude_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/settings/locations', [
            'name' => 'Invalid Lon Store',
            'latitude' => -6.175392,
            'longitude' => -200.0, // Invalid lon < -180
            'radius_meters' => 50,
            'max_accuracy_meters' => 100,
        ]);

        $response->assertSessionHasErrors('longitude');
    }

    public function test_inside_radius_calculation_correct(): void
    {
        $storeLocation = AttendanceLocation::create([
            'name' => 'Monas Store',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        // Point ~20 meters away
        $empLat = -6.175300;
        $empLon = 106.827150;

        $distance = $this->geofenceService->calculateDistanceMeters(
            $empLat,
            $empLon,
            $storeLocation->latitude,
            $storeLocation->longitude
        );

        $this->assertLessThanOrEqual(100.0, $distance);
        $this->assertTrue($this->geofenceService->isWithinRadius($empLat, $empLon, $storeLocation));

        $evaluation = $this->geofenceService->evaluateGeofence($empLat, $empLon, 15.0, $storeLocation);
        $this->assertTrue($evaluation['is_valid']);
        $this->assertTrue($evaluation['is_within_radius']);
        $this->assertNull($evaluation['error_message']);
    }

    public function test_outside_radius_calculation_correct(): void
    {
        $storeLocation = AttendanceLocation::create([
            'name' => 'Monas Store',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'radius_meters' => 50,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        // Point ~500 meters away
        $empLat = -6.180000;
        $empLon = 106.827153;

        $distance = $this->geofenceService->calculateDistanceMeters(
            $empLat,
            $empLon,
            $storeLocation->latitude,
            $storeLocation->longitude
        );

        $this->assertGreaterThan(50.0, $distance);
        $this->assertFalse($this->geofenceService->isWithinRadius($empLat, $empLon, $storeLocation));

        $evaluation = $this->geofenceService->evaluateGeofence($empLat, $empLon, 15.0, $storeLocation);
        $this->assertFalse($evaluation['is_valid']);
        $this->assertFalse($evaluation['is_within_radius']);
        $this->assertNotNull($evaluation['error_message']);
    }

    public function test_inactive_location_cannot_be_used(): void
    {
        $inactiveLocation = AttendanceLocation::create([
            'name' => 'Closed Branch',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => false,
        ]);

        $evaluation = $this->geofenceService->evaluateGeofence(
            -6.175392,
            106.827153,
            10.0,
            $inactiveLocation
        );

        $this->assertFalse($evaluation['is_valid']);
        $this->assertEquals('Lokasi absensi saat ini tidak aktif.', $evaluation['error_message']);
    }

    public function test_gps_accuracy_validation_works(): void
    {
        $storeLocation = AttendanceLocation::create([
            'name' => 'Monas Store',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'radius_meters' => 100,
            'max_accuracy_meters' => 30, // max 30 meters accuracy
            'is_active' => true,
        ]);

        // Employee right at store center but GPS accuracy is 150m (poor)
        $evaluation = $this->geofenceService->evaluateGeofence(
            -6.175392,
            106.827153,
            150.0,
            $storeLocation
        );

        $this->assertFalse($evaluation['is_valid']);
        $this->assertFalse($evaluation['is_accuracy_valid']);
        $this->assertStringContainsString('terlalu rendah', $evaluation['error_message']);
    }

    public function test_attendance_settings_can_be_updated_by_owner(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/settings/attendance', [
            'timezone' => 'Asia/Makassar',
            'require_checkout_geofence' => '1',
            'require_selfie' => '1',
        ]);

        $response->assertRedirect('/admin/settings/attendance');

        $this->assertDatabaseHas('app_settings', [
            'key' => 'timezone',
            'value' => 'Asia/Makassar',
        ]);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'attendance_require_checkout_geofence',
            'value' => '1',
        ]);
    }
}
