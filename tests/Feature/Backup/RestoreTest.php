<?php

namespace Tests\Feature\Backup;

use App\Models\AppSetting;
use App\Models\BackupRecord;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RestoreTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadminUser;

    protected User $ownerUser;

    protected User $employeeUser;

    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->superadminUser = User::create([
            'name' => 'Superadmin Utama',
            'email' => 'superadmin@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->ownerUser = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'name' => 'Karyawan',
            'email' => 'karyawan@selonbeauty.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $this->backupService = app(BackupService::class);
    }

    public function test_restore_requires_superadmin_and_password_re_authentication(): void
    {
        $backup = $this->backupService->createBackup('database', $this->superadminUser);

        // Employee tries restore -> Redirected to employee dashboard
        $responseEmp = $this->actingAs($this->employeeUser)->post(route('admin.settings.backups.restore', $backup->id), [
            'password' => 'password123',
        ]);
        $responseEmp->assertRedirect(route('employee.dashboard'));

        // Owner tries restore -> 403 Forbidden (Sprint 18.5 policy: Superadmin ONLY)
        $responseOwner = $this->actingAs($this->ownerUser)->post(route('admin.settings.backups.restore', $backup->id), [
            'password' => 'password123',
        ]);
        $responseOwner->assertStatus(403);

        // Superadmin with wrong password -> Error session
        $responseWrongPass = $this->actingAs($this->superadminUser)->post(route('admin.settings.backups.restore', $backup->id), [
            'password' => 'wrongpassword',
        ]);
        $responseWrongPass->assertSessionHas('error');
    }

    public function test_pre_restore_backup_created_before_restore(): void
    {
        $backup = $this->backupService->createBackup('database', $this->superadminUser);
        $initialCount = BackupRecord::count();

        $response = $this->actingAs($this->superadminUser)->post(route('admin.settings.backups.restore', $backup->id), [
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // A Pre-Restore Safety Backup record should have been created!
        $this->assertDatabaseHas('backup_records', [
            'is_pre_restore' => true,
            'status' => 'completed',
        ]);

        $this->assertGreaterThan($initialCount, BackupRecord::count());

        // Audit Log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'restore.completed',
        ]);
    }

    public function test_restore_rejects_corrupted_checksum(): void
    {
        $backup = $this->backupService->createBackup('database', $this->superadminUser);

        // Tamper with checksum
        $backup->update(['checksum' => 'invalidchecksum12345']);

        $response = $this->actingAs($this->superadminUser)->post(route('admin.settings.backups.restore', $backup->id), [
            'password' => 'password123',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Integritas file backup (Checksum SHA-256) tidak cocok', session('error'));
    }

    public function test_full_restore_preserves_branding_path_and_public_media(): void
    {
        Storage::disk('public')->put('branding/logo-restored.png', 'restored-branding');
        AppSetting::set('app_logo_path', 'branding/logo-restored.png', 'string', true);
        $backup = $this->backupService->createBackup('full', $this->superadminUser);

        Storage::disk('public')->delete('branding/logo-restored.png');
        AppSetting::set('app_logo_path', 'branding/replaced.png', 'string', true);

        $this->backupService->restoreBackup($backup, 'password123', $this->superadminUser);

        $this->assertSame('branding/logo-restored.png', AppSetting::get('app_logo_path'));
        Storage::disk('public')->assertExists('branding/logo-restored.png');
        $this->get(route('branding.media', ['type' => 'logo']))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
