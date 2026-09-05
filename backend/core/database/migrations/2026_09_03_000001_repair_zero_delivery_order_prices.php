<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('delivery_order_items')
            ->where(function ($query) {
                $query->whereNull('unit_price')->orWhere('unit_price', '<=', 0);
            })
            ->orderBy('id')
            ->chunkById(200, function ($items) {
                foreach ($items as $item) {
                    $product = DB::table('products')->where('id', $item->product_id)->first();
                    if (!$product) continue;

                    $basePrice = (float) (($product->discount_price ?? 0) > 0
                        ? $product->discount_price
                        : $product->price);

                    $variation = DB::table('delivery_order_item_variations')
                        ->where('delivery_order_item_id', $item->id)->first();
                    $variationPrice = (float) ($variation->variation_price ?? 0);
                    $unitPrice = $variationPrice > 0 ? $variationPrice : $basePrice;

                    $addons = (float) DB::table('delivery_order_item_addons')
                        ->where('delivery_order_item_id', $item->id)->sum('addon_price');
                    $unitPrice += $addons;
                    $totalPrice = $unitPrice * max(1, (int) $item->quantity);

                    DB::table('delivery_order_items')->where('id', $item->id)->update([
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'updated_at' => now(),
                    ]);
                }
            });

        DB::table('delivery_orders')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $subtotal = (float) DB::table('delivery_order_items')
                    ->where('delivery_order_id', $order->id)->sum('total_price');
                if ($subtotal <= 0) continue;

                DB::table('delivery_orders')->where('id', $order->id)->update([
                    'subtotal' => $subtotal,
                    'total' => max(0, $subtotal - (float) ($order->discount ?? 0))
                        + (float) ($order->delivery_fee ?? 0)
                        + (float) ($order->tip ?? 0),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Data repair is intentionally irreversible.
    }
};
