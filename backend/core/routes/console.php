<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('sunat:process-invoices')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('packages:check-expiration')->hourly()->withoutOverlapping();
Schedule::command('delivery:rotate-admin-requests')->everyThirtySeconds()->withoutOverlapping();
Schedule::command('delivery:dispatch-seller-favors')->everyFifteenSeconds()->withoutOverlapping();
