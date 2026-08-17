<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'delivery_time_rate')) {
                $table->decimal('delivery_time_rate', 5, 2)->default(0.30)->after('delivery_base_km')
                    ->comment('Tarifa por minuto estimado (S/min)');
            }
            if (!Schema::hasColumn('general_settings', 'delivery_avg_speed')) {
                $table->decimal('delivery_avg_speed', 5, 1)->default(20)->after('delivery_time_rate')
                    ->comment('Velocidad promedio para estimar tiempo (km/h)');
            }
            if (!Schema::hasColumn('general_settings', 'delivery_surge')) {
                $table->decimal('delivery_surge', 4, 2)->default(1.00)->after('delivery_avg_speed')
                    ->comment('Multiplicador de demanda (1.0 = normal, 1.5 = alta demanda)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['delivery_time_rate', 'delivery_avg_speed', 'delivery_surge']);
        });
    }
};
