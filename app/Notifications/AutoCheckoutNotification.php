<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AutoCheckoutNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $shiftName,
        public string $checkoutTime,
        public string $targetUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'attendance_auto_checkout',
            'title' => 'Checkout Otomatis',
            'message' => "Sistem melakukan checkout otomatis pada {$this->checkoutTime} karena Anda belum melakukan checkout setelah {$this->shiftName} berakhir.",
            'target_url' => $this->targetUrl,
            'icon' => 'clock',
        ];
    }
}
