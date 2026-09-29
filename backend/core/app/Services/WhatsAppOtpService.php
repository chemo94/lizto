<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\DeviceToken;
use App\Models\Driver;
use App\Models\Seller;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class WhatsAppOtpService
{
    /**
     * Cache key prefix for OTP records
     */
    const CACHE_PREFIX = 'wa_otp_';

    /**
     * Cache key prefix for rate limiting
     */
    const RATE_PREFIX = 'wa_rate_';

    /**
     * Expiration time for OTP in seconds (5 minutes)
     */
    const OTP_EXPIRY_SECONDS = 300;

    /**
     * Max OTP attempts allowed per session
     */
    const MAX_ATTEMPTS = 4;

    /**
     * Normalize a phone number to E.164 without plus sign (e.g. 51987654321).
     */
    public static function normalizePhone(string $phone, string $dialCode = '51'): string
    {
        $clean = preg_replace('/\D+/', '', $phone) ?? '';
        $cleanDial = preg_replace('/\D+/', '', $dialCode) ?? '51';

        if (empty($clean)) {
            return '';
        }

        // If number starts with 0, strip it (common in local mobile dialing)
        if (str_starts_with($clean, '0')) {
            $clean = substr($clean, 1);
        }

        // If Peru (51) and 9 digits starting with 9, prepend 51
        if ($cleanDial === '51' && strlen($clean) === 9 && str_starts_with($clean, '9')) {
            return '51' . $clean;
        }

        // If not already prefixed with dial code, add it
        if (!str_starts_with($clean, $cleanDial)) {
            $clean = $cleanDial . $clean;
        }

        return $clean;
    }

    /**
     * Send WhatsApp OTP to the specified phone number.
     *
     * @param string $mobile
     * @param string $dialCode
     * @param string $userType 'user' | 'driver' | 'seller'
     * @return array ['success' => bool, 'message' => string, 'data' => ?array]
     */
    public static function sendOtp(string $mobile, string $dialCode = '51', string $userType = 'user'): array
    {
        $normalized = self::normalizePhone($mobile, $dialCode);

        if (empty($normalized) || strlen($normalized) < 8) {
            return [
                'success' => false,
                'message' => 'El número de teléfono ingresado no es válido.',
            ];
        }

        // Rate limiting check: max 4 sends every 10 minutes per phone
        $rateKey = self::RATE_PREFIX . $normalized;
        $sendCount = (int) Cache::get($rateKey, 0);

        if ($sendCount >= 4) {
            return [
                'success' => false,
                'message' => 'Has solicitado demasiados códigos. Por favor espera unos minutos antes de intentar nuevamente.',
            ];
        }

        // Generate 6-digit numeric OTP
        $otpCode = (string) random_int(100000, 999999);

        // Prepare message body
        $appName = config('app.name', 'Lizto');
        $message = "🔐 Tu código de verificación {$appName} es: *{$otpCode}*\n\n" .
                   "Válido por 5 minutos. Por tu seguridad, no compartas este código con nadie.";

        // Store OTP in Cache (valid for 5 minutes)
        $cacheKey = self::CACHE_PREFIX . "{$userType}_{$normalized}";
        Cache::put($cacheKey, [
            'code'       => $otpCode,
            'mobile'     => $mobile,
            'dial_code'  => $dialCode,
            'user_type'  => $userType,
            'attempts'   => 0,
            'created_at' => Carbon::now()->toIso8601String(),
        ], self::OTP_EXPIRY_SECONDS);

        // Send via WhatsApp waapi microservice (previewUrl = false for instant delivery)
        $sendResult = WhatsAppNotificationService::sendTextMessage($normalized, $message, false);

        if (!$sendResult['success']) {
            Log::error("WhatsAppOtpService: Falló el envío a {$normalized}: " . ($sendResult['error'] ?? 'desconocido'));
            return [
                'success' => false,
                'message' => 'No pudimos confirmar la entrega por WhatsApp. Si te llegó el código en tu WhatsApp, puedes ingresarlo.',
            ];
        }

        // Update rate limiter (10 minutes expiry)
        Cache::put($rateKey, $sendCount + 1, 600);

        return [
            'success' => true,
            'message' => 'Código de verificación enviado a tu WhatsApp.',
            'data'    => [
                'phone'             => $normalized,
                'expire_in_seconds' => self::OTP_EXPIRY_SECONDS,
            ],
        ];
    }

    /**
     * Verify OTP code for the given phone.
     *
     * @param string $mobile
     * @param string $otpCode
     * @param string $dialCode
     * @param string $userType 'user' | 'driver' | 'seller'
     * @param string|null $deviceToken
     * @return array
     */
    public static function verifyOtp(
        string $mobile,
        string $otpCode,
        string $dialCode = '51',
        string $userType = 'user',
        ?string $deviceToken = null
    ): array {
        $normalized = self::normalizePhone($mobile, $dialCode);
        $cacheKey = self::CACHE_PREFIX . "{$userType}_{$normalized}";

        $record = Cache::get($cacheKey);

        if (!$record) {
            return [
                'success' => false,
                'message' => 'El código ha expirado o no ha sido solicitado. Solicita uno nuevo.',
            ];
        }

        // Check attempts
        if (($record['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            Cache::forget($cacheKey);
            return [
                'success' => false,
                'message' => 'Has superado el número de intentos permitidos. Solicita un nuevo código.',
            ];
        }

        // Validate code
        if (trim((string) $record['code']) !== trim($otpCode)) {
            $record['attempts'] = ($record['attempts'] ?? 0) + 1;
            Cache::put($cacheKey, $record, self::OTP_EXPIRY_SECONDS);

            $remaining = self::MAX_ATTEMPTS - $record['attempts'];
            return [
                'success' => false,
                'message' => "Código incorrecto. Te quedan {$remaining} intento(s).",
            ];
        }

        // OTP is valid! Clear OTP cache
        Cache::forget($cacheKey);

        // Check if account exists
        $account = self::findAccountByPhone($normalized, $userType);

        if ($account) {
            // Check status / deleted
            if (isset($account->is_deleted) && $account->is_deleted == Status::YES) {
                return [
                    'success' => false,
                    'message' => 'Tu cuenta ha sido desactivada o eliminada. Contacta a soporte.',
                ];
            }

            // Mark mobile/phone as verified since WhatsApp OTP was just completed
            if (isset($account->sv)) {
                $account->sv = Status::VERIFIED;
            }
            if (property_exists($account, 'phone_verified_at') || isset($account->phone_verified_at)) {
                $account->phone_verified_at = now();
            }
            try {
                $account->save();
            } catch (\Throwable $e) {}

            // Existing user -> Generate token & login
            $tokenInfo = self::generateTokenForAccount($account, $userType, $deviceToken);

            return [
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',
                'data'    => [
                    'is_new_user'   => false,
                    'user'          => $account,
                    'driver'        => $userType === 'driver' ? $account : null,
                    'seller'        => $userType === 'seller' ? $account : null,
                    'access_token'  => $tokenInfo['token'],
                    'token'         => $tokenInfo['token'],
                    'token_type'    => 'Bearer',
                ],
            ];
        }

        // New user -> Generate a signed temporary phone verification token
        $phoneToken = self::generatePhoneToken($normalized, $dialCode, $userType);

        // Local 9-digit extract for national phone fields
        $localMobile = substr($normalized, -9);

        return [
            'success' => true,
            'message' => 'Número de WhatsApp verificado exitosamente.',
            'data'    => [
                'is_new_user'   => true,
                'mobile'        => $localMobile,
                'dial_code'     => $dialCode,
                'full_phone'    => $normalized,
                'phone_token'   => $phoneToken,
            ],
        ];
    }

    /**
     * Find existing account across tables according to user type.
     */
    public static function findAccountByPhone(string $normalizedPhone, string $userType): ?object
    {
        $suffix9 = substr($normalizedPhone, -9);

        switch ($userType) {
            case 'driver':
                return Driver::query()
                    ->where(function ($q) use ($normalizedPhone, $suffix9) {
                        $q->where('mobile', $suffix9)
                          ->orWhere('mobile', $normalizedPhone)
                          ->orWhere('mobile', 'like', "%{$suffix9}");
                    })
                    ->first();

            case 'seller':
                return Seller::query()
                    ->where(function ($q) use ($normalizedPhone, $suffix9) {
                        $q->where('phone', $suffix9)
                          ->orWhere('phone', $normalizedPhone)
                          ->orWhere('phone', 'like', "%{$suffix9}");
                    })
                    ->first();

            case 'user':
            default:
                return User::query()
                    ->where(function ($q) use ($normalizedPhone, $suffix9) {
                        $q->where('mobile', $suffix9)
                          ->orWhere('mobile', $normalizedPhone)
                          ->orWhere('mobile', 'like', "%{$suffix9}");
                    })
                    ->first();
        }
    }

    /**
     * Generate Sanctum token and register device token.
     */
    private static function generateTokenForAccount(object $account, string $userType, ?string $deviceToken = null): array
    {
        $tokenName = match ($userType) {
            'driver' => 'driver_token',
            'seller' => 'seller_token',
            default  => 'auth_token',
        };

        $abilities = match ($userType) {
            'driver' => ['driver'],
            'seller' => ['seller'],
            default  => ['user'],
        };

        $plainTextToken = $account->createToken($tokenName, $abilities)->plainTextToken;

        // Register device token for push notifications if provided
        if (!empty($deviceToken)) {
            try {
                match ($userType) {
                    'driver' => DeviceToken::updateOrCreate(
                        ['token' => $deviceToken],
                        ['driver_id' => $account->id, 'user_id' => null, 'seller_id' => null, 'is_app' => Status::YES, 'app_type' => 'driver']
                    ),
                    'seller' => DeviceToken::updateOrCreate(
                        ['token' => $deviceToken],
                        ['seller_id' => $account->id, 'pos_staff_id' => null, 'user_id' => null, 'driver_id' => null, 'is_app' => Status::YES, 'app_type' => 'seller']
                    ),
                    default  => DeviceToken::updateOrCreate(
                        ['token' => $deviceToken],
                        ['user_id' => $account->id, 'driver_id' => null, 'seller_id' => null, 'is_app' => Status::YES, 'app_type' => 'passenger']
                    ),
                };
            } catch (\Throwable $e) {
                Log::warning("WhatsAppOtpService: Error saving device token: " . $e->getMessage());
            }
        }

        return ['token' => $plainTextToken];
    }

    /**
     * Generate temporary signed verification token (valid for 30 minutes).
     */
    public static function generatePhoneToken(string $normalizedPhone, string $dialCode, string $userType): string
    {
        $payload = [
            'phone'      => $normalizedPhone,
            'dial_code'  => $dialCode,
            'user_type'  => $userType,
            'verified'   => true,
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
        ];

        $json = json_encode($payload);
        $signature = hash_hmac('sha256', $json, config('app.key'));

        return base64_encode($json . '||' . $signature);
    }

    /**
     * Verify if a phone token is valid and returns its data.
     */
    public static function verifyPhoneToken(string $token): ?array
    {
        try {
            $decoded = base64_decode($token);
            if (!$decoded || !str_contains($decoded, '||')) {
                return null;
            }

            [$json, $signature] = explode('||', $decoded, 2);
            $expected = hash_hmac('sha256', $json, config('app.key'));

            if (!hash_equals($expected, $signature)) {
                return null;
            }

            $payload = json_decode($json, true);
            if (!is_array($payload) || ($payload['expires_at'] ?? 0) < Carbon::now()->timestamp) {
                return null;
            }

            return $payload;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
