<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            $table->string('shipment_type', 30)->nullable()->after('priority_level');
            $table->string('evidence_type', 20)->nullable()->after('shipment_type');
            $table->decimal('cod_amount', 10, 2)->nullable()->after('evidence_type');
            $table->boolean('is_heavy')->default(false)->after('cod_amount');
            $table->boolean('is_temperature_controlled')->default(false)->after('is_heavy');
        });
    }

    public function down(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            $table->dropColumn([
                'shipment_type',
                'evidence_type',
                'cod_amount',
                'is_heavy',
                'is_temperature_controlled',
            ]);
        });
    }
};
