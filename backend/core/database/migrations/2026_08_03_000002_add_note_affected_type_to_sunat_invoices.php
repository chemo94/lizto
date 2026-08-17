<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sunat_invoices', 'note_affected_type')) {
                $table->string('note_affected_type', 4)->nullable()->after('note_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sunat_invoices', 'note_affected_type')) $table->dropColumn('note_affected_type');
        });
    }
};
