<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();



$base = 3;
$rate = 0.85;
$min = 4;
$distance = 1.37;

$recommend = max($base + ($rate * $distance), $min);
echo "Base: $base, Rate: $rate, Min: $min, Dist: $distance\n";
echo "Formula: $base + ($rate * $distance) = " . ($base + ($rate * $distance)) . "\n";
echo "Max with Min Trip: " . $recommend . "\n";
echo "Rounded: " . getFareAmount($recommend) . "\n";

$distance2 = 4.34;
$recommend2 = max($base + ($rate * $distance2), $min);
echo "\nDist: $distance2\n";
echo "Formula: $base + ($rate * $distance2) = " . ($base + ($rate * $distance2)) . "\n";
echo "Rounded: " . getFareAmount($recommend2) . "\n";
