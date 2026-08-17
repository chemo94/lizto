<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$gw = App\Models\Gateway::where('alias', 'MercadoPago')->first();
if ($gw) {
    echo "GATEWAY PARAMETERS:\n";
    var_dump($gw->gateway_parameters);
} else {
    echo "MercadoPago Gateway not found\n";
}
