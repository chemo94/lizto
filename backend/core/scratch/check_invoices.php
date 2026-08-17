<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PosOrder;
use App\Models\Seller;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

echo "Total Sellers: " . Seller::count() . "\n";
echo "Total Stores: " . Store::count() . "\n";
echo "Total PosOrders: " . PosOrder::count() . "\n";

echo "PosOrder status counts:\n";
foreach (PosOrder::groupBy('status')->selectRaw('status, count(*) as count')->get() as $row) {
    echo " - " . ($row->status ?? 'NULL') . ": " . $row->count . "\n";
}

echo "Tables list:\n";
$tables = DB::select('SHOW TABLES');
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    // Check if table contains data
    $count = DB::table($tableName)->count();
    if ($count > 0) {
        echo " - $tableName: $count rows\n";
    }
}
