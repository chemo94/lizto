<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('store_packages', 'invoice_quota_starts_at')) {
            Schema::table('store_packages', function (Blueprint $table) {
                $table->timestamp('invoice_quota_starts_at')->nullable()->after('starts_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('store_packages', 'invoice_quota_starts_at')) {
            Schema::table('store_packages', function (Blueprint $table) {
                $table->dropColumn('invoice_quota_starts_at');
            });
        }
    }
};
