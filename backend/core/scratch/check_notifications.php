<?php
use App\Models\GeneralSetting;
use App\Models\NotificationTemplate;
use App\Models\Driver;
use App\Constants\Status;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Public Path: " . public_path() . "\n";
echo "Base Path: " . base_path() . "\n";
echo "Storage Path: " . storage_path() . "\n";

$gs = GeneralSetting::first();
echo "Push Notification Enabled (gs('pn')): " . ($gs->pn ? 'Yes' : 'No') . "\n";

$template = NotificationTemplate::where('act', 'DEFAULT')->first();
if ($template) {
    echo "Default Template Push Status: " . ($template->push_status == Status::ENABLE ? 'Enabled' : 'Disabled') . "\n";
} else {
    echo "Default Template NOT FOUND\n";
}

use App\Models\DeviceToken;
echo "--- All Device Tokens ---\n";
foreach (DeviceToken::all() as $t) {
    echo "ID: " . $t->id . " | User ID: " . $t->user_id . " | Driver ID: " . $t->driver_id . " | Token: " . substr($t->token, 0, 20) . "...\n";
}
