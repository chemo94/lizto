<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $favorId;
    public $latitude;
    public $longitude;
    public $bearing;
    public $orderId;
    public $userId;

    public function __construct($favorId, $latitude, $longitude, $bearing = null, $orderId = null, $userId = null)
    {
        $this->favorId   = $favorId;
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
        $this->bearing   = $bearing;
        $this->orderId   = $orderId;
        $this->userId    = $userId;
    }

    public function broadcastOn()
    {
        $channels = [];
        if ($this->favorId) {
            $channels[] = new Channel('private-favor.' . $this->favorId);
            $channels[] = new Channel('private-job.' . $this->favorId);
        }
        if ($this->orderId) {
            $channels[] = new Channel('private-tracking.' . $this->orderId);
            $channels[] = new Channel('private-job.' . $this->orderId);
        }
        if ($this->userId) {
            $channels[] = new Channel('private-delivery-order.' . $this->userId);
        }
        return $channels;
    }

    public function broadcastAs()
    {
        return 'location_update';
    }

    public function broadcastWith()
    {
        return [
            'favor_id'  => $this->favorId,
            'order_id'  => $this->orderId,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
            'bearing'   => $this->bearing,
        ];
    }
}
