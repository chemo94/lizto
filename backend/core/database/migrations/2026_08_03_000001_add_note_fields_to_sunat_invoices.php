<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sunat_invoices', 'note_motivo')) {
                $table->string('note_motivo', 4)->nullable()->after('consumption_description');
            }
            if (!Schema::hasColumn('sunat_invoices', 'note_description')) {
                $table->string('note_description', 500)->nullable()->after('note_motivo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sunat_invoices', 'note_description')) $table->dropColumn('note_description');
            if (Schema::hasColumn('sunat_invoices', 'note_motivo')) $table->dropColumn('note_motivo');
        });
    }
};
