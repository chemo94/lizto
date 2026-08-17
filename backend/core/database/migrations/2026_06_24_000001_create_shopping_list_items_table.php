<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('favor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('image_url')->nullable();
            $table->enum('status', [
                'pending', 'found', 'not_found', 'substituted', 'cancelled'
            ])->default('pending');
            $table->string('substitute_name')->nullable();
            $table->decimal('substitute_price', 10, 2)->nullable();
            $table->string('substitute_image_url')->nullable();
            $table->text('substitute_notes')->nullable();
            $table->timestamp('store_confirmed_at')->nullable();
            $table->timestamp('customer_approved_at')->nullable();
            $table->boolean('customer_approved')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['favor_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shopping_list_items');
    }
};
