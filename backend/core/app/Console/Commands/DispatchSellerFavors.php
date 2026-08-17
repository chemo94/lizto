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
        $this->info('Processed ' . SellerFavorDispatchService::processDueRequests() . ' seller favor request(s).');
        return self::SUCCESS;
    }
}
