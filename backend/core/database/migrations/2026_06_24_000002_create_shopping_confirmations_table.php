<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shopping_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('favor_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['receipt', 'product_photo', 'substitution', 'delivery_proof']);
            $table->string('image_url');
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by_type');
            $table->unsignedBigInteger('created_by_id');
            $table->timestamps();

            $table->index(['favor_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shopping_confirmations');
    }
};
