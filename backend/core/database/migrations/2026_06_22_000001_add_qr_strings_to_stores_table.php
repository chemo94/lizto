<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->text('yape_qr_string')->nullable()->after('webhook_secret');
            $table->text('plin_qr_string')->nullable()->after('yape_qr_string');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['yape_qr_string', 'plin_qr_string']);
        });
    }
};
