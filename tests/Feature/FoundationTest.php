<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_returns_ok(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'database' => 'ok',
            ]);
    }

    public function test_admin_dashboard_loads_with_real_database_counts(): void
    {
        $owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get('/admin/dashboard');

        $response->assertStatus(200)
            ->assertSee('SELON BEAUTY')
            ->assertSee('Karyawan Aktif')
            ->assertSee('Belum Ada Karyawan Terdaftar');
    }

    public function test_employee_dashboard_loads_without_error(): void
    {
        $employee = User::create([
            'name' => 'Karyawan System',
            'email' => 'karyawan@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->actingAs($employee)->get('/app/dashboard');

        $response->assertStatus(200)
            ->assertSee('Beranda Karyawan')
            ->assertSee('Jadwal Kerja Belum Ditetapkan');
    }

    public function test_geofence_haversine_calculation(): void
    {
        $geofence = new GeofenceService();
        // Point 1: Monas Jakarta (-6.175392, 106.827153)
        // Point 2: ~100m away (-6.176292, 106.827153)
        $distance = $geofence->calculateDistanceMeters(-6.175392, 106.827153, -6.176292, 106.827153);

        $this->assertGreaterThan(90, $distance);
        $this->assertLessThan(110, $distance);
    }
}
