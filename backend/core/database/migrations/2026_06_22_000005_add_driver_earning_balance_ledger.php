<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('earning_balance', 28, 8)->default(0)->after('cash_in_hand');
        });

        Schema::create('driver_earning_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // earning, settlement, adjustment
            $table->string('trx_type', 1); // + / -
            $table->decimal('amount', 28, 8);
            $table->decimal('post_balance', 28, 8);
            $table->string('settlement_method', 30)->nullable(); // balance, bank, yape, plin
            $table->nullableMorphs('ref');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['driver_id', 'created_at']);
            $table->unique(['type', 'ref_type', 'ref_id'], 'driver_earning_source_unique');
        });

        $driverIds = DB::table('courier_earnings')
            ->select('courier_id', DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('courier_id')
            ->get();

        foreach ($driverIds as $row) {
            DB::table('drivers')->where('id', $row->courier_id)->update(['earning_balance' => $row->total]);
            DB::table('driver_earning_transactions')->insert([
                'driver_id' => $row->courier_id,
                'type' => 'adjustment',
                'trx_type' => '+',
                'amount' => $row->total,
                'post_balance' => $row->total,
                'notes' => 'Saldo inicial de ganancias generado desde el historial',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_earning_transactions');
        Schema::table('drivers', fn(Blueprint $table) => $table->dropColumn('earning_balance'));
    }
};
