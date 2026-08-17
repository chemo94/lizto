<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_service_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('service_type', ['taxi', 'delivery']);
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('pickup');
            $table->string('destination');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_service_requests');
    }
};
