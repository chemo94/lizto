<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot del tipo tributario al momento de crear el ítem del pedido
        Schema::table('pos_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_order_items', 'tax_type')) {
                $table->string('tax_type')->default('gravado')->after('total_price');
                // gravado | exonerado | inafecto
            }
        });

        // Desglose tributario en el comprobante emitido
        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sunat_invoices', 'total_exonerada')) {
                $table->decimal('total_exonerada', 12, 2)->default(0)->after('total_gravada');
            }
            if (!Schema::hasColumn('sunat_invoices', 'total_inafecta')) {
                $table->decimal('total_inafecta', 12, 2)->default(0)->after('total_exonerada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('pos_order_items', 'tax_type')) {
                $table->dropColumn('tax_type');
            }
        });

        Schema::table('sunat_invoices', function (Blueprint $table) {
            foreach (['total_exonerada', 'total_inafecta'] as $col) {
                if (Schema::hasColumn('sunat_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
