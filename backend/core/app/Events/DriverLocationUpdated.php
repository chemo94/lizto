<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $driverId;
    public $rideId;

    public function __construct($rideId, $driverId, $data = [])
    {
        $this->rideId   = $rideId;
        $this->driverId = $driverId;
        $this->data     = $data;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("ride-location.{$this->rideId}");
    }

    public function broadcastAs()
    {
        return 'driver_location_updated';
    }

    public function broadcastWith()
    {
        return [
            'ride_id'    => $this->rideId,
            'driver_id'  => $this->driverId,
            'latitude'   => $this->data['latitude'] ?? null,
            'longitude'  => $this->data['longitude'] ?? null,
            'bearing'    => $this->data['bearing'] ?? null,
            'speed'      => $this->data['speed'] ?? null,
            'timestamp'  => now()->toIso8601String(),
        ];
    }
}
