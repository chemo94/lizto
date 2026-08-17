<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('fleets', 'invite_code')) {
            Schema::table('fleets', function (Blueprint $table) {
                $table->string('invite_code', 20)->unique()->nullable()->after('name');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('fleets', 'invite_code')) {
            Schema::table('fleets', function (Blueprint $table) {
                $table->dropColumn('invite_code');
            });
        }
    }
};
