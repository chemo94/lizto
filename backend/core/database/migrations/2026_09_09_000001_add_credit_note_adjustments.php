<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sunat_invoices', fn(Blueprint $t) => $t->json('note_adjustments')->nullable());
    }
    public function down(): void
    {
        Schema::table('sunat_invoices', fn(Blueprint $t) => $t->dropColumn('note_adjustments'));
    }
};
