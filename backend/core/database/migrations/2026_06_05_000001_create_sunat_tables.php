<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // SUNAT config on sellers table
        if (!Schema::hasColumn('sellers', 'sunat_sol_user')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->string('sunat_sol_user')->nullable();
                $table->string('sunat_sol_pass')->nullable();
                $table->string('sunat_cert_path')->nullable();
                $table->string('sunat_cert_pass')->nullable();
                $table->string('sunat_env')->default('beta');
            });
        }

        // SUNAT invoice tracking
        Schema::create('sunat_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo_doc'); // 01=Factura, 03=Boleta, 07=NC, 08=ND
            $table->string('serie');
            $table->integer('correlativo');
            $table->string('cliente_tipo_doc')->default('6'); // 1=DNI, 6=RUC
            $table->string('cliente_num_doc');
            $table->string('cliente_nombre');
            $table->decimal('total_gravada', 28, 8)->default(0);
            $table->decimal('total_igv', 28, 8)->default(0);
            $table->decimal('total', 28, 8)->default(0);
            $table->string('moneda')->default('PEN');
            $table->string('hash')->nullable();
            $table->string('cdr_status')->nullable();
            $table->text('cdr_response')->nullable();
            $table->text('xml_content')->nullable();
            $table->text('sunat_response')->nullable();
            $table->text('errors')->nullable();
            $table->string('ticket')->nullable();
            $table->timestamp('fecha_emision')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sunat_invoices');
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn(['sunat_sol_user','sunat_sol_pass','sunat_cert_path','sunat_cert_pass','sunat_env']);
        });
    }
};
