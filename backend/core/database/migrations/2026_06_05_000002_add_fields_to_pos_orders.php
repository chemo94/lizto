<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('pos_orders', 'invoice_type_code')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->string('invoice_type_code')->nullable();
            });
        }
        if (!Schema::hasColumn('pos_orders', 'customer_doc')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->string('customer_doc')->nullable();
            });
        }
        if (!Schema::hasColumn('pos_orders', 'customer_doc_type')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->string('customer_doc_type')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_type_code', 'customer_doc', 'customer_doc_type']);
        });
    }
};
