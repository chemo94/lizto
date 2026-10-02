<?php

namespace App\Events;

use App\Models\CourierJobOffer;
use App\Models\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierOfferCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Driver $driver;
    public CourierJobOffer $offer;
    public array $offerDetails;

    public function __construct(Driver $driver, CourierJobOffer $offer, array $offerDetails)
    {
        $this->driver       = $driver;
        $this->offer        = $offer;
        $this->offerDetails = $offerDetails;
    }

    public function broadcastOn()
    {
        return [
            new Channel('private-courier.' . $this->driver->id),
            new Channel('private-nearby-couriers'),
        ];
    }

    public function broadcastAs()
    {
        return 'courier_offer_created';
    }

    public function broadcastWith()
    {
        $remainingSeconds = max(0, $this->offer->expires_at ? now()->diffInSeconds($this->offer->expires_at, false) : 15);

        return array_merge($this->offerDetails, [
            'offer_id'          => $this->offer->id,
            'driver_id'         => $this->driver->id,
            'status'            => $this->offer->status,
            'offered_at'        => optional($this->offer->offered_at)->toIso8601String(),
            'expires_at'        => optional($this->offer->expires_at)->toIso8601String(),
            'remaining_seconds' => (int) $remainingSeconds,
        ]);
    }
}
