<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$tables = ['pos_orders', 'pos_cash_sessions', 'pos_expenses', 'pos_order_items'];
foreach ($tables as $t) {
    echo "$t columns:\n";
    print_r(Schema::getColumnListing($t));
    echo "\n";
}
