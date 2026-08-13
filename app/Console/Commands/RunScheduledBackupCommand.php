<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\BackupRecord;
use App\Models\User;
use App\Notifications\ScheduledBackupFailedNotification;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-scheduled-backup {--force : Force execution ignoring time matching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan scheduled backup otomatis berdasarkan konfigurasi di AppSetting';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $enabled = AppSetting::get('backup_scheduled_enabled', false);
        $isForced = $this->option('force');

        if (! $enabled && ! $isForced) {
            $this->info('Scheduled backup tidak aktif (disabled).');

            return Command::SUCCESS;
        }

        $now = Carbon::now('Asia/Jakarta');
        $frequency = AppSetting::get('backup_scheduled_frequency', 'daily');
        $targetTime = AppSetting::get('backup_scheduled_time', '02:00');
        $targetDay = (int) AppSetting::get('backup_scheduled_day', 0); // 0 = Sunday, 1 = Monday, etc.
        $type = AppSetting::get('backup_scheduled_type', 'full');

        // Time Matching Check (unless forced)
        if (! $isForced) {
            $currentTimeStr = $now->format('H:i');
            if ($currentTimeStr !== $targetTime) {
                $this->info("Waktu saat ini ({$currentTimeStr}) belum sesuai jam jadwal ({$targetTime}). Skipped.");

                return Command::SUCCESS;
            }

            if ($frequency === 'weekly' && (int) $now->dayOfWeek !== $targetDay) {
                $this->info("Hari saat ini ({$now->format('l')}) belum sesuai hari jadwal ({$targetDay}). Skipped.");

                return Command::SUCCESS;
            }
        }

        // Duplicate Run Protection: Check if scheduled backup was already created today
        $todayStart = (clone $now)->startOfDay();
        $todayEnd = (clone $now)->endOfDay();

        $alreadyRun = BackupRecord::whereNull('created_by')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->exists();

        if ($alreadyRun && ! $isForced) {
            $this->info("Scheduled backup untuk hari ini ({$now->toDateString()}) sudah pernah dibuat. Skipped.");

            return Command::SUCCESS;
        }

        $this->info("Memulai pembuatan Scheduled Backup ({$type})...");

        try {
            $record = $backupService->createBackup($type, user: null);
            $deletedCount = $backupService->applyRetentionPolicy();

            $this->info("Scheduled backup berhasil dibuat: {$record->backup_uuid} (File Size: {$record->file_size} bytes). Excess backups deleted: {$deletedCount}");
            Log::info("Scheduled backup success: {$record->backup_uuid}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal menjalankan scheduled backup: {$e->getMessage()}");
            Log::error('Scheduled backup command failed.', [
                'exception' => $e,
            ]);

            try {
                User::whereIn('role', ['superadmin', 'owner'])
                    ->where('is_active', true)
                    ->each(fn (User $user) => $user->notify(new ScheduledBackupFailedNotification($e->getMessage())));
            } catch (\Throwable $notificationException) {
                Log::error('Scheduled backup failure notification could not be sent.', [
                    'primary_exception' => $e->getMessage(),
                    'notification_exception' => $notificationException,
                ]);
            }

            return Command::FAILURE;
        }
    }
}
