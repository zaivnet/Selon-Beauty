<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\BackupService;
use App\Services\UserRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadminUser;
    protected User $ownerUser;
    protected User $adminUser;
    protected User $employeeUser;
    protected Employee $employee1;
    protected JobTitle $jobTitleAdmin;
    protected JobTitle $jobTitleOwner;
    protected UserRoleService $userRoleService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->userRoleService = new UserRoleService();

        $this->superadminUser = User::create([
            'name' => 'Superadmin System',
            'email' => 'superadmin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->ownerUser = User::create([
            'name' => 'Owner System',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin System',
            'email' => 'admin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->jobTitleAdmin = JobTitle::create([
            'name' => 'Admin Toko',
            'code' => 'ADM-TK',
            'is_active' => true,
        ]);

        $this->jobTitleOwner = JobTitle::create([
            'name' => 'Owner',
            'code' => 'OWN-TK',
            'is_active' => true,
        ]);

        $this->employee1 = Employee::create([
            'employee_code' => 'SB-001',
            'full_name' => 'Karyawan SBY',
            'job_title_id' => $this->jobTitleAdmin->id,
            'status' => 'active',
        ]);

        $this->employeeUser = User::create([
            'employee_id' => $this->employee1->id,
            'name' => 'Karyawan SBY',
            'email' => 'karyawan@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);
    }

    // SECTION 39: JABATAN VS ROLE SEPARATION TESTS
    public function test_employee_with_job_title_admin_remains_employee_role(): void
    {
        $this->assertEquals('employee', $this->employeeUser->role);
        $this->assertEquals('Admin Toko', $this->employee1->jobTitle->name);

        $response = $this->actingAs($this->employeeUser)->get('/admin/dashboard');
        $response->assertRedirect('/app/dashboard');
    }

    public function test_employee_with_job_title_owner_remains_employee_role(): void
    {
        $this->employee1->update(['job_title_id' => $this->jobTitleOwner->id]);
        $this->employee1->refresh();

        $this->assertEquals('employee', $this->employeeUser->role);
        $this->assertEquals('Owner', $this->employee1->jobTitle->name);

        $response = $this->actingAs($this->employeeUser)->get('/admin/dashboard');
        $response->assertRedirect('/app/dashboard');
    }

    public function test_job_title_name_does_not_grant_admin_access(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/admin/employees');
        $response->assertRedirect('/app/dashboard');
    }

    public function test_job_title_name_does_not_grant_owner_access(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/admin/settings/branding');
        $response->assertRedirect('/app/dashboard');
    }

    // SECTION 40: SUPERADMIN & ROLE MANAGEMENT PERMISSIONS
    public function test_superadmin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->superadminUser)->get('/admin/dashboard');
        $response->assertOk();
    }

    public function test_superadmin_can_manage_owner_role(): void
    {
        $targetUser = User::create([
            'name' => 'Target Admin',
            'email' => 'targetadmin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $updated = $this->userRoleService->updateUserRole($this->superadminUser, $targetUser, 'owner');
        $this->assertEquals('owner', $updated->role);
    }

    public function test_superadmin_can_manage_admin_role(): void
    {
        $updated = $this->userRoleService->updateUserRole($this->superadminUser, $this->employeeUser, 'admin');
        $this->assertEquals('admin', $updated->role);
    }

    public function test_superadmin_can_manage_employee_role(): void
    {
        $updated = $this->userRoleService->updateUserRole($this->superadminUser, $this->adminUser, 'employee');
        $this->assertEquals('employee', $updated->role);
    }

    public function test_owner_cannot_assign_superadmin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->userRoleService->updateUserRole($this->ownerUser, $this->employeeUser, 'superadmin');
    }

    public function test_owner_cannot_assign_owner(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->userRoleService->updateUserRole($this->ownerUser, $this->employeeUser, 'owner');
    }

    public function test_admin_cannot_assign_role(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->userRoleService->updateUserRole($this->adminUser, $this->employeeUser, 'admin');
    }

    public function test_employee_cannot_assign_role(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->userRoleService->updateUserRole($this->employeeUser, $this->employeeUser, 'admin');
    }

    public function test_last_active_superadmin_cannot_be_demoted(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimal satu Superadmin aktif harus tetap tersedia.');

        $this->userRoleService->ensureSuperadminSafety($this->superadminUser, newRole: 'admin', newIsActive: true);
    }

    public function test_last_active_superadmin_cannot_be_deactivated(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimal satu Superadmin aktif harus tetap tersedia.');

        $this->userRoleService->ensureSuperadminSafety($this->superadminUser, newRole: 'superadmin', newIsActive: false);
    }

    // SECTION 41: ROLE CHANGE & INVALIDATION TESTS
    public function test_superadmin_can_promote_employee_to_admin(): void
    {
        $response = $this->actingAs($this->superadminUser)->put("/admin/employees/{$this->employee1->id}", [
            'employee_code' => $this->employee1->employee_code,
            'full_name' => $this->employee1->full_name,
            'status' => 'active',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/employees');
        $this->assertEquals('admin', $this->employeeUser->fresh()->role);
    }

    public function test_owner_can_promote_employee_to_admin(): void
    {
        $response = $this->actingAs($this->ownerUser)->put("/admin/employees/{$this->employee1->id}", [
            'employee_code' => $this->employee1->employee_code,
            'full_name' => $this->employee1->full_name,
            'status' => 'active',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/employees');
        $this->assertEquals('admin', $this->employeeUser->fresh()->role);
    }

    public function test_owner_cannot_promote_admin_to_owner(): void
    {
        $adminEmployee = Employee::create(['employee_code' => 'SB-002', 'full_name' => 'Admin Emp', 'status' => 'active']);
        $adminUser = User::create(['employee_id' => $adminEmployee->id, 'name' => 'Admin Emp', 'email' => 'adminemp@selonbeauty.com', 'password' => Hash::make('password123'), 'role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($this->ownerUser)->put("/admin/employees/{$adminEmployee->id}", [
            'employee_code' => $adminEmployee->employee_code,
            'full_name' => $adminEmployee->full_name,
            'status' => 'active',
            'role' => 'owner',
        ]);

        $this->assertEquals('admin', $adminUser->fresh()->role);
    }

    public function test_admin_cannot_promote_employee(): void
    {
        $response = $this->actingAs($this->adminUser)->put("/admin/employees/{$this->employee1->id}", [
            'employee_code' => $this->employee1->employee_code,
            'full_name' => $this->employee1->full_name,
            'status' => 'active',
            'role' => 'admin',
        ]);

        $this->assertEquals('employee', $this->employeeUser->fresh()->role);
    }

    public function test_user_cannot_promote_self_beyond_permission(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->userRoleService->updateUserRole($this->ownerUser, $this->ownerUser, 'superadmin');
    }

    public function test_forged_superadmin_role_request_rejected(): void
    {
        $response = $this->actingAs($this->ownerUser)->put("/admin/employees/{$this->employee1->id}", [
            'employee_code' => $this->employee1->employee_code,
            'full_name' => $this->employee1->full_name,
            'status' => 'active',
            'role' => 'superadmin',
        ]);

        $this->assertEquals('employee', $this->employeeUser->fresh()->role);
    }

    public function test_role_change_creates_audit_log(): void
    {
        $this->userRoleService->updateUserRole($this->superadminUser, $this->employeeUser, 'admin');

        $log = AuditLog::where('action', 'user.role_changed')->where('auditable_id', $this->employeeUser->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('employee', $log->before_data['role']);
        $this->assertEquals('admin', $log->after_data['role']);
    }

    public function test_role_change_invalidates_target_session(): void
    {
        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $this->employeeUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'dummy',
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['user_id' => $this->employeeUser->id]);

        $oldToken = $this->employeeUser->remember_token;
        $this->userRoleService->updateUserRole($this->superadminUser, $this->employeeUser, 'admin');

        $this->assertDatabaseMissing('sessions', ['user_id' => $this->employeeUser->id]);
        $this->assertNotEquals($oldToken, $this->employeeUser->fresh()->remember_token);
    }

    // SECTION 42: ACCESS CONTROL TESTS
    public function test_superadmin_can_access_owner_admin_routes(): void
    {
        $response = $this->actingAs($this->superadminUser)->get('/admin/attendance');
        $response->assertOk();

        $response2 = $this->actingAs($this->superadminUser)->get('/admin/settings/branding');
        $response2->assertOk();
    }

    public function test_owner_can_access_allowed_admin_routes(): void
    {
        $response = $this->actingAs($this->ownerUser)->get('/admin/attendance');
        $response->assertOk();

        $response2 = $this->actingAs($this->ownerUser)->get('/admin/settings/branding');
        $response2->assertOk();
    }

    public function test_admin_can_access_operational_routes(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/attendance');
        $response->assertOk();

        $response2 = $this->actingAs($this->adminUser)->get('/admin/schedules');
        $response2->assertOk();
    }

    public function test_employee_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/admin/dashboard');
        $response->assertRedirect('/app/dashboard');
    }

    // SECTION 43: SENSITIVE FEATURE TESTS
    public function test_superadmin_can_restore_backup(): void
    {
        $backup = BackupRecord::create([
            'backup_uuid' => 'test-uuid-restore',
            'filename' => 'selon-backup-full.zip',
            'file_path' => 'private/backups/selon-backup-full.zip',
            'file_size' => 1024,
            'type' => 'full',
            'status' => 'completed',
            'checksum' => hash('sha256', 'dummy'),
        ]);

        $response = $this->actingAs($this->superadminUser)->post("/admin/settings/backups/{$backup->id}/restore", [
            'password' => 'wrongpass',
        ]);

        // Returns error validation/password message, NOT 403 Forbidden!
        $response->assertRedirect();
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_owner_cannot_restore_backup(): void
    {
        $backup = BackupRecord::create([
            'backup_uuid' => 'test-uuid-restore-owner',
            'filename' => 'selon-backup-full.zip',
            'file_path' => 'private/backups/selon-backup-full.zip',
            'file_size' => 1024,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->ownerUser)->post("/admin/settings/backups/{$backup->id}/restore", [
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_restore_backup(): void
    {
        $backup = BackupRecord::create([
            'backup_uuid' => 'test-uuid-restore-admin',
            'filename' => 'selon-backup-full.zip',
            'file_path' => 'private/backups/selon-backup-full.zip',
            'file_size' => 1024,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->adminUser)->post("/admin/settings/backups/{$backup->id}/restore", [
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_employee_cannot_restore_backup(): void
    {
        $backup = BackupRecord::create([
            'backup_uuid' => 'test-uuid-restore-emp',
            'filename' => 'selon-backup-full.zip',
            'file_path' => 'private/backups/selon-backup-full.zip',
            'file_size' => 1024,
            'type' => 'full',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->employeeUser)->post("/admin/settings/backups/{$backup->id}/restore", [
            'password' => 'password123',
        ]);

        $response->assertRedirect('/app/dashboard');
    }

    public function test_superadmin_can_create_backup(): void
    {
        $backupService = app(BackupService::class);
        $record = $backupService->createBackup('database', $this->superadminUser);
        $this->assertNotNull($record);
    }

    public function test_owner_can_create_backup(): void
    {
        $backupService = app(BackupService::class);
        $record = $backupService->createBackup('database', $this->ownerUser);
        $this->assertNotNull($record);
    }

    public function test_admin_cannot_create_backup(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/settings/backups', [
            'type' => 'database',
        ]);

        $response->assertStatus(403);
    }

    public function test_employee_cannot_create_backup(): void
    {
        $response = $this->actingAs($this->employeeUser)->post('/admin/settings/backups', [
            'type' => 'database',
        ]);

        $response->assertRedirect('/app/dashboard');
    }

    public function test_superadmin_can_update_branding(): void
    {
        $response = $this->actingAs($this->superadminUser)->post('/admin/settings/branding', [
            'app_name' => 'SELON BEAUTY',
            'app_short_name' => 'SELON',
            'company_name' => 'PT SELON BEAUTY',
            'app_tagline' => 'Beauty Store',
            'brand_primary' => '#E11D48',
            'brand_accent' => '#F43F5E',
            'pwa_theme_color' => '#E11D48',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_owner_can_update_branding(): void
    {
        $response = $this->actingAs($this->ownerUser)->post('/admin/settings/branding', [
            'app_name' => 'SELON BEAUTY',
            'app_short_name' => 'SELON',
            'company_name' => 'PT SELON BEAUTY',
            'app_tagline' => 'Beauty Store',
            'brand_primary' => '#E11D48',
            'brand_accent' => '#F43F5E',
            'pwa_theme_color' => '#E11D48',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_cannot_update_branding(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/settings/branding', [
            'app_name' => 'HACKED NAME',
            'app_short_name' => 'HACK',
            'brand_primary' => '#000000',
            'brand_accent' => '#000000',
            'pwa_theme_color' => '#000000',
        ]);

        $response->assertStatus(403);
    }

    public function test_employee_cannot_update_branding(): void
    {
        $response = $this->actingAs($this->employeeUser)->post('/admin/settings/branding', [
            'app_name' => 'HACKED NAME',
        ]);

        $response->assertRedirect('/app/dashboard');
    }

    public function test_superadmin_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->superadminUser)->get('/admin/audit-logs');
        $response->assertOk();
    }

    public function test_owner_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->ownerUser)->get('/admin/audit-logs');
        $response->assertOk();
    }

    public function test_admin_cannot_view_sensitive_audit_logs(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/audit-logs');
        $response->assertStatus(403);
    }

    public function test_employee_cannot_view_audit_logs(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/admin/audit-logs');
        $response->assertRedirect('/app/dashboard');
    }
}
