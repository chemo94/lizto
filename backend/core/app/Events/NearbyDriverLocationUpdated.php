<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NearbyDriverLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $driver;

    public function __construct($driver)
    {
        $this->driver = $driver;
    }

    public function broadcastOn()
    {
        return [new PrivateChannel('nearby-drivers')];
    }

    public function broadcastAs()
    {
        return 'driver_location_updated';
    }

    public function broadcastWith()
    {
        return [
            'driver_id'    => $this->driver->id,
            'latitude'     => $this->driver->current_lat,
            'longitude'    => $this->driver->current_lot,
            'bearing'      => $this->driver->bearing ?? 0,
            'service_name' => $this->driver->service?->name,
        ];
    }
}
