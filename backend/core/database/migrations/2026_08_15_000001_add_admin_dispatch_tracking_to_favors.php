<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            if (!Schema::hasColumn('favors', 'dispatch_attempted_driver_ids')) {
                $table->json('dispatch_attempted_driver_ids')->nullable()->after('dispatch_timeout_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            if (Schema::hasColumn('favors', 'dispatch_attempted_driver_ids')) {
                $table->dropColumn('dispatch_attempted_driver_ids');
            }
        });
    }
};
