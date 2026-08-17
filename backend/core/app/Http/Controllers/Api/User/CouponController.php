<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\Status;
use App\Models\Ride;
use App\Models\Coupon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function coupons()
    {
        $coupons  = Coupon::active()->forTaxi()->orderBy('id', 'desc')->get();
        $notify[] = 'Coupon code';
        return apiResponse('coupon', 'success', $notify, $coupons);
    }

    public function applyCoupon(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $coupon = Coupon::active()->forTaxi()->where('code', $request->coupon_code)->first();

        if (!$coupon) {
            $notify[] = 'The coupon is not found or is not valid for rides';
            return apiResponse('not_found', 'error', $notify);
        }

        $ride  = Ride::where('status', Status::RIDE_END)->where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = 'The ride is not found';
            return apiResponse('not_found', 'error', $notify);
        }

        $couponUsingTime = Ride::where('applied_coupon_id', $coupon->id)->where('user_id', $ride->user_id)->whereIn('status', [Status::RIDE_COMPLETED, Status::RIDE_END])->count();

        if ($couponUsingTime != 0 && $couponUsingTime >= $coupon->maximum_using_time) {
            $notify[] = 'You are using the maximum time of this coupon.';
            return apiResponse('not_available', 'error', $notify);
        }

        $minimumAmount = $coupon->minimum_amount;
        $amount        = $ride->amount;

        if ($amount < $minimumAmount) {
            $notify[] = 'Minimum of ' . showAmount($minimumAmount) . ' will be spent on this using this coupon.';
            return apiResponse('limit', 'error', $notify);
        }

        if ($coupon->discount_type == Status::DISCOUNT_PERCENT) {
            $discountAmount = $amount / 100 * $coupon->amount;
        } else {
            $discountAmount = $coupon->amount;
        }

        $ride->applied_coupon_id = $coupon->id;
        $ride->discount_amount   = $discountAmount;
        $ride->commission_amount = ($ride->amount - $discountAmount) / 100 * $ride->commission_percentage;
        $ride->save();

        $notify[] =  'Coupon applied successfully';

        return apiResponse('coupon_applied', 'success', $notify, [
            'discount_amount' => $discountAmount,
            'coupon'          => $coupon->coupon,
        ]);
    }

    public function removeCoupon($id)
    {
        $ride  = Ride::where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = 'The ride is not found';
            return apiResponse('not_found', 'error', $notify);
        }

        if (!$ride->applied_coupon_id) {
            $notify[] = 'You have not applied the coupon for this ride';
            return apiResponse('not_available', 'error', $notify);
        }

        $ride->applied_coupon_id = 0;
        $ride->discount_amount   = 0;
        $ride->commission_amount = $ride->amount / 100 * $ride->commission_percentage;
        $ride->save();

        $notify[] =  'Coupon delete successfully';
        return apiResponse('coupon_delete', 'success', $notify);
    }

    public function applyDeliveryCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'   => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $coupon = Coupon::active()->forDelivery()->where('code', $request->code)->first();

        if (!$coupon) {
            return apiResponse('not_found', 'error', ['Cupón no encontrado, vencido o no válido para Delivery']);
        }

        if ($request->amount < $coupon->minimum_amount) {
            return apiResponse('limit', 'error', ['Monto mínimo: S/ ' . $coupon->minimum_amount]);
        }

        if ($coupon->discount_type == Status::DISCOUNT_PERCENT) {
            $discount = $request->amount / 100 * $coupon->amount;
        } else {
            $discount = $coupon->amount;
        }

        return apiResponse('coupon_applied', 'success', ['Cupón aplicado'], [
            'discount' => round($discount, 2),
        ]);
    }
}
