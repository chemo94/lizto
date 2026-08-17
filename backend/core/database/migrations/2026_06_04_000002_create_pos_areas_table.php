<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pos_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('pos_tables', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_tables', 'pos_area_id')) {
                $table->foreignId('pos_area_id')->nullable()->constrained('pos_areas')->nullOnDelete();
            }
            if (!Schema::hasColumn('pos_tables', 'pos_x')) {
                $table->integer('pos_x')->default(0);
            }
            if (!Schema::hasColumn('pos_tables', 'pos_y')) {
                $table->integer('pos_y')->default(0);
            }
            if (!Schema::hasColumn('pos_tables', 'shape')) {
                $table->string('shape')->default('square');
            }
        });
    }

    public function down()
    {
        Schema::table('pos_tables', function (Blueprint $table) {
            $table->dropForeign(['pos_area_id']);
            $table->dropColumn(['pos_area_id', 'pos_x', 'pos_y', 'shape']);
        });
        Schema::dropIfExists('pos_areas');
    }
};
