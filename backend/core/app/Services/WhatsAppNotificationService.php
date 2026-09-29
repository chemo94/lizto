<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    /**
     * Send order notifications to the store and the central admin number.
     *
     * @param DeliveryOrder $order
     * @param bool $force Force sending even if already sent
     * @return array
     */
    public static function sendOrderNotification(DeliveryOrder $order, bool $force = false): array
    {
        $enabled = config('services.waapi.enabled', true);
        if (!$enabled) {
            Log::info("WhatsAppNotificationService: Envío deshabilitado en configuración.");
            return ['status' => 'disabled'];
        }

        // Check if already sent (Idempotency)
        if (!$force && !empty($order->whatsapp_sent_at)) {
            Log::info("WhatsAppNotificationService: Pedido #{$order->order_no} ya fue notificado previamente el {$order->whatsapp_sent_at}.");
            return ['status' => 'already_sent', 'order_id' => $order->id];
        }

        // Ensure all required relationships are loaded if order exists in DB
        if ($order->exists) {
            $order->loadMissing([
                'store.seller',
                'user',
                'items.variation',
                'items.addons',
            ]);
        }

        $message = self::formatOrderMessage($order);
        $results = [];

        // 1. Número de la tienda (Store phone o fallback a Seller phone)
        $storePhoneRaw = $order->store?->phone ?: $order->store?->seller?->phone;
        $storePhone = self::normalizePhoneNumber($storePhoneRaw);

        // 2. Número fijo / central administrativa (por defecto 997428341)
        $adminPhoneRaw = config('services.waapi.admin_phone', '997428341');
        $adminPhone = self::normalizePhoneNumber($adminPhoneRaw);

        $recipients = [];
        if ($storePhone) {
            $recipients[$storePhone] = [
                'role'  => 'store',
                'name'  => $order->store?->name ?? 'Tienda',
                'phone' => $storePhone,
            ];
        } else {
            Log::warning("WhatsAppNotificationService: La tienda '{$order->store?->name}' (ID: {$order->store_id}) no tiene número de teléfono registrado.");
        }

        if ($adminPhone) {
            // Evitar duplicar si la tienda tiene el mismo número que el admin
            if (!isset($recipients[$adminPhone])) {
                $recipients[$adminPhone] = [
                    'role'  => 'admin_central',
                    'name'  => 'Central Lizto (Fijo)',
                    'phone' => $adminPhone,
                ];
            }
        }

        if (empty($recipients)) {
            Log::warning("WhatsAppNotificationService: No se encontraron destinatarios válidos para el pedido #{$order->order_no}.");
            return ['status' => 'no_recipients'];
        }

        $anySent = false;
        foreach ($recipients as $recipientPhone => $recipientInfo) {
            $sendRes = self::sendTextMessage($recipientPhone, $message);
            $results[$recipientPhone] = [
                'recipient' => $recipientInfo,
                'response'  => $sendRes,
            ];
            if (!empty($sendRes['success'])) {
                $anySent = true;
            }
        }

        // Marcar como enviado si al menos a uno se envió correctamente o se intentó (y el pedido existe en BD)
        if ($order->exists && ($anySent || !empty($results))) {
            try {
                $order->update(['whatsapp_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::error("WhatsAppNotificationService: Error al actualizar whatsapp_sent_at: " . $e->getMessage());
            }
        }

        return [
            'status'   => $anySent ? 'success' : 'partial_or_failed',
            'order_no' => $order->order_no,
            'details'  => $results,
        ];
    }

    /**
     * Send a text message via waapi microservice.
     *
     * @param string $to
     * @param string $message
     * @return array
     */
    public static function sendTextMessage(string $to, string $message, bool $previewUrl = false): array
    {
        $baseUrl = rtrim(config('services.waapi.base_url', 'http://localhost/waapi'), '/');
        $apiKey  = config('services.waapi.api_key', 'wa_secret_key_change_me_12345');
        $platform = config('services.waapi.platform', 'lizto');

        $url = "{$baseUrl}/api/send/text";

        $payload = [
            'to'          => $to,
            'message'     => $message,
            'preview_url' => $previewUrl,
            'platform'    => $platform,
        ];

        try {
            $client = Http::connectTimeout(5)->timeout(12);
            if (config('app.env') === 'local' || !config('services.waapi.ssl_verify', true)) {
                $client = $client->withoutVerifying();
            }

            $response = $client
                ->withHeaders([
                    'X-API-KEY'    => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("WhatsAppNotificationService: Mensaje enviado exitosamente a {$to} vía {$url}.");
                return [
                    'success' => true,
                    'status'  => $response->status(),
                    'data'    => $response->json(),
                ];
            }

            Log::error("WhatsAppNotificationService: Error HTTP {$response->status()} enviando a {$to}: " . $response->body());
            return [
                'success' => false,
                'status'  => $response->status(),
                'error'   => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error("WhatsAppNotificationService: Excepción de conexión con waapi ({$url}) al enviar a {$to}: " . $e->getMessage());
            return [
                'success' => false,
                'status'  => 500,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Formats the order into a structured WhatsApp message.
     *
     * @param DeliveryOrder $order
     * @return string
     */
    public static function formatOrderMessage(DeliveryOrder $order): string
    {
        $dateFormatted = $order->created_at ? $order->created_at->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A');
        $storeName     = $order->store?->name ?? 'Tienda';
        $clientName    = $order->contact_name ?: ($order->user?->fullname ?: 'Cliente');
        $clientPhone   = $order->contact_phone ?: ($order->user?->mobile ?: 'No especificado');
        $address       = $order->delivery_address ?: 'No especificada';
        $notes         = $order->notes ? trim($order->notes) : null;

        $lines = [];
        $lines[] = "🛍️ *¡NUEVO PEDIDO RECIBIDO!*";
        $lines[] = "━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "📦 *Pedido:* #{$order->order_no}";
        $lines[] = "📅 *Fecha:* {$dateFormatted}";
        $lines[] = "🏪 *Tienda:* {$storeName}";
        $lines[] = "━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "";
        $lines[] = "👤 *DATOS DEL CLIENTE*";
        $lines[] = "• *Nombre:* {$clientName}";
        $lines[] = "• *Teléfono:* {$clientPhone}";
        $lines[] = "• *Dirección:* {$address}";

        // Google Maps Location
        if (!empty($order->delivery_lat) && !empty($order->delivery_lng)) {
            $lat = number_format((float) $order->delivery_lat, 6, '.', '');
            $lng = number_format((float) $order->delivery_lng, 6, '.', '');
            $lines[] = "📍 *Ubicación GPS:* https://www.google.com/maps?q={$lat},{$lng}";
        }

        if ($notes) {
            $lines[] = "📝 *Notas / Ref.:* {$notes}";
        }

        $lines[] = "";
        $lines[] = "🛒 *DETALLE DEL PEDIDO*";

        $items = $order->items ?? collect();
        if ($items->isEmpty()) {
            $lines[] = "_Sin detalle de productos_";
        } else {
            $index = 1;
            foreach ($items as $item) {
                $itemTotal = number_format((float) $item->total_price, 2);
                $lines[] = "{$index}. *{$item->quantity}x* {$item->product_name} - S/ {$itemTotal}";

                // Variation
                if (!empty($item->variation?->variation_name)) {
                    $vPrice = (float) ($item->variation->variation_price ?? 0);
                    $vPriceText = $vPrice > 0 ? " (+S/ " . number_format($vPrice, 2) . ")" : "";
                    $lines[] = "   ↳ Variante: {$item->variation->variation_name}{$vPriceText}";
                }

                // Addons
                if ($item->addons && $item->addons->isNotEmpty()) {
                    foreach ($item->addons as $addon) {
                        $addPrice = number_format((float) ($addon->addon_price ?? 0), 2);
                        $lines[] = "   ↳ Extra: {$addon->addon_name} (+S/ {$addPrice})";
                    }
                }
                $index++;
            }
        }

        $lines[] = "";
        $lines[] = "━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "💰 *RESUMEN DE PAGO*";
        $lines[] = "• Subtotal: S/ " . number_format((float) $order->subtotal, 2);
        $lines[] = "• Costo de Envío: S/ " . number_format((float) $order->delivery_fee, 2);

        if ((float) $order->discount > 0) {
            $lines[] = "• Descuento: -S/ " . number_format((float) $order->discount, 2);
        }
        if ((float) $order->tip > 0) {
            $lines[] = "• Propina: S/ " . number_format((float) $order->tip, 2);
        }

        $lines[] = "━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "💵 *TOTAL A PAGAR:* *S/ " . number_format((float) $order->total, 2) . "*";

        $paymentMethod = $order->payment_method_name ?: 'Efectivo';
        $lines[] = "💳 *Método de Pago:* {$paymentMethod}";

        if ((float) ($order->cash_pay_amount ?? 0) > 0) {
            $cashAmount = (float) $order->cash_pay_amount;
            $change = max(0, $cashAmount - (float) $order->total);
            $lines[] = "💵 *Paga con:* S/ " . number_format($cashAmount, 2) . " (Vuelto: S/ " . number_format($change, 2) . ")";
        }

        $lines[] = "━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "🔔 *ACCIÓN REQUERIDA PARA LA TIENDA:*";
        $lines[] = "Por favor, ingresa a la *App de Tienda* para aceptar y despachar el pedido, activando el *tracking de estado en tiempo real* para el cliente.";
        $lines[] = "";
        $lines[] = "💻 *O gestiona desde tu panel web:*";
        $lines[] = "👉 https://liztodelivery.com/seller/orders";
        $lines[] = "━━━━━━━━━━━━━━━━━━━━";
        $lines[] = "🛵 _Lizto Delivery - Sistema de Pedidos_";

        return implode("\n", $lines);
    }

    /**
     * Normalizes a phone number to standard international WhatsApp format (e.g., 51997428341).
     *
     * @param string|null $phone
     * @return string|null
     */
    public static function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Keep only digits
        $clean = preg_replace('/[^\d]/', '', (string) $phone);

        if (empty($clean)) {
            return null;
        }

        // Peruvian mobile numbers have 9 digits starting with 9 (e.g., 997428341)
        if (strlen($clean) === 9 && str_starts_with($clean, '9')) {
            return '51' . $clean;
        }

        // If 10 digits starting with 09 (e.g., 0997428341)
        if (strlen($clean) === 10 && str_starts_with($clean, '09')) {
            return '51' . substr($clean, 1);
        }

        // If 11 digits starting with 519 (e.g., 51997428341)
        if (strlen($clean) === 11 && str_starts_with($clean, '519')) {
            return $clean;
        }

        // If it already has international code (minimum 10 digits, max 15 digits)
        if (strlen($clean) >= 10 && strlen($clean) <= 15) {
            return $clean;
        }

        // Fallback: if 9 digits of any sort, assume Peru (country code 51)
        if (strlen($clean) === 9) {
            return '51' . $clean;
        }

        return $clean;
    }
}
