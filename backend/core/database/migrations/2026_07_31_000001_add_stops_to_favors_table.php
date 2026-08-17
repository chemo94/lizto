<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            if (!Schema::hasColumn('favors', 'stops')) {
                $table->json('stops')->nullable()->after('delivery_lng');
            }
        });
    }

    public function down(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            if (Schema::hasColumn('favors', 'stops')) {
                $table->dropColumn('stops');
            }
        });
    }
};
