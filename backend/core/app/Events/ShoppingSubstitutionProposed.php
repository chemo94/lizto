<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShoppingSubstitutionProposed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $favor;
    public $item;

    public function __construct($favor, $item)
    {
        $this->favor = $favor;
        $this->item  = $item;
    }

    public function broadcastOn()
    {
        $channels = [
            new Channel('private-favor.' . $this->favor->id),
            new Channel('private-favor-customer.' . $this->favor->user_id),
        ];

        return $channels;
    }

    public function broadcastAs()
    {
        return 'shopping_substitution_proposed';
    }

    public function broadcastWith()
    {
        return [
            'favor_id'     => $this->favor->id,
            'order_no'     => $this->favor->order_no,
            'item'         => $this->item,
            'message'      => 'El repartidor propuso un sustituto para: ' . $this->item->name,
        ];
    }
}
