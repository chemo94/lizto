<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // 'taxi' = cupones aplicables para los viajes/taxis
            // 'delivery' = cupones aplicables para pedidos de comida / delivery
            $table->string('coupon_type', 20)->default('taxi')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('coupon_type');
        });
    }
};
