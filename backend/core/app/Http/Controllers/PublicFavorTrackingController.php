<?php

namespace App\Http\Controllers;

use App\Models\Favor;

class PublicFavorTrackingController extends Controller
{
    public function show(string $token)
    {
        $favor = $this->favor($token);
        return view('tracking.favor', compact('favor', 'token'));
    }

    public function data(string $token)
    {
        $favor = $this->favor($token);
        $courier = $favor->courier;
        $isDeliveryLeg = in_array($favor->status, ['on_way_to_delivery', 'delivered'], true);

        return response()->json([
            'status' => $favor->status,
            'phase' => $isDeliveryLeg ? 'delivery' : 'pickup',
            'courier' => $courier ? [
                'name' => $courier->fullname,
                'phone' => $courier->mobileNumber,
                'latitude' => $courier->current_lat ?? $courier->latitude,
                'longitude' => $courier->current_lot ?? $courier->longitude,
                'updated_at' => optional($courier->updated_at)->toIso8601String(),
            ] : null,
            'pickup' => ['lat' => (float) $favor->pickup_lat, 'lng' => (float) $favor->pickup_lng, 'address' => $favor->pickup_address],
            'delivery' => ['lat' => (float) $favor->delivery_lat, 'lng' => (float) $favor->delivery_lng, 'address' => $favor->delivery_address],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function favor(string $token): Favor
    {
        return Favor::with('courier')->where('tracking_share_token', $token)->firstOrFail();
    }
}
