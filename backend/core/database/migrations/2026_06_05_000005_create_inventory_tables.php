<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inv_items')) {
            Schema::create('inv_items', function (Blueprint $t) {
                $t->id(); $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $t->string('code')->nullable(); $t->string('name'); $t->string('category')->nullable();
                $t->string('unit')->default('UNIDAD'); $t->decimal('stock',28,8)->default(0);
                $t->decimal('min_stock',28,8)->default(0); $t->decimal('cost',28,8)->default(0);
                $t->decimal('last_cost',28,8)->default(0); $t->string('status')->default('active'); $t->timestamps();
            });
        }
        if (!Schema::hasTable('inv_suppliers')) {
            Schema::create('inv_suppliers', function (Blueprint $t) {
                $t->id(); $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $t->string('name'); $t->string('document_type')->default('6');
                $t->string('document_number')->nullable(); $t->string('phone')->nullable();
                $t->string('email')->nullable(); $t->string('address')->nullable();
                $t->string('status')->default('active'); $t->timestamps();
            });
        }
        if (!Schema::hasTable('inv_purchases')) {
            Schema::create('inv_purchases', function (Blueprint $t) {
                $t->id(); $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $t->foreignId('supplier_id')->nullable()->constrained('inv_suppliers')->nullOnDelete();
                $t->foreignId('cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
                $t->string('document_type')->default('01'); $t->string('document_series')->nullable();
                $t->string('document_number')->nullable(); $t->date('document_date');
                $t->decimal('subtotal',28,8)->default(0); $t->decimal('igv',28,8)->default(0);
                $t->decimal('total',28,8)->default(0); $t->string('payment_method')->default('cash');
                $t->string('status')->default('completed'); $t->text('notes')->nullable(); $t->timestamps();
            });
        }
        if (!Schema::hasTable('inv_purchase_items')) {
            Schema::create('inv_purchase_items', function (Blueprint $t) {
                $t->id(); $t->foreignId('purchase_id')->constrained('inv_purchases')->cascadeOnDelete();
                $t->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $t->decimal('quantity',28,8)->default(0); $t->decimal('unit_cost',28,8)->default(0);
                $t->decimal('total',28,8)->default(0); $t->timestamps();
            });
        }
        if (!Schema::hasTable('inv_kardex')) {
            Schema::create('inv_kardex', function (Blueprint $t) {
                $t->id(); $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $t->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $t->string('type'); $t->string('reference_type')->nullable(); $t->unsignedBigInteger('reference_id')->nullable();
                $t->decimal('quantity',28,8)->default(0); $t->decimal('unit_cost',28,8)->default(0);
                $t->decimal('total_cost',28,8)->default(0); $t->decimal('balance_stock',28,8)->default(0);
                $t->string('description')->nullable(); $t->timestamps();
            });
        }
        if (!Schema::hasTable('inv_product_items')) {
            Schema::create('inv_product_items', function (Blueprint $t) {
                $t->id(); $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $t->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $t->decimal('quantity',28,8)->default(0); $t->string('unit')->default('UNIDAD'); $t->timestamps();
            });
        }
        if (!Schema::hasColumn('sellers', 'cash_required')) {
            Schema::table('sellers', function (Blueprint $t) { $t->boolean('cash_required')->default(true); });
        }
    }

    public function down()
    {
        Schema::table('sellers', function (Blueprint $t) { $t->dropColumn('cash_required'); });
        Schema::dropIfExists('inv_product_items');
        Schema::dropIfExists('inv_kardex');
        Schema::dropIfExists('inv_purchase_items');
        Schema::dropIfExists('inv_purchases');
        Schema::dropIfExists('inv_suppliers');
        Schema::dropIfExists('inv_items');
    }
};
