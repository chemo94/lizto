<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_messages', function (Blueprint $table) {
            $table->id();
            $table->morphs('job'); // job_type (DeliveryOrder / Favor), job_id
            $table->nullableMorphs('sender'); // sender_type (Driver / User), sender_id
            $table->string('sender_name')->nullable();
            $table->string('sender_role', 20); // courier, customer, admin
            $table->text('message')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_messages');
    }
};
