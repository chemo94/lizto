<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ride_queues')) {
            Schema::create('ride_queues', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ride_id');
                $table->string('action_type');
                $table->integer('ordering');
                $table->integer('dispatch_count')->default(0);
                $table->timestamps();

                $table->index('ordering');
                $table->index('dispatch_count');
                $table->foreign('ride_id')->references('id')->on('rides')->onDelete('cascade');
            });
            return;
        }

        if (!Schema::hasColumn('ride_queues', 'dispatch_count')) {
            Schema::table('ride_queues', function (Blueprint $table) {
                $table->integer('dispatch_count')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_queues');
    }
};
