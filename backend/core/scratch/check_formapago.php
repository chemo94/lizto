<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ref = new ReflectionClass(\Greenter\Model\Sale\FormaPago\FormaPagoContado::class);
echo "Class exists: " . $ref->getName() . "\n";
