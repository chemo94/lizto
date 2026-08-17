<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create fleet_owners table
        Schema::create('fleet_owners', function (Blueprint $table) {
            $table->id();
            $table->string('firstname', 40)->nullable();
            $table->string('lastname', 40)->nullable();
            $table->string('username', 40)->unique();
            $table->string('email', 40)->unique();
            $table->string('password');
            $table->string('phone', 40)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });

        // Create fleets table
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable(); // references fleet_owners table
            $table->string('commission_type', 20)->default('percentage'); // percentage or subscription
            $table->decimal('lizto_commission_rate', 5, 2)->default(3.00); // % paid to Lizto
            $table->decimal('driver_commission_rate', 5, 2)->default(15.00); // % charged to drivers
            
            // Fleet custom fares overrides
            $table->decimal('base_fare', 28, 8)->nullable();
            $table->decimal('rate_per_km', 28, 8)->nullable();
            $table->decimal('rate_per_minute', 28, 8)->nullable();
            
            $table->tinyInteger('status')->default(1); // 1 = Active, 0 = Suspended
            $table->timestamps();
            
            $table->foreign('owner_id')->references('id')->on('fleet_owners')->onDelete('set null');
        });

        // Add fleet_id column to drivers table
        if (!Schema::hasColumn('drivers', 'fleet_id')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->unsignedBigInteger('fleet_id')->nullable()->after('id');
            });
        }

        // Add fleet_id column to rides table
        if (!Schema::hasColumn('rides', 'fleet_id')) {
            Schema::table('rides', function (Blueprint $table) {
                $table->unsignedBigInteger('fleet_id')->nullable()->after('id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('rides', 'fleet_id')) {
            Schema::table('rides', function (Blueprint $table) {
                $table->dropColumn('fleet_id');
            });
        }

        if (Schema::hasColumn('drivers', 'fleet_id')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropColumn('fleet_id');
            });
        }

        Schema::dropIfExists('fleets');
        Schema::dropIfExists('fleet_owners');
    }
};
