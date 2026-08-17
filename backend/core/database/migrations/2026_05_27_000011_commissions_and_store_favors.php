<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Update delivery_commissions for % vs fixed per role
        Schema::table('delivery_commissions', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_commissions', 'courier_commission_type'))
                $table->enum('courier_commission_type', ['percent', 'fixed'])->default('percent')->after('min_commission');
            if (!Schema::hasColumn('delivery_commissions', 'courier_fixed_amount'))
                $table->decimal('courier_fixed_amount', 28, 8)->default(0)->after('courier_commission_type');
            if (!Schema::hasColumn('delivery_commissions', 'store_commission_type'))
                $table->enum('store_commission_type', ['percent', 'fixed'])->default('percent')->after('courier_fixed_amount');
            if (!Schema::hasColumn('delivery_commissions', 'store_fixed_amount'))
                $table->decimal('store_fixed_amount', 28, 8)->default(0)->after('store_commission_type');
        });

        // Add source tracking to favors (who created it: user or seller)
        Schema::table('favors', function (Blueprint $table) {
            if (!Schema::hasColumn('favors', 'source_type'))
                $table->string('source_type', 20)->default('user')->after('user_id');
            if (!Schema::hasColumn('favors', 'seller_id'))
                $table->foreignId('seller_id')->nullable()->after('user_id');
        });
    }

    public function down()
    {
        Schema::table('delivery_commissions', function (Blueprint $table) {
            $table->dropColumn(['courier_commission_type', 'courier_fixed_amount', 'store_commission_type', 'store_fixed_amount']);
        });
        Schema::table('favors', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'seller_id']);
        });
    }
};
