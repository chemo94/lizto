<?php

namespace App\Lib;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\DeviceToken;
use App\Models\Driver;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Support\Facades\Hash;
use Socialite;
use Illuminate\Support\Facades\Config;

class SocialLogin
{
    private $guard;
    private $provider;

    public function __construct($guard = 'user',$provider="google")
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
        } else {
            if (!gs('driver_registration')) {
                $notify[] = 'New account registration is currently disabled';
                return apiResponse('restricted', 'error', $notify);
            }
        }

        $provider=$this->provider;

        if($provider ==  'apple'){
            try {
                $user = getUserFromApple(request()->token);
            } catch (\Throwable $th) {
                $notify[] = "Something went wrong";
                return apiResponse('exception', 'error', $notify);
            }

        }else{
            $googleToken = request()->token;
            // Si viene code en vez de token (Google Identity Services), intercambiarlo
            if (!$googleToken && request()->code) {
                $googleToken = $this->exchangeCodeForToken(request()->code);
                if (!$googleToken) {
                    $notify[] = "No se pudo verificar con Google";
                    return apiResponse('exception', 'error', $notify);
                }
            }
            $driver = Socialite::driver($provider);
            try {
                $user = (object)$driver->userFromToken($googleToken)->user;
            } catch (\Throwable $th) {
                $notify[] = "Something went wrong";
                return apiResponse('exception', 'error', $notify);
            }
        }
        

        if ($this->guard == "user") {
            $userData = User::where('provider_id', $user->id)->first();
        } else {
            $userData = Driver::where('provider_id', $user->id)->first();
        }

        if (!$userData) {
            if ($this->guard == "user") {
                $emailExists = User::where('email', @$user->email)->exists();
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
        } else {
            
            $tokenResult = $userData->createToken('driver_token')->plainTextToken;
            $userKeyName = "driver";
        }

        $this->loginLog($userData);

        $deviceToken = request()->device_token ?? request()->fcm_token ?? request()->deviceToken ?? request()->fcmToken ?? request()->header('device-token') ?? request()->header('x-device-token');
        if ($deviceToken) {
            $ownerKey = $this->guard === 'user' ? 'user_id' : 'driver_id';
            DeviceToken::where($ownerKey, $userData->id)->where('token', '!=', $deviceToken)->delete();
            DeviceToken::updateOrCreate(
                ['token' => $deviceToken],
                $this->guard === 'user'
                    ? ['user_id' => $userData->id, 'driver_id' => null, 'seller_id' => null, 'is_app' => Status::YES, 'app_type' => 'passenger']
                    : ['driver_id' => $userData->id, 'user_id' => null, 'seller_id' => null, 'is_app' => Status::YES, 'app_type' => 'driver']
            );
        }

        $response[] = 'Login Successful';
        return apiResponse("login_success", "success", $response, [
            $userKeyName   => $userData,
            'access_token' => $tokenResult,
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
        $ip        = getRealIP();
        if ($this->guard == "user") {
            $exist     = UserLogin::where('user_ip', $ip)->where('user_id', $user->id)->first();
        } else {
            $exist     = UserLogin::where('user_ip', $ip)->where('driver_id', $user->id)->first();
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
