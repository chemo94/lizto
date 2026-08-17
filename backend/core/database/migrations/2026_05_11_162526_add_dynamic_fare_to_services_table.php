<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('city_base_fare', 28, 8)->unsigned()->default(0);
            $table->decimal('city_rate_per_km', 28, 8)->unsigned()->default(0);
            $table->decimal('intercity_base_fare', 28, 8)->unsigned()->default(0);
            $table->decimal('intercity_rate_per_km', 28, 8)->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['city_base_fare', 'city_rate_per_km', 'intercity_base_fare', 'intercity_rate_per_km']);
        });
    }
};
