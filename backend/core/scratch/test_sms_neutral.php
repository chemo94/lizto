<?php
use App\Models\NotificationTemplate;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = NotificationTemplate::where('act', 'SVER_CODE')->first();
if ($template) {
    echo "sms_body before: " . $template->sms_body . "\n";
    $template->sms_body = '{{code}}';
    $template->save();
    echo "sms_body after: " . $template->sms_body . "\n";
    echo "DONE - message will just be the code number\n";
}
