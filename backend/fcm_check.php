<?php

// Quick diagnostic via artisan
// Chdir into core dir because artisan needs it
chdir(__DIR__ . '/core');
require __DIR__ . '/core/vendor/autoload.php';

$app = require_once __DIR__ . '/core/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FCM DIAGNOSTICO ===\n";

echo "PN (push notifications): " . (gs('pn') ? 'ACTIVADO' : 'DESACTIVADO') . "\n";

$fc = gs('firebase_config');
echo "Firebase config: " . ($fc ? json_encode($fc) : 'NO CONFIGURADO') . "\n";

$path = getFilePath('pushConfig') . '/push_config.json';
echo "push_config.json path: " . $path . "\n";
echo "push_config.json exists: " . (file_exists($path) ? 'SI' : 'NO') . "\n";

echo "\nTotal device tokens: " . \App\Models\DeviceToken::count() . "\n";
echo "  - users:   " . \App\Models\DeviceToken::whereNotNull('user_id')->count() . "\n";
echo "  - sellers: " . \App\Models\DeviceToken::whereNotNull('seller_id')->count() . "\n";
echo "  - drivers: " . \App\Models\DeviceToken::whereNotNull('driver_id')->count() . "\n";

echo "\nActive users: " . \App\Models\User::where('status', 1)->count() . "\n";

// Show first user with a token
$token = \App\Models\DeviceToken::whereNotNull('user_id')->first();
echo "\nFirst user token: " . ($token ? 'user_id=' . $token->user_id . ' token=' . substr($token->token, 0, 40) . '... app_type=' . $token->app_type : 'NONE') . "\n";

// Try sending
if ($token) {
    $user = \App\Models\User::find($token->user_id);
    if ($user && gs('pn')) {
        try {
            $r = \App\Services\FcmService::sendToUser($user, 'Test', 'Diagnostico FCM', ['type' => 'test']);
            echo "\nTest envio a user #" . $user->id . ": " . ($r ? 'EXITO' : 'FALLO (verificar push_config.json y credenciales)') . "\n";
        } catch (\Exception $e) {
            echo "\nEXCEPCION: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\nNo se pudo probar: " . (!$user ? 'usuario no encontrado' : 'PN desactivado') . "\n";
    }
}
