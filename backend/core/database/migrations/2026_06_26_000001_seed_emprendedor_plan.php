<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert the Emprendedor (basic) plan
        $now = now();
        DB::table('business_packages')->insert([
            'name'          => 'Emprendedor',
            'type'          => 'basic',
            'slug'          => 'emprendedor',
            'price'         => 29.90,
            'duration_days' => 30,
            'sort_order'    => 0,
            'features'      => json_encode([
                'POS + Pedidos en tiempo real',
                'Cocina (Pantalla de Comandas)',
                'Tipos de pedido (Mesa, Delivery, etc.)',
                'Control de Caja / Arqueos diarios',
                'Gestión de Productos y Categorías',
                'Menú Digital con Código QR',
                'Facturación Electrónica SUNAT (Límite: 50 comprobantes/mes)',
                'Límite: 1 sola Empresa',
            ]),
            'status'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Update Destacado (featured) price from 29.90 to 39.90
        DB::table('business_packages')
            ->where('type', 'featured')
            ->update(['price' => 39.90, 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('business_packages')->where('type', 'basic')->delete();
        DB::table('business_packages')->where('type', 'featured')->update(['price' => 29.90]);
    }
};
