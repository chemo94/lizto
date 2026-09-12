<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->morphs('job');
            $table->string('source', 40)->nullable();
            $table->string('status', 20)->default('offered');
            $table->boolean('notification_delivered')->default(false);
            $table->timestamp('offered_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['driver_id', 'offered_at']);
            $table->index(['job_type', 'job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_job_offers');
    }
};
