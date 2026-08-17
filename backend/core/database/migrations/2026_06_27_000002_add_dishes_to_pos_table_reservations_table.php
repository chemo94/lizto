<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_table_reservations', function (Blueprint $table) {
            $table->json('dishes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('pos_table_reservations', function (Blueprint $table) {
            $table->dropColumn('dishes');
        });
    }
};
