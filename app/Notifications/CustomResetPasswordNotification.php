<?php

namespace App\Notifications;

use App\Services\BrandingService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $brandingService = app(BrandingService::class);
        $appName = $brandingService->getAppName() ?: config('app.name');

        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset Password — ' . $appName)
            ->greeting('Halo, ' . ($notifiable->name ?? 'Pengguna'))
            ->line('Kami menerima permintaan untuk mengatur ulang password akun Anda di ' . $appName . '.')
            ->action('Reset Password', $resetUrl)
            ->line('Link reset password ini memiliki masa berlaku 60 menit dan hanya dapat digunakan satu kali.')
            ->line('Jika Anda tidak meminta perubahan password, abaikan email ini secara aman.')
            ->salutation('Salam, ' . $appName);
    }
}
