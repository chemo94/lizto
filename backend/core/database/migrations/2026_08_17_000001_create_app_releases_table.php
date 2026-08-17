<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('app', 30);
            $table->string('platform', 12);
            $table->string('latest_version', 30);
            $table->unsignedInteger('latest_build');
            $table->unsignedInteger('minimum_build')->default(1);
            $table->boolean('force_update')->default(false);
            $table->string('store_url', 1000)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['app', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
