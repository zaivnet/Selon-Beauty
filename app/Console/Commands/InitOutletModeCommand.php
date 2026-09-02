<?php

namespace App\Console\Commands;

use App\Services\OutletModeService;
use Illuminate\Console\Command;

class InitOutletModeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-outlet-mode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Explicitly and idempotently initialize the application outlet_mode setting';

    /**
     * Execute the console command.
     */
    public function handle(OutletModeService $service): int
    {
        $details = [];
        $mode = $service->initializeIfMissing($details);

        if ($details['status'] === 'no_active_outlets') {
            $this->warn($details['message']);
            return Command::FAILURE;
        }

        if ($details['status'] === 'already_configured') {
            $this->info($details['message']);
            return Command::SUCCESS;
        }

        $this->info($details['message']);

        return Command::SUCCESS;
    }
}
