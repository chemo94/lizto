<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique();
            $table->tinyInteger('claim_type')->default(1)->comment('1 = Reclamo, 2 = Queja');
            $table->tinyInteger('status')->default(0)->comment('0 = Pendiente, 1 = En Proceso, 2 = Resuelto, 3 = Rechazado');
            $table->string('full_name', 150);
            $table->string('document_type', 30);
            $table->string('document_number', 30);
            $table->string('phone', 30);
            $table->string('email', 100);
            $table->string('address', 255);
            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name', 150)->nullable();
            $table->string('guardian_document_type', 30)->nullable();
            $table->string('guardian_document_number', 30)->nullable();
            $table->tinyInteger('item_type')->default(1)->comment('1 = Producto, 2 = Servicio');
            $table->decimal('amount_claimed', 10, 2)->default(0.00);
            $table->text('item_description');
            $table->text('detail');
            $table->text('request');
            $table->text('provider_actions')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
