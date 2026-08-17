<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shopping_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('favor_id')->constrained()->cascadeOnDelete()->unique();
            $table->decimal('max_product_budget', 10, 2)->default(0);
            $table->decimal('max_delivery_fee', 10, 2)->nullable();
            $table->decimal('actual_product_cost', 10, 2)->nullable();
            $table->decimal('actual_delivery_fee', 10, 2)->nullable();
            $table->string('payment_method', 20)->default('cash');
            $table->enum('payment_status', ['unpaid', 'prepaid', 'collecting', 'settled'])->default('unpaid');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shopping_budgets');
    }
};
