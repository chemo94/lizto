<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pos_areas', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_areas', 'floorplan_image')) {
                $table->string('floorplan_image')->nullable()->after('sort_order');
            }
        });
    }

    public function down()
    {
        Schema::table('pos_areas', function (Blueprint $table) {
            $table->dropColumn('floorplan_image');
        });
    }
};
