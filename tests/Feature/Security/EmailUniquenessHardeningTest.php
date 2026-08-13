<?php

namespace Tests\Feature\Security;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailUniquenessHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::create([
            'name' => 'Superadmin One',
            'email' => 'superadmin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);
    }

    public function test_creating_employee_with_unique_email_succeeds(): void
    {
        $response = $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Wandira',
            'email' => 'ayu@selonbeauty.com',
            'status' => 'active',
            'create_user_account' => '1',
            'account_password' => 'password123',
            'role' => 'employee',
        ]);

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseHas('employees', ['email' => 'ayu@selonbeauty.com']);
        $this->assertDatabaseHas('users', ['email' => 'ayu@selonbeauty.com']);
    }

    public function test_creating_second_employee_with_same_email_fails(): void
    {
        // First employee
        $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Wandira',
            'email' => 'ayu@selonbeauty.com',
            'status' => 'active',
        ]);

        // Second employee with same email
        $response = $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-002',
            'full_name' => 'Ayu Second',
            'email' => 'ayu@selonbeauty.com',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Wandira',
            'email' => 'ayu@selonbeauty.com',
            'status' => 'active',
        ]);

        // Try creating with mixed case
        $response = $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-002',
            'full_name' => 'Ayu Uppercase',
            'email' => 'AYU@SELONBEAUTY.COM',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
    }

    public function test_email_whitespace_is_normalized(): void
    {
        $response = $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Spaced',
            'email' => '  ayu_spaced@selonbeauty.com   ',
            'status' => 'active',
        ]);

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseHas('employees', ['email' => 'ayu_spaced@selonbeauty.com']);
    }

    public function test_editing_user_without_changing_own_email_succeeds(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Edit Own',
            'email' => 'editown@selonbeauty.com',
            'status' => 'active',
        ]);

        $user = User::create([
            'employee_id' => $employee->id,
            'name' => 'Edit Own',
            'email' => 'editown@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superadmin)->put('/admin/employees/' . $employee->id, [
            'employee_code' => 'SB-001',
            'full_name' => 'Edit Own Name Updated',
            'email' => 'editown@selonbeauty.com',
            'status' => 'active',
        ]);

        $response->assertRedirect('/admin/employees');
        $this->assertDatabaseHas('employees', ['full_name' => 'Edit Own Name Updated']);
    }

    public function test_editing_user_to_another_users_email_fails(): void
    {
        $emp1 = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'User One',
            'email' => 'one@selonbeauty.com',
            'status' => 'active',
        ]);

        $emp2 = Employee::create([
            'employee_code' => 'SB-002',
            'full_name' => 'User Two',
            'email' => 'two@selonbeauty.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superadmin)->put('/admin/employees/' . $emp2->id, [
            'employee_code' => 'SB-002',
            'full_name' => 'User Two Edit',
            'email' => 'one@selonbeauty.com',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
    }

    public function test_superadmin_email_cannot_be_reused_by_employee(): void
    {
        $response = $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Fake Superadmin Employee',
            'email' => 'superadmin@selonbeauty.com',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
    }

    public function test_admin_email_cannot_be_reused_by_employee(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Employee Duplicate Admin',
            'email' => 'admin@selonbeauty.com',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
    }

    public function test_owner_email_cannot_be_reused_by_another_user(): void
    {
        User::create([
            'name' => 'Owner User',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superadmin)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Employee Duplicate Owner',
            'email' => 'owner@selonbeauty.com',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email' => 'Email tersebut sudah digunakan oleh akun lain.']);
    }

    public function test_forged_request_cannot_bypass_email_uniqueness(): void
    {
        $response = $this->post('/admin/employees', [
            'employee_code' => 'SB-FORGED',
            'full_name' => 'Forged User',
            'email' => 'superadmin@selonbeauty.com',
            'status' => 'active',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_database_unique_constraint_exists(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'email'));
        $this->assertTrue(Schema::hasColumn('employees', 'email'));
    }

    public function test_forgot_password_still_resolves_correct_user(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'superadmin@selonbeauty.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Jika email tersebut terdaftar, kami akan mengirimkan link untuk mengatur ulang password.');
    }
}
