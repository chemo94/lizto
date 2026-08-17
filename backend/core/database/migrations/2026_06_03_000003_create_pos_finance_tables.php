<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pos_cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->decimal('opening_balance', 28, 8)->default(0);
            $table->decimal('closing_balance', 28, 8)->nullable();
            $table->decimal('total_sales', 28, 8)->default(0);
            $table->decimal('total_expenses', 28, 8)->default(0);
            $table->decimal('total_cash_in', 28, 8)->default(0);
            $table->decimal('total_cash_out', 28, 8)->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // sale, cash_in, cash_out, expense, refund
            $table->decimal('amount', 28, 8)->default(0);
            $table->string('payment_method')->default('cash');
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
            $table->string('category'); // supplies, rent, utilities, salary, maintenance, other
            $table->decimal('amount', 28, 8)->default(0);
            $table->string('description');
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->timestamp('expense_date')->nullable();
            $table->timestamps();
        });

        // Add cancel fields to pos_orders
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->decimal('refund_amount', 28, 8)->nullable();
        });
    }

    public function down()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancel_reason', 'refund_amount']);
        });
        Schema::dropIfExists('pos_expenses');
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('pos_cash_sessions');
    }
};
