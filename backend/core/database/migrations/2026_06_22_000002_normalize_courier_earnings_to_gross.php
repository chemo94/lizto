<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Históricamente amount se guardaba neto. La comisión ya había sido
        // debitada del wallet, así que la reincorporamos a la ganancia.
        DB::table('courier_earnings')
            ->where('commission', '>', 0)
            ->update(['amount' => DB::raw('amount + commission')]);
    }

    public function down(): void
    {
        DB::table('courier_earnings')
            ->where('commission', '>', 0)
            ->update(['amount' => DB::raw('GREATEST(0, amount - commission)')]);
    }
};
