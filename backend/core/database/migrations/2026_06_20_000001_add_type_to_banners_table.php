<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // 'taxi' = banners para el bottom sheet de "¿A dónde vas?"
            // 'delivery' = banners para el PromoSlider de Comida/Delivery
            $table->string('type', 20)->default('taxi')->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
