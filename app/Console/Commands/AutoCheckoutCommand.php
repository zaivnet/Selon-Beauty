<?php

namespace App\Console\Commands;

use App\Services\AutoCheckoutService;
use Illuminate\Console\Command;

class AutoCheckoutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-checkout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically check out open attendances that have exceeded shift end plus grace period';

    /**
     * Execute the console command.
     */
    public function handle(AutoCheckoutService $service): int
    {
        try {
            $summary = $service->process();

            $this->info('Auto checkout completed.');
            $this->line("Processed: {$summary['processed']}");
            $this->line("Checked out: {$summary['checked_out']}");
            $this->line("Skipped: {$summary['skipped']}");
            $this->line("Errors: {$summary['errors']}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Auto checkout command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
