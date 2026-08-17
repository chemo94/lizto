<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_orders', 'payment_details')) {
                $table->text('payment_details')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropColumn('payment_details');
        });
    }
};
