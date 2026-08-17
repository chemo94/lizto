<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sunat_invoices', 'detail_mode')) {
                $table->string('detail_mode', 20)->default('detailed')->after('cliente_nombre');
            }
            if (!Schema::hasColumn('sunat_invoices', 'consumption_description')) {
                $table->string('consumption_description', 250)->nullable()->after('detail_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sunat_invoices', 'consumption_description')) $table->dropColumn('consumption_description');
            if (Schema::hasColumn('sunat_invoices', 'detail_mode')) $table->dropColumn('detail_mode');
        });
    }
};
