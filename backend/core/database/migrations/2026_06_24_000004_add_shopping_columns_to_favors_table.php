<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('favors', function (Blueprint $table) {
            $table->enum('shopping_status', [
                'preparing', 'submitted', 'shopping', 'awaiting_approval',
                'purchasing', 'purchased', 'delivering', 'delivered'
            ])->nullable()->after('status');
            $table->string('store_photo_url')->nullable()->after('cancel_reason');
            $table->string('receipt_url')->nullable()->after('store_photo_url');
            $table->decimal('actual_total', 10, 2)->nullable()->after('receipt_url');
        });
    }

    public function down()
    {
        Schema::table('favors', function (Blueprint $table) {
            $table->dropColumn([
                'shopping_status', 'store_photo_url', 'receipt_url', 'actual_total'
            ]);
        });
    }
};
