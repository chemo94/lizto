<?php

namespace App\Listeners;

use App\Events\NewDeliveryOrderPlaced;
use App\Models\DeliveryOrder;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendWhatsAppOrderNotification
{
    /**
     * Handle the event.
     *
     * @param NewDeliveryOrderPlaced $event
     * @return void
     */
    public function handle(NewDeliveryOrderPlaced $event): void
    {
        try {
            if ($event->order instanceof DeliveryOrder) {
                WhatsAppNotificationService::sendOrderNotification($event->order);
            }
        } catch (\Throwable $e) {
            Log::error("SendWhatsAppOrderNotification: Error procesando evento: " . $e->getMessage());
        }
    }
}
