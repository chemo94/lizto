<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'dispatch_mode')) {
                $table->string('dispatch_mode', 24)->nullable()->after('courier_id');
            }
            if (!Schema::hasColumn('delivery_orders', 'dispatch_timeout_at')) {
                $table->timestamp('dispatch_timeout_at')->nullable()->after('dispatch_mode');
            }
            if (!Schema::hasColumn('delivery_orders', 'dispatch_attempted_driver_ids')) {
                $table->json('dispatch_attempted_driver_ids')->nullable()->after('dispatch_timeout_at');
            }
            $table->index(['status', 'dispatch_mode', 'dispatch_timeout_at'], 'delivery_orders_dispatch_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropIndex('delivery_orders_dispatch_due_idx');
            foreach (['dispatch_attempted_driver_ids', 'dispatch_timeout_at', 'dispatch_mode'] as $column) {
                if (Schema::hasColumn('delivery_orders', $column)) $table->dropColumn($column);
            }
        });
    }
};
