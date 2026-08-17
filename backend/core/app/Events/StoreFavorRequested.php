<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreFavorRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $favor;
    public $message;

    public function __construct($favor)
    {
        $this->favor   = $favor;
        $this->message = 'Tienda ' . ($favor->seller?->name ?? '') . ' solicita repartidor: ' . $favor->description;
    }

    public function broadcastOn()
    {
        return [
            new Channel('private-admin-notifications'),
            new Channel('private-nearby-couriers'),
        ];
    }

    public function broadcastAs()
    {
        return 'store_favor_requested';
    }

    public function broadcastWith()
    {
        return [
            'favor_id'       => $this->favor->id,
            'order_no'       => $this->favor->order_no,
            'seller_name'    => $this->favor->seller?->name,
            'description'    => $this->favor->description,
            'pickup_address' => $this->favor->pickup_address,
            'delivery_address' => $this->favor->delivery_address,
            'total'          => number_format($this->favor->total, 2),
            'message'        => $this->message,
        ];
    }
}
