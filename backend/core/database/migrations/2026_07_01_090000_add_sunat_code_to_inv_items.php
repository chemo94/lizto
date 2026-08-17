<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inv_items', 'sunat_code')) {
                $table->string('sunat_code', 8)->nullable()->after('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            if (Schema::hasColumn('inv_items', 'sunat_code')) {
                $table->dropColumn('sunat_code');
            }
        });
    }
};
