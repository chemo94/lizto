<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ── Wallets (billetera universal) ──
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('holder'); // user, driver, seller
            $table->decimal('balance', 28, 8)->default(0);
            $table->decimal('blocked_balance', 28, 8)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // ── Wallet Transactions ──
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('trx', 40)->unique();
            $table->decimal('amount', 28, 8);
            $table->decimal('post_balance', 28, 8);
            $table->decimal('charge', 28, 8)->default(0);
            $table->string('trx_type', 10); // + or -
            $table->string('details')->nullable();
            $table->string('remark', 50)->nullable(); // deposit, withdraw, payment, refund, earning, bonus, commission
            $table->nullableMorphs('ref'); // polymorphic reference to order, favor, refund, etc.
            $table->tinyInteger('status')->default(1); // 1=completed, 0=pending, 2=rejected
            $table->timestamps();
        });

        // ── Delivery Commissions Config ──
        Schema::create('delivery_commissions', function (Blueprint $table) {
            $table->id();
            $table->decimal('delivery_percent', 5, 2)->default(10); // 10% comisión sobre pedido
            $table->decimal('favor_percent', 5, 2)->default(15);    // 15% comisión sobre favor
            $table->decimal('min_commission', 28, 8)->default(1);    // comisión mínima
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // ── Add wallet_id to existing tables ──
        if (!Schema::hasColumn('users', 'wallet_id')) {
            Schema::table('users', fn($t) => $t->foreignId('wallet_id')->nullable()->after('id'));
        }
        if (!Schema::hasColumn('drivers', 'wallet_id')) {
            Schema::table('drivers', fn($t) => $t->foreignId('wallet_id')->nullable()->after('id'));
        }
        if (!Schema::hasColumn('sellers', 'wallet_id')) {
            Schema::table('sellers', fn($t) => $t->foreignId('wallet_id')->nullable()->after('id'));
        }

        // ── Store balance / commission earned ──
        if (!Schema::hasColumn('delivery_orders', 'commission_amount')) {
            Schema::table('delivery_orders', fn($t) => $t->decimal('commission_amount', 28, 8)->default(0)->after('total'));
        }
        if (!Schema::hasColumn('favors', 'commission_amount')) {
            Schema::table('favors', fn($t) => $t->decimal('commission_amount', 28, 8)->default(0)->after('total'));
        }
    }

    public function down()
    {
        Schema::dropIfExists('delivery_commissions');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
