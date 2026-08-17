<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('device_tokens', 'app_type')) {
            Schema::table('device_tokens', function (Blueprint $table) {
                $table->string('app_type')->default('passenger');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('device_tokens', 'app_type')) {
            Schema::table('device_tokens', function (Blueprint $table) {
                $table->dropColumn('app_type');
            });
        }
    }
};
