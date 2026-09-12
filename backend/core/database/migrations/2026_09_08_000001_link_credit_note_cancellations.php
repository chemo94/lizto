<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('original_invoice_id')->nullable()->index();
            $table->timestamp('note_processing_at')->nullable();
            $table->timestamp('cancellation_applied_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('sunat_invoices', function (Blueprint $table) {
            $table->dropIndex(['original_invoice_id']);
            $table->dropColumn(['original_invoice_id', 'note_processing_at', 'cancellation_applied_at']);
        });
    }
};
