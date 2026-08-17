<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'delivery_base_km')) {
                $table->decimal('delivery_base_km', 5, 1)->default(3)->after('delivery_fee_per_km')->comment('Kilómetros incluidos en la tarifa base');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('delivery_base_km');
        });
    }
};
