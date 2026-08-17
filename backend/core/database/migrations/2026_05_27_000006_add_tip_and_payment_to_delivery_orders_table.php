<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->decimal('tip', 28, 8)->default(0)->after('discount');
            $table->decimal('payment_method_code', 28, 8)->nullable()->after('cancel_reason');
            $table->string('payment_method_name')->nullable()->after('payment_method_code');
            $table->tinyInteger('payment_status')->default(0)->after('payment_method_name');
        });

        Schema::create('delivery_order_item_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_item_id')->constrained()->cascadeOnDelete();
            $table->string('addon_name');
            $table->decimal('addon_price', 28, 8)->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_order_item_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_item_id')->constrained()->cascadeOnDelete();
            $table->string('variation_name');
            $table->decimal('variation_price', 28, 8)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_item_variations');
        Schema::dropIfExists('delivery_order_item_addons');
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn(['tip', 'payment_method_code', 'payment_method_name', 'payment_status']);
        });
    }
};
