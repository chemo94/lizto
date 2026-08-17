<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Recetas ────────────────────────────────────────────────────────
        if (!Schema::hasTable('inv_recipes')) {
            Schema::create('inv_recipes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $t->string('name');
                $t->string('recipe_type')->default('kitchen'); // kitchen | bar
                $t->decimal('portions', 10, 4)->default(1);   // porciones/tragos que produce
                $t->string('unit_produced')->default('porcion'); // porcion, copa, vaso...
                $t->text('notes')->nullable();
                $t->string('status')->default('active');
                $t->timestamps();
            });
        }

        // ── 2. Ingredientes de Receta (con merma) ────────────────────────────
        if (!Schema::hasTable('inv_recipe_items')) {
            Schema::create('inv_recipe_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('recipe_id')->constrained('inv_recipes')->cascadeOnDelete();
                $t->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $t->decimal('quantity_gross', 28, 8)->default(0); // cantidad bruta
                $t->decimal('waste_pct', 5, 2)->default(0);       // % merma (0-100)
                $t->decimal('quantity_net', 28, 8)->default(0);   // bruto*(1-merma/100)
                $t->string('unit')->default('UNIDAD');
                $t->string('notes')->nullable();
                $t->timestamps();
            });
        }

        // ── 3. Producciones ──────────────────────────────────────────────────
        if (!Schema::hasTable('inv_productions')) {
            Schema::create('inv_productions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $t->foreignId('recipe_id')->constrained('inv_recipes')->cascadeOnDelete();
                $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $t->foreignId('cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
                $t->integer('portions_produced')->default(1);
                $t->timestamp('produced_at')->nullable();
                $t->string('status')->default('completed'); // completed | voided
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        // ── 4. Mermas ────────────────────────────────────────────────────────
        if (!Schema::hasTable('inv_wastes')) {
            Schema::create('inv_wastes', function (Blueprint $t) {
                $t->id();
                $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $t->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $t->foreignId('cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
                $t->decimal('quantity', 28, 8)->default(0);
                $t->string('unit')->nullable();
                $t->string('reason');                          // rotura, caducidad, accidente...
                $t->date('waste_date');
                $t->string('status')->default('active');       // active | voided
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        // ── 5. Ventas Directas (Tienda) ──────────────────────────────────────
        if (!Schema::hasTable('inv_sales')) {
            Schema::create('inv_sales', function (Blueprint $t) {
                $t->id();
                $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
                $t->foreignId('supplier_id')->nullable()->constrained('inv_suppliers')->nullOnDelete();
                $t->foreignId('cash_session_id')->nullable()->constrained('pos_cash_sessions')->nullOnDelete();
                $t->string('document_type')->default('00');   // 00=sin comprobante, 01=factura, 03=boleta
                $t->string('document_series')->nullable();
                $t->string('document_number')->nullable();
                $t->date('document_date');
                $t->decimal('subtotal_gravado', 28, 8)->default(0);
                $t->decimal('subtotal_exonerado', 28, 8)->default(0);
                $t->decimal('subtotal_inafecto', 28, 8)->default(0);
                $t->decimal('igv', 28, 8)->default(0);
                $t->decimal('total', 28, 8)->default(0);
                $t->string('payment_method')->default('cash');
                $t->string('status')->default('completed');   // completed | voided
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        // ── 6. Items de Venta Directa ────────────────────────────────────────
        if (!Schema::hasTable('inv_sale_items')) {
            Schema::create('inv_sale_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sale_id')->constrained('inv_sales')->cascadeOnDelete();
                $t->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
                $t->decimal('quantity', 28, 8)->default(0);
                $t->decimal('unit_price', 28, 8)->default(0);
                $t->string('tax_type')->default('gravado');   // gravado | exonerado | inafecto
                $t->decimal('subtotal', 28, 8)->default(0);   // sin IGV
                $t->decimal('igv', 28, 8)->default(0);
                $t->decimal('total', 28, 8)->default(0);
                $t->timestamps();
            });
        }

        // ── Columnas adicionales en inv_items ────────────────────────────────
        Schema::table('inv_items', function (Blueprint $t) {
            if (!Schema::hasColumn('inv_items', 'item_type')) {
                $t->string('item_type')->default('insumo')->after('category');
                // insumo: entra por compras, sale por producción/merma
                // producto: compra-venta directa (tienda)
            }
            if (!Schema::hasColumn('inv_items', 'tax_type')) {
                $t->string('tax_type')->default('gravado')->after('item_type');
                // gravado | exonerado | inafecto
            }
            if (!Schema::hasColumn('inv_items', 'is_bar_item')) {
                $t->boolean('is_bar_item')->default(false)->after('tax_type');
            }
            if (!Schema::hasColumn('inv_items', 'bar_category')) {
                $t->string('bar_category')->nullable()->after('is_bar_item');
                // licor | mixer | garnish | preparado
            }
            if (!Schema::hasColumn('inv_items', 'sale_price')) {
                $t->decimal('sale_price', 28, 8)->default(0)->after('bar_category');
            }
        });

        // ── tax_type en products ──────────────────────────────────────────────
        if (!Schema::hasColumn('products', 'tax_type')) {
            Schema::table('products', function (Blueprint $t) {
                $t->string('tax_type')->default('gravado')->after('status');
            });
        }

        // ── has_bar y default_tax_type en seller_companies ───────────────────
        Schema::table('seller_companies', function (Blueprint $t) {
            if (!Schema::hasColumn('seller_companies', 'has_bar')) {
                $t->boolean('has_bar')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('seller_companies', 'default_tax_type')) {
                $t->string('default_tax_type')->default('gravado')->after('has_bar');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_sale_items');
        Schema::dropIfExists('inv_sales');
        Schema::dropIfExists('inv_wastes');
        Schema::dropIfExists('inv_productions');
        Schema::dropIfExists('inv_recipe_items');
        Schema::dropIfExists('inv_recipes');

        Schema::table('inv_items', function (Blueprint $t) {
            foreach (['item_type','tax_type','is_bar_item','bar_category','sale_price'] as $col) {
                if (Schema::hasColumn('inv_items', $col)) $t->dropColumn($col);
            }
        });
        if (Schema::hasColumn('products', 'tax_type')) {
            Schema::table('products', fn($t) => $t->dropColumn('tax_type'));
        }
        Schema::table('seller_companies', function (Blueprint $t) {
            foreach (['has_bar','default_tax_type'] as $col) {
                if (Schema::hasColumn('seller_companies', $col)) $t->dropColumn($col);
            }
        });
    }
};
