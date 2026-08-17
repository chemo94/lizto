<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pos_staff')) {
            Schema::create('pos_staff', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->decimal('commission_rate', 5, 2)->default(0.00); // e.g. 5.00%
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('pos_orders', 'pos_staff_id')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->foreignId('pos_staff_id')->nullable()->constrained('pos_staff')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('pos_orders', 'pos_staff_id')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                $table->dropForeign(['pos_staff_id']);
                $table->dropColumn('pos_staff_id');
            });
        }
        Schema::dropIfExists('pos_staff');
    }
};
