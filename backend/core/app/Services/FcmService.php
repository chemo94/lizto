<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public static function courierJobPayload($job, array $extra = []): array
    {
        $isDelivery = $job instanceof \App\Models\DeliveryOrder;
        $baseFee = (float) ($job->delivery_fee ?? 0);
        $payableAmount = $isDelivery ? $baseFee : (float) ($job->total ?? $baseFee);

        return array_merge([
            'type'             => 'new_delivery_request',
            'job_type'         => $isDelivery ? 'delivery' : 'favor',
            'job_id'           => (string) $job->id,
            'favor_id'         => $isDelivery ? '' : (string) $job->id,
            'order_id'         => (string) $job->id,
            'order_no'         => (string) ($job->order_no ?? $job->id),
            'store_name'       => (string) ($job->store_name ?? ($isDelivery ? $job->store?->name : ($job->seller?->name ?? 'Lizto'))),
            'customer_name'    => (string) ($job->recipient_name ?? $job->user?->fullname ?? 'Cliente'),
            'customer_phone'   => (string) ($job->recipient_phone ?? $job->user?->mobile ?? ''),
            'pickup_address'   => (string) ($job->pickup_address ?? ($isDelivery ? $job->store?->address : '')),
            'pickup_lat'       => (string) ($job->pickup_lat ?? ($isDelivery ? $job->store?->latitude : '')),
            'pickup_lng'       => (string) ($job->pickup_lng ?? ($isDelivery ? $job->store?->longitude : '')),
            'delivery_address' => (string) ($job->delivery_address ?? ($isDelivery ? $job->shipping_address : '')),
            'delivery_lat'     => (string) ($job->delivery_lat ?? ($isDelivery ? $job->latitude : '')),
            'delivery_lng'     => (string) ($job->delivery_lng ?? ($isDelivery ? $job->longitude : '')),
            'delivery_fee'     => (string) $payableAmount,
            'base_delivery_fee'=> (string) $baseFee,
            'additional_charge'=> (string) ($isDelivery ? 0 : ($job->estimated_amount ?? 0)),
            'total'            => (string) ($job->total ?? $payableAmount),
            'total_earning'    => (string) $payableAmount,
            'description'      => (string) ($job->description ?? $job->notes ?? ''),
            'for_app'          => 'courier_job_detail-' . $job->id,
            'click_action'     => 'FLUTTER_NOTIFICATION_CLICK',
        ], $extra);
    }

    public static function sendToUser($user, string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::where('user_id', $user->id)->pluck('token')->toArray();
        Log::info('FCM sendToUser', ['user_id' => $user->id, 'tokens_count' => count($tokens), 'title' => $title]);
        return self::send($tokens, $title, $body, $data);
    }

    public static function sendToSeller($seller, string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::where('seller_id', $seller->id)->pluck('token')->toArray();
        Log::info('FCM sendToSeller', ['seller_id' => $seller->id, 'tokens_count' => count($tokens), 'title' => $title]);
        return self::send($tokens, $title, $body, $data);
    }

    public static function sendToAdmin(string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::whereNotNull('admin_id')->pluck('token')->toArray();
        Log::info('FCM sendToAdmin', ['tokens_count' => count($tokens), 'title' => $title]);
        return self::send($tokens, $title, $body, $data);
    }

    public static function sendToDriver($driver, string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::where('driver_id', $driver->id)->pluck('token')->toArray();
        Log::info('FCM sendToDriver', ['driver_id' => $driver->id, 'tokens_count' => count($tokens), 'title' => $title]);
        return self::send($tokens, $title, $body, $data);
    }

    public static function sendToDrivers(array $driverIds, string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::whereIn('driver_id', $driverIds)->pluck('token')->toArray();
        Log::info('FCM sendToDrivers', ['driver_ids' => $driverIds, 'tokens_count' => count($tokens), 'title' => $title]);
        return self::send($tokens, $title, $body, $data);
    }

    public static function sendToAllUsers(string $title, string $body, array $data = [])
    {
        $tokens = DeviceToken::whereNotNull('user_id')->pluck('token')->toArray();
        Log::info('FCM sendToAllUsers', ['tokens_count' => count($tokens), 'title' => $title]);
        return self::send($tokens, $title, $body, $data);
    }

    public static function sendToAllCouriers(string $title, string $body, array $data = [])
    {
        $drivers = \App\Models\Driver::whereIn('service_type', ['delivery', 'both'])
            ->where('status', 1)->get();

        $count = 0;
        $sentCount = 0;
        foreach ($drivers as $driver) {
            if (self::sendToDriver($driver, $title, $body, $data)) {
                $sentCount++;
            }
            $count++;
        }
        Log::info('FCM sendToAllCouriers', ['drivers_count' => $count, 'sent_count' => $sentCount, 'title' => $title]);
        return $sentCount > 0;
    }

    private static function send(array $tokens, string $title, string $body, array $data = [])
    {
        if (empty($tokens)) {
            Log::warning('FCM: No tokens provided', ['title' => $title]);
            return false;
        }

        if (!gs('pn')) {
            Log::warning('FCM: Push notifications disabled (gs.pn = false)', ['title' => $title]);
            return false;
        }

        try {
            $credentialsFilePath = getFilePath('pushConfig') . '/push_config.json';
            if (!file_exists($credentialsFilePath)) {
                $altPath = base_path('../' . getFilePath('pushConfig') . '/push_config.json');
                if (file_exists($altPath)) {
                    $credentialsFilePath = $altPath;
                } else {
                    $altPath2 = public_path(getFilePath('pushConfig') . '/push_config.json');
                    if (file_exists($altPath2)) {
                        $credentialsFilePath = $altPath2;
                    }
                }
            }

            if (!file_exists($credentialsFilePath)) {
                Log::error('FCM: push_config.json not found', ['path' => $credentialsFilePath]);
                return false;
            }

            $guzzleClient = new \GuzzleHttp\Client(['verify' => false]);
            $client = new \Google_Client();
            $client->setHttpClient($guzzleClient);
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $token = $client->fetchAccessTokenWithAssertion($guzzleClient);
            $access_token = $token['access_token'] ?? null;
            if (!$access_token) {
                Log::error('FCM: Failed to fetch access token', ['token_response' => $token]);
                return false;
            }

            $projectId = gs('firebase_config')->projectId ?? null;
            if (!$projectId) {
                Log::error('FCM: Firebase project ID not configured in general settings');
                return false;
            }

            $headers = [
                "Authorization: Bearer $access_token",
                'Content-Type: application/json'
            ];

            $successCount = 0;
            $failCount = 0;

            foreach (array_unique($tokens) as $deviceToken) {
                // FCM data values must be strings. Normalizing here keeps the
                // payload accepted for every event producer.
                $normalizedData = collect($data)->map(fn ($value) => is_scalar($value) || $value === null
                    ? (string) ($value ?? '')
                    : json_encode($value))->all();
                $message = [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => $normalizedData,
                    'android' => [
                        'priority'     => 'high',
                        'notification' => [
                            'channel_id' => 'high_importance_channel',
                            'sound'       => 'default',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound'            => 'default',
                                'content-available' => 1,
                            ],
                        ],
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                    ],
                ];

                $payloadData['message'] = $message;
                $payload = json_encode($payloadData);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                $result = json_decode($response, true);

                if ($httpCode === 200 && !isset($result['error'])) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errorMsg = $result['error']['message'] ?? $curlError ?? 'Unknown error';
                    Log::warning('FCM: Token failed', [
                        'token_prefix' => substr($deviceToken, 0, 20) . '...',
                        'http_code' => $httpCode,
                        'error' => $errorMsg,
                    ]);
                    if (self::isInvalidTokenResponse($result)) {
                        DeviceToken::where('token', $deviceToken)->delete();
                        Log::info('FCM: Invalid device token removed', [
                            'token_prefix' => substr($deviceToken, 0, 20) . '...',
                        ]);
                    }
                }
            }

            Log::info('FCM: Send complete', [
                'title' => $title,
                'total' => count($tokens),
                'success' => $successCount,
                'failed' => $failCount,
            ]);

            return $successCount > 0;
        } catch (\Exception $e) {
            Log::error('FCM: Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'title' => $title,
            ]);
            return false;
        }
    }

    private static function isInvalidTokenResponse(?array $result): bool
    {
        $error = $result['error'] ?? [];
        $status = strtoupper((string) ($error['status'] ?? ''));
        $message = strtoupper((string) ($error['message'] ?? ''));

        return in_array($status, ['NOT_FOUND', 'UNREGISTERED'], true)
            || str_contains($message, 'UNREGISTERED')
            || str_contains($message, 'REGISTRATION TOKEN IS NOT A VALID FCM REGISTRATION TOKEN');
    }
}
