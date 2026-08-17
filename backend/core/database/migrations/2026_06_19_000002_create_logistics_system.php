<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. ALMACENES / SUCURSALES ─────────────────────────────────────────
        if (!Schema::hasTable('inv_warehouses')) {
            Schema::create('inv_warehouses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('address')->nullable();
                $table->boolean('is_default')->default(false);
                $table->string('status')->default('active'); // active | inactive
                $table->timestamps();
            });
        }

        // ── 2. STOCK POR ALMACÉN E INSUMO ────────────────────────────────────
        if (!Schema::hasTable('inv_warehouse_stocks')) {
            Schema::create('inv_warehouse_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $table->decimal('stock', 28, 8)->default(0.00);
                $table->timestamps();
                $table->unique(['warehouse_id', 'item_id']);
            });
        }

        // ── 3. ÓRDENES DE COMPRA ─────────────────────────────────────────────
        if (!Schema::hasTable('inv_purchase_orders')) {
            Schema::create('inv_purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('inv_suppliers')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
                $table->string('order_number');
                $table->date('order_date');
                $table->string('status')->default('draft'); // draft, pending, approved, in_transit, received, cancelled
                $table->decimal('subtotal', 28, 8)->default(0);
                $table->decimal('igv', 28, 8)->default(0);
                $table->decimal('total', 28, 8)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ── 4. DETALLE DE ÓRDENES DE COMPRA ──────────────────────────────────
        if (!Schema::hasTable('inv_purchase_order_items')) {
            Schema::create('inv_purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('inv_purchase_orders')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $table->decimal('quantity', 28, 8)->default(0);
                $table->decimal('unit_cost', 28, 8)->default(0);
                $table->decimal('total', 28, 8)->default(0);
                $table->timestamps();
            });
        }

        // ── 5. RECEPCIONES DE MERCADERÍA ────────────────────────────────────
        if (!Schema::hasTable('inv_receptions')) {
            Schema::create('inv_receptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('inv_purchase_orders')->nullOnDelete();
                $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
                $table->date('reception_date');
                $table->string('document_type')->default('09'); // 09=Guía de Remisión, 01=Factura
                $table->string('document_number')->nullable();
                $table->string('status')->default('completed'); // completed | voided
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ── 6. DETALLE DE RECEPCIONES ────────────────────────────────────────
        if (!Schema::hasTable('inv_reception_items')) {
            Schema::create('inv_reception_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reception_id')->constrained('inv_receptions')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $table->decimal('quantity_ordered', 28, 8)->default(0);
                $table->decimal('quantity_received', 28, 8)->default(0);
                $table->decimal('quantity_damaged', 28, 8)->default(0);
                $table->date('expiration_date')->nullable();
                $table->timestamps();
            });
        }

        // ── 7. TRANSFERENCIAS ENTRE ALMACENES ────────────────────────────────
        if (!Schema::hasTable('inv_warehouse_transfers')) {
            Schema::create('inv_warehouse_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $table->foreignId('from_warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
                $table->string('status')->default('pending'); // pending | sent | received | cancelled
                $table->text('notes')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 8. DETALLE DE TRANSFERENCIAS ─────────────────────────────────────
        if (!Schema::hasTable('inv_warehouse_transfer_items')) {
            Schema::create('inv_warehouse_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id')->constrained('inv_warehouse_transfers')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $table->decimal('quantity', 28, 8)->default(0);
                $table->timestamps();
            });
        }

        // ── 9. MODIFICACIONES EN TABLAS EXISTENTES ───────────────────────────
        Schema::table('inv_suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('inv_suppliers', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('address');
            }
            if (!Schema::hasColumn('inv_suppliers', 'rating_delivery_time')) {
                $table->integer('rating_delivery_time')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('inv_suppliers', 'rating_price')) {
                $table->integer('rating_price')->nullable()->after('rating_delivery_time');
            }
            if (!Schema::hasColumn('inv_suppliers', 'rating_quality')) {
                $table->integer('rating_quality')->nullable()->after('rating_price');
            }
        });

        Schema::table('inv_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inv_items', 'max_stock')) {
                $table->decimal('max_stock', 28, 8)->default(0)->after('min_stock');
            }
        });

        // Relacionar almacenes con las transacciones existentes
        foreach (['inv_kardex', 'inv_purchases', 'inv_sales', 'inv_wastes', 'inv_productions'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'warehouse_id')) {
                    $table->foreignId('warehouse_id')->nullable()->constrained('inv_warehouses')->nullOnDelete();
                }
            });
        }

        // Relacionar almacén con tiendas y empresas del seller
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->constrained('inv_warehouses')->nullOnDelete();
            }
        });
        Schema::table('seller_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('seller_companies', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->constrained('inv_warehouses')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('seller_companies', function (Blueprint $table) {
            if (Schema::hasColumn('seller_companies', 'warehouse_id')) $table->dropColumn('warehouse_id');
        });
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'warehouse_id')) $table->dropColumn('warehouse_id');
        });

        foreach (['inv_kardex', 'inv_purchases', 'inv_sales', 'inv_wastes', 'inv_productions'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (Schema::hasColumn($tbl, 'warehouse_id')) $table->dropColumn('warehouse_id');
            });
        }

        Schema::table('inv_items', function (Blueprint $table) {
            if (Schema::hasColumn('inv_items', 'max_stock')) $table->dropColumn('max_stock');
        });

        Schema::table('inv_suppliers', function (Blueprint $table) {
            foreach (['contact_person', 'rating_delivery_time', 'rating_price', 'rating_quality'] as $col) {
                if (Schema::hasColumn('inv_suppliers', $col)) $table->dropColumn($col);
            }
        });

        Schema::dropIfExists('inv_warehouse_transfer_items');
        Schema::dropIfExists('inv_warehouse_transfers');
        Schema::dropIfExists('inv_reception_items');
        Schema::dropIfExists('inv_receptions');
        Schema::dropIfExists('inv_purchase_order_items');
        Schema::dropIfExists('inv_purchase_orders');
        Schema::dropIfExists('inv_warehouse_stocks');
        Schema::dropIfExists('inv_warehouses');
    }
};
