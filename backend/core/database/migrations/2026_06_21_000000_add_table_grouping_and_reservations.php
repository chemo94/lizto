<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Agregar columna linked_to_table_id a pos_tables
        Schema::table('pos_tables', function (Blueprint $table) {
            $table->foreignId('linked_to_table_id')->nullable()->after('status')->constrained('pos_tables')->nullOnDelete();
        });

        // 2. Crear tabla pos_table_reservations
        Schema::create('pos_table_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('pos_table_id')->nullable()->constrained('pos_tables')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->dateTime('reservation_time');
            $table->integer('guests_count')->default(2);
            $table->string('status')->default('pending'); // pending, confirmed, seated, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_table_reservations');
        Schema::table('pos_tables', function (Blueprint $table) {
            $table->dropForeign(['linked_to_table_id']);
            $table->dropColumn('linked_to_table_id');
        });
    }
};
