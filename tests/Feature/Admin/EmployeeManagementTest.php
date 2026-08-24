<?php

namespace Tests\Feature\Admin;

use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Karyawan Ordinary',
            'email' => 'karyawan@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_create_employee(): void
    {
        $jobTitle = JobTitle::create(['name' => 'Hair Stylist', 'is_active' => true]);

        $response = $this->actingAs($this->owner)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Lestari',
            'email' => 'ayu@selonbeauty.com',
            'phone' => '081234567891',
            'job_title_id' => $jobTitle->id,
            'status' => 'active',
            'create_user_account' => '1',
            'account_password' => 'password123',
        ]);

        $response->assertRedirect('/admin/employees');

        $this->assertDatabaseHas('employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Ayu Lestari',
            'email' => 'ayu@selonbeauty.com',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ayu@selonbeauty.com',
            'role' => 'employee',
        ]);
    }

    public function test_owner_can_update_employee(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-002',
            'full_name' => 'Budi Santoso',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->put("/admin/employees/{$employee->id}", [
            'employee_code' => 'SB-002',
            'full_name' => 'Budi Santoso Updated',
            'status' => 'active',
        ]);

        $response->assertRedirect('/admin/employees');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'full_name' => 'Budi Santoso Updated',
        ]);
    }

    public function test_employee_code_must_be_unique(): void
    {
        Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Employee One',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->post('/admin/employees', [
            'employee_code' => 'SB-001',
            'full_name' => 'Employee Duplicate Code',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('employee_code');
    }

    public function test_employee_cannot_access_employee_management(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/admin/employees');

        $response->assertRedirect('/app/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_inactive_employee_can_be_reactivated(): void
    {
        $employee = Employee::create([
            'employee_code' => 'SB-003',
            'full_name' => 'Citra Dewi',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->owner)->post("/admin/employees/{$employee->id}/toggle-status");

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'status' => 'active',
        ]);
    }

    public function test_employee_account_has_employee_role(): void
    {
        $response = $this->actingAs($this->owner)->post('/admin/employees', [
            'employee_code' => 'SB-004',
            'full_name' => 'Dewi Sartika',
            'email' => 'dewi@selonbeauty.com',
            'status' => 'active',
            'create_user_account' => '1',
            'account_password' => 'password123',
        ]);

        $user = User::where('email', 'dewi@selonbeauty.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('employee', $user->role);
    }

    public function test_profile_upload_validation_works(): void
    {
        Storage::fake('public');

        // Test non-image upload fails
        $textfile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->owner)->post('/admin/employees', [
            'employee_code' => 'SB-005',
            'full_name' => 'Eka Putri',
            'status' => 'active',
            'profile_photo' => $textfile,
        ]);

        $response->assertSessionHasErrors('profile_photo');

        // Test valid image upload succeeds
        $image = UploadedFile::fake()->image('avatar.jpg');

        $response2 = $this->actingAs($this->owner)->post('/admin/employees', [
            'employee_code' => 'SB-005',
            'full_name' => 'Eka Putri',
            'status' => 'active',
            'profile_photo' => $image,
        ]);

        $response2->assertRedirect('/admin/employees');

        $employee = Employee::where('employee_code', 'SB-005')->first();
        $this->assertNotNull($employee->profile_photo_path);
        Storage::disk('public')->assertExists($employee->profile_photo_path);
    }

    public function test_job_title_crud_works(): void
    {
        // Create Job Title
        $response = $this->actingAs($this->owner)->post('/admin/job-titles', [
            'name' => 'Beautician Senior',
            'description' => 'Ahli kecantikan senior',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/job-titles');
        $this->assertDatabaseHas('job_titles', ['name' => 'Beautician Senior']);

        $jobTitle = JobTitle::where('name', 'Beautician Senior')->first();

        // Toggle Status
        $this->actingAs($this->owner)->post("/admin/job-titles/{$jobTitle->id}/toggle-status");
        $this->assertDatabaseHas('job_titles', ['id' => $jobTitle->id, 'is_active' => false]);
    }

    public function test_admin_employee_index_renders_responsive_toolbar_and_actions(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.employees.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Karyawan');
        $response->assertSee('Cari nama, kode, email...');
        $response->assertSee('Semua Status');
        $response->assertSee('Filter');
        $response->assertSee('Kelola Jabatan');
        $response->assertSee('Tambah Karyawan');
    }
}
