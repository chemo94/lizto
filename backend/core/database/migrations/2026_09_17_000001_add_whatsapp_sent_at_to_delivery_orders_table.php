<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'whatsapp_sent_at')) {
                $table->timestamp('whatsapp_sent_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_orders', 'whatsapp_sent_at')) {
                $table->dropColumn('whatsapp_sent_at');
            }
        });
    }
};
