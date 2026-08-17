<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sunat_invoices', 'total_exonerada')) {
                $table->decimal('total_exonerada', 28, 8)->default(0)->after('total_gravada');
            }
            if (!Schema::hasColumn('sunat_invoices', 'total_inafecta')) {
                $table->decimal('total_inafecta', 28, 8)->default(0)->after('total_exonerada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            $table->dropColumn(['total_exonerada', 'total_inafecta']);
        });
    }
};
