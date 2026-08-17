<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['inv_items', 'inv_suppliers', 'inv_purchases', 'pos_orders', 'pos_tables'];
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) continue;
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tables = ['inv_items', 'inv_suppliers', 'inv_purchases', 'pos_orders', 'pos_tables'];
        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'deleted_at')) continue;
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
