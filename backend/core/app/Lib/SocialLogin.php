<?php

namespace App\Lib;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\DeviceToken;
use App\Models\Driver;
use App\Models\Seller;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Socialite;
use Illuminate\Support\Facades\Config;

class SocialLogin
{
    private $guard;
    private $provider;

    public function __construct($guard = 'user', $provider = "google")
    {
        $this->guard    = $guard;
        $this->provider = $provider;
        $credentials    = gs('socialite_credentials');
        $config         = @$credentials->{$provider} ?? null;
        Config::set('services.' . $provider, [
            'client_id'     => @$config->client_id ?? '',
            'client_secret' => @$config->client_secret ?? '',
            'redirect'      => "#",
        ]);
    }

    public function login()
    {
        if ($this->guard == "user") {
            if (!gs('registration')) {
                $notify[] = 'New account registration is currently disabled';
                return apiResponse('restricted', 'error', $notify);
            }
        } elseif ($this->guard == "seller") {
            // Seller registration / login is allowed
        } else {
            if (!gs('driver_registration')) {
                $notify[] = 'New account registration is currently disabled';
                return apiResponse('restricted', 'error', $notify);
            }
        }

        $provider = $this->provider;

        if ($provider == 'apple') {
            try {
                $user = getUserFromApple(request()->token);
            } catch (\Throwable $th) {
                $notify[] = "Something went wrong";
                return apiResponse('exception', 'error', $notify);
            }
        } else {
            $googleToken = request()->token;
            // Si viene code en vez de token (Google Identity Services), intercambiarlo
            if (!$googleToken && request()->code) {
                $googleToken = $this->exchangeCodeForToken(request()->code);
                if (!$googleToken) {
                    $notify[] = "No se pudo verificar con Google";
                    return apiResponse('exception', 'error', $notify);
                }
            }

            $user = null;
            // 1. Try Socialite with access_token
            if ($googleToken) {
                try {
                    $driver = Socialite::driver($provider);
                    $user = (object)$driver->userFromToken($googleToken)->user;
                } catch (\Throwable $th) {
                    // 2. If Socialite fails, verify as Google ID token (JWT)
                    $user = $this->getUserFromGoogleIdToken($googleToken);
                }
            }

            if (!$user || empty($user->email)) {
                $notify[] = "No se pudo verificar la cuenta de Google";
                return apiResponse('exception', 'error', $notify);
            }
        }

        if ($this->guard == "user") {
            $userData = User::where('provider_id', $user->id)->first();
            if (!$userData && !empty($user->email)) {
                $userData = User::where('email', $user->email)->first();
                if ($userData) {
                    $userData->provider_id = $user->id;
                    $userData->provider = $provider;
                    $userData->save();
                }
            }
        } elseif ($this->guard == "seller") {
            $userData = null;
            if (Schema::hasColumn('sellers', 'provider_id')) {
                $userData = Seller::where('provider_id', $user->id)->first();
            }
            if (!$userData && !empty($user->email)) {
                $userData = Seller::where('email', $user->email)->first();
                if ($userData) {
                    if (Schema::hasColumn('sellers', 'provider_id')) {
                        $userData->provider_id = $user->id;
                    }
                    if (Schema::hasColumn('sellers', 'provider')) {
                        $userData->provider = $provider;
                    }
                    $userData->save();
                }
            }
        } else {
            $userData = Driver::where('provider_id', $user->id)->first();
            if (!$userData && !empty($user->email)) {
                $userData = Driver::where('email', $user->email)->first();
                if ($userData) {
                    $userData->provider_id = $user->id;
                    $userData->provider = $provider;
                    $userData->save();
                }
            }
        }

        if (!$userData) {
            if ($this->guard == "user") {
                $emailExists = User::where('email', @$user->email)->exists();
            } elseif ($this->guard == "seller") {
                $emailExists = Seller::where('email', @$user->email)->exists();
            } else {
                $emailExists = Driver::where('email', @$user->email)->exists();
            }

            if ($emailExists) {
                $notify[] = 'Email already exists';
                return apiResponse('email_exists', 'error', $notify);
            }

            $userData = $this->createUser($user, $provider);
        }

        if ($this->guard == "user") {
            $tokenResult = $userData->createToken('auth_token')->plainTextToken;
            $userKeyName = "user";
        } elseif ($this->guard == "seller") {
            if (!$userData->status) {
                $notify[] = 'Tu cuenta está desactivada';
                return apiResponse('inactive', 'error', $notify);
            }
            $tokenResult = $userData->createToken('seller_token')->plainTextToken;
            $userKeyName = "seller";
        } else {
            $tokenResult = $userData->createToken('driver_token')->plainTextToken;
            $userKeyName = "driver";
        }

        $this->loginLog($userData);

        $deviceToken = request()->device_token ?? request()->fcm_token ?? request()->deviceToken ?? request()->fcmToken ?? request()->header('device-token') ?? request()->header('x-device-token');
        if ($deviceToken) {
            if ($this->guard === 'user') {
                DeviceToken::where('user_id', $userData->id)->where('token', '!=', $deviceToken)->delete();
                DeviceToken::updateOrCreate(
                    ['token' => $deviceToken],
                    ['user_id' => $userData->id, 'driver_id' => null, 'seller_id' => null, 'pos_staff_id' => null, 'is_app' => Status::YES, 'app_type' => 'passenger']
                );
            } elseif ($this->guard === 'seller') {
                DeviceToken::where('seller_id', $userData->id)->whereNull('pos_staff_id')->where('token', '!=', $deviceToken)->delete();
                DeviceToken::updateOrCreate(
                    ['token' => $deviceToken],
                    ['seller_id' => $userData->id, 'pos_staff_id' => null, 'user_id' => null, 'driver_id' => null, 'is_app' => Status::YES, 'app_type' => 'seller']
                );
            } else {
                DeviceToken::where('driver_id', $userData->id)->where('token', '!=', $deviceToken)->delete();
                DeviceToken::updateOrCreate(
                    ['token' => $deviceToken],
                    ['driver_id' => $userData->id, 'user_id' => null, 'seller_id' => null, 'pos_staff_id' => null, 'is_app' => Status::YES, 'app_type' => 'driver']
                );
            }
        }

        $response[] = 'Login Successful';
        return apiResponse("login_success", "success", $response, [
            $userKeyName   => $userData,
            'access_token' => $tokenResult,
            'token'        => $tokenResult,
            'token_type'   => 'Bearer'
        ]);
    }

