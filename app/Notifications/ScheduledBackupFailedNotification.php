<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ScheduledBackupFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $errorMessage) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'scheduled_backup_failed',
            'title' => 'Scheduled Backup Gagal',
            'message' => 'Scheduled backup gagal dibuat. Periksa log aplikasi untuk root cause.',
            'error' => mb_strimwidth($this->errorMessage, 0, 500, '...'),
            'target_url' => route('admin.settings.backups.index'),
            'icon' => 'alert-triangle',
        ];
    }
}
