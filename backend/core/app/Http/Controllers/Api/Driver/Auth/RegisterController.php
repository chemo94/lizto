<?php

namespace App\Http\Controllers\Api\Driver\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\DeviceToken;
use App\Models\Driver;
use App\Models\UserLogin;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;


    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }
        $agree = 'nullable';
        if (gs('agree')) {
            $agree = 'required';
        }

        $dialCode = preg_replace('/\D+/', '', (string) ($data['dial_code'] ?? '51'));
        $validate = Validator::make($data, [
            'firstname'    => 'required|string|max:40',
            'lastname'     => 'required|string|max:40',
            'email'        => 'required|string|email|unique:drivers',
            'mobile'       => [
                'required',
                'regex:/^([0-9]*)$/',
                Rule::unique('drivers', 'mobile')->where('dial_code', $dialCode),
            ],
            'password'     => ['required', 'confirmed', $passwordValidation],
            'service_type' => 'nullable|in:ride,rider,delivery,both',
            'device_token' => 'nullable|string|max:500',
            'agree'        => $agree,
        ], [
            'firstname.required' => 'El nombre es obligatorio',
            'lastname.required'  => 'El apellido es obligatorio',
            'mobile.required'    => 'El número de celular es obligatorio',
            'mobile.unique'      => 'Este número de celular ya está registrado como conductor',
            'email.unique'       => 'Este correo electrónico ya está registrado',
        ]);

        return $validate;
    }


    public function register(Request $request)
    {
        if (!gs('driver_registration')) {
            $notify[] = 'Registration not allowed';
            return apiResponse("registration_disabled", "error", $notify);
        }

        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        if ($request->filled('invite_code')) {
            $fleet = \App\Models\Fleet::where('invite_code', $request->invite_code)->first();
            if (!$fleet) {
                return apiResponse("validation_error", "error", ["El código de invitación de la flota no es válido"]);
            }
        }

        $driver = $this->create($request->all());

        if ($request->filled('invite_code')) {
            $fleet = \App\Models\Fleet::where('invite_code', $request->invite_code)->first();
            if ($fleet) {
                $driver->update(['fleet_id' => $fleet->id]);
            }
        }

        $deviceToken = $request->device_token ?? $request->fcm_token;
        if ($deviceToken) {
            DeviceToken::where('driver_id', $driver->id)->where('token', '!=', $deviceToken)->delete();
            DeviceToken::updateOrCreate(
                ['token' => $deviceToken],
                ['driver_id' => $driver->id, 'user_id' => null, 'seller_id' => null, 'is_app' => Status::YES, 'app_type' => 'driver']
            );
        }

        $data['access_token'] = $driver->createToken('driver_token')->plainTextToken;
        $data['driver']       = $driver;
        $data['user']         = $driver; // compatibility for mobile app consumers
        $data['token_type']   = 'Bearer';
        $data['image_path']   = getFilePath('driver');
        $notify[]             = 'Registration successful';

        return apiResponse("registration_success", "success", $notify,  $data);
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \App\User
     */
    protected function create(array $data)
    {

        $driver            = new Driver();
        $driver->firstname = $data['firstname'];
        $driver->lastname  = $data['lastname'];
        $driver->email     = strtolower($data['email']);
        $driver->service_type = ($data['service_type'] ?? '') === 'rider' ? 'ride' : ($data['service_type'] ?? 'ride');
        $driver->password  = Hash::make($data['password']);
        $driver->ev        = gs('ev') ? Status::UNVERIFIED : Status::VERIFIED;

        if (!empty($data['mobile'])) {
            $driver->mobile    = preg_replace('/\D+/', '', (string) $data['mobile']);
            $driver->dial_code = preg_replace('/\D+/', '', (string) ($data['dial_code'] ?? '51'));
        }

        // Si viene con phone_token de WhatsApp verificado
        $isPhoneVerified = false;
        if (!empty($data['phone_token'])) {
            $tokenPayload = \App\Services\WhatsAppOtpService::verifyPhoneToken($data['phone_token']);
            if ($tokenPayload && ($tokenPayload['verified'] ?? false)) {
                $isPhoneVerified = true;
            }
        }

        $driver->sv = $isPhoneVerified ? Status::VERIFIED : (gs('sv') ? Status::UNVERIFIED : Status::VERIFIED);
        $driver->ts = Status::DISABLE;
        $driver->tv = Status::VERIFIED;

        // Auto-generar username único si no viene especificado
        if (!empty($data['username'])) {
            $driver->username = trim($data['username']);
        } else {
            $baseUsername = strtolower(preg_replace('/[^a-z0-9]/', '', $data['firstname'] . '.' . $data['lastname']));
            if (strlen($baseUsername) < 6) {
                $cleanEmail = preg_replace('/[^a-z0-9]/', '', strtolower(explode('@', $data['email'])[0]));
                $baseUsername = strlen($cleanEmail) >= 6 ? $cleanEmail : 'driver' . rand(10000, 99999);
            }
            $username = $baseUsername;
            $counter = 1;
            while (Driver::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }
            $driver->username = $username;
        }

        $driver->save();


        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = 0;
        $adminNotification->driver_id = $driver->id;
        $adminNotification->title     = 'New driver registered';
        $adminNotification->click_url = urlPath('admin.driver.detail', $driver->id);
        $adminNotification->save();


        //Login Log Create
        $ip        = getRealIP();
        $exist     = UserLogin::where('user_ip', $ip)->where('driver_id', $driver->id)->first();
        $driverLogin = new UserLogin();

        //Check exist or not
        if ($exist) {
            $driverLogin->longitude    = $exist->longitude;
            $driverLogin->latitude     = $exist->latitude;
            $driverLogin->city         = $exist->city;
            $driverLogin->country_code = $exist->country_code;
            $driverLogin->country      = $exist->country;
        } else {
            $info                    = json_decode(json_encode(getIpInfo()), true);
            $driverLogin->longitude    = @implode(',', $info['long']);
            $driverLogin->latitude     = @implode(',', $info['lat']);
            $driverLogin->city         = @implode(',', $info['city']);
            $driverLogin->country_code = @implode(',', $info['code']);
            $driverLogin->country      = @implode(',', $info['country']);
        }

        $driverAgent            = osBrowser();
        $driverLogin->driver_id = $driver->id;
        $driverLogin->user_ip   = $ip;

        $driverLogin->browser = @$driverAgent['browser'];
        $driverLogin->os      = @$driverAgent['os_platform'];
        $driverLogin->save();

        $driver = Driver::find($driver->id);

        return $driver;
    }
}
