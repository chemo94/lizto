<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrder;
use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

class TestWhatsAppOrderNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wa:test-order {order_id? : ID del pedido a probar} {--phone= : Número destino personalizado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el envío del mensaje de pedido por WhatsApp usando el microservicio waapi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("==================================================");
        $this->info("  PROBADOR DE NOTIFICACIÓN WHATSAPP (waapi)       ");
        $this->info("==================================================");

        $baseUrl  = config('services.waapi.base_url');
        $apiKey   = config('services.waapi.api_key');
        $defAdmin = config('services.waapi.admin_phone');

        $this->line("• Microservicio URL: <comment>{$baseUrl}</comment>");
        $this->line("• API Key:           <comment>" . (substr($apiKey, 0, 8) . '...' . substr($apiKey, -4)) . "</comment>");
        $this->line("• Admin Phone Fijo:  <comment>{$defAdmin}</comment>");
        $this->newLine();

        $orderId = $this->argument('order_id');
        $customPhone = $this->option('phone');

        $order = null;
        if ($orderId) {
            $order = DeliveryOrder::with(['store.seller', 'user', 'items.variation', 'items.addons'])->find($orderId);
            if (!$order) {
                $this->error("No se encontró el pedido con ID {$orderId}.");
                return self::FAILURE;
            }
        } else {
            // Intentar obtener el último pedido
            try {
                $order = DeliveryOrder::with(['store.seller', 'user', 'items.variation', 'items.addons'])->latest()->first();
            } catch (\Throwable $e) {
                $this->warn("No se pudo consultar la base de datos: " . $e->getMessage());
            }
        }

        if ($order) {
            $this->info("Usando Pedido real: #{$order->order_no} (ID: {$order->id})");
            $formattedMessage = WhatsAppNotificationService::formatOrderMessage($order);
        } else {
            $this->warn("No hay pedidos en la BD. Creando pedido simulado para la prueba...");
            $order = new DeliveryOrder();
            $order->setRawAttributes([
                'order_no'            => 'ORD-PRUEBA-' . rand(1000, 9999),
                'status'              => 'pending',
                'subtotal'            => 45.00,
                'delivery_fee'        => 5.00,
                'discount'            => 0.00,
                'tip'                 => 2.00,
                'total'               => 52.00,
                'delivery_address'    => 'Jr. San Martín 450, Moyobamba, San Martín',
                'delivery_lat'        => -6.033333,
                'delivery_lng'        => -76.966667,
                'contact_name'        => 'Juan Pérez (Cliente de Prueba)',
                'contact_phone'       => '997428341',
                'notes'               => 'Casa de dos pisos portón negro. Timbre 2.',
                'payment_method_name' => 'Efectivo',
                'cash_pay_amount'     => 100.00,
            ], true);
            $order->created_at = now();

            // Set relation mocks without DB appends
            $storeMock = new class extends \App\Models\Store {
                protected $appends = [];
            };
            $storeMock->setRawAttributes([
                'name'  => 'Restaurante El Buen Sabor',
                'phone' => '997428341',
            ], true);
            $order->setRelation('store', $storeMock);

            $item1 = new \App\Models\DeliveryOrderItem();
            $item1->setRawAttributes([
                'product_name' => 'Hamburguesa Clásica Doble',
                'quantity'     => 2,
                'unit_price'   => 18.00,
                'total_price'  => 36.00,
            ], true);

            $varMock = new \App\Models\DeliveryOrderItemVariation();
            $varMock->setRawAttributes([
                'variation_name'  => 'Con Papas Nativas',
                'variation_price' => 0,
            ], true);
            $item1->setRelation('variation', $varMock);

            $addonMock = new \App\Models\DeliveryOrderItemAddon();
            $addonMock->setRawAttributes([
                'addon_name'  => 'Queso Extra',
                'addon_price' => 3.00,
            ], true);
            $item1->setRelation('addons', collect([$addonMock]));

            $item2 = new \App\Models\DeliveryOrderItem();
            $item2->setRawAttributes([
                'product_name' => 'Gaseosa Inka Cola 500ml',
                'quantity'     => 1,
                'unit_price'   => 9.00,
                'total_price'  => 9.00,
            ], true);

            $order->setRelation('items', collect([$item1, $item2]));
            $formattedMessage = WhatsAppNotificationService::formatOrderMessage($order);
        }

        $this->newLine();
        $this->info("--- VISTA PREVIA DEL MENSAJE FORMATEADO ---");
        $this->line($formattedMessage);
        $this->info("-------------------------------------------");
        $this->newLine();

        if ($customPhone) {
            $dest = WhatsAppNotificationService::normalizePhoneNumber($customPhone);
            $this->info("Enviando mensaje de prueba al teléfono personalizado: {$dest}...");
            $res = WhatsAppNotificationService::sendTextMessage($dest, $formattedMessage);
            $this->line("Resultado: " . json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return !empty($res['success']) ? self::SUCCESS : self::FAILURE;
        }

        $this->info("Enviando notificación completa del pedido...");
        $res = WhatsAppNotificationService::sendOrderNotification($order, force: true);

        $this->line("Resumen del despacho:");
        $this->line(json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
