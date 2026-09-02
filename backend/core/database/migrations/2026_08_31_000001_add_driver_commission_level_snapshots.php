<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_earnings', function (Blueprint $table) {
            $table->string('commission_tier', 30)->nullable()->after('commission');
            $table->decimal('commission_base_percent', 5, 2)->nullable()->after('commission_tier');
            $table->decimal('commission_effective_percent', 5, 2)->nullable()->after('commission_base_percent');
            $table->decimal('commission_minimum', 28, 8)->nullable()->after('commission_effective_percent');
            $table->unsignedInteger('completed_jobs_snapshot')->nullable()->after('commission_minimum');
        });
    }

    public function down(): void
    {
        Schema::table('courier_earnings', function (Blueprint $table) {
            $table->dropColumn([
                'commission_tier',
                'commission_base_percent',
                'commission_effective_percent',
                'commission_minimum',
                'completed_jobs_snapshot',
            ]);
        });
    }
};
