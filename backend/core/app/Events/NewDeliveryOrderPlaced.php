<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDeliveryOrderPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $type; // 'delivery' or 'favor'

    public function __construct($order, $type = 'delivery')
    {
        $this->order = $order;
        $this->type  = $type;
    }

    public function broadcastOn()
    {
        $channels = [
            new Channel('private-admin-notifications'),
            new Channel('private-nearby-couriers'),
        ];

        // Also notify the store/seller
        if ($this->type === 'delivery' && $this->order->store?->seller_id) {
            $channels[] = new Channel('private-seller.' . $this->order->store->seller_id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'new_delivery_order';
    }

    public function broadcastWith()
    {
        $storeName = $this->order->store?->name ?? 'N/A';
        $customerName = $this->order->user?->fullname ?? 'Cliente';
        $total = number_format($this->order->total, 2);
        $itemsCount = $this->order->items?->count() ?? 0;

        return [
            'event_id'      => 'delivery-created-' . $this->order->id,
            'id'            => $this->order->id,
            'order_no'      => $this->order->order_no,
            'type'          => $this->type,
            'store_name'    => $storeName,
            'customer_name' => $customerName,
            'total'         => $total,
            'items_count'   => $itemsCount,
            'address'       => $this->order->delivery_address ?? '',
            'created_at'    => $this->order->created_at?->format('H:i'),
            'message'       => "Nuevo pedido #{$this->order->order_no} de {$customerName}",
            'sound'         => 'notification.mp3',
        ];
    }
}
