<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'delivery_min_fee')) {
                $table->decimal('delivery_min_fee', 28, 8)->default(4)->after('min_fare');
            }
            if (!Schema::hasColumn('general_settings', 'delivery_fee_per_km')) {
                $table->decimal('delivery_fee_per_km', 28, 8)->default(1)->after('delivery_min_fee');
            }
            if (!Schema::hasColumn('general_settings', 'delivery_coverage_radius')) {
                $table->decimal('delivery_coverage_radius', 10, 2)->default(10)->after('delivery_fee_per_km');
            }
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'cash_pay_amount')) {
                $table->decimal('cash_pay_amount', 28, 8)->nullable()->after('payment_status');
            }
        });

        Schema::table('favors', function (Blueprint $table) {
            if (!Schema::hasColumn('favors', 'cash_pay_amount')) {
                $table->decimal('cash_pay_amount', 28, 8)->nullable()->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            if (Schema::hasColumn('favors', 'cash_pay_amount')) {
                $table->dropColumn('cash_pay_amount');
            }
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_orders', 'cash_pay_amount')) {
                $table->dropColumn('cash_pay_amount');
            }
        });

        Schema::table('general_settings', function (Blueprint $table) {
            foreach (['delivery_min_fee', 'delivery_fee_per_km', 'delivery_coverage_radius'] as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
