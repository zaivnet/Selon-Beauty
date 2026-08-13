<?php

namespace App\Console\Commands;

use App\Services\BrandingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'selon:test-mail {email : Recipient email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tes pengiriman email SMTP secara aman tanpa membocorkan kredensial';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = $this->argument('email');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tidak valid: ' . $recipient);
            return self::FAILURE;
        }

        $brandingService = app(BrandingService::class);
        $branding = $brandingService->getBranding();
        $appName = $branding['app_name'] ?? config('app.name', 'SELON BEAUTY');

        $this->info("--- Testing SMTP Delivery for [{$appName}] ---");
        $this->info("Recipient : {$recipient}");
        $this->info("Mailer    : " . config('mail.default', 'smtp'));
        $this->info("Host      : " . config('mail.mailers.smtp.host', 'unconfigured'));
        $this->info("Port      : " . config('mail.mailers.smtp.port', 'unconfigured'));
        $this->info("From      : " . config('mail.from.address', 'unconfigured'));

        try {
            Mail::raw("Halo!\n\nIni adalah email uji pengiriman SMTP untuk aplikasi {$appName}.\nPengiriman email berhasil disiapkan.\n\nWaktu: " . now()->toIso8601String(), function ($message) use ($recipient, $appName) {
                $message->to($recipient)
                    ->subject("Uji Coba Email SMTP — {$appName}");
            });

            $this->info("BERHASIL! Email tes telah dikirimkan ke [{$recipient}].");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("GAGAL! Terjadi kesalahan saat pengiriman email: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
