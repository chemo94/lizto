<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_packages', function (Blueprint $table) {
            $table->string('service_mode', 30)->default('restaurant')->after('type')->index();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('service_mode', 30)->default('restaurant')->after('store_type')->index();
        });

        Schema::create('seller_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->unique()->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('business_packages')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('expires_at');
            $table->dateTime('converted_at')->nullable();
            $table->timestamps();
        });

        $now = now();
        $plans = [
            'emprendedor' => [
                'name' => 'Emprendedor', 'type' => 'basic', 'price' => 29.90, 'sort_order' => 10,
                'description' => 'Para controlar ventas, caja, comandas y menú digital.',
                'features' => [
                    ['key'=>'restaurant_platform','name'=>'Sistema POS y pedidos en tiempo real','value'=>'si'],
                    ['key'=>'kitchen','name'=>'Cocina y pantalla de comandas','value'=>'si'],
                    ['key'=>'cash','name'=>'Control de caja y arqueos diarios','value'=>'si'],
                    ['key'=>'products','name'=>'Productos, categorías y menú QR','value'=>'si'],
                    ['key'=>'invoices','name'=>'Comprobantes electrónicos','value'=>'50'],
                    ['key'=>'companies','name'=>'Empresa registrada','value'=>'1'],
                    ['key'=>'delivery_requests','name'=>'Solicitud de repartidores Lizto','value'=>'si'],
                ],
            ],
            'profesional' => [
                'name' => 'Profesional', 'type' => 'featured', 'price' => 39.90, 'sort_order' => 20,
                'description' => 'Mayor visibilidad, inventario y reportes para crecer.',
                'features' => [
                    ['key'=>'restaurant_platform','name'=>'Todo lo del Plan Emprendedor','value'=>'si'],
                    ['key'=>'inventory','name'=>'Gestión de inventario, Kardex y compras','value'=>'si'],
                    ['key'=>'reports','name'=>'Reportes de ventas y rendimiento','value'=>'si'],
                    ['key'=>'invoices','name'=>'Comprobantes electrónicos','value'=>'120'],
                    ['key'=>'delivery_requests','name'=>'Solicitud de repartidores Lizto','value'=>'si'],
                ],
            ],
            'premium' => [
                'name' => 'Premium', 'type' => 'premium', 'price' => 99.90, 'sort_order' => 30,
                'description' => 'Gestión empresarial completa, SUNAT e inventario.',
                'features' => [
                    ['key'=>'restaurant_platform','name'=>'Sistema completo para restaurantes','value'=>'si'],
                    ['key'=>'companies','name'=>'Empresas registradas','value'=>'ilimitado'],
                    ['key'=>'invoicing','name'=>'Facturación electrónica SUNAT','value'=>'si'],
                    ['key'=>'inventory','name'=>'Inventario, Kardex y compras','value'=>'si'],
                    ['key'=>'reports','name'=>'Reportes avanzados e inteligentes','value'=>'si'],
                    ['key'=>'notifications','name'=>'Notificaciones push FCM','value'=>'si'],
                    ['key'=>'delivery_requests','name'=>'Solicitud de repartidores Lizto','value'=>'si'],
                ],
            ],
            'solo-envios' => [
                'name' => 'Solo Envíos', 'type' => 'delivery', 'price' => 20.00, 'sort_order' => 40,
                'description' => 'Para solicitar recojos y entregas desde la web o la app Seller.',
                'features' => [
                    ['key'=>'delivery_requests','name'=>'Solicitudes de envío y recojo','value'=>'si'],
                    ['key'=>'delivery_tracking','name'=>'Seguimiento del repartidor','value'=>'si'],
                    ['key'=>'delivery_history','name'=>'Historial de solicitudes','value'=>'si'],
                ],
            ],
        ];

        foreach ($plans as $slug => $plan) {
            $serviceMode = $slug === 'solo-envios' ? 'delivery_only' : 'restaurant';
            $existing = DB::table('business_packages')->where('slug', $slug)->first();
            if (!$existing && $slug === 'profesional') {
                $existing = DB::table('business_packages')->where('type', 'featured')->first();
            }
            $values = array_merge($plan, [
                'slug' => $slug, 'service_mode' => $serviceMode, 'duration_days' => 30,
                'features' => json_encode($plan['features']), 'status' => 1, 'updated_at' => $now,
            ]);
            if ($existing) {
                DB::table('business_packages')->where('id', $existing->id)->update($values);
            } else {
                DB::table('business_packages')->insert($values + ['created_at' => $now]);
            }
        }

        DB::table('business_packages')->whereNotIn('slug', array_keys($plans))->update(['status' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_trials');
        Schema::table('stores', fn (Blueprint $table) => $table->dropColumn('service_mode'));
        Schema::table('business_packages', fn (Blueprint $table) => $table->dropColumn('service_mode'));
    }
};
