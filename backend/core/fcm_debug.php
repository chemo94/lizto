<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FCM DIAGNOSTICO ===\n";

echo 'PN (Push Notifications): ' . (gs('pn') ? 'ACTIVADO' : 'DESACTIVADO') . "\n";
echo 'Firebase Project ID: ' . (gs('firebase_config')->projectId ?? 'NO CONFIGURADO') . "\n";

$configPath = getFilePath('pushConfig') . '/push_config.json';
echo 'push_config.json: ' . (file_exists($configPath) ? 'EXISTE => ' . $configPath : 'NO EXISTE => ' . $configPath) . "\n";

echo "\n--- Device Tokens ---\n";
$tokens = \App\Models\DeviceToken::all();
echo 'Total tokens: ' . $tokens->count() . "\n";
echo '  users:   ' . $tokens->whereNotNull('user_id')->count() . "\n";
echo '  sellers: ' . $tokens->whereNotNull('seller_id')->count() . "\n";
echo '  drivers: ' . $tokens->whereNotNull('driver_id')->count() . "\n";

echo "\n--- Ultimos 5 tokens ---\n";
foreach ($tokens->take(5) as $t) {
    echo 'ID=' . $t->id . ' user_id=' . ($t->user_id ?? 'NULL') . ' seller_id=' . ($t->seller_id ?? 'NULL') . ' driver_id=' . ($t->driver_id ?? 'NULL') . ' app_type=' . $t->app_type . ' token=' . substr($t->token, 0, 30) . "...\n";
}

echo "\n--- Test envio a 1er user ---\n";
$firstUser = \App\Models\User::where('status', 1)->first();
if ($firstUser) {
    echo 'User #' . $firstUser->id . ' (' . $firstUser->email . ') status=' . $firstUser->status . "\n";
    $hasToken = \App\Models\DeviceToken::where('user_id', $firstUser->id)->exists();
    echo 'Tiene device token: ' . ($hasToken ? 'SI' : 'NO') . "\n";

    if ($hasToken) {
        $result = \App\Services\FcmService::sendToUser($firstUser, 'Test Diagnostico', 'Mensaje de prueba desde liztogo', ['type' => 'test', 'time' => now()->toDateTimeString()]);
        echo 'Resultado FcmService::sendToUser: ' . ($result ? 'EXITO' : 'FALLO') . "\n";
    }
} else {
    echo "No hay usuarios activos\n";
}
