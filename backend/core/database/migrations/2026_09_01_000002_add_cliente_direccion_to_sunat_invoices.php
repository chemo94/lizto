<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sunat_invoices', 'cliente_direccion')) {
            Schema::table('sunat_invoices', function (Blueprint $table) {
                $table->string('cliente_direccion', 500)->nullable()->after('cliente_nombre');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sunat_invoices', 'cliente_direccion')) {
            Schema::table('sunat_invoices', function (Blueprint $table) {
                $table->dropColumn('cliente_direccion');
            });
        }
    }
};
