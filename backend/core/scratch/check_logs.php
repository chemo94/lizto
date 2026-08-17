<?php
use App\Models\NotificationLog;
use App\Models\Driver;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Recent Notification Logs ---\n";
$logs = NotificationLog::orderBy('id', 'desc')->take(10)->get();
foreach ($logs as $log) {
    echo "ID: " . $log->id . " | Driver ID: " . $log->driver_id . " | Rider ID: " . $log->user_id . " | Method: " . $log->notification_type . " | Status: " . ($log->status ? 'Sent' : 'Failed') . " | To: " . $log->sent_to . "\n";
}

$riderId = 10;
echo "\n--- Logs for Rider ID $riderId ---\n";
$logs = NotificationLog::where('user_id', $riderId)->orderBy('id', 'desc')->get();
if ($logs->isEmpty()) {
    echo "No logs found for Rider ID $riderId\n";
} else {
    foreach ($logs as $log) {
        echo "ID: " . $log->id . " | Method: " . $log->notification_type . " | Status: " . ($log->status ? 'Sent' : 'Failed') . " | To: " . $log->sent_to . "\n";
    }
}

use App\Models\User;
$rider = User::find(10);
if ($rider) {
    echo "\nRider ID 10 exists: " . $rider->username . "\n";
} else {
    echo "\nRider ID 10 NOT FOUND\n";
}
