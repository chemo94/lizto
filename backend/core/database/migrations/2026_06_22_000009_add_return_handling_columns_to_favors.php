<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            if (!Schema::hasColumn('favors', 'return_status')) {
                $table->string('return_status', 30)->nullable()->after('eta_updated_at');
            }
            if (!Schema::hasColumn('favors', 'return_reason')) {
                $table->string('return_reason', 50)->nullable()->after('return_status');
            }
            if (!Schema::hasColumn('favors', 'return_notes')) {
                $table->text('return_notes')->nullable()->after('return_reason');
            }
            if (!Schema::hasColumn('favors', 'return_requested_at')) {
                $table->timestamp('return_requested_at')->nullable()->after('return_notes');
            }
            if (!Schema::hasColumn('favors', 'return_picked_up_at')) {
                $table->timestamp('return_picked_up_at')->nullable()->after('return_requested_at');
            }
            if (!Schema::hasColumn('favors', 'return_completed_at')) {
                $table->timestamp('return_completed_at')->nullable()->after('return_picked_up_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('favors', function (Blueprint $table) {
            $columns = [
                'return_status', 'return_reason', 'return_notes',
                'return_requested_at', 'return_picked_up_at', 'return_completed_at',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('favors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
