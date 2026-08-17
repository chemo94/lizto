<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\PosStaff;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        // 1. Try seller login
        $seller = Seller::where('email', $request->email)->first();
        if ($seller && Hash::check($request->password, $seller->password)) {
            if (!$seller->status) {
                return apiResponse('inactive', 'error', ['Tu cuenta está desactivada']);
            }

            $token = $seller->createToken('seller_token')->plainTextToken;

            $deviceToken = $request->device_token ?? $request->fcm_token;
            if ($deviceToken) {
                DeviceToken::where('seller_id', $seller->id)->whereNull('pos_staff_id')->where('token', '!=', $deviceToken)->delete();
                DeviceToken::updateOrCreate(
                    ['token' => $deviceToken],
                    ['seller_id' => $seller->id, 'pos_staff_id' => null, 'user_id' => null, 'driver_id' => null, 'is_app' => 1, 'app_type' => 'seller']
                );
            }

            return apiResponse('login_success', 'success', ['Inicio de sesión exitoso'], [
                'seller'     => $seller,
                'token'      => $token,
                'token_type' => 'Bearer',
                'is_staff'   => false,
            ]);
        }

        // 2. Try staff/mozo login
        $staff = PosStaff::where('email', $request->email)->first();
        if ($staff && $staff->password && Hash::check($request->password, $staff->password)) {
            if ($staff->status !== 'active') {
                return apiResponse('inactive', 'error', ['Cuenta de empleado inactiva']);
            }

            $token = $staff->createToken('staff_token')->plainTextToken;

            $deviceToken = $request->device_token ?? $request->fcm_token;
            if ($deviceToken) {
                DeviceToken::where('pos_staff_id', $staff->id)->where('token', '!=', $deviceToken)->delete();
                DeviceToken::updateOrCreate(
                    ['token' => $deviceToken],
                    ['pos_staff_id' => $staff->id, 'seller_id' => $staff->seller_id, 'user_id' => null, 'driver_id' => null, 'is_app' => 1, 'app_type' => 'seller']
                );
            }

            return apiResponse('login_success', 'success', ['Inicio de sesión exitoso'], [
                'seller'     => $staff->seller,
                'token'      => $token,
                'token_type' => 'Bearer',
                'is_staff'   => true,
                'staff'      => [
                    'id'          => $staff->id,
                    'name'        => $staff->name,
                    'email'       => $staff->email,
                    'position'    => $staff->position,
                    'permissions' => $staff->permissions ?? [],
                ],
            ]);
        }

        return apiResponse('login_error', 'error', ['Credenciales incorrectas']);
    }

    public function dashboard()
    {
        $seller = auth()->user();
        $stores = $seller->stores()->withCount('products')->get();
        $totalStores  = $stores->count();
        $totalProducts = $stores->sum('products_count');

        return apiResponse('seller_dashboard', 'success', ['Dashboard'], [
            'seller'         => $seller,
            'stores'         => $stores,
            'total_stores'   => $totalStores,
            'total_products'  => $totalProducts,
            'receivable_balance' => (float) $seller->receivable_balance,
            'store_image_path' => getFilePath('store'),
            'product_image_path' => 'storage',
        ]);
    }

    public function profile()
    {
        $seller = auth()->user();
        return apiResponse('seller_profile', 'success', ['Perfil'], [
            'seller' => $seller,
        ]);
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

        if ($user instanceof \App\Models\PosStaff) {
            DeviceToken::where('pos_staff_id', $user->id)->where('token', '!=', $request->token)->delete();
            DeviceToken::updateOrCreate(
                ['token' => $request->token],
                ['pos_staff_id' => $user->id, 'seller_id' => $user->seller_id, 'user_id' => null, 'driver_id' => null, 'admin_id' => null, 'is_app' => 1, 'app_type' => 'seller']
            );
        } else {
            DeviceToken::where('seller_id', $user->id)->whereNull('pos_staff_id')->where('token', '!=', $request->token)->delete();
            DeviceToken::updateOrCreate(
                ['token' => $request->token],
                ['seller_id' => $user->id, 'pos_staff_id' => null, 'user_id' => null, 'driver_id' => null, 'admin_id' => null, 'is_app' => 1, 'app_type' => 'seller']
            );
        }

        return apiResponse('token_saved', 'success', ['Token registrado']);
    }
}
