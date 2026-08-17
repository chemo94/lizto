<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_service_requests', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('service_type')->constrained()->nullOnDelete();
            $table->decimal('delivery_lat', 10, 8)->nullable()->after('destination');
            $table->decimal('delivery_lng', 11, 8)->nullable()->after('delivery_lat');
            $table->json('cart_json')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('landing_service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
            $table->dropColumn(['delivery_lat', 'delivery_lng', 'cart_json']);
        });
    }
};
