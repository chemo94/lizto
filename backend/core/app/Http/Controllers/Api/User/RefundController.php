<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\DeliveryRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $refunds = DeliveryRefund::where('user_id', $user->id)
            ->with('order', 'favor')
            ->orderBy('id', 'desc')
            ->get();

        return apiResponse('refunds', 'success', ['Tus reembolsos'], [
            'refunds' => $refunds,
        ]);
    }

    public function requestRefund(Request $request, $id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $refund = DeliveryRefund::create([
            'user_id'  => $user->id,
            'order_id' => $order->id,
            'amount'   => $order->total,
            'reason'   => $request->reason,
            'status'   => 'pending',
        ]);

        return apiResponse('refund_requested', 'success', ['Solicitud enviada'], ['refund' => $refund]);
    }
}
