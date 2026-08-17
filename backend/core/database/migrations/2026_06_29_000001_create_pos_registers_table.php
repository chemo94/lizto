<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pos_registers table (named cash registers: Caja 01, Caja Bar, etc.)
        Schema::create('pos_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('name');               // "Caja 01", "Caja Bar", etc.
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add register_id to pos_cash_sessions
        if (!Schema::hasColumn('pos_cash_sessions', 'pos_register_id')) {
            Schema::table('pos_cash_sessions', function (Blueprint $table) {
                $table->foreignId('pos_register_id')->nullable()->after('seller_id')
                      ->constrained('pos_registers')->nullOnDelete();
            });
        }

        // 3. Add register_id to pos_staff (each staff member is assigned to a register)
        if (!Schema::hasColumn('pos_staff', 'pos_register_id')) {
            Schema::table('pos_staff', function (Blueprint $table) {
                $table->foreignId('pos_register_id')->nullable()->after('seller_id')
                      ->constrained('pos_registers')->nullOnDelete();
            });
        }

        // 4. Add max_registers column to business_packages
        if (!Schema::hasColumn('business_packages', 'max_registers')) {
            Schema::table('business_packages', function (Blueprint $table) {
                $table->unsignedSmallInteger('max_registers')->default(1)->after('sort_order');
            });

            // Set limits by plan type
            DB::table('business_packages')->where('type', 'basic')->update(['max_registers' => 1]);
            DB::table('business_packages')->where('type', 'featured')->update(['max_registers' => 2]);
            DB::table('business_packages')->where('type', 'premium')->update(['max_registers' => 5]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_staff', 'pos_register_id')) {
            Schema::table('pos_staff', function (Blueprint $table) {
                $table->dropForeign(['pos_register_id']);
                $table->dropColumn('pos_register_id');
            });
        }

        if (Schema::hasColumn('pos_cash_sessions', 'pos_register_id')) {
            Schema::table('pos_cash_sessions', function (Blueprint $table) {
                $table->dropForeign(['pos_register_id']);
                $table->dropColumn('pos_register_id');
            });
        }

        if (Schema::hasColumn('business_packages', 'max_registers')) {
            Schema::table('business_packages', function (Blueprint $table) {
                $table->dropColumn('max_registers');
            });
        }

        Schema::dropIfExists('pos_registers');
    }
};
