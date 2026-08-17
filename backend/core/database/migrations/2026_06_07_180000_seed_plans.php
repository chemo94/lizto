<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Disable FK checks to allow truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('store_packages')->truncate();
        DB::table('business_packages')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Insert the 3 correct plans
        $now = now();
        DB::table('business_packages')->insert([
            [
                'name'          => 'Gratis',
                'type'          => 'free',
                'slug'          => 'gratis',
                'price'         => 0,
                'duration_days' => 0,
                'sort_order'    => 0,
                'features'      => json_encode([
                    'POS + Pedidos en 1 pantalla',
                    'Cocina (Comandas)',
                    'Tipos de pedido (DAZ, LLAMA, Rappi, PedidosYa)',
                    'Caja / Arqueo',
                    'Gestión de Productos',
                    'Menú Digital + QR descargable',
                    'Pedidos Delivery App',
                    'Pedido Externo Rápido',
                    'Facturación Electrónica SUNAT (Límite: 50 comprobantes/mes)',
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'          => 'Destacado',
                'type'          => 'featured',
                'slug'          => 'destacado',
                'price'         => 29.90,
                'duration_days' => 30,
                'sort_order'    => 1,
                'features'      => json_encode([
                    'Top 5 en resultados de búsqueda',
                    'Badge Destacado visible',
                    '30 días de visibilidad',
                    'Campañas SMS (100 créditos/mes)',
                    'Estadísticas básicas',
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'          => 'Premium',
                'type'          => 'premium',
                'slug'          => 'premium',
                'price'         => 49.90,
                'duration_days' => 30,
                'sort_order'    => 2,
                'features'      => json_encode([
                    'Todo lo del plan Destacado',
                    'QR de menú personalizado',
                    'Campañas SMS ilimitadas',
                    'Notificaciones push a clientes',
                    'Facturación electrónica SUNAT',
                    'Gestión de inventario completo',
                    'Reportes avanzados con gráficos',
                    'Stock tracking y Kardex',
                    'Soporte prioritario 24/7',
                ]),
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        // No rollback — destructive but intentional
    }
};
