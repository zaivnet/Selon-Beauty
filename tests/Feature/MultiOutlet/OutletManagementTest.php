<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\AppSetting::set('outlet_mode', \App\Services\OutletModeService::MODE_MULTI, 'string', false);
    }

    public function test_superadmin_can_view_outlets_list(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $outlet = Outlet::create([
            'name' => 'Selon Cabang Kemang',
            'code' => 'CBG01',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 100,
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.outlets.index'));

        $response->assertStatus(200);
        $response->assertSee('Selon Cabang Kemang');
        $response->assertSee('CBG01');
    }

    public function test_owner_can_create_new_outlet(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'is_active' => true]);

        $response = $this->actingAs($owner)->post(route('admin.outlets.store'), [
            'name' => 'Selon Cabang Bandung',
            'code' => 'BDG01',
            'address' => 'Jl. Riau No 10',
            'latitude' => -6.914744,
            'longitude' => 107.609810,
            'radius_meters' => 150,
            'max_accuracy_meters' => 80,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.outlets.index'));
        $this->assertDatabaseHas('outlets', [
            'name' => 'Selon Cabang Bandung',
            'code' => 'BDG01',
            'radius_meters' => 150,
        ]);
    }

    public function test_admin_can_access_scoped_outlet_management(): void
    {
        $outlet = Outlet::create([
            'name' => 'Selon Kuta',
            'code' => 'KUTA01',
            'latitude' => -8.72,
            'longitude' => 115.17,
            'radius_meters' => 100,
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'outlet_id' => $outlet->id]);
        $admin->assignedOutlets()->sync([$outlet->id]);

        $response = $this->actingAs($admin)->get(route('admin.outlets.index'));

        $response->assertStatus(200);
        $response->assertSee('Selon Kuta');
    }
}
