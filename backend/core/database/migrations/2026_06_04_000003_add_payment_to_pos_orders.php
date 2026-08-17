<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_orders', 'payment_status')) {
                $table->string('payment_status')->default('pending');
            }
            if (!Schema::hasColumn('pos_orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (!Schema::hasColumn('pos_orders', 'invoice_series')) {
                $table->string('invoice_series')->nullable();
            }
            if (!Schema::hasColumn('pos_orders', 'invoice_number')) {
                $table->string('invoice_number')->nullable();
            }
            if (!Schema::hasColumn('pos_orders', 'invoice_type_id')) {
                $table->foreignId('invoice_type_id')->nullable()->constrained('pos_invoice_types')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_orders', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropForeign(['invoice_type_id']);
            $table->dropColumn(['payment_status','paid_at','invoice_series','invoice_number','invoice_type_id','payment_method']);
        });
    }
};
