<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Create pos_bank_accounts table
        if (!Schema::hasTable('pos_bank_accounts')) {
            Schema::create('pos_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
                $table->string('name'); // e.g. Yape Oficina, BCP Soles
                $table->string('type')->default('bank'); // bank, wallet, pos_card
                $table->string('account_number')->nullable(); // Account number or phone
                $table->string('bank_name')->nullable(); // BCP, BBVA, Yape, Plin, Niubiz, Izipay
                $table->string('status')->default('active'); // active, inactive
                $table->timestamps();
            });
        }

        // Add pos_bank_account_id to pos_transactions
        Schema::table('pos_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_transactions', 'pos_bank_account_id')) {
                $table->foreignId('pos_bank_account_id')->nullable()->after('pos_order_id')->constrained('pos_bank_accounts')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropForeign(['pos_bank_account_id']);
            $table->dropColumn('pos_bank_account_id');
        });

        Schema::dropIfExists('pos_bank_accounts');
    }
};
