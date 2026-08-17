<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Get all product IDs that are linked to inventory items
        $linkedProductIds = DB::table('inv_product_items')->pluck('product_id')->unique()->toArray();

        if (empty($linkedProductIds)) {
            return;
        }

        // 2. Fetch the products
        $products = DB::table('products')->whereIn('id', $linkedProductIds)->get();

        foreach ($products as $product) {
            $storeId = $product->store_id;

            // 3. Find or create the "Inventario" category for this store
            $category = DB::table('store_categories')
                ->where('store_id', $storeId)
                ->where('name', 'Inventario')
                ->first();

            if (!$category) {
                $categoryId = DB::table('store_categories')->insertGetId([
                    'store_id' => $storeId,
                    'name'     => 'Inventario',
                    'status'   => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $categoryId = $category->id;
            }

            // 4. Update product category ID
            DB::table('products')
                ->where('id', $product->id)
                ->update(['store_category_id' => $categoryId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data backfill
    }
};
