<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_reset_target_user_password_with_reauthentication(): void
    {
        $superadmin = User::create([
            'name' => 'Superadmin Admin',
            'email' => 'superadmin@selonbeauty.com',
            'password' => Hash::make('superadminpass123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP-101',
            'full_name' => 'Target Employee',
            'status' => 'active',
        ]);

        $targetUser = User::create([
            'employee_id' => $employee->id,
            'name' => 'Target Employee',
            'email' => 'targetemp@selonbeauty.com',
            'password' => Hash::make('olduserpass123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        // Insert active session in sessions table for target user
        DB::table('sessions')->insert([
            'id' => 'target_session_999',
            'user_id' => $targetUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($superadmin)
            ->post("/admin/employees/{$employee->id}/reset-password", [
                'superadmin_password' => 'superadminpass123',
                'new_password' => 'newadminsetpass123',
                'new_password_confirmation' => 'newadminsetpass123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertTrue(Hash::check('newadminsetpass123', $targetUser->password));

        // Assert target user sessions were revoked
        $this->assertEquals(0, DB::table('sessions')->where('user_id', $targetUser->id)->count());

        // Assert audit log event created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset.admin_completed',
            'user_id' => $superadmin->id,
        ]);
    }

    public function test_superadmin_must_re_authenticate_with_correct_password(): void
    {
        $superadmin = User::create([
            'name' => 'Superadmin Reauth',
            'email' => 'reauth@selonbeauty.com',
            'password' => Hash::make('correctsuperpass123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP-102',
            'full_name' => 'Target Employee Two',
            'status' => 'active',
        ]);

        $targetUser = User::create([
            'employee_id' => $employee->id,
            'name' => 'Target Employee Two',
            'email' => 'targetemp2@selonbeauty.com',
            'password' => Hash::make('olduserpass123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        // Wrong Superadmin password
        $responseWrong = $this->actingAs($superadmin)
            ->post("/admin/employees/{$employee->id}/reset-password", [
                'superadmin_password' => 'WRONGpass123',
                'new_password' => 'newadminsetpass123',
                'new_password_confirmation' => 'newadminsetpass123',
            ]);

        $responseWrong->assertSessionHasErrors(['superadmin_password']);

        $targetUser->refresh();
        $this->assertTrue(Hash::check('olduserpass123', $targetUser->password));
    }

    public function test_owner_cannot_directly_reset_another_user_password(): void
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('ownerpass123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP-103',
            'full_name' => 'Employee Three',
            'status' => 'active',
        ]);

        User::create([
            'employee_id' => $employee->id,
            'name' => 'Employee Three',
            'email' => 'emp3@selonbeauty.com',
            'password' => Hash::make('oldpass123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)
            ->post("/admin/employees/{$employee->id}/reset-password", [
                'superadmin_password' => 'ownerpass123',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'newpass123',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_directly_reset_another_user_password(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'adminuser@selonbeauty.com',
            'password' => Hash::make('adminpass123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'employee_code' => 'EMP-104',
            'full_name' => 'Employee Four',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/employees/{$employee->id}/reset-password", [
                'superadmin_password' => 'adminpass123',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'newpass123',
            ]);

        $response->assertStatus(403);
    }

    public function test_employee_cannot_directly_reset_another_user_password(): void
    {
        $employeeUser = User::create([
            'name' => 'Employee User',
            'email' => 'empuser@selonbeauty.com',
            'password' => Hash::make('emppass123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $employeeTarget = Employee::create([
            'employee_code' => 'EMP-105',
            'full_name' => 'Employee Five',
            'status' => 'active',
        ]);

        $response = $this->actingAs($employeeUser)
            ->post("/admin/employees/{$employeeTarget->id}/reset-password", [
                'superadmin_password' => 'emppass123',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'newpass123',
            ]);

        $response->assertRedirect('/app/dashboard');
    }
}
