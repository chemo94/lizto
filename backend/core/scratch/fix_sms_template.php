<?php
use App\Models\GeneralSetting;
use App\Models\NotificationTemplate;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gs = GeneralSetting::first();

echo "=== GLOBAL SMS TEMPLATE ===\n";
echo "before: " . $gs->sms_template . "\n\n";

echo "=== SVER_CODE ===\n";
$sver = NotificationTemplate::where('act', 'SVER_CODE')->first();
if ($sver) {
    echo "sms_body (before): " . $sver->sms_body . " (len=" . strlen($sver->sms_body) . ")\n";
    $sver->sms_body = 'Tu codigo de verificacion es {{code}}';
    $sver->push_body = 'Tu codigo de verificacion es {{code}}';
    $sver->save();
    echo "sms_body (after):  " . $sver->sms_body . " (len=" . strlen($sver->sms_body) . ")\n\n";
}

echo "=== EVER_CODE ===\n";
$ever = NotificationTemplate::where('act', 'EVER_CODE')->first();
if ($ever) {
    echo "sms_body (before): " . $ever->sms_body . " (len=" . strlen($ever->sms_body) . ")\n";
    $ever->sms_body = 'Tu codigo de verificacion es {{code}}';
    $ever->push_body = 'Tu codigo de verificacion es {{code}}';
    $ever->save();
    echo "sms_body (after):  " . $ever->sms_body . " (len=" . strlen($ever->sms_body) . ")\n\n";
}

echo "=== UPDATE GLOBAL SMS TEMPLATE ===\n";
$gs->sms_template = '{{message}}';
$gs->save();
echo "sms_template (after): " . $gs->sms_template . "\n\n";

echo "DONE - Final message will be: 'Tu codigo de verificacion es XXXXXX'\n";

