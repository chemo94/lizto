<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('store_categories', 'external_id')) {
                $table->string('external_id', 100)->nullable()->after('sort_order');
                $table->index(['store_id', 'external_id']);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'external_id')) {
                $table->string('external_id', 100)->nullable()->after('sort_order');
                $table->index(['store_id', 'external_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            if (Schema::hasColumn('store_categories', 'external_id')) {
                $table->dropIndex(['store_id', 'external_id']);
                $table->dropColumn('external_id');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'external_id')) {
                $table->dropIndex(['store_id', 'external_id']);
                $table->dropColumn('external_id');
            }
        });
    }
};
