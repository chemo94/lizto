<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotifier
{
    protected string $gatewayUrl;
    protected string $gatewayToken;

    public function __construct()
    {
        $this->gatewayUrl = config('services.openclaw.gateway_url', 'http://127.0.0.1:18789');
        $this->gatewayToken = config('services.openclaw.gateway_token', '5e8a8c5df0bfd620f6d72f1be4dd51b7b549bf2735c93b4f');
    }

    /**
     * Notify a seller about a new delivery order via OpenClaw WhatsApp
     */
    public function notifyNewDeliveryOrder(object $order): bool
    {
        $seller = $order->store?->seller;
        if (!$seller || !$seller->phone) {
            return false;
        }

        $phone = $this->formatPhone($seller->phone);
        $storeName = $order->store->name ?? 'Tienda';
        $items = $order->items->count();
        $total = number_format($order->total, 2);
        $orderNo = $order->order_no;
        $customerName = $order->contact_name ?? 'Cliente';
        $customerPhone = $order->contact_phone ?? '—';
        $address = $order->delivery_address ?? '—';

        $message = "🛵 *NUEVO PEDIDO - LiztoDelivery* 🛵\n\n"
                 . "📦 *Pedido:* #{$orderNo}\n"
                 . "🏪 *Tienda:* {$storeName}\n"
                 . "📋 *Items:* {$items} productos\n"
                 . "💰 *Total:* S/ {$total}\n"
                 . "👤 *Cliente:* {$customerName}\n"
                 . "📞 *Teléfono:* {$customerPhone}\n"
                 . "📍 *Dirección:* {$address}\n\n"
                 . "Revisa la app para más detalles.";

        return $this->sendToWhatsApp($phone, $message);
    }

    /**
     * Notify about a new favor (delivery task)
     */
    public function notifyNewFavor(object $favor): bool
    {
        $phone = null;
        $sellerName = null;

        if ($favor->seller_id && $favor->seller) {
            $phone = $this->formatPhone($favor->seller->phone);
            $sellerName = $favor->seller->name;
        }

        if (!$phone) {
            return false;
        }

        $orderNo = $favor->order_no;
        $type = $favor->type === 'buy' ? 'Compra' : 'Envío';
        $description = $favor->description ?? '—';
        $total = number_format($favor->total, 2);
        $storeName = $favor->store_name ?? '—';
        $pickup = $favor->pickup_address ?? '—';
        $delivery = $favor->delivery_address ?? '—';
        $recipient = $favor->recipient_name ?? '—';
        $recipientPhone = $favor->recipient_phone ?? '—';

        $message = "📋 *NUEVO FAVOR - LiztoDelivery* 📋\n\n"
                 . "📦 *Código:* #{$orderNo}\n"
                 . "🔤 *Tipo:* {$type}\n"
                 . "📝 *Descripción:* {$description}\n"
                 . "🏪 *Tienda:* {$storeName}\n"
                 . "💰 *Total:* S/ {$total}\n"
                 . "📍 *Recojo:* {$pickup}\n"
                 . "📍 *Entrega:* {$delivery}\n"
                 . "👤 *Destinatario:* {$recipient}\n"
                 . "📞 *Tel:* {$recipientPhone}\n\n"
                 . "Revisa la app para más detalles.";

        return $this->sendToWhatsApp($phone, $message);
    }

    /**
     * Send the message via OpenClaw Gateway API
     */
    public function sendToWhatsApp(string $phone, string $message): bool
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . $this->gatewayToken,
                'Content-Type' => 'application/json',
            ])->post($this->gatewayUrl . '/tools/invoke', [
                'tool' => 'sessions_send',
                'args' => [
                    'sessionKey' => $phone,
                    'message' => $message,
                ],
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp sent to {$phone} via OpenClaw", [
                    'response' => $response->json()
                ]);
                return true;
            }

            Log::warning("WhatsApp send failed to {$phone}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp exception for {$phone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format phone to E.164 for OpenClaw session matching
     */
    public function formatPhone(?string $phone): string
    {
        if (!$phone) return '';

        // Remove non-numeric
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // If it starts with 51, keep as is (Peru)
        if (strlen($clean) === 9) {
            $clean = '51' . $clean;
        }
        // If it starts with +51 or 51 and has 11+ digits
        if (strlen($clean) >= 11 && substr($clean, 0, 2) === '51') {
            return '+' . $clean;
        }

        return '+' . $clean;
    }
}
