<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->decimal('favor_min_fee', 28, 8)->default(5)->after('delivery_surge')
                ->comment('Tarifa mínima para favores (send package)');
            $table->decimal('favor_fee_per_km', 28, 8)->default(2)->after('favor_min_fee')
                ->comment('Tarifa por km extra para favores');
            $table->decimal('favor_base_km', 5, 1)->default(2)->after('favor_fee_per_km')
                ->comment('Kilómetros incluidos en tarifa base de favores');
            $table->decimal('favor_time_rate', 5, 2)->default(0.35)->after('favor_base_km')
                ->comment('Tarifa por minuto estimado para favores');
            $table->decimal('favor_avg_speed', 5, 1)->default(25)->after('favor_time_rate')
                ->comment('Velocidad promedio para favores (km/h)');
            $table->decimal('favor_surge', 4, 2)->default(1.00)->after('favor_avg_speed')
                ->comment('Multiplicador de demanda para favores');
            $table->decimal('favor_coverage_radius', 10, 2)->default(15)->after('favor_surge')
                ->comment('Radio de cobertura para favores (km)');
        });
    }

    public function down()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'favor_min_fee',
                'favor_fee_per_km',
                'favor_base_km',
                'favor_time_rate',
                'favor_avg_speed',
                'favor_surge',
                'favor_coverage_radius',
            ]);
        });
    }
};
