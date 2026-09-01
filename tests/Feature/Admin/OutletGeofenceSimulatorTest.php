<?php

namespace Tests\Feature\Admin;

use App\Models\Outlet;
use App\Models\User;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletGeofenceSimulatorTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletPusat;
    protected Outlet $outletCabang;
    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->outletCabang = Outlet::create([
            'name' => 'Kopi Selon Cabang',
            'code' => 'KSC',
            'latitude' => -6.9147440,
            'longitude' => 107.6098100,
            'radius_meters' => 100,
            'max_accuracy_meters' => 80,
            'is_active' => true,
        ]);

        $this->outletPusat = Outlet::create([
            'name' => 'Kopi Selon Pusat',
            'code' => 'KSP',
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'radius_meters' => 100,
            'max_accuracy_meters' => 100,
            'is_active' => true,
        ]);
    }

    public function test_initial_simulator_page_renders_with_empty_employee_coordinates(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.outlets.index'));

        $response->assertStatus(200);
        $response->assertSee('Simulator Evaluasi Geofence Per-Outlet');
        $response->assertSee('Gunakan Lokasi Saya Saat Ini');
        $response->assertSee('value="" placeholder="Contoh: -6.2000000"', false);
        $response->assertSee('value="" placeholder="Contoh: 106.8166660"', false);
        $response->assertDontSee('value="-6.914744"', false);
    }

    public function test_simulator_calculates_zero_distance_when_exact_outlet_coordinates_are_provided(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.outlets.index', [
            'test_outlet_id' => $this->outletCabang->id,
            'test_lat' => -6.9147440,
            'test_lon' => 107.6098100,
            'test_accuracy' => 20,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Hasil Evaluasi Terhadap Kopi Selon Cabang (KSC)');
        $response->assertSee('0 m');
        $response->assertSee('DALAM RADIUS (VALID)');
        $response->assertSee('value="-6.914744"', false);
        $response->assertSee('value="107.60981"', false);
    }

    public function test_simulator_detects_out_of_radius_when_user_is_far_from_selected_outlet(): void
    {
        // User is at Jakarta coordinates (-6.2000000, 106.8166660) testing against Bandung Cabang
        $response = $this->actingAs($this->superadmin)->get(route('admin.outlets.index', [
            'test_outlet_id' => $this->outletCabang->id,
            'test_lat' => -6.2000000,
            'test_lon' => 106.8166660,
            'test_accuracy' => 15,
        ]));

        $response->assertStatus(200);
        $response->assertSee('DI LUAR RADIUS (DITOLAK)');
        $response->assertSee('Posisi Anda');
        $response->assertDontSee('DALAM RADIUS (VALID)');
    }

    public function test_same_employee_coordinates_produce_different_distances_for_different_outlets(): void
    {
        $employeeLat = -6.2000000;
        $employeeLon = 106.8166660;

        // Test against Pusat (Exact match -> 0m)
        $responsePusat = $this->actingAs($this->superadmin)->get(route('admin.outlets.index', [
            'test_outlet_id' => $this->outletPusat->id,
            'test_lat' => $employeeLat,
            'test_lon' => $employeeLon,
            'test_accuracy' => 15,
        ]));
        $responsePusat->assertStatus(200);
        $responsePusat->assertSee('DALAM RADIUS (VALID)');
        $responsePusat->assertSee('0 m');

        // Test against Cabang (~120km away -> Out of radius)
        $responseCabang = $this->actingAs($this->superadmin)->get(route('admin.outlets.index', [
            'test_outlet_id' => $this->outletCabang->id,
            'test_lat' => $employeeLat,
            'test_lon' => $employeeLon,
            'test_accuracy' => 15,
        ]));
        $responseCabang->assertStatus(200);
        $responseCabang->assertSee('DI LUAR RADIUS (DITOLAK)');
    }

    public function test_simulator_validates_required_coordinates_and_rejects_empty_inputs(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.outlets.index', [
            'test_outlet_id' => $this->outletCabang->id,
            'test_lat' => '',
            'test_lon' => '',
        ]));

        $response->assertSessionHasErrors(['test_lat', 'test_lon']);
    }

    public function test_simulator_validates_latitude_and_longitude_ranges(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.outlets.index', [
            'test_outlet_id' => $this->outletCabang->id,
            'test_lat' => 95.000,
            'test_lon' => 200.000,
        ]));

        $response->assertSessionHasErrors(['test_lat', 'test_lon']);
    }

    public function test_scoped_admin_cannot_simulate_unauthorized_outlet(): void
    {
        $scopedAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => $this->outletPusat->id,
        ]);
        $scopedAdmin->assignedOutlets()->sync([$this->outletPusat->id]);

        // Attempt to simulate against Cabang (which scopedAdmin cannot access)
        $response = $this->actingAs($scopedAdmin)->get(route('admin.outlets.index', [
            'test_outlet_id' => $this->outletCabang->id,
            'test_lat' => -6.2000000,
            'test_lon' => 106.8166660,
        ]));

        $response->assertRedirect(route('admin.outlets.index'));
        $response->assertSessionHas('error');
    }

    public function test_geofence_service_distance_calculation_remains_exact(): void
    {
        $service = app(GeofenceService::class);

        // Same point = 0m
        $this->assertEquals(0.0, $service->calculateDistanceMeters(-6.2, 106.8, -6.2, 106.8));

        // Known points: Monas (-6.175392, 106.827153) to Bundaran HI (-6.195020, 106.823055) ~ 2.22 km
        $dist = $service->calculateDistanceMeters(-6.175392, 106.827153, -6.195020, 106.823055);
        $this->assertGreaterThan(2100, $dist);
        $this->assertLessThan(2300, $dist);
    }
}
