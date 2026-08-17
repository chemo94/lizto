<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShoppingReceiptUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $favor;
    public $confirmation;

    public function __construct($favor, $confirmation)
    {
        $this->favor        = $favor;
        $this->confirmation = $confirmation;
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
        return 'shopping_receipt_uploaded';
    }

    public function broadcastWith()
    {
        return [
            'favor_id'     => $this->favor->id,
            'order_no'     => $this->favor->order_no,
            'confirmation' => $this->confirmation,
            'actual_total' => $this->favor->actual_total,
            'message'      => 'El repartidor subió el comprobante de compra. Total: S/ ' . number_format($this->favor->actual_total, 2),
        ];
    }
}
