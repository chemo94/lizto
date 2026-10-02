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
        // 1. Wallets: Campos para diferenciar saldo promocional de recargas y estado económico
        Schema::table('wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('wallets', 'promotional_balance')) {
                $table->decimal('promotional_balance', 28, 8)->default(0)->after('balance');
            }
            if (!Schema::hasColumn('wallets', 'recharge_balance')) {
                $table->decimal('recharge_balance', 28, 8)->default(0)->after('promotional_balance');
            }
            if (!Schema::hasColumn('wallets', 'economic_state')) {
                $table->string('economic_state', 40)->default('PROMOTIONAL_BALANCE')->after('recharge_balance');
            }
        });

        // 2. General Settings: Configuración económica de Lizto
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'min_driver_recharge')) {
                $table->decimal('min_driver_recharge', 28, 8)->default(8.00)->after('delivery_coverage_radius');
            }
            if (!Schema::hasColumn('general_settings', 'initial_promotional_credit')) {
                $table->decimal('initial_promotional_credit', 28, 8)->default(15.00)->after('min_driver_recharge');
            }
        });

        // 3. Drivers: Configuración de autoaceptación para motor de pedidos
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'auto_accept_enabled')) {
                $table->boolean('auto_accept_enabled')->default(false)->after('online_status');
            }
            if (!Schema::hasColumn('drivers', 'auto_accept_min_earning')) {
                $table->decimal('auto_accept_min_earning', 28, 8)->default(0)->after('auto_accept_enabled');
            }
            if (!Schema::hasColumn('drivers', 'auto_accept_max_distance')) {
                $table->decimal('auto_accept_max_distance', 28, 8)->default(10.0)->after('auto_accept_min_earning');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['promotional_balance', 'recharge_balance', 'economic_state']);
        });

        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['min_driver_recharge', 'initial_promotional_credit']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['auto_accept_enabled', 'auto_accept_min_earning', 'auto_accept_max_distance']);
        });
    }
};
