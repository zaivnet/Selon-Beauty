<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceParticipationChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public bool $enabled) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $targetUrl = in_array($notifiable->role, ['superadmin', 'owner', 'admin'], true)
            ? route('admin.dashboard')
            : route('employee.dashboard');

        return [
            'type' => $this->enabled ? 'attendance_participation_enabled' : 'attendance_participation_disabled',
            'title' => 'Status Sistem Kehadiran Diperbarui',
            'message' => $this->enabled
                ? 'Anda sekarang terdaftar dalam sistem kehadiran.'
                : 'Status sistem kehadiran Anda dinonaktifkan. Anda tidak lagi diwajibkan mengikuti jadwal dan absensi.',
            'target_url' => $targetUrl,
            'icon' => $this->enabled ? 'check-circle' : 'info',
        ];
    }
}
