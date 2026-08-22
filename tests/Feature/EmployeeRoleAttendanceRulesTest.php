<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRoleAttendanceRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private User $owner;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->admin->assignedOutlets()->sync([$this->admin->outlet_id]);
    }

    public function test_karyawan_role_always_forces_attendance_enabled_to_true(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('admin.employees.store'), [
            'employee_code' => 'EMP-TEST-01',
            'full_name' => 'Test Karyawan Mandatory',
            'email' => 'karyawan.mandatory@example.com',
            'status' => 'active',
            'create_user_account' => 1,
            'account_password' => 'password123',
            'role' => 'employee',
            'attendance_enabled' => 0, // Attemping false
        ]);

        $response->assertRedirect(route('admin.employees.index'));

        $employee = Employee::where('employee_code', 'EMP-TEST-01')->firstOrFail();
        $this->assertTrue($employee->participatesInAttendance());
        $this->assertEquals(1, $employee->attendance_enabled);
    }

    public function test_owner_and_admin_roles_allow_optional_attendance(): void
    {
        // Superadmin creates Owner with attendance_enabled = false
        $this->actingAs($this->superadmin)->post(route('admin.employees.store'), [
            'employee_code' => 'EMP-OWNER-01',
            'full_name' => 'Test Owner Non Attendance',
            'email' => 'owner.admin@example.com',
            'status' => 'active',
            'create_user_account' => 1,
            'account_password' => 'password123',
            'role' => 'owner',
            'attendance_enabled' => 0,
        ]);

        $ownerEmp = Employee::where('employee_code', 'EMP-OWNER-01')->firstOrFail();
        $this->assertFalse($ownerEmp->participatesInAttendance());

        // Owner creates Admin with attendance_enabled = false
        $this->actingAs($this->owner)->post(route('admin.employees.store'), [
            'employee_code' => 'EMP-ADMIN-01',
            'full_name' => 'Test Admin Non Attendance',
            'email' => 'admin.work@example.com',
            'status' => 'active',
            'create_user_account' => 1,
            'account_password' => 'password123',
            'role' => 'admin',
            'attendance_enabled' => 0,
        ]);

        $adminEmp = Employee::where('employee_code', 'EMP-ADMIN-01')->firstOrFail();
        $this->assertFalse($adminEmp->participatesInAttendance());
    }

    public function test_admin_actor_can_only_create_karyawan_role(): void
    {
        // Admin attempts to create Admin role -> should be forced/overridden to employee
        $response = $this->actingAs($this->admin)->post(route('admin.employees.store'), [
            'employee_code' => 'EMP-FORCED-01',
            'full_name' => 'Admin Attempting Admin Role',
            'email' => 'forced.employee@example.com',
            'status' => 'active',
            'create_user_account' => 1,
            'account_password' => 'password123',
            'role' => 'admin', // Admin actor cannot assign admin role
        ]);

        $response->assertRedirect(route('admin.employees.index'));

        $emp = Employee::where('employee_code', 'EMP-FORCED-01')->firstOrFail();
        $this->assertEquals('employee', $emp->user->role);
        $this->assertTrue($emp->participatesInAttendance());
    }

    public function test_admin_actor_cannot_manage_non_karyawan_users(): void
    {
        $ownerEmp = Employee::create([
            'employee_code' => 'OWN-MANAGE-01',
            'full_name' => 'Target Owner Employee',
            'email' => 'target.owner@example.com',
            'status' => 'active',
            'attendance_enabled' => true,
        ]);
        $this->owner->employee_id = $ownerEmp->id;
        $this->owner->save();
        $ownerEmp->refresh();

        // Admin tries to edit Owner -> Access denied
        $response = $this->actingAs($this->admin)->get(route('admin.employees.edit', $ownerEmp));
        $response->assertRedirect(route('admin.employees.index'));
        $response->assertSessionHas('error');

        // Admin tries to delete Owner -> Access denied
        $responseDel = $this->actingAs($this->admin)->delete(route('admin.employees.destroy', $ownerEmp));
        $responseDel->assertSessionHas('error');

        // Admin tries to toggle status of Owner -> Access denied
        $responseToggle = $this->actingAs($this->admin)->post(route('admin.employees.toggle-status', $ownerEmp));
        $responseToggle->assertSessionHas('error');
    }

    public function test_owner_actor_cannot_manage_superadmin_or_owner_users(): void
    {
        $superadminEmp = Employee::create([
            'employee_code' => 'SUP-MANAGE-01',
            'full_name' => 'Target Superadmin Employee',
            'email' => 'target.superadmin@example.com',
            'status' => 'active',
            'attendance_enabled' => false,
        ]);
        $this->superadmin->employee_id = $superadminEmp->id;
        $this->superadmin->save();
        $superadminEmp->refresh();

        // Owner tries to edit Superadmin -> Access denied
        $response = $this->actingAs($this->owner)->get(route('admin.employees.edit', $superadminEmp));
        $response->assertRedirect(route('admin.employees.index'));
        $response->assertSessionHas('error');

        // Owner tries to delete Superadmin -> Access denied
        $responseDel = $this->actingAs($this->owner)->delete(route('admin.employees.destroy', $superadminEmp));
        $responseDel->assertSessionHas('error');
    }

    public function test_updating_employee_role_to_karyawan_forces_attendance_enabled(): void
    {
        // Admin user with attendance_enabled = false
        $adminEmp = Employee::create([
            'employee_code' => 'ADM-UPDATE-01',
            'full_name' => 'Target Admin To Employee',
            'email' => 'target.admin.update@example.com',
            'status' => 'active',
            'attendance_enabled' => false,
        ]);
        $adminUser = User::factory()->create([
            'role' => 'admin',
            'employee_id' => $adminEmp->id,
            'is_active' => true,
        ]);

        // Superadmin changes role to employee and attempts attendance_enabled = false
        $response = $this->actingAs($this->superadmin)->put(route('admin.employees.update', $adminEmp), [
            'employee_code' => $adminEmp->employee_code,
            'full_name' => $adminEmp->full_name,
            'email' => $adminEmp->email,
            'status' => 'active',
            'role' => 'employee',
            'attendance_enabled' => 0,
        ]);

        $response->assertRedirect(route('admin.employees.index'));

        $adminEmp->refresh();
        $this->assertEquals('employee', $adminEmp->user->role);
        $this->assertTrue($adminEmp->participatesInAttendance());
        $this->assertEquals(1, $adminEmp->attendance_enabled);
    }
}
