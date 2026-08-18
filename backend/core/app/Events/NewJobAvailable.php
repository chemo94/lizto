<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewJobAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $job;
    public $message;

    public function __construct($job, $message = null)
    {
        $this->job     = $job;
        $this->message = $message ?? 'Hay un nuevo pedido cerca de tu ubicación';
    }

    public function broadcastOn()
    {
        $courierId = $this->job->driver_id ?? $this->job->courier_id ?? null;
        $channels = [new Channel('private-nearby-couriers')];
        if ($courierId) {
            $channels[] = new Channel('private-courier.' . $courierId);
        }
        return $channels;
    }

    public function broadcastAs()
    {
        return 'new_job_available';
    }

    public function broadcastWith()
    {
        $isDelivery = $this->job instanceof \App\Models\DeliveryOrder;
        return [
            'event_id'         => 'job-available-' . $this->job->id . '-' . ($this->job->updated_at?->format('Uu') ?? now()->format('Uu')),
            'job_id'           => (int) $this->job->id,
            'id'               => (int) $this->job->id,
            'favor_id'         => (int) $this->job->id,
            'order_id'         => (int) $this->job->id,
            'order_no'         => $this->job->order_no ?? (string) $this->job->id,
            'job_type'         => $isDelivery ? 'delivery' : 'favor',
            'type'             => $isDelivery ? 'delivery' : 'favor',
            'store_name'       => $this->job->store_name ?? ($isDelivery ? $this->job->store?->name : ($this->job->seller?->name ?? 'Lizto')),
            'pickup_address'   => $this->job->pickup_address ?? ($isDelivery ? $this->job->store?->address : null),
            'pickup_lat'       => (float) ($this->job->pickup_lat ?? ($isDelivery ? $this->job->store?->latitude : 0)),
            'pickup_lng'       => (float) ($this->job->pickup_lng ?? ($isDelivery ? $this->job->store?->longitude : 0)),
            'delivery_address' => $this->job->delivery_address ?? ($isDelivery ? $this->job->shipping_address : null),
            'delivery_lat'     => (float) ($this->job->delivery_lat ?? ($isDelivery ? $this->job->latitude : 0)),
            'delivery_lng'     => (float) ($this->job->delivery_lng ?? ($isDelivery ? $this->job->longitude : 0)),
            'delivery_fee'     => (float) ($this->job->delivery_fee ?? 0),
            'total'            => (float) ($this->job->total ?? 0),
            'total_earning'    => (float) ($this->job->delivery_fee ?? $this->job->total ?? 0),
            'description'      => $this->job->description ?? null,
            'message'          => $this->message,
        ];
    }
}
