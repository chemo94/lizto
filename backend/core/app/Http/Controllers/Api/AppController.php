<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\DeliveryOrder;
use App\Models\DeviceToken;
use App\Models\Favor;
use App\Models\Language;
use App\Models\Ride;
use App\Models\Seller;
use App\Models\Store;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AppController extends Controller
{
    public function generalSetting()
    {
        $notify[]       = 'General setting data';
        $generalSetting = gs();
        $countries      = [];

        foreach ($generalSetting->operating_country as $k => $country) {
            $countries[] = [
                'country'      => $country->country,
                'dial_code'    => $country->dial_code,
                'country_code' => $k,
            ];
        }
        $generalSetting->operating_country = $countries;

        $reverbScheme = env('REVERB_PUBLIC_SCHEME', 'https');
        $reverbConfig = [
            'app_key'  => config('reverb.apps.apps.0.key'),
            'host'     => env('REVERB_PUBLIC_HOST', 'liztodelivery.com'),
            'port'     => (int) env('REVERB_PUBLIC_PORT', 443),
            'scheme'   => $reverbScheme,
            'use_tls'  => $reverbScheme === 'https',
        ];

        return apiResponse("general_setting", "success", $notify, [
            'general_setting'         => $generalSetting,
            'reverb_config'           => (object) $reverbConfig,
            'notification_audio_path' => getFilePath('notification_audio'),
            'banners'                 => Banner::active()->orderBy('sort_order')->get(),
            'banner_image_path'       => getFilePath('banner'),
        ]);
    }

    public function banners()
    {
        $notify[] = 'Banners';
        $banners  = Banner::active()->orderBy('sort_order')->get();

        return apiResponse("banners", "success", $notify, [
            'banners'          => $banners,
            'banner_image_path' => getFilePath('banner'),
        ]);
    }

    public function getCountries()
    {
        $countryData = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $notify[]    = 'Country List';
        $countries   = [];

        foreach ($countryData as $k => $country) {
            $countries[] = [
                'country'      => $country->country,
                'dial_code'    => $country->dial_code,
                'country_code' => $k,
            ];
        }
        return apiResponse("country_data", "success", $notify, [
            'countries' => $countries
        ]);
    }

    public function getLanguage($code)
    {
        $languages     = Language::get();
        $languageCodes = $languages->pluck('code')->toArray();

        if (!in_array($code, $languageCodes)) {
            $notify[] = 'Invalid code given';
            return apiResponse("invalid_code", "error", $notify);
        }

        $jsonFile = file_get_contents(resource_path('lang/' . $code . '.json'));
        $notify[] = 'Language';

        return apiResponse("language", "success", $notify, [
            'languages'  => $languages,
            'file'       => json_decode($jsonFile) ?? [],
            'image_path' => getFilePath('language')
        ]);
    }

    public function policies()
    {
        $policies = getContent('policy_pages.element', orderById: true);
        $notify[] = 'All policies';

        return apiResponse("policy_data", "success", $notify, [
            'policies' => $policies,
        ]);
    }


    public function faq()
    {
        $faq      = getContent('faq.element', orderById: true);
        $notify[] = 'FAQ';
        return apiResponse("faq", "success", $notify, [
            'faq' => $faq,
        ]);
    }

    public function zone()
    {
        $zones    = Zone::searchable(['name'])->active()->orderby('name')->paginate(getPaginate());
        $notify[] = 'Zones';

        return apiResponse("zone", "success", $notify, [
            'zones' => $zones,
        ]);
    }

    public function broadcastingAuth(Request $request)
    {
        $socketId   = $request->input('socket_id');
        $channelName = $request->input('channel_name');

        if (!$socketId || !$channelName) {
            return response()->json(['message' => 'Missing parameters'], 422);
        }

        $user = $this->userFromBearerToken($request);

        // Fallback: check web session guards (admin/seller from browser)
        if (!$user) {
            if (auth()->guard('admin')->check()) {
                $user = auth()->guard('admin')->user();
            } elseif (auth()->guard('seller')->check()) {
                $user = auth()->guard('seller')->user();
            } elseif ($request->session() && $request->session()->has('seller_id')) {
                $user = \App\Models\Seller::find($request->session()->get('seller_id'));
            }
        }

        if (!$user || !$this->canAccessBroadcastChannel($user, $channelName)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reverbSecret = config('reverb.apps.apps.0.secret');
        $reverbKey    = config('reverb.apps.apps.0.key');
        $str          = $socketId . ':' . $channelName;
        $hash         = hash_hmac('sha256', $str, $reverbSecret);

        return response()->json([
            'auth' => $reverbKey . ':' . $hash,
        ]);
    }

    private function userFromBearerToken(Request $request)
    {
        $bearer = $request->bearerToken();
        if (!$bearer) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($bearer);
        return $accessToken?->tokenable;
    }

    private function canAccessBroadcastChannel($user, string $channelName): bool
    {
        if (str_starts_with($channelName, 'private-rider-user-')) {
            return $user instanceof \App\Models\User
                && (int) str_replace('private-rider-user-', '', $channelName) === (int) $user->id;
        }

        if (str_starts_with($channelName, 'private-rider-driver-')) {
            return $user instanceof \App\Models\Driver
                && (int) str_replace('private-rider-driver-', '', $channelName) === (int) $user->id;
        }

        if (str_starts_with($channelName, 'private-ride-location-')) {
            $rideId = (int) str_replace('private-ride-location-', '', $channelName);
            $ride = Ride::find($rideId);
            return $ride
                && (
                    ($user instanceof \App\Models\User && (int) $ride->user_id === (int) $user->id)
                    || ($user instanceof \App\Models\Driver && (int) $ride->driver_id === (int) $user->id)
                );
        }

        if (str_starts_with($channelName, 'private-delivery-order.')) {
            return $user instanceof \App\Models\User
                && (int) str_replace('private-delivery-order.', '', $channelName) === (int) $user->id;
        }

        if (str_starts_with($channelName, 'private-favor.')) {
            $favorId = (int) str_replace('private-favor.', '', $channelName);
            $favor = Favor::find($favorId);
            return $favor && (
                ($user instanceof \App\Models\User && (int) $favor->user_id === (int) $user->id)
                || ($user instanceof \App\Models\Driver && (int) $favor->courier_id === (int) $user->id)
                || ($user instanceof Seller && isset($favor->seller_id) && (int) $favor->seller_id === (int) $user->id)
            );
        }

        if (str_starts_with($channelName, 'private-job.')) {
            $jobId = (int) str_replace('private-job.', '', $channelName);
            // Check DeliveryOrder
            $order = \App\Models\DeliveryOrder::find($jobId);
            if ($order) {
                return (
                    ($user instanceof \App\Models\User && (int) $order->user_id === (int) $user->id)
                    || ($user instanceof \App\Models\Driver && (int) $order->driver_id === (int) $user->id)
                );
            }
            // Check Favor
            $favor = Favor::find($jobId);
            if ($favor) {
                return (
                    ($user instanceof \App\Models\User && (int) $favor->user_id === (int) $user->id)
                    || ($user instanceof \App\Models\Driver && (int) $favor->courier_id === (int) $user->id)
                );
            }
            return false;
        }

        if (str_starts_with($channelName, 'private-favor-customer.')) {
            return $user instanceof \App\Models\User
                && (int) str_replace('private-favor-customer.', '', $channelName) === (int) $user->id;
        }

        if (str_starts_with($channelName, 'private-courier.')) {
            return $user instanceof \App\Models\Driver
                && (int) str_replace('private-courier.', '', $channelName) === (int) $user->id;
        }

        if (str_starts_with($channelName, 'private-seller.')) {
            return $user instanceof Seller
                && (int) str_replace('private-seller.', '', $channelName) === (int) $user->id;
        }

        if ($channelName === 'private-nearby-couriers') {
            return $user instanceof \App\Models\Driver;
        }

        if (str_starts_with($channelName, 'private-tracking.')) {
            $orderId = (int) str_replace('private-tracking.', '', $channelName);
            $order = DeliveryOrder::find($orderId);
            return $order && (int) $user->id === (int) $order->user_id;
        }

        if (str_starts_with($channelName, 'private-store.')) {
            $storeId = (int) str_replace('private-store.', '', $channelName);
            $store = Store::find($storeId);
            return $store && (
                ($user instanceof Seller && (int) $store->seller_id === (int) $user->id)
                || $user instanceof Admin
            );
        }

        if ($channelName === 'private-admin-notifications') {
            return $user instanceof Admin;
        }

        return false;
    }

    public function testBroadcast()
    {
        $order = \App\Models\DeliveryOrder::first();
        if (!$order) return response()->json(['error' => 'No orders found']);

        try {
            broadcast(new \App\Events\DeliveryOrderStatusUpdated($order));
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'status' => $order->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ]);
        }
    }

    public function saveAdminDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $adminId = null;
        $sellerId = null;

        if (auth()->guard('admin')->check()) {
            $adminId = auth()->guard('admin')->id();
        } elseif (auth()->guard('seller')->check()) {
            $sellerId = auth()->guard('seller')->id();
        }

        if (!$adminId && !$sellerId) {
            return apiResponse('unauthorized', 'error', ['No autenticado']);
        }

        // A token can move between accounts (or be refreshed by FCM). Always
        // bind it to the active panel session instead of retaining stale owner
        // columns that would deliver admin/seller notifications to the wrong
        // account.
        DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id'     => null,
                'driver_id'   => null,
                'seller_id'   => $sellerId,
                'admin_id'    => $adminId,
                'pos_staff_id'=> null,
                'is_app'      => 0,
                'app_type'    => $adminId ? 'admin_panel' : 'seller_panel',
            ]
        );

        return apiResponse('token_saved', 'success', ['Token registrado']);
    }
}
