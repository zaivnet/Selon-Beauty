<?php

namespace Tests\Feature\MultiOutlet;

use App\Models\Employee;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOutletScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected User $adminA;

    protected User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outletA = Outlet::create([
            'name' => 'Selon Outlet A',
            'code' => 'OUTA',
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'radius_meters' => 100,
        ]);

        $this->outletB = Outlet::create([
            'name' => 'Selon Outlet B',
            'code' => 'OUTB',
            'latitude' => -6.3000000,
            'longitude' => 106.9166660,
            'radius_meters' => 100,
        ]);

        $this->adminA = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->adminB = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => $this->outletB->id,
        ]);
        $this->adminA->assignedOutlets()->sync([$this->outletA->id]);
        $this->adminB->assignedOutlets()->sync([$this->outletB->id]);
    }

    public function test_admin_only_sees_employees_in_their_assigned_outlet(): void
    {
        $employeeA = Employee::create([
            'employee_code' => 'EMP-OUT-A',
            'full_name' => 'Karyawan Outlet A',
            'email' => 'karyawan.a@test.com',
            'status' => 'active',
            'outlet_id' => $this->outletA->id,
        ]);

        $employeeB = Employee::create([
            'employee_code' => 'EMP-OUT-B',
            'full_name' => 'Karyawan Outlet B',
            'email' => 'karyawan.b@test.com',
            'status' => 'active',
            'outlet_id' => $this->outletB->id,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.employees.index'));

        $response->assertStatus(200);
        $response->assertSee('Karyawan Outlet A');
        $response->assertDontSee('Karyawan Outlet B');
    }

    public function test_admin_creating_employee_automatically_inherits_admin_outlet(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.employees.store'), [
            'employee_code' => 'SB-901',
            'full_name' => 'Karyawan Baru Outlet A',
            'email' => 'karyawan.baru.a@selon.test',
            'phone' => '081111111111',
            'status' => 'active',
            'create_user_account' => 1,
            'account_password' => 'password123',
            'role' => 'employee',
        ]);

        $response->assertRedirect(route('admin.employees.index'));

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'SB-901',
            'outlet_id' => $this->outletA->id,
        ]);
    }

    public function test_admin_cannot_access_employee_in_different_outlet(): void
    {
        $employeeB = Employee::create([
            'employee_code' => 'EMP-ACC-B',
            'full_name' => 'Karyawan Outlet B Access Test',
            'email' => 'karyawan.acc.b@test.com',
            'status' => 'active',
            'outlet_id' => $this->outletB->id,
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.employees.show', $employeeB));

        $response->assertStatus(403);
    }

    public function test_admin_without_outlet_fails_closed(): void
    {
        $corruptAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'outlet_id' => null,
        ]);

        $response = $this->actingAs($corruptAdmin)->get(route('admin.employees.index'));

        $response->assertStatus(403);
    }
}
