<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\SocialLogin;
use App\Models\DeviceToken;
use App\Models\UserLogin;
use App\Models\User;
use App\Services\FirebasePhoneAuthService;
use Throwable;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    protected $username;

    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function __construct()
    {
        $this->username = $this->findUsername();
    }

    public function login(Request $request)
    {
        $validator = $this->validateLogin($request);
        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        $credentials = request([$this->username, 'password']);

        if (!Auth::attempt(array_merge($credentials, ['is_deleted' => Status::NO]))) {
            $response[] = 'The provided credentials can not match our record';
            return apiResponse("invalid_credential", "error", $response);
        }

        $user        = $request->user();
        $tokenResult = $user->createToken('auth_token', ['user'])->plainTextToken;
        $this->authenticated($request, $user);

        $deviceToken = $request->device_token ?? $request->fcm_token;
        if ($deviceToken) {
            DeviceToken::where('user_id', $user->id)->where('token', '!=', $deviceToken)->delete();
            DeviceToken::updateOrCreate(
                ['token' => $deviceToken],
                ['user_id' => $user->id, 'driver_id' => null, 'seller_id' => null, 'is_app' => Status::YES, 'app_type' => 'passenger']
            );
        }

        $response[] = 'Login Successful';

        return apiResponse("login_success", "success", $response, [
            'user'         => auth()->user(),
            'access_token' => $tokenResult,
            'token_type'   => 'Bearer'
        ]);
    }

    public function findUsername()
    {
        $login     = request()->input('username');
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : (User::where('mobile', $login)->exists() ? 'mobile' : 'username');
        request()->merge([$fieldType => $login]);
        return $fieldType;
    }

    public function username()
    {
        return $this->username;
    }

    protected function validateLogin(Request $request):object
    {
        $validationRule = [
            $this->username() => 'required|string',
            'password'        => 'required|string',
        ];
        $validate = Validator::make($request->all(), $validationRule);
        return $validate;
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        $notify[] = 'Logout Successful';
        return apiResponse("logout", "success", $notify);
    }

    public function registerDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $user = auth()->user();
        $deviceToken = $request->token;

        DeviceToken::where('user_id', $user->id)->where('token', '!=', $deviceToken)->delete();
        DeviceToken::updateOrCreate(
            ['token' => $deviceToken],
            ['user_id' => $user->id, 'driver_id' => null, 'seller_id' => null, 'is_app' => Status::YES, 'app_type' => 'passenger']
        );

        return apiResponse('token_saved', 'success', ['Token registrado']);
    }

    public function authenticated(Request $request, $user)
    {
        $user->tv = $user->ts == Status::VERIFIED ? Status::UNVERIFIED : Status::VERIFIED;
        $user->save();
        $ip        = getRealIP();
        $exist     = UserLogin::where('user_ip', $ip)->first();
        $userLogin = new UserLogin();
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

        $userAgent          = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os      = @$userAgent['os_platform'];
        $userLogin->save();
    }

    public function checkToken(Request $request)
    {
        $validationRule = [
            'token' => 'required',
        ];

        $validator = Validator::make($request->all(), $validationRule);
        
        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        $accessToken = PersonalAccessToken::findToken($request->token);

        if ($accessToken) {
            $notify[]      = 'Token exists';
            $data['token'] = $request->token;
            return apiResponse("token_exists", "success", $notify, $data);
        }

        $notify[] = 'Token doesn\'t exists';
        return apiResponse("token_not_exists", "error", $notify);
    }

    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:google,apple,phone',
            'token'    => 'required_without:code',
            'code'     => 'required_without:token',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        if ($request->provider === 'phone') {
            try {
                $user = app(FirebasePhoneAuthService::class)->findAccount($request->token, User::class);
            } catch (Throwable $exception) {
                report($exception);
                return apiResponse('firebase_token_invalid', 'error', ['No se pudo validar el teléfono con Firebase']);
            }
            if (!$user || $user->is_deleted == Status::YES) {
                return apiResponse('phone_account_not_found', 'error', ['No existe una cuenta con este número celular']);
            }
            $token = $user->createToken('auth_token', ['user'])->plainTextToken;
            $this->storePhoneDeviceToken($request, $user, 'passenger');
            return apiResponse('login_success', 'success', ['Inicio de sesión exitoso'], ['user' => $user, 'access_token' => $token, 'token_type' => 'Bearer']);
        }

        $socialLogin = new SocialLogin("user",$request->provider);
        return $socialLogin->login();
    }

    private function storePhoneDeviceToken(Request $request, User $user, string $appType): void
    {
        $deviceToken = $request->device_token ?? $request->fcm_token;
        if (!$deviceToken) return;
        DeviceToken::updateOrCreate(['token' => $deviceToken], ['user_id' => $user->id, 'driver_id' => null, 'seller_id' => null, 'is_app' => Status::YES, 'app_type' => $appType]);
    }
}
