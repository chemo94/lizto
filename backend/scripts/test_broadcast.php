<?php
require_once __DIR__.'/../core/vendor/autoload.php';
$app = require_once __DIR__.'/../core/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliveryOrder;
use App\Events\DeliveryOrderStatusUpdated;

$order = DeliveryOrder::find(1);
echo "Order #{$order->id}, status={$order->status}\n";

// Use event() which dispatches ShouldBroadcastNow synchronously
$start = microtime(true);
event(new DeliveryOrderStatusUpdated($order));
$elapsed = round((microtime(true) - $start) * 1000);
echo "event() completed in {$elapsed}ms\n";

// Also try direct broadcaster
echo "Testing direct broadcaster...\n";
$broadcaster = Illuminate\Support\Facades\Broadcast::driver('reverb');
$broadcaster->broadcast(
    ['private-delivery-order.4'],
    'test_from_script',
    ['msg' => 'Direct from PHP script', 'order_id' => 1]
);
echo "Direct broadcast sent\n";
