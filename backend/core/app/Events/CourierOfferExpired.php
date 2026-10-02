<?php

namespace App\Events;

use App\Models\CourierJobOffer;
use App\Models\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierOfferExpired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Driver $driver;
    public CourierJobOffer $offer;

    public function __construct(Driver $driver, CourierJobOffer $offer)
    {
        $this->driver = $driver;
        $this->offer  = $offer;
    }

    public function broadcastOn()
    {
        return [
            new Channel('private-courier.' . $this->driver->id),
        ];
    }

    public function broadcastAs()
    {
        return 'courier_offer_expired';
    }

    public function broadcastWith()
    {
        return [
            'offer_id'   => $this->offer->id,
            'driver_id'  => $this->driver->id,
            'job_id'     => $this->offer->job_id,
            'job_type'   => $this->offer->job_type,
            'status'     => 'expired',
            'expired_at' => now()->toIso8601String(),
        ];
    }
}
