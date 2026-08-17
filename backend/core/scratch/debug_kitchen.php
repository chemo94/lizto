<?php
// Quick debug script for kitchen route
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->bootstrapWith([
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
    \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
    \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
    \Illuminate\Foundation\Bootstrap\BootProviders::class,
]);
$request = Illuminate\Http\Request::create('/admin/kitchen', 'GET');
$app->instance('request', $request);
auth()->guard('admin')->login(App\Models\Admin::first());
try {
    $response = $kernel->handle($request);
    echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
    if ($response->getStatusCode() >= 400) {
        echo substr(strip_tags($response->getContent()), 0, 3000);
    } else {
        echo 'OK - Page loaded successfully';
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString();
}
