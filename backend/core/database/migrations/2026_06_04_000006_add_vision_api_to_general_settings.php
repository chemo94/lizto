<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('general_settings', 'google_vision_api_key')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->string('google_vision_api_key')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('general_settings', 'google_vision_api_key')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('google_vision_api_key');
            });
        }
    }
};
