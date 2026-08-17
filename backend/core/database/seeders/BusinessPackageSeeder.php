<?php

namespace Database\Seeders;

use App\Models\BusinessPackage;
use Illuminate\Database\Seeder;

class BusinessPackageSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks to allow truncation
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        BusinessPackage::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $packages = [
            [
                'name'          => 'Plan Emprendedor (Básico)',
                'slug'          => 'basico',
                'description'   => 'Ideal para pequeños negocios locales que necesitan controlar sus ventas, caja y comandas.',
                'price'         => 29.90,
                'duration_days' => 30,
                'type'          => 'basic',
                'icon'          => '⭐',
                'features' => json_encode([
                    'POS + Pedidos en tiempo real',
                    'Cocina (Pantalla de Comandas)',
                    'Tipos de pedido (Mesa, Delivery, etc.)',
                    'Control de Caja / Arqueos diarios',
                    'Gestión de Productos y Categorías',
                    'Menú Digital con Código QR',
                    'Facturación Electrónica SUNAT (Límite: 50 comprobantes/mes)',
                    'Límite: 1 sola Empresa'
                ]),
                'sort_order' => 1,
                'status'     => 1,
            ],
            [
                'name'          => 'Plan Profesional (Destacado)',
                'slug'          => 'destacado',
                'description'   => 'Destaca tu negocio en el aplicativo móvil local para recibir el doble de pedidos delivery.',
                'price'         => 59.90,
                'duration_days' => 30,
                'type'          => 'featured',
                'icon'          => '🚀',
                'features' => json_encode([
                    'Todo lo del Plan Emprendedor',
                    'Límite: 1 sola Empresa',
                    'Top 5 en resultados de búsqueda',
                    'Badge "Destacado" visible en App',
                    'Mayor alcance y visibilidad local',
                    'Reportes de venta y rendimiento'
                ]),
                'sort_order' => 2,
                'status'     => 1,
            ],
            [
                'name'          => 'Plan Premium (Empresarial)',
                'slug'          => 'premium',
                'description'   => 'El plan más completo. Gestión de múltiples empresas, facturación SUNAT e inventario.',
                'price'         => 99.90,
                'duration_days' => 30,
                'type'          => 'premium',
                'icon'          => '🏆',
                'features' => json_encode([
                    'Empresas registradas ILIMITADAS',
                    'Notificaciones Push FCM directas',
                    'Facturación Electrónica SUNAT',
                    'Gestión de Inventario (Kardex / Compras)',
                    'Reportes Avanzados e Inteligentes',
                    'Video de portada en perfil',
                    'Soporte prioritario 24/7'
                ]),
                'sort_order' => 3,
                'status'     => 1,
            ],
        ];

        foreach ($packages as $pkg) {
            BusinessPackage::create($pkg);
        }
    }
}
