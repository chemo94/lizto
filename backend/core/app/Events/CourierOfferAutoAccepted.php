<?php

namespace App\Events;

use App\Models\CourierJobOffer;
use App\Models\Driver;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierOfferAutoAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Driver $driver;
    public CourierJobOffer $offer;
    public array $jobData;

    public function __construct(Driver $driver, CourierJobOffer $offer, array $jobData)
    {
        $this->driver  = $driver;
        $this->offer   = $offer;
        $this->jobData = $jobData;
    }

    public function broadcastOn()
    {
        return [
            new Channel('private-courier.' . $this->driver->id),
        ];
    }

    public function broadcastAs()
    {
        return 'courier_offer_auto_accepted';
    }

    public function broadcastWith()
    {
        return [
            'offer_id'      => $this->offer->id,
            'driver_id'     => $this->driver->id,
            'job_id'        => $this->offer->job_id,
            'job_type'      => $this->offer->job_type,
            'status'        => 'accepted',
            'auto_accepted' => true,
            'message'       => '¡Pedido auto-aceptado según tus preferencias de ganancias y distancia!',
            'job'           => $this->jobData,
            'accepted_at'   => now()->toIso8601String(),
        ];
    }
}
