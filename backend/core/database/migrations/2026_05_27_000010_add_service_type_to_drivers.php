<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('drivers', 'service_type')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->string('service_type', 50)->default('ride')->after('service_id')
                    ->comment('ride=Taxi, delivery=Repartidor, both=Ambos');
            });
        }
    }

    public function down()
    {
        Schema::table('drivers', fn($t) => $t->dropColumn('service_type'));
    }
};
