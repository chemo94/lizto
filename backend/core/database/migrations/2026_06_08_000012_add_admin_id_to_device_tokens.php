<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('device_tokens', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('seller_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('device_tokens', 'admin_id')) {
                $table->dropColumn('admin_id');
            }
        });
    }
};
