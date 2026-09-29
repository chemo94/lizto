<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Coupon;
use App\Models\DeviceToken;
use App\Models\Driver;
use App\Models\Favor;
use App\Models\GatewayCurrency;
use App\Models\NotificationLog;
use App\Models\Review;
use App\Models\Ride;
use App\Models\RidePayment;
use App\Models\Service;
use App\Models\Transaction;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    public function dashboard()
    {
        $notify[]    = 'User Dashboard';
        $services    = Service::active()->orderBy('name')->get();
        $user        = auth()->user();
        $runningRide = Ride::running()->where('user_id', $user->id)->with(['user', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year'])->first();

        $paymentMethod = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->active();
        })->with('method')->orderby('method_code')->get();

        $banners         = Banner::active()->forTaxi()->orderBy('sort_order')->get();
        $deliveryBanners = Banner::active()->forDelivery()->orderBy('sort_order')->get();
        $taxiCoupon      = Coupon::active()->forTaxi()->orderBy('id', 'desc')->first();
        $deliveryCoupon  = Coupon::active()->forDelivery()->orderBy('id', 'desc')->first();

        $recentDestinations = Ride::where('user_id', $user->id)
            ->whereIn('status', [Status::RIDE_COMPLETED, Status::RIDE_END])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get(['destination', 'destination_latitude', 'destination_longitude'])
            ->unique('destination')
            ->take(3)
            ->values();

        return apiResponse("dashboard", "success", $notify, [
            'user'                => $user,
            'payment_method'      => $paymentMethod,
            'services'            => $services,
            'running_ride'        => $runningRide,
            'banners'             => $banners,          // tipo: taxi
            'delivery_banners'    => $deliveryBanners,  // tipo: delivery
            'coupon'              => $taxiCoupon,       // fallback para compatibilidad
            'taxi_coupon'         => $taxiCoupon,
            'delivery_coupon'     => $deliveryCoupon,
            'recent_destinations' => $recentDestinations,
            'service_image_path'  => getFilePath('service'),
            'gateway_image_path'  => getFilePath('gateway'),
            'user_image_path'     => getFilePath('user'),
            'banner_image_path'   => getFilePath('banner'),
        ]);
    }

    public function userDataSubmit(Request $request)
    {
        $user = auth()->user();

        if ($user->profile_complete == Status::YES) {
            $notify[] = 'You\'ve already completed your profile';
            return apiResponse("already_completed", "error", $notify);
        }

        $hasMobile = !empty($user->mobile) && $user->mobile !== 'null';
        $hasUsername = !empty($user->username) && $user->username !== 'null';

        $rules = [
            'country_code' => 'nullable|string|max:10',
            'country'      => 'nullable|string|max:50',
            'mobile_code'  => 'nullable|string|max:10',
            'username'     => [
                $hasUsername ? 'nullable' : 'required',
                'min:3',
                Rule::unique('users')->ignore($user->id),
            ],
            'mobile'       => [
                $hasMobile ? 'nullable' : 'required',
                'regex:/^([0-9]*)$/',
                Rule::unique('users')->where('dial_code', $request->mobile_code ?? $user->dial_code)->ignore($user->id),
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        if ($request->filled('username') && $request->username !== 'null') {
            if (preg_match("/[^a-z0-9_]/", trim($request->username))) {
                $notify[] = 'No special character, space or capital letters in username';
                return apiResponse("validation_error", "error", $notify);
            }
            $user->username = trim($request->username);
        } elseif (empty($user->username) || $user->username === 'null') {
            $base = strtolower(preg_replace('/[^a-z0-9]/', '', $user->firstname ?? 'user'));
            if (empty($base)) $base = 'user';
            $gen = $base . '_' . rand(1000, 9999);
            while (User::where('username', $gen)->where('id', '!=', $user->id)->exists()) {
                $gen = $base . '_' . rand(10000, 99999);
            }
            $user->username = $gen;
        }

        if ($request->filled('mobile') && $request->mobile !== 'null') {
            $user->mobile = preg_replace('/\D+/', '', (string) $request->mobile);
        }
        if ($request->filled('mobile_code') && $request->mobile_code !== 'null') {
            $user->dial_code = preg_replace('/\D+/', '', (string) $request->mobile_code);
        }
        if ($request->filled('country_code') && $request->country_code !== 'null') {
            $user->country_code = $request->country_code;
        }
        if ($request->filled('country') && $request->country !== 'null') {
            $user->country_name = $request->country;
        }

        $user->address      = ($request->address !== 'null') ? ($request->address ?? $user->address) : $user->address;
        $user->city         = ($request->city !== 'null') ? ($request->city ?? $user->city) : $user->city;
        $user->state        = ($request->state !== 'null') ? ($request->state ?? $user->state) : $user->state;
        $user->zip          = ($request->zip !== 'null') ? ($request->zip ?? $user->zip) : $user->zip;

        $user->profile_complete = Status::YES;
        $user->save();

        $notify[] = 'Profile completed successfully';

        return apiResponse("profile_completed", "success", $notify, [
            'user' => $user
        ]);
    }

    public function paymentHistory()
    {
        $payments = RidePayment::where('rider_id', auth()->id())->orderBy('id', 'desc')->with('rider', 'ride', 'driver')->paginate(getPaginate());
        $notify[] = 'Payment Data';
        return apiResponse("payments", "success", $notify, [
            'payments' => $payments,
        ]);
    }

    public function transactions(Request $request)
    {
        $remarks      = Transaction::distinct('remark')->get('remark');
        $transactions = Transaction::where('user_id', auth()->id());

        if ($request->search) {
            $transactions = $transactions->where('trx', $request->search);
        }

        if ($request->type) {
            $type         = $request->type == 'plus' ? '+' : '-';
            $transactions = $transactions->where('trx_type', $type);
        }

        if ($request->remark) {
            $transactions = $transactions->where('remark', $request->remark);
        }

        $transactions = $transactions->orderBy('id', 'desc')->paginate(getPaginate());
        $notify[]     = 'Transactions data';

        return apiResponse("transactions", "success", $notify, [
            'transactions' => $transactions,
            'remarks'      => $remarks,
        ]);
    }

    public function submitProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname'  => 'required',
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
        ], [
            'firstname.required' => 'The first name field is required',
            'lastname.required'  => 'The last name field is required'
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }



        $user            = auth()->user();
        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;
        $user->address   = $request->address;
        $user->city      = $request->city;
        $user->state     = $request->state;
        $user->zip       = $request->zip;

        if ($request->hasFile('image')) {
            try {
                $user->image = fileUploader($request->image, getFilePath('user'), getFileSize('user'), $user->image);
            } catch (\Exception $exp) {
                $notify[] = 'Couldn\'t upload your image';
                return apiResponse('exception', 'error', $notify);
            }
        }

        $user->save();

        $notify[] = 'Profile updated successfully';

        return apiResponse("profile_updated", "success", $notify);
    }

    public function submitPassword(Request $request)
    {
        $passwordValidation = Password::min(6);
        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', $passwordValidation]
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        $user = auth()->user();
        if (Hash::check($request->current_password, $user->password)) {
            $password       = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = 'Password changed successfully';
            return apiResponse("password_changed", "success", $notify);
        } else {
            $notify[] = 'The password doesn\'t match!';
            return apiResponse("not_match", "validation_error", $notify);
        }
    }

    public function addDeviceToken(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);
        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }

        $user = auth()->user();

        DeviceToken::where('user_id', $user->id)->where('token', '!=', $request->token)->delete();
        DeviceToken::updateOrCreate(
            ['token' => $request->token],
            ['user_id' => $user->id, 'driver_id' => null, 'seller_id' => null, 'is_app' => Status::YES]
        );

        $notify[] = 'Token saved successfully';
        return apiResponse("token_saved", "success", $notify);
    }



    public function pushNotifications()
    {
        $notifications = NotificationLog::where('user_id', auth()->id())->where('sender', 'firebase')->orderBy('id', 'desc')->paginate(getPaginate());
        $notify[]      = 'Push notifications';
        return apiResponse("notifications", "success", $notify, [
            'notifications' => $notifications,
        ]);
    }


    public function pushNotificationsRead($id)
    {
        $notification = NotificationLog::where('user_id', auth()->id())->where('sender', 'firebase')->find($id);
        if (!$notification) {
            $notify[] = 'Notification not found';
            return apiResponse("notification_not_found", "error", $notify);
        }
        $notify[]                = 'Notification marked as read successfully';
        $notification->user_read = 1;
        $notification->save();

        return apiResponse("notification_read", "success", $notify);
    }


    public function userInfo()
    {
        $user = auth()->user();
        if ($user) {
            if (empty($user->username) || $user->username === 'null') {
                $base = strtolower(preg_replace('/[^a-z0-9]/', '', $user->firstname ?? 'user'));
                if (empty($base) && !empty($user->email)) {
                    $base = strtolower(preg_replace('/[^a-z0-9]/', '', explode('@', $user->email)[0]));
                }
                if (empty($base)) $base = 'user';
                $gen = $base . '_' . rand(1000, 9999);
                while (User::where('username', $gen)->where('id', '!=', $user->id)->exists()) {
                    $gen = $base . '_' . rand(10000, 99999);
                }
                $user->username = $gen;
                $user->save();
            }
            if ($user->mobile === 'null') {
                $user->mobile = null;
                $user->save();
            }
        }

        $notify[] = 'User information';
        return apiResponse("user_info", "success", $notify, [
            'user'       => $user,
            'image_path' => getFilePath('user')
        ]);
    }

    public function deleteAccount()
    {
        $user             = auth()->user();
        $user->is_deleted = Status::YES;
        $user->save();

        $user->tokens()->delete();

        $notify[] = 'Account deleted successfully';
        return apiResponse("account_deleted", "success", $notify);
    }

    public function pusher($socketId, $channelName)
    {
        $user = auth()->user();
        if ($user instanceof \App\Models\Seller) {
            $allowed = $channelName === "private-seller.$user->id"
                || $channelName === "private-seller-$user->id";

            if (!$allowed && str_starts_with($channelName, 'private-favor.')) {
                $favorId = (int) str_replace('private-favor.', '', $channelName);
                $allowed = Favor::where('id', $favorId)->where('seller_id', $user->id)->exists();
            }
        } else {
            $allowed = $channelName === "private-rider-user-$user->id"
                || $channelName === "private-delivery-order.$user->id"
                || $channelName === "private-favor-customer.$user->id"
                || $channelName === "private-nearby-drivers";

            if (!$allowed && str_starts_with($channelName, 'private-ride-location-')) {
                $rideId = (int) str_replace('private-ride-location-', '', $channelName);
                $allowed = Ride::where('id', $rideId)->where('user_id', $user->id)->exists();
            }

            if (!$allowed && str_starts_with($channelName, 'private-favor.')) {
                $favorId = (int) str_replace('private-favor.', '', $channelName);
                $allowed = Favor::where('id', $favorId)->where('user_id', $user->id)->exists();
            }
        }

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reverbSecret = config('reverb.apps.apps.0.secret');
        $reverbKey    = config('reverb.apps.apps.0.key');
        $str          = $socketId . ":" . $channelName;
        $hash         = hash_hmac('sha256', $str, $reverbSecret);

        return response()->json([
            'auth' => $reverbKey . ":" . $hash,
        ]);
    }

    public function review($driverId)
    {
        $notify[] = 'Driver Review List';
        return apiResponse("review", "success", $notify, [
            'user_image_path'   => getFilePath('user'),
            'driver_image_path' => getFilePath('driver'),
            "reviews"           => Review::with("user")->latest('id')->where('driver_id', $driverId)->get()
        ]);
    }

    public function nearbyDrivers(Request $request)
    {
        $lat       = (float) $request->lat;
        $lng       = (float) $request->lng;
        $radius    = (float) ($request->radius ?? 10);
        $serviceId = $request->filled('service_id') ? (int) $request->service_id : null;

        $drivers = \App\Services\H3\DriverGeoRedisService::findNearby(
            lat: $lat,
            lng: $lng,
            radiusKm: $radius,
            serviceId: $serviceId,
            serviceType: ['ride', 'both'],
            limit: 30,
            fallbackToDb: true
        );

        $originH3 = \App\Services\H3\H3Grid::geoToH3($lat, $lng, 8);

        $data = [
            'h3_cell'           => $originH3,
            'driver_image_path' => getFilePath('driver'),
            'drivers'           => $drivers->map(function ($driver) {
                return [
                    'id'           => $driver['id'],
                    'firstname'    => $driver['firstname'],
                    'lastname'     => $driver['lastname'],
                    'latitude'     => $driver['latitude'],
                    'longitude'    => $driver['longitude'],
                    'bearing'      => $driver['bearing'] ?? 0,
                    'service_name' => $driver['service_name'] ?? null,
                    'distance_km'  => round($driver['distance_km'], 2),
                    'image'        => $driver['image'] ?? null,
                    'h3'           => $driver['h3'] ?? null,
                ];
            })->values(),
        ];

        $notify[] = 'Nearby drivers';

        return apiResponse("nearby_drivers", "success", $notify, $data);
    }
}
