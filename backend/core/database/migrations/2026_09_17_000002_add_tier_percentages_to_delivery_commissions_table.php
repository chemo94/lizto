<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_commissions', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_commissions', 'tier_inicial_percent')) {
                $table->decimal('tier_inicial_percent', 5, 2)->default(15.00)->after('courier_fixed_amount');
            }
            if (!Schema::hasColumn('delivery_commissions', 'tier_bronce_percent')) {
                $table->decimal('tier_bronce_percent', 5, 2)->default(13.00)->after('tier_inicial_percent');
            }
            if (!Schema::hasColumn('delivery_commissions', 'tier_plata_percent')) {
                $table->decimal('tier_plata_percent', 5, 2)->default(11.00)->after('tier_bronce_percent');
            }
            if (!Schema::hasColumn('delivery_commissions', 'tier_preferente_percent')) {
                $table->decimal('tier_preferente_percent', 5, 2)->default(10.00)->after('tier_plata_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_commissions', function (Blueprint $table) {
            $cols = ['tier_inicial_percent', 'tier_bronce_percent', 'tier_plata_percent', 'tier_preferente_percent'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('delivery_commissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
