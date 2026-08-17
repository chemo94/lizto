<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ref = new ReflectionClass(\Greenter\Model\Sale\Invoice::class);
foreach ($ref->getMethods() as $method) {
    if (str_starts_with($method->getName(), 'set')) {
        echo " - " . $method->getName() . "\n";
    }
}
