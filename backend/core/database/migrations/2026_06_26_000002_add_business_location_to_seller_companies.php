<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_companies', function (Blueprint $table) {
            $table->string('business_address', 500)->nullable()->after('address');
            $table->decimal('latitude', 10, 7)->nullable()->after('business_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('seller_companies', function (Blueprint $table) {
            $table->dropColumn(['business_address', 'latitude', 'longitude']);
        });
    }
};
