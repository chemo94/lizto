<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->string('identity_key', 190);
            $table->string('document_type', 2)->nullable();
            $table->string('document_number', 20)->nullable();
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('address', 500)->nullable();
            $table->timestamps();
            $table->unique(['seller_id', 'identity_key']);
            $table->index(['seller_id', 'document_number']);
            $table->index(['seller_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_customer_profiles');
    }
};