    private function createUser($user, $provider)
    {
        $password = getTrx(8);

        $firstName = null;
        $lastName = null;

        if (@$user->first_name) {
            $firstName = $user->first_name;
        }
        if (@$user->last_name) {
            $lastName = $user->last_name;
        }

        if ((!$firstName || !$lastName) && @$user->name) {
            $firstName = preg_replace('/\W\w+\s*(\W*)$/', '$1', $user->name);
            $pieces    = explode(' ', $user->name);
            $lastName  = array_pop($pieces);
        }

        if ($this->guard == "user") {
            $newUser = new User();
        } elseif ($this->guard == "seller") {
            $newUser = new Seller();
            $fullName = trim(($firstName ? $firstName . ' ' : '') . ($lastName ?? ''));
            if (empty($fullName)) {
                $fullName = @$user->name ?: explode('@', $user->email)[0];
            }
            $newUser->name = $fullName;
            $newUser->email = $user->email;
            $newUser->password = Hash::make($password);
            $newUser->status = 1;
            $newUser->is_verified = 1;
            if (Schema::hasColumn('sellers', 'provider_id')) {
                $newUser->provider_id = $user->id;
            }
            if (Schema::hasColumn('sellers', 'provider')) {
                $newUser->provider = $provider;
            }
            $newUser->save();

            // Create default wallet for seller if wallet model exists
            try {
                if (class_exists('\App\Models\Wallet')) {
                    $wallet = \App\Models\Wallet::firstOrCreate(
                        ['holder_type' => Seller::class, 'holder_id' => $newUser->id],
                        ['balance' => 0]
                    );
                    if (Schema::hasColumn('sellers', 'wallet_id') && !$newUser->wallet_id) {
                        $newUser->wallet_id = $wallet->id;
                        $newUser->save();
                    }
                }
            } catch (\Throwable $e) {}

            return $newUser;
        } else {
            $newUser = new Driver();
            $svc = request()->service_type ?? 'ride';
            $newUser->service_type = $svc === 'rider' ? 'ride' : $svc;
        }

        $newUser->provider_id = $user->id;
        $newUser->email       = $user->email;

        $newUser->password  = Hash::make($password);
        $newUser->firstname = $firstName;
        $newUser->lastname  = $lastName;

        $newUser->status   = Status::VERIFIED;
        $newUser->ev       = Status::VERIFIED;
        $newUser->sv       = gs('sv') ? Status::UNVERIFIED : Status::VERIFIED;
        $newUser->ts       = Status::DISABLE;
        $newUser->tv       = Status::VERIFIED;
        $newUser->provider = $provider;
        $newUser->save();

        $adminNotification          = new AdminNotification();
        $adminNotification->user_id = $newUser->id;

        if ($this->guard == "user") {
            $adminNotification->title     = 'New rider registered';
            $adminNotification->click_url = urlPath('admin.rider.detail', $newUser->id);
            $user = User::find($newUser->id);
        } else {
            $adminNotification->title     = 'New driver registered';
            $adminNotification->click_url = urlPath('admin.driver.detail', $newUser->id);
            $user = Driver::find($newUser->id);
        }
        $adminNotification->save();

        return $user;
    }

