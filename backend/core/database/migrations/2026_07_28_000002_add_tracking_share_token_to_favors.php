<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('favors', function (Blueprint $table) {
            if (!Schema::hasColumn('favors', 'tracking_share_token')) {
                $table->string('tracking_share_token', 64)->nullable()->unique()->after('courier_id');
            }
        });
    }
    public function down(): void {
        Schema::table('favors', function (Blueprint $table) {
            if (Schema::hasColumn('favors', 'tracking_share_token')) $table->dropUnique(['tracking_share_token']);
            if (Schema::hasColumn('favors', 'tracking_share_token')) $table->dropColumn('tracking_share_token');
        });
    }
};
