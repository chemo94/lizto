<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('driver_earning_transactions') || !Schema::hasColumn('drivers', 'earning_balance')) {
            return;
        }

        DB::table('drivers')
            ->where('earning_balance', '>', 0)
            ->orderBy('id')
            ->select('id', 'earning_balance')
            ->chunkById(200, function ($drivers) {
                foreach ($drivers as $driver) {
                    $exists = DB::table('driver_earning_transactions')
                        ->where('driver_id', $driver->id)
                        ->where('type', 'adjustment')
                        ->whereNull('ref_type')
                        ->whereNull('ref_id')
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('driver_earning_transactions')->insert([
                        'driver_id' => $driver->id,
                        'type' => 'adjustment',
                        'trx_type' => '+',
                        'amount' => $driver->earning_balance,
                        'post_balance' => $driver->earning_balance,
                        'notes' => 'Saldo inicial de ganancias generado desde el historial',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('driver_earning_transactions')) {
            return;
        }

        DB::table('driver_earning_transactions')
            ->where('type', 'adjustment')
            ->whereNull('ref_type')
            ->whereNull('ref_id')
            ->where('notes', 'Saldo inicial de ganancias generado desde el historial')
            ->delete();
    }
};
