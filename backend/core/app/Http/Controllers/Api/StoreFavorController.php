<?php

namespace App\Http\Controllers\Api;

use App\Events\StoreFavorRequested;
use App\Http\Controllers\Controller;
use App\Models\Favor;
use App\Models\FavorBid;
use App\Services\FcmService;
use App\Services\SellerFavorDispatchService;
use App\Support\DeliveryPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreFavorController extends Controller
{
    public function feeEstimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_lat'   => 'required|numeric',
            'pickup_lng'   => 'required|numeric',
            'delivery_lat' => 'required|numeric',
            'delivery_lng' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        [$pickupLat, $pickupLng] = DeliveryPricing::coordinateFromRequest($request, ['pickup_lat'], ['pickup_lng']);

        $estimate = DeliveryPricing::estimateForFavor($pickupLat, $pickupLng, (float) $request->delivery_lat, (float) $request->delivery_lng);
        if (!$estimate['in_coverage']) {
            return apiResponse('outside_delivery_coverage', 'error', ['El destino está fuera de la zona de cobertura']);
        }

        return apiResponse('store_favor_fee_estimate', 'success', ['Tarifa calculada'], ['estimate' => $estimate]);
    }

    public function create(Request $request)
    {
        $seller = auth()->user();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $validator = Validator::make($request->all(), [
            'description'      => 'required|string|max:1000',
            'pickup_address'   => 'required|string|max:500',
            'pickup_lat'       => 'nullable|numeric',
            'pickup_lng'       => 'nullable|numeric',
            'delivery_address' => 'required|string|max:500',
            'delivery_lat'     => 'nullable|numeric',
            'delivery_lng'     => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $store = $seller->stores->first();
        [$pickupLat, $pickupLng] = DeliveryPricing::coordinateFromRequest(
            $request,
            ['pickup_lat'],
            ['pickup_lng']
        );
        [$deliveryLat, $deliveryLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);
        $pickupLat = $pickupLat ?? ($store?->latitude !== null ? (float) $store->latitude : null);
        $pickupLng = $pickupLng ?? ($store?->longitude !== null ? (float) $store->longitude : null);

        $estimate = DeliveryPricing::estimateForFavor($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
        if (!$estimate['in_coverage']) {
            return apiResponse('outside_delivery_coverage', 'error', ['El destino está fuera de la zona de cobertura']);
        }
        $deliveryFee = $estimate['delivery_fee'];
        $orderNo = 'STF-' . now()->format('Ymd') . '-' . strtoupper(\Str::random(6));
        $pinCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $favor = Favor::create([
            'order_no'         => $orderNo,
            'user_id'          => 0,
            'seller_id'        => $seller->id,
            'source_type'      => 'seller',
            'type'             => 'send',
            'description'      => $request->description,
            'pickup_address'   => $request->pickup_address,
            'pickup_lat'       => $pickupLat,
            'pickup_lng'       => $pickupLng,
            'delivery_address' => $request->delivery_address,
            'delivery_lat'     => $deliveryLat,
            'delivery_lng'     => $deliveryLng,
            'delivery_fee'     => $deliveryFee,
            'total'            => $deliveryFee,
            'status'           => 'searching_courier',
            'pin_code'         => $pinCode,
        ]);

        $favor->pin()->create(['pin_code' => $pinCode]);

        SellerFavorDispatchService::start($favor);

        return apiResponse('store_favor_created', 'success', ['Buscando repartidor cercano'], [
            'favor' => $favor->fresh('courier', 'bids'),
            'search' => $this->searchPayload($favor->fresh('courier')),
        ]);
    }

    public function myFavors(Request $request)
    {
        $seller = auth()->user();
        $favors = Favor::where('seller_id', $seller->id)
            ->with('courier', 'bids')
            ->orderBy('id', 'desc')
            ->paginate($request->per_page ?? 20);

        return apiResponse('store_favors', 'success', ['Tus solicitudes'], [
            'favors' => $favors,
        ]);
    }

    public function detail($id)
    {
        $seller = auth()->user();
        $favor = Favor::where('seller_id', $seller->id)
            ->with('courier', 'bids.courier', 'messages')
            ->findOrFail($id);

        return apiResponse('favor_detail', 'success', ['Detalle'], [
            'favor' => $favor,
            'search' => $this->searchPayload($favor),
            'tracking' => $this->trackingPayload($favor),
        ]);
    }

    public function searchStatus($id)
    {
        $seller = auth()->user();
        SellerFavorDispatchService::processDueRequests();
        $favor = Favor::where('seller_id', $seller->id)->with('courier')->findOrFail($id);
        return apiResponse('store_favor_search_status', 'success', ['Estado de búsqueda'], [
            'favor' => $favor,
            'search' => $this->searchPayload($favor),
        ]);
    }

    public function retrySearch($id)
    {
        $seller = auth()->user();
        $favor = Favor::where('seller_id', $seller->id)->where('status', 'searching_courier')->findOrFail($id);
        SellerFavorDispatchService::start($favor);
        $favor = $favor->fresh('courier');
        return apiResponse('store_favor_search_restarted', 'success', ['Búsqueda reiniciada'], [
            'favor' => $favor,
            'search' => $this->searchPayload($favor),
        ]);
    }

    private function searchPayload(Favor $favor): array
    {
        $mode = $favor->dispatch_mode;
        return [
            'phase' => $mode === 'seller_exhausted' ? 'exhausted' : ($mode === 'seller_expanded' ? 'expanded' : 'nearby'),
            'courier_name' => in_array($mode, ['seller_nearby', 'seller_expanded'], true) ? $favor->courier?->fullname : null,
            'seconds_remaining' => $favor->dispatch_timeout_at ? max(0, now()->diffInSeconds($favor->dispatch_timeout_at, false)) : null,
        ];
    }

    private function trackingPayload(Favor $favor): array
    {
        $courier = $favor->courier;
        return [
            'status' => $favor->status,
            'courier' => $courier ? [
                'name' => $courier->fullname,
                'latitude' => $courier->current_lat ?? $courier->latitude,
                'longitude' => $courier->current_lot ?? $courier->longitude,
            ] : null,
        ];
    }

    public function cancel($id)
    {
        $seller = auth()->user();
        $favor = Favor::where('seller_id', $seller->id)
            ->whereIn('status', ['searching_courier', 'accepted'])
            ->findOrFail($id);

        $favor->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        return apiResponse('cancelled', 'success', ['Solicitud cancelada']);
    }

    public function bids($id)
    {
        $seller = auth()->user();
        $favor = Favor::where('seller_id', $seller->id)->findOrFail($id);
        $bids  = $favor->bids()->with('courier')->orderBy('bid_amount')->get();

        return apiResponse('bids', 'success', ['Ofertas'], ['bids' => $bids]);
    }

    public function acceptBid($favorId, $bidId)
    {
        $seller = auth()->user();
        $favor = Favor::where('seller_id', $seller->id)
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

        broadcast(new \App\Events\FavorStatusUpdated($favor, 'courier_accepted'));

        return apiResponse('bid_accepted', 'success', ['Repartidor asignado']);
    }
}
