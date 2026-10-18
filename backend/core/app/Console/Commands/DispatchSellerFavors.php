<?php

namespace App\Console\Commands;

use App\Services\SellerFavorDispatchService;
use Illuminate\Console\Command;

class DispatchSellerFavors extends Command
{
    protected $signature = 'delivery:dispatch-seller-favors';
    protected $description = 'Rotates seller favor requests between nearby and expanded-range couriers';

    public function handle(): int
    {
        $favors = SellerFavorDispatchService::processDueRequests();
        $orders = \App\Services\SellerDeliveryOrderDispatchService::processDueOrders();
        $this->info("Processed {$favors} seller favor request(s) and {$orders} delivery order(s).");
        return self::SUCCESS;
    }
}
