<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('cash_in_hand', 28, 8)->default(0)->after('balance');
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->decimal('receivable_balance', 28, 8)->default(0)->after('wallet_id');
        });

        Schema::create('driver_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // collection, remittance, adjustment
            $table->string('trx_type', 1); // + / -
            $table->decimal('amount', 28, 8);
            $table->decimal('post_balance', 28, 8);
            $table->nullableMorphs('ref');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['driver_id', 'created_at']);
            $table->unique(['type', 'ref_type', 'ref_id'], 'driver_cash_source_unique');
        });

        Schema::create('seller_receivable_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // earning, settlement, adjustment
            $table->string('trx_type', 1); // + / -
            $table->decimal('amount', 28, 8);
            $table->decimal('post_balance', 28, 8);
            $table->string('payment_channel', 30)->nullable();
            $table->string('settlement_method', 30)->nullable();
            $table->nullableMorphs('ref');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'created_at']);
            $table->unique(['type', 'ref_type', 'ref_id'], 'seller_receivable_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_receivable_transactions');
        Schema::dropIfExists('driver_cash_transactions');
        Schema::table('sellers', fn(Blueprint $table) => $table->dropColumn('receivable_balance'));
        Schema::table('drivers', fn(Blueprint $table) => $table->dropColumn('cash_in_hand'));
    }
};
