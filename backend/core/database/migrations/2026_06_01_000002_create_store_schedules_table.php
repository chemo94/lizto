<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('store_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day'); // 0=Sun,1=Mon,...,6=Sat
            $table->time('open_time');
            $table->time('close_time');
            $table->timestamps();

            $table->index(['store_id', 'day']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_schedules');
    }
};
