<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gs = \Illuminate\Support\Facades\DB::table('general_settings')->first();
echo "sms_template before: " . $gs->sms_template . "\n";

$data = (array) $gs;
$data['sms_template'] = '{{message}}';
unset($data['id']);

\Illuminate\Support\Facades\DB::table('general_settings')->update($data);

$gs2 = \Illuminate\Support\Facades\DB::table('general_settings')->first();
echo "sms_template after: " . $gs2->sms_template . "\n";
echo "DONE\n";
