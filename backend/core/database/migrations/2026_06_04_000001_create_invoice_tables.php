<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pos_invoice_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('code')->comment('01=Factura, 03=Boleta, 07=NotaCredito, 08=NotaDebito, NV=NotaVenta');
            $table->string('name');
            $table->string('sunat_code')->nullable();
            $table->boolean('is_electronic')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('pos_invoice_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_type_id')->constrained('pos_invoice_types')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('series')->comment('Ej: B001, F001, NV01');
            $table->bigInteger('current_number')->default(1);
            $table->bigInteger('max_number')->default(99999999);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Add document fields to sellers
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'document_type')) {
                $table->string('document_type')->nullable();
            }
            if (!Schema::hasColumn('sellers', 'document_number')) {
                $table->string('document_number')->nullable();
            }
            if (!Schema::hasColumn('sellers', 'business_name')) {
                $table->string('business_name')->nullable();
            }
            if (!Schema::hasColumn('sellers', 'trade_name')) {
                $table->string('trade_name')->nullable();
            }
            if (!Schema::hasColumn('sellers', 'address')) {
                $table->string('address')->nullable();
            }
            if (!Schema::hasColumn('sellers', 'ubigeo')) {
                $table->string('ubigeo')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_invoice_series');
        Schema::dropIfExists('pos_invoice_types');
    }
};
