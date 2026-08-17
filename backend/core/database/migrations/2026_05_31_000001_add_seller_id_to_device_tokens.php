<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('device_tokens', 'seller_id')) {
                $table->foreignId('seller_id')->nullable()->after('driver_id')->constrained()->cascadeOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('device_tokens', 'seller_id')) {
                $table->dropConstrainedForeignId('seller_id');
            }
        });
    }
};
