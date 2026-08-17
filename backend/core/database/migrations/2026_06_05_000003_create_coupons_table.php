<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->enum('type', ['percentage', 'fixed', 'free_delivery'])->default('percentage');
                $table->decimal('value', 28, 8)->default(0);
                $table->decimal('min_order', 28, 8)->default(0);
                $table->decimal('max_discount', 28, 8)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_count')->default(0);
                $table->integer('per_user_limit')->default(1);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->integer('status')->default(1);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('coupon_usage')) {
            Schema::create('coupon_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('delivery_orders')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('free_deliveries')) {
            Schema::create('free_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->integer('remaining')->default(1);
                $table->text('notes')->nullable();
                $table->integer('status')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('free_deliveries');
        Schema::dropIfExists('coupon_usage');
        Schema::dropIfExists('coupons');
    }
};
