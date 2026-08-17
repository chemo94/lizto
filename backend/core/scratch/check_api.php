<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::first();
$gateway = App\Models\Gateway::where('alias', 'MercadoPago')->first();

if (!$user || !$gateway) {
    echo "Missing user or gateway\n";
    exit;
}

$order = new App\Models\DeliveryOrder();
$order->total = 100.00;
$order->order_no = 'DEL-TEST-123';

$param       = json_decode($gateway->gateway_parameters);
$accessToken = $param->access_token->value ?? ($param->access_token ?? '');
$publicKey   = $param->public_key->value   ?? ($param->public_key   ?? '');

echo "accessToken: '$accessToken'\n";
echo "publicKey: '$publicKey'\n";

if (!$accessToken || !$publicKey) {
    echo "Fails check: accessToken or publicKey is empty/falsy\n";
}

$currency     = $gateway->singleCurrency;
if (!$currency) {
    echo "Fails: singleCurrency is null\n";
} else {
    echo "currency: " . $currency->currency . "\n";
}

if ($accessToken && $publicKey && $currency) {
    $pct          = max(0, (float) ($currency->percent_charge ?? 0));
    $fix          = max(0, (float) ($currency->fixed_charge  ?? 0));
    $baseTotal    = (float) $order->total;
    $totalWithFee = $pct >= 100
        ? $baseTotal + $fix
        : round(($baseTotal + $fix) / (1 - ($pct / 100)), 2);

    $data = [
        'checkout_api'       => true,
        'public_key'         => $publicKey,
        'order_no'           => $order->order_no,
        'amount'             => $totalWithFee,
        'base_amount'        => $baseTotal,
        'gateway_fee'        => round($totalWithFee - $baseTotal, 2),
        'currency'           => $currency->currency ?? 'PEN',
        'payer_email'        => $user->email ?? '',
        'description'        => 'Pedido ' . $order->order_no . ' - ' . ($order->store->name ?? ''),
        'notification_url'   => url('/api/ipn/wallet-mercadopago'),
        'gateway_percent'    => $pct,
        'gateway_fixed'      => $fix,
    ];

    print_r($data);
} else {
    echo "Result will be null\n";
}
