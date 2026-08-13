<?php

namespace Tests\Feature\Backup;

use App\Models\BackupRecord;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;

    protected User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

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
    }

    public function test_owner_can_create_database_backup(): void
    {
        $response = $this->actingAs($this->ownerUser)->post(route('admin.settings.backups.create'), [
            'type' => 'database',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record = BackupRecord::where('type', 'database')->first();
        $this->assertNotNull($record);
        $this->assertEquals('completed', $record->status);
        $this->assertNotNull($record->checksum);
        $this->assertFileExists(storage_path('app/'.$record->file_path));
    }

    public function test_employee_cannot_create_backup(): void
    {
        $response = $this->actingAs($this->employeeUser)->post(route('admin.settings.backups.create'), [
            'type' => 'full',
        ]);

        $response->assertRedirect(route('employee.dashboard'));
        $this->assertEquals(0, BackupRecord::count());
    }

    public function test_backup_stored_outside_public_storage(): void
    {
        $this->actingAs($this->ownerUser)->post(route('admin.settings.backups.create'), [
            'type' => 'database',
        ]);

        $record = BackupRecord::first();
        $this->assertStringContainsString('private/backups', $record->file_path);
        $this->assertStringNotContainsString('public', $record->file_path);
    }

    public function test_backup_history_lists_completed_backup(): void
    {
        $this->actingAs($this->ownerUser)->post(route('admin.settings.backups.create'), [
            'type' => 'full',
        ]);

        $response = $this->actingAs($this->ownerUser)->get(route('admin.settings.backups.index'));
        $response->assertOk();
        $response->assertSee('selon-backup-full-');
    }

    public function test_backup_download_requires_owner_authorization(): void
    {
        $this->actingAs($this->ownerUser)->post(route('admin.settings.backups.create'), [
            'type' => 'database',
        ]);
        $record = BackupRecord::first();

        // Employee download -> Redirected to employee dashboard by EnsureUserRole middleware
        $responseEmp = $this->actingAs($this->employeeUser)->get(route('admin.settings.backups.download', $record->id));
        $responseEmp->assertRedirect(route('employee.dashboard'));

        // Owner download -> Binary File Download
        $responseOwner = $this->actingAs($this->ownerUser)->get(route('admin.settings.backups.download', $record->id));
        $responseOwner->assertOk();
        $responseOwner->assertHeader('Content-Type', 'application/zip');
    }

    public function test_backup_delete_removes_physical_file_safely(): void
    {
        $this->actingAs($this->ownerUser)->post(route('admin.settings.backups.create'), [
            'type' => 'database',
        ]);
        $record = BackupRecord::first();
        $filePath = storage_path('app/'.$record->file_path);
        $this->assertFileExists($filePath);

        $response = $this->actingAs($this->ownerUser)->delete(route('admin.settings.backups.destroy', $record->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $record->refresh();
        $this->assertEquals('deleted', $record->status);
        $this->assertFileDoesNotExist($filePath);
    }

    public function test_full_backup_contains_private_runtime_media_and_manifest_counts(): void
    {
        Storage::fake('public');
        Storage::disk('local')->put('attendance/10/2026/08/check-in.jpg', 'selfie-bytes');
        Storage::disk('local')->put('overtime/10/2026/08/overtime-in.jpg', 'overtime-bytes');
        Storage::disk('local')->put('leave-attachments/10/2026/08/medical.pdf', 'leave-bytes');
        Storage::disk('public')->put('branding/logo.png', 'branding-bytes');

        $record = app(BackupService::class)->createBackup('full', $this->ownerUser);
        $entries = $this->archiveEntries(storage_path('app/'.$record->file_path));

        $this->assertArrayHasKey('database/dump.json', $entries);
        $this->assertArrayHasKey('files/attendance/10/2026/08/check-in.jpg', $entries);
        $this->assertArrayHasKey('files/overtime/10/2026/08/overtime-in.jpg', $entries);
        $this->assertArrayHasKey('files/leave-attachments/10/2026/08/medical.pdf', $entries);
        $this->assertArrayHasKey('files/branding/logo.png', $entries);

        $manifest = json_decode($entries['backup-manifest.json'], true);
        $this->assertSame('database/dump.json', $manifest['database_file']);
        $this->assertSame(1, $manifest['media_categories']['attendance_selfies']['file_count']);
        $this->assertSame(1, $manifest['media_categories']['overtime_selfies']['file_count']);
        $this->assertSame(1, $manifest['media_categories']['leave_attachments']['file_count']);
        $this->assertSame(1, $manifest['media_categories']['branding']['file_count']);
        $this->assertGreaterThan(0, $manifest['media_total_size']);
    }

    /** @return array<string, string> */
    private function archiveEntries(string $path): array
    {
        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $entries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $entries[$name] = $zip->getFromIndex($i);
            }
            $zip->close();

            return $entries;
        }

        return array_map('base64_decode', json_decode(file_get_contents($path), true));
    }
}
