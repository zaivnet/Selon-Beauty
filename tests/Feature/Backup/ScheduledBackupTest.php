<?php

namespace Tests\Feature\Backup;

use App\Models\AppSetting;
use App\Models\BackupRecord;
use App\Models\User;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScheduledBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerUser;

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
    }

    public function test_scheduled_backup_respects_enabled_flag(): void
    {
        AppSetting::set('backup_scheduled_enabled', false, 'boolean', true);

        Artisan::call('app:run-scheduled-backup');

        $this->assertEquals(0, BackupRecord::count());
    }

    public function test_scheduled_backup_runs_when_enabled_and_forced(): void
    {
        AppSetting::set('backup_scheduled_enabled', true, 'boolean', true);
        AppSetting::set('backup_scheduled_type', 'database', 'string', true);

        Artisan::call('app:run-scheduled-backup', ['--force' => true]);

        $this->assertEquals(1, BackupRecord::count());
        $record = BackupRecord::first();
        $this->assertNull($record->created_by); // Created by System Cron
        $this->assertEquals('completed', $record->status);
    }

    public function test_duplicate_scheduled_run_prevented(): void
    {
        $nowStr = now('Asia/Jakarta')->format('H:i');
        AppSetting::set('backup_scheduled_enabled', true, 'boolean', true);
        AppSetting::set('backup_scheduled_time', $nowStr, 'string', true);
        AppSetting::set('backup_scheduled_type', 'database', 'string', true);

        // First run -> creates 1 backup
        Artisan::call('app:run-scheduled-backup');
        $this->assertEquals(1, BackupRecord::count());

        // Second run on same day at scheduled time -> duplicate run prevented!
        Artisan::call('app:run-scheduled-backup');
        $this->assertEquals(1, BackupRecord::count());
    }

    public function test_retention_policy_deletes_only_excess_backups(): void
    {
        $backupService = app(BackupService::class);
        AppSetting::set('backup_scheduled_enabled', true, 'boolean', true);
        AppSetting::set('backup_scheduled_retention_count', 3, 'integer', true);

        // Create 5 completed backups with delay to simulate history
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(now()->addMinutes($i));
            $backupService->createBackup('database', $this->ownerUser);
        }

        $this->assertEquals(5, BackupRecord::where('status', 'completed')->count());

        // Apply Retention Policy (max 3)
        $deletedCount = $backupService->applyRetentionPolicy();

        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(3, BackupRecord::where('status', 'completed')->count());
        $this->assertEquals(2, BackupRecord::where('status', 'deleted')->count());
    }

    public function test_protected_safety_backup_not_deleted_by_retention(): void
    {
        $backupService = app(BackupService::class);
        AppSetting::set('backup_scheduled_retention_count', 3, 'integer', true);

        // Create 1 Pre-Restore Safety Backup
        $safetyBackup = $backupService->createBackup('full', $this->ownerUser, isPreRestore: true);

        // Create 5 normal completed backups
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(now()->addMinutes($i));
            $backupService->createBackup('database', $this->ownerUser);
        }

        $backupService->applyRetentionPolicy();

        $safetyBackup->refresh();
        $this->assertEquals('completed', $safetyBackup->status);
        $this->assertTrue($safetyBackup->is_pre_restore);
    }

    public function test_scheduler_executes_scheduled_backup_in_process_without_child_php_binary(): void
    {
        $nowStr = now('Asia/Jakarta')->format('H:i');
        AppSetting::set('backup_scheduled_enabled', true, 'boolean', true);
        AppSetting::set('backup_scheduled_time', $nowStr, 'string', true);
        AppSetting::set('backup_scheduled_type', 'database', 'string', true);

        $this->artisan('schedule:run')->assertExitCode(0);

        $this->assertEquals(1, BackupRecord::count());
        $record = BackupRecord::first();
        $this->assertNull($record->created_by);
        $this->assertEquals('completed', $record->status);
    }
}
