<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_package_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('business_packages')->cascadeOnDelete();
            $table->foreignId('store_package_id')->nullable()->constrained('store_packages')->nullOnDelete();
            $table->string('trx')->unique();
            $table->string('gateway_alias')->default('MercadoPago');
            $table->string('gateway_currency')->nullable();
            $table->decimal('package_amount', 28, 8)->default(0);
            $table->decimal('gateway_fee', 28, 8)->default(0);
            $table->decimal('total_amount', 28, 8)->default(0);
            $table->string('preference_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('status')->default('initiated');
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_package_payments');
    }
};
