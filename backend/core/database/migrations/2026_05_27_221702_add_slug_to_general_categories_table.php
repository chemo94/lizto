<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('general_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('general_categories', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
