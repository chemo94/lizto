<?php
use App\Models\GeneralSetting;
use App\Models\NotificationTemplate;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gs = GeneralSetting::first();
echo "SMS Notification Enabled (gs('sn')): " . ($gs->sn ? 'Yes' : 'No') . "\n";
echo "SMS Config: " . json_encode($gs->sms_config) . "\n";

$template = NotificationTemplate::where('act', 'SVER_CODE')->first();
if ($template) {
    echo "SVER_CODE Template SMS Status: " . ($template->sms_status ? 'Enabled' : 'Disabled') . "\n";
    echo "SVER_CODE Template SMS Body: " . $template->sms_body . "\n";
} else {
    echo "SVER_CODE Template NOT FOUND\n";
}
