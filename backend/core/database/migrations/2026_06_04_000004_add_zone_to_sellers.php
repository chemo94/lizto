<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('sellers', 'zone_id')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->unsignedBigInteger('zone_id')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('sellers', 'zone_id')) {
            Schema::table('sellers', function (Blueprint $table) {
                $table->dropColumn('zone_id');
            });
        }
    }
};
