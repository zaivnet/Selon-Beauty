<?php

namespace App\Notifications;

use App\Enums\UserRole;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RoleChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $beforeRole,
        public string $afterRole
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $beforeLabel = UserRole::tryFrom($this->beforeRole)?->label() ?? ucfirst($this->beforeRole);
        $afterLabel = UserRole::tryFrom($this->afterRole)?->label() ?? ucfirst($this->afterRole);

        $actionUrl = in_array($this->afterRole, ['superadmin', 'owner', 'admin'], true)
            ? route('admin.dashboard')
            : route('employee.dashboard');

        return [
            'type' => 'role_changed',
            'title' => 'Hak Akses Akun Diperbarui',
            'message' => "Hak akses akun Anda diubah dari {$beforeLabel} menjadi {$afterLabel}.",
            'before_role' => $this->beforeRole,
            'after_role' => $this->afterRole,
            'target_url' => $actionUrl,
        ];
    }
}
