<?php

namespace App\Console\Commands;

use App\Services\AdminDeliveryRequestDispatchService;
use Illuminate\Console\Command;

class RotateAdminDeliveryRequests extends Command
{
    protected $signature = 'delivery:rotate-admin-requests';
    protected $description = 'Rotates unanswered admin delivery requests between available couriers';

    public function handle(): int
    {
        $count = AdminDeliveryRequestDispatchService::processDueRequests();
        $this->info("Processed {$count} due admin delivery request(s).");

        return self::SUCCESS;
    }
}
