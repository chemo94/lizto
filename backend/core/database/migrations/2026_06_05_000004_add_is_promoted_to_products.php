<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('products', 'is_promoted')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_promoted')->default(false);
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('products', 'is_promoted')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_promoted');
            });
        }
    }
};