    private function getUserFromGoogleIdToken($idToken)
    {
        if (!$idToken) return null;

        // 1. Try Google's tokeninfo endpoint
        try {
            $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (!empty($data['sub']) && !empty($data['email'])) {
                    return (object)[
                        'id'         => $data['sub'],
                        'email'      => $data['email'],
                        'name'       => $data['name'] ?? '',
                        'first_name' => $data['given_name'] ?? '',
                        'last_name'  => $data['family_name'] ?? '',
                        'picture'    => $data['picture'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // 2. Fallback: decode JWT payload directly
        try {
            $parts = explode('.', $idToken);
            if (count($parts) === 3) {
                $payloadJson = base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT));
                $payload = json_decode($payloadJson, true);
                if (!empty($payload['sub']) && !empty($payload['email'])) {
                    return (object)[
                        'id'         => $payload['sub'],
                        'email'      => $payload['email'],
                        'name'       => $payload['name'] ?? '',
                        'first_name' => $payload['given_name'] ?? '',
                        'last_name'  => $payload['family_name'] ?? '',
                        'picture'    => $payload['picture'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        return null;
    }

    private function exchangeCodeForToken($code)
    {
        $config = gs('socialite_credentials');
        $googleConfig = @$config->google;
        $clientId = @$googleConfig->client_id ?? '';
        $clientSecret = @$googleConfig->client_secret ?? '';

        if (!$clientId || !$clientSecret) return null;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => 'postmessage',
                'grant_type' => 'authorization_code',
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['id_token'] ?? ($data['access_token'] ?? null);
    }

    private function loginLog($user)
    {
        //Login Log Create
        $ip = getRealIP();
        if ($this->guard == "user") {
            $exist = UserLogin::where('user_ip', $ip)->where('user_id', $user->id)->first();
        } elseif ($this->guard == "seller") {
            // Seller login logging
            return;
        } else {
            $exist = UserLogin::where('user_ip', $ip)->where('driver_id', $user->id)->first();
        }

        $userLogin = new UserLogin();

        //Check exist or not
        if ($exist) {
            $userLogin->longitude    = $exist->longitude;
            $userLogin->latitude     = $exist->latitude;
            $userLogin->city         = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country      = $exist->country;
        } else {
            $info                    = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude    = @implode(',', $info['long']);
            $userLogin->latitude     = @implode(',', $info['lat']);
            $userLogin->city         = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country      = @implode(',', $info['country']);
        }

        $userAgent = osBrowser();
        if ($this->guard == "user") {
            $userLogin->user_id = $user->id;
        } else {
            $userLogin->driver_id = $user->id;
        }

        $userLogin->user_ip =  $ip;
        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os = @$userAgent['os_platform'];
        $userLogin->save();
    }
}

