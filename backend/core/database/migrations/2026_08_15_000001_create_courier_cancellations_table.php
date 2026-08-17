<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('drivers')->cascadeOnDelete();
            $table->unsignedBigInteger('job_id');
            $table->string('job_type', 20);
            $table->string('reason_code', 50);
            $table->string('reason_detail', 500)->nullable();
            $table->unsignedSmallInteger('penalty_minutes')->default(0);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamps();

            $table->index(['courier_id', 'created_at']);
            $table->index(['courier_id', 'blocked_until']);
            $table->index(['job_type', 'job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_cancellations');
    }
};
