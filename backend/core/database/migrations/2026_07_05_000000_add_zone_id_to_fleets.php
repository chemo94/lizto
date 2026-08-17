<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('fleets', 'zone_id')) {
            Schema::table('fleets', function (Blueprint $table) {
                $table->unsignedBigInteger('zone_id')->nullable()->after('owner_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('fleets', 'zone_id')) {
            Schema::table('fleets', function (Blueprint $table) {
                $table->dropColumn('zone_id');
            });
        }
    }
};
