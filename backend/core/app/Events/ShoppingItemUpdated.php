<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShoppingItemUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $favor;
    public $item;
    public $action;

    public function __construct($favor, $item, $action = 'updated')
    {
        $this->favor  = $favor;
        $this->item   = $item;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        $channels = [
            new Channel('private-favor.' . $this->favor->id),
            new Channel('private-favor-customer.' . $this->favor->user_id),
        ];

        if ($this->favor->courier_id) {
            $channels[] = new Channel('private-courier.' . $this->favor->courier_id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'shopping_item_updated';
    }

    public function broadcastWith()
    {
        return [
            'favor_id'     => $this->favor->id,
            'order_no'     => $this->favor->order_no,
            'action'       => $this->action,
            'item'         => $this->item,
            'progress'     => $this->favor->shopping_progress,
            'needs_approval' => $this->favor->needs_substitution_approval,
        ];
    }
}
