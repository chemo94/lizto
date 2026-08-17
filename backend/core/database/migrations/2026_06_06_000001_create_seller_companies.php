<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seller_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('document_number');
            $table->string('business_name');
            $table->string('trade_name')->nullable();
            $table->string('ubigeo')->nullable();
            $table->string('address')->nullable();
            $table->string('sunat_sol_user')->nullable();
            $table->string('sunat_sol_pass')->nullable();
            $table->string('sunat_env')->default('beta');
            $table->string('sunat_cert_path')->nullable();
            $table->string('sunat_cert_pass')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::table('pos_invoice_types', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_invoice_types', 'seller_company_id')) {
                $table->foreignId('seller_company_id')->nullable()->constrained('seller_companies')->nullOnDelete();
            }
        });

        Schema::table('pos_invoice_series', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_invoice_series', 'seller_company_id')) {
                $table->foreignId('seller_company_id')->nullable()->constrained('seller_companies')->nullOnDelete();
            }
        });

        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sunat_invoices', 'seller_company_id')) {
                $table->foreignId('seller_company_id')->nullable()->constrained('seller_companies')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_company_id');
        });
        Schema::table('pos_invoice_series', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_company_id');
        });
        Schema::table('pos_invoice_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_company_id');
        });
        Schema::dropIfExists('seller_companies');
    }
};
