<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasColumn('seller_companies', 'department')) {
    DB::statement("ALTER TABLE seller_companies ADD department VARCHAR(100) NULL, ADD province VARCHAR(100) NULL, ADD district VARCHAR(100) NULL");
    echo "Columns added successfully!\n";
} else {
    echo "Columns already exist.\n";
}
