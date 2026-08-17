<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            // Sprint 1: Auto Dispatch
            if (!Schema::hasColumn('favors', 'dispatch_mode')) {
                $table->string('dispatch_mode', 20)->nullable()->after('payer_type');
            }
            if (!Schema::hasColumn('favors', 'dispatch_timeout_at')) {
                $table->timestamp('dispatch_timeout_at')->nullable()->after('dispatch_mode');
            }

            // Sprint 1: Dynamic ETA
            if (!Schema::hasColumn('favors', 'estimated_minutes')) {
                $table->integer('estimated_minutes')->nullable()->after('dispatch_timeout_at');
            }
            if (!Schema::hasColumn('favors', 'eta_updated_at')) {
                $table->timestamp('eta_updated_at')->nullable()->after('estimated_minutes');
            }

            // Sprint 2: Package Details
            if (!Schema::hasColumn('favors', 'package_weight_kg')) {
                $table->decimal('package_weight_kg', 6, 2)->nullable()->after('eta_updated_at');
            }
            if (!Schema::hasColumn('favors', 'package_dimensions')) {
                $table->string('package_dimensions', 50)->nullable()->after('package_weight_kg');
            }
            if (!Schema::hasColumn('favors', 'is_fragile')) {
                $table->boolean('is_fragile')->default(false)->after('package_dimensions');
            }
            if (!Schema::hasColumn('favors', 'item_value')) {
                $table->decimal('item_value', 10, 2)->nullable()->after('is_fragile');
            }

            // Sprint 2: Time Slot
            if (!Schema::hasColumn('favors', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('item_value');
            }
            if (!Schema::hasColumn('favors', 'time_slot')) {
                $table->string('time_slot', 30)->nullable()->after('scheduled_at');
            }

            // Sprint 2: Structured Cancellation
            if (!Schema::hasColumn('favors', 'cancelled_by')) {
                $table->string('cancelled_by', 20)->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('favors', 'cancel_reason_code')) {
                $table->string('cancel_reason_code', 50)->nullable()->after('cancelled_by');
            }

            // Sprint 3: Express/Priority
            if (!Schema::hasColumn('favors', 'is_express')) {
                $table->boolean('is_express')->default(false)->after('cancel_reason_code');
            }
            if (!Schema::hasColumn('favors', 'priority_level')) {
                $table->integer('priority_level')->default(0)->after('is_express');
            }
        });
    }

    public function down(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            $columns = [
                'dispatch_mode', 'dispatch_timeout_at',
                'estimated_minutes', 'eta_updated_at',
                'package_weight_kg', 'package_dimensions', 'is_fragile', 'item_value',
                'scheduled_at', 'time_slot',
                'cancelled_by', 'cancel_reason_code',
                'is_express', 'priority_level',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('favors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
