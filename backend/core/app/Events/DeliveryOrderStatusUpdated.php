<?php

namespace App\Events;

use App\Models\DeliveryOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliveryOrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(DeliveryOrder $order)
    {
        $this->order = $order;
        Log::info('DeliveryOrderStatusUpdated constructed', [
            'order_id' => $order->id,
            'status'   => $order->status,
            'user_id'  => $order->user_id,
        ]);
    }

    public function broadcastOn()
    {
        $channels = [
            new Channel('private-delivery-order.' . $this->order->user_id),
            new Channel('private-tracking.' . $this->order->id),
        ];

        if ($this->order->driver_id) {
            $channels[] = new Channel('private-courier.' . $this->order->driver_id);
        }

        // The Seller app listens for this event to refresh its orders and
        // kitchen views when a courier advances the delivery.
        $sellerId = $this->order->store?->seller_id;
        if ($sellerId) {
            $channels[] = new Channel('private-seller.' . $sellerId);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'delivery_order_status_updated';
    }

    public function broadcastWith()
    {
        return [
            'event_id' => 'delivery-status-' . $this->order->id . '-' . ($this->order->updated_at?->format('Uu') ?? now()->format('Uu')),
            'order_id' => $this->order->id,
            'job_id'   => $this->order->id,
            'job_type' => 'delivery',
            'order_no' => $this->order->order_no,
            'status'   => $this->order->status,
            'message'  => 'El estado del pedido cambio a: ' . $this->order->status,
        ];
    }
}
