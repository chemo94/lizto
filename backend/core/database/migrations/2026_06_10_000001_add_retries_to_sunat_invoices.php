<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('sunat_invoices', 'retries')) {
            Schema::table('sunat_invoices', function (Blueprint $table) {
                $table->integer('retries')->default(0)->after('errors');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('sunat_invoices', 'retries')) {
            Schema::table('sunat_invoices', function (Blueprint $table) {
                $table->dropColumn('retries');
            });
        }
    }
};
