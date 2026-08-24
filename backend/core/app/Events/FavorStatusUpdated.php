<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FavorStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $favor;
    public $status;
    public $message;

    public function __construct($favor, $status = null)
    {
        $this->favor   = $favor;
        $this->status  = $status ?? $favor->status;
        $this->message = 'El estado de tu favor ha cambiado a: ' . $this->status;
    }

    public function broadcastOn()
    {
        $channels = [
            new PrivateChannel('favor.' . $this->favor->id),
        ];

        if ($this->favor->user_id) {
            $channels[] = new PrivateChannel('favor-customer.' . $this->favor->user_id);
        }

        if ($this->favor->seller_id) {
            $channels[] = new PrivateChannel('seller.' . $this->favor->seller_id);
        }

        if ($this->favor->courier_id) {
            $channels[] = new PrivateChannel('courier.' . $this->favor->courier_id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'favor_status_updated';
    }

    public function broadcastWith()
    {
        return [
            'favor_id'   => $this->favor->id,
            'order_no'   => $this->favor->order_no,
            'status'     => $this->status,
            'message'    => $this->message,
            'favor'      => $this->favor->load('courier'),
        ];
    }
}
