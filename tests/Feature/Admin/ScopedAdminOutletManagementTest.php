<?php

namespace Tests\Feature\Admin;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopedAdminOutletManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $owner;
    protected User $admin;
    protected User $employeeUser;

    protected Outlet $outletA;
    protected Outlet $outletB;
    protected Outlet $outletC;
    protected Outlet $softDeletedOutlet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::query()->where('code', 'PUSAT')->first();
        if (! $this->outletA) {
            $this->outletA = Outlet::create([
                'name' => 'Selon Beauty Pusat',
                'code' => 'PUSAT',
                'address' => 'Jl. Merdeka No. 1',
                'latitude' => -6.175110,
                'longitude' => 106.827220,
                'radius_meters' => 100,
                'max_accuracy_meters' => 50,
                'is_active' => true,
            ]);
        }

        $this->outletB = Outlet::create([
            'name' => 'Selon Beauty Cabang B',
            'code' => 'OUT-B',
            'address' => 'Jl. Sudirman No. 12',
            'latitude' => -6.208800,
            'longitude' => 106.845600,
            'radius_meters' => 150,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->outletC = Outlet::create([
            'name' => 'Selon Beauty Cabang C',
            'code' => 'OUT-C',
            'address' => 'Jl. Gatot Subroto No. 45',
            'latitude' => -6.230000,
            'longitude' => 106.810000,
            'radius_meters' => 200,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->softDeletedOutlet = Outlet::create([
            'name' => 'Selon Beauty Deleted',
            'code' => 'OUT-DEL',
            'address' => 'Jl. Lama No. 99',
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => false,
        ]);
        $this->softDeletedOutlet->delete();

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Scoped',
            'email' => 'admin@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'outlet_id' => $this->outletA->id,
            'is_active' => true,
        ]);
        $this->admin->assignedOutlets()->sync([$this->outletA->id, $this->outletB->id]);

        $this->employeeUser = User::create([
            'name' => 'Employee User',
            'email' => 'employee@selonbeauty.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    public function test_1_superadmin_can_view_all_outlets(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('admin.outlets.index'));

        $response->assertOk();
        $response->assertSee($this->outletA->name);
        $response->assertSee('Selon Beauty Cabang B');
        $response->assertSee('Selon Beauty Cabang C');
    }

    public function test_2_owner_can_view_all_outlets(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.outlets.index'));

        $response->assertOk();
        $response->assertSee($this->outletA->name);
        $response->assertSee('Selon Beauty Cabang B');
        $response->assertSee('Selon Beauty Cabang C');
    }

    public function test_3_admin_can_open_outlets_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.outlets.index'));

        $response->assertOk();
    }

    public function test_4_and_5_admin_only_sees_assigned_outlets_and_cannot_see_unassigned(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.outlets.index'));

        $response->assertOk();
        $response->assertSee($this->outletA->name);
        $response->assertSee('Selon Beauty Cabang B');
        $response->assertDontSee('Selon Beauty Cabang C');
    }

    public function test_6_to_9_admin_can_edit_assigned_outlet_location_coordinates_and_radius(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.outlets.update', $this->outletB), [
            'name' => 'Selon Beauty Cabang B Updated',
            'address' => 'Jl. Sudirman Baru No. 99',
            'latitude' => -6.210000,
            'longitude' => 106.850000,
            'radius_meters' => 300,
            'max_accuracy_meters' => 80,
        ]);

        $response->assertRedirect(route('admin.outlets.index'));

        $this->outletB->refresh();
        $this::assertEquals('Selon Beauty Cabang B Updated', $this->outletB->name);
        $this::assertEquals('Jl. Sudirman Baru No. 99', $this->outletB->address);
        $this::assertEquals(-6.210000, $this->outletB->latitude);
        $this::assertEquals(106.850000, $this->outletB->longitude);
        $this::assertEquals(300, $this->outletB->radius_meters);
        $this::assertEquals(80, $this->outletB->max_accuracy_meters);
    }

    public function test_10_and_11_admin_direct_url_or_update_to_unassigned_outlet_rejected_with_403(): void
    {
        // Edit page direct URL
        $responseEdit = $this->actingAs($this->admin)->get(route('admin.outlets.edit', $this->outletC));
        $responseEdit->assertForbidden();

        // Update direct PUT
        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.outlets.update', $this->outletC), [
            'name' => 'Forged Update',
            'latitude' => -6.111111,
            'longitude' => 106.111111,
            'radius_meters' => 500,
        ]);
        $responseUpdate->assertForbidden();

        $this->outletC->refresh();
        $this::assertEquals('Selon Beauty Cabang C', $this->outletC->name);
    }

    public function test_12_admin_cannot_access_create_outlet_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.outlets.create'));

        $response->assertForbidden();
    }

    public function test_13_admin_cannot_store_new_outlet_via_forged_post(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.outlets.store'), [
            'name' => 'Outlet Forged',
            'code' => 'FORGED',
            'latitude' => -6.123456,
            'longitude' => 106.123456,
            'radius_meters' => 100,
        ]);

        $response->assertForbidden();
        $this::assertDatabaseMissing('outlets', ['code' => 'FORGED']);
    }

    public function test_14_admin_cannot_delete_outlet(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('admin.outlets.destroy', $this->outletB));

        $response->assertForbidden();
        $this::assertDatabaseHas('outlets', ['id' => $this->outletB->id]);
    }

    public function test_15_and_16_admin_cannot_deactivate_or_reactivate_outlet(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.outlets.toggle-status', $this->outletB));

        $response->assertForbidden();
        $this->outletB->refresh();
        $this::assertTrue($this->outletB->is_active);
    }

    public function test_17_and_18_admin_cannot_modify_protected_fields_code_or_status(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.outlets.update', $this->outletB), [
            'name' => 'Cabang B Renamed',
            'code' => 'HACKED-CODE',
            'is_active' => false,
            'latitude' => -6.208800,
            'longitude' => 106.845600,
            'radius_meters' => 150,
        ]);

        $response->assertRedirect(route('admin.outlets.index'));

        $this->outletB->refresh();
        $this::assertEquals('Cabang B Renamed', $this->outletB->name);
        $this::assertEquals('OUT-B', $this->outletB->code); // Protected, unchanged!
        $this::assertTrue($this->outletB->is_active); // Protected, unchanged!
    }

    public function test_19_owner_create_and_update_behavior_unchanged(): void
    {
        $responseStore = $this->actingAs($this->owner)->post(route('admin.outlets.store'), [
            'name' => 'Outlet Baru Owner',
            'code' => 'NEW-OWNER',
            'address' => 'Jl. Owner No. 1',
            'latitude' => -6.500000,
            'longitude' => 106.500000,
            'radius_meters' => 200,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $responseStore->assertRedirect(route('admin.outlets.index'));
        $this::assertDatabaseHas('outlets', ['code' => 'NEW-OWNER']);
    }

    public function test_20_superadmin_create_and_update_behavior_unchanged(): void
    {
        $responseStore = $this->actingAs($this->superadmin)->post(route('admin.outlets.store'), [
            'name' => 'Outlet Baru Superadmin',
            'code' => 'NEW-SUPER',
            'address' => 'Jl. Super No. 1',
            'latitude' => -6.600000,
            'longitude' => 106.600000,
            'radius_meters' => 250,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $responseStore->assertRedirect(route('admin.outlets.index'));
        $this::assertDatabaseHas('outlets', ['code' => 'NEW-SUPER']);
    }

    public function test_21_employee_cannot_access_outlet_management(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('admin.outlets.index'));

        $response->assertRedirect(route('employee.dashboard'));
    }

    public function test_22_soft_deleted_outlet_cannot_be_edited_by_admin(): void
    {
        $responseEdit = $this->actingAs($this->admin)->get(route('admin.outlets.edit', $this->softDeletedOutlet));
        $responseEdit->assertForbidden();

        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.outlets.update', $this->softDeletedOutlet), [
            'name' => 'Try Edit Soft Deleted',
            'latitude' => -6.300000,
            'longitude' => 106.900000,
            'radius_meters' => 100,
        ]);
        $responseUpdate->assertForbidden();
    }

    public function test_23_sidebar_outlet_visible_to_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.outlets.index'));
        $response->assertSee('Outlet');
    }

    public function test_24_create_and_delete_lifecycle_actions_hidden_from_admin_ui(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.outlets.index'));

        $response->assertOk();
        $response->assertDontSee(route('admin.outlets.create'));
        $response->assertDontSee('Tambah Outlet Baru');
        $response->assertDontSee('title="Hapus Outlet"', false);
    }
}
