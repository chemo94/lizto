<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inv_purchase_items') && !Schema::hasColumn('inv_purchase_items', 'tax_type')) {
            Schema::table('inv_purchase_items', function (Blueprint $table) {
                $table->string('tax_type', 20)->nullable()->after('unit_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inv_purchase_items') && Schema::hasColumn('inv_purchase_items', 'tax_type')) {
            Schema::table('inv_purchase_items', function (Blueprint $table) {
                $table->dropColumn('tax_type');
            });
        }
    }
};
