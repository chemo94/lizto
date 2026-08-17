<?php

namespace App\Http\Controllers\Api;

use App\Events\FavorMessageReceived;
use App\Events\FavorStatusUpdated;
use App\Events\NewDeliveryOrderPlaced;
use App\Events\NewJobAvailable;
use App\Http\Controllers\Controller;
use App\Models\Favor;
use App\Models\FavorBid;
use App\Models\FavorMessage;
use App\Models\Gateway;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavorController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'              => 'required|in:buy,send',
            'description'       => 'required|string|max:1000',
            'store_name'        => 'nullable|string|max:255',
            'store_address'     => 'nullable|string|max:500',
            'estimated_amount'  => 'nullable|numeric|min:0',
            'pickup_address'    => 'required|string|max:500',
            'pickup_lat'        => 'nullable|numeric',
            'pickup_lng'        => 'nullable|numeric',
            'delivery_address'  => 'required|string|max:500',
            'delivery_lat'      => 'nullable|numeric',
            'delivery_lng'      => 'nullable|numeric',
            'recipient_name'    => 'nullable|string|max:255',
            'recipient_phone'   => 'nullable|string|max:20',
            'payment_method_code' => 'nullable|exists:gateways,code',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $user = auth()->user();

        $deliveryFee = 5.00; // Configurable base fee
        $total = $deliveryFee;

        $paymentMethodCode = null;
        if ($request->payment_method_code) {
            $gateway = Gateway::where('code', $request->payment_method_code)->active()->first();
            if ($gateway) $paymentMethodCode = $gateway->code;
        }

        $orderNo = 'FAV-' . now()->format('Ymd') . '-' . strtoupper(\Str::random(6));
        $pinCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $favor = Favor::create([
            'order_no'           => $orderNo,
            'user_id'            => $user->id,
            'type'               => $request->type,
            'description'        => $request->description,
            'store_name'         => $request->store_name,
            'store_address'      => $request->store_address,
            'estimated_amount'   => $request->estimated_amount,
            'pickup_address'     => $request->pickup_address,
            'pickup_lat'         => $request->pickup_lat,
            'pickup_lng'         => $request->pickup_lng,
            'delivery_address'   => $request->delivery_address,
            'delivery_lat'       => $request->delivery_lat,
            'delivery_lng'       => $request->delivery_lng,
            'recipient_name'     => $request->recipient_name,
            'recipient_phone'    => $request->recipient_phone,
            'delivery_fee'       => $deliveryFee,
            'total'              => $total,
            'status'             => 'searching_courier',
            'pin_code'           => $pinCode,
            'payment_method_code' => $paymentMethodCode,
        ]);

        $favor->pin()->create(['pin_code' => $pinCode]);

        // Broadcast to admin panel
        broadcast(new NewDeliveryOrderPlaced($favor, 'favor'));

        return apiResponse('favor_created', 'success', ['Favor creado correctamente'], [
            'favor'              => $favor->load('bids'),
            'courier_image_path' => 'assets/images/driver',
        ]);
    }

    public function list(Request $request)
    {
        $user   = auth()->user();
        $favors = Favor::where('user_id', $user->id)
            ->with('courier', 'bids', 'review')
            ->orderBy('id', 'desc')
            ->paginate($request->per_page ?? 20);

        return apiResponse('user_favors', 'success', ['Tus favores'], [
            'favors'              => $favors,
            'courier_image_path'  => 'assets/images/driver',
        ]);
    }

    public function detail($id)
    {
        $user  = auth()->user();
        $favor = Favor::where('user_id', $user->id)
            ->with('courier', 'bids.courier', 'messages.sender', 'review')
            ->findOrFail($id);

        return apiResponse('favor_detail', 'success', ['Detalle del favor'], [
            'favor'              => $favor,
            'courier_image_path' => 'assets/images/driver',
            'chat_image_path'    => 'assets/images/chat',
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $user  = auth()->user();
        $favor = Favor::where('user_id', $user->id)
            ->whereIn('status', ['searching_courier', 'accepted'])
            ->findOrFail($id);

        $favor->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $request->reason ?? 'Cancelado por el usuario',
        ]);

        broadcast(new FavorStatusUpdated($favor))->toOthers();

        return apiResponse('favor_cancelled', 'success', ['Favor cancelado']);
    }

    // ── Bids ──

    public function bids($id)
    {
        $user = auth()->user();
        $favor = Favor::where('user_id', $user->id)->findOrFail($id);
        $bids  = $favor->bids()->with('courier')->orderBy('bid_amount')->get();

        return apiResponse('favor_bids', 'success', ['Ofertas recibidas'], [
            'bids' => $bids,
        ]);
    }

    public function acceptBid($favorId, $bidId)
    {
        $user = auth()->user();
        $favor = Favor::where('user_id', $user->id)
            ->where('status', 'searching_courier')
            ->findOrFail($favorId);

        $bid = $favor->bids()->where('id', $bidId)->firstOrFail();

        $bid->update(['status' => 'accepted']);
        $favor->bids()->where('id', '!=', $bidId)->update(['status' => 'rejected']);

        $favor->update([
            'status'            => 'accepted',
            'courier_id'        => $bid->courier_id,
            'accepted_bid_id'   => $bid->id,
            'total'             => $bid->bid_amount,
            'courier_assigned_at' => now(),
        ]);

        broadcast(new FavorStatusUpdated($favor, 'courier_accepted'))->toOthers();

        return apiResponse('bid_accepted', 'success', ['Repartidor asignado'], [
            'favor' => $favor->load('courier'),
        ]);
    }

    // ── Messages ──

    public function messages($id)
    {
        $user   = auth()->user();
        $favor  = Favor::where('user_id', $user->id)->findOrFail($id);
        $msgs   = $favor->messages()->orderBy('id', 'desc')->paginate(50);

        return apiResponse('favor_messages', 'success', ['Mensajes'], [
            'messages' => $msgs,
        ]);
    }

    public function sendMessage(Request $request, $id)
    {
        $user  = auth()->user();
        $favor = Favor::where('user_id', $user->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:image|string|max:1000',
            'image'   => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = uploadImage($request->file('image'), 'assets/images/chat', null, null, null, true);
        }

        $msg = $favor->messages()->create([
            'sender_id'   => $user->id,
            'sender_type' => User::class,
            'sender_name' => $user->fullname ?? $user->username,
            'sender_role' => 'customer',
            'message'     => $request->message,
            'image'       => $imagePath,
        ]);

        broadcast(new FavorMessageReceived($msg))->toOthers();

        return apiResponse('message_sent', 'success', ['Mensaje enviado'], [
            'message' => $msg,
        ]);
    }

    public function sendImage(Request $request, $id)
    {
        $request->merge(['image' => $request->file('image')]);
        return $this->sendMessage($request, $id);
    }

    // ── Review ──

    public function review(Request $request, $id)
    {
        $user  = auth()->user();
        $favor = Favor::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'review' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $review = $favor->review()->updateOrCreate(
            ['reviewable_type' => Favor::class, 'reviewable_id' => $favor->id],
            [
                'user_id'    => $user->id,
                'courier_id' => $favor->courier_id,
                'rating'     => $request->rating,
                'review'     => $request->review,
            ]
        );

        return apiResponse('review_saved', 'success', ['Calificación enviada'], ['review' => $review]);
    }

    // ── Refund ──

    public function requestRefund(Request $request, $id)
    {
        $user  = auth()->user();
        $favor = Favor::where('user_id', $user->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $refund = $favor->refund()->create([
            'user_id' => $user->id,
            'amount'  => $favor->total,
            'reason'  => $request->reason,
            'status'  => 'pending',
        ]);

        return apiResponse('refund_requested', 'success', ['Solicitud de reembolso enviada'], ['refund' => $refund]);
    }

    // ── Gateways ──

    public function gateways()
    {
        $gateways = Gateway::active()->with('singleCurrency')->automatic()->get()
            ->map(fn($g) => [
                'id'       => $g->id,
                'code'     => $g->code,
                'name'     => $g->name,
                'image'    => $g->singleCurrency?->image,
                'currency' => $g->singleCurrency?->currency,
                'symbol'   => $g->singleCurrency?->symbol,
            ]);

        return apiResponse('gateways', 'success', ['Métodos de pago'], [
            'gateways'           => $gateways,
            'gateway_image_path' => getFilePath('gateway'),
        ]);
    }
}
