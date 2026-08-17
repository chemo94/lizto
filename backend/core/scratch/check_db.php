<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "DB Host: " . env('DB_HOST') . "\n";
echo "DB Port: " . env('DB_PORT') . "\n";
echo "DB Database: " . env('DB_DATABASE') . "\n";
echo "DB Username: " . env('DB_USERNAME') . "\n";
echo "Config DB Database: " . config('database.connections.mysql.database') . "\n";
