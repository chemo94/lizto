<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'vehicle_type')) {
                $table->string('vehicle_type', 30)->default('auto')->index();
            }
            if (!Schema::hasColumn('services', 'allow_offer')) {
                $table->boolean('allow_offer')->default(true);
            }
            if (!Schema::hasColumn('services', 'min_vehicle_capacity')) {
                $table->unsignedTinyInteger('min_vehicle_capacity')->default(1);
            }
            if (!Schema::hasColumn('services', 'max_passengers')) {
                $table->unsignedTinyInteger('max_passengers')->default(12);
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'passenger_capacity')) {
                $table->unsignedTinyInteger('passenger_capacity')->default(4);
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'passenger_capacity')) {
                $table->dropColumn('passenger_capacity');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            foreach (['vehicle_type', 'allow_offer', 'min_vehicle_capacity', 'max_passengers'] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
