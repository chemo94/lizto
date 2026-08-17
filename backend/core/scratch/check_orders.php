<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\PosOrder;
echo "Total orders count: " . PosOrder::count() . "\n";
$latest = PosOrder::latest()->limit(10)->get();
foreach ($latest as $o) {
    echo "ID: {$o->id} | Order No: {$o->order_no} | Series: {$o->invoice_series} | Number: {$o->invoice_number} | Status: {$o->payment_status}\n";
}
