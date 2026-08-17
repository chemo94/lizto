<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StorePackage;

class CheckPackageExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and expire subscription packages when they reach their expires_at date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking expired packages...');

        $expiredCount = StorePackage::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Successfully expired {$expiredCount} packages.");
    }
}
