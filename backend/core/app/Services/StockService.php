<?php

namespace App\Services;

use App\Models\InvItem;
use App\Models\InvKardex;
use App\Models\InvProductItem;
use App\Models\InvWarehouseStock;
use App\Models\InvWarehouse;
use App\Models\InvRecipe;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class StockService
{
    private int $sellerId;
    private ?int $warehouseId;

    public function __construct(int $sellerId)
    {
        $this->sellerId = $sellerId;
        $this->warehouseId = $this->resolveWarehouse();
    }

    private function resolveWarehouse(): ?int
    {
        $store = Store::where('seller_id', $this->sellerId)->first();
        if ($store && $store->warehouse_id) {
            return $store->warehouse_id;
        }
        return InvWarehouse::where('seller_id', $this->sellerId)->where('is_default', true)->value('id')
            ?? InvWarehouse::where('seller_id', $this->sellerId)->value('id');
    }

    public function consumeForOrder($orderItems): array
    {
        $consumed = [];

        foreach ($orderItems as $orderItem) {
            $product = Product::find($orderItem->product_id);
            if (!$product || !$product->hasStockTracking()) continue;

            $recipe = InvRecipe::where('product_id', $product->id)->active()->with('items.item')->first();
            if ($recipe) {
                foreach ($recipe->items as $recipeItem) {
                    $invItem = $recipeItem->item;
                    if (!$invItem) continue;

                    $portions = max(0.01, floatval($recipe->portions));
                    $qtyUsed = ($recipeItem->quantity_net / $portions) * $orderItem->quantity;

                    $this->deductStock($invItem, $qtyUsed, 'salida', 'sale', $orderItem->pos_order_id,
                        "Consumo Receta Venta #{$orderItem->pos_order_id} — {$product->name}");

                    $consumed[] = "{$invItem->name}: -{$qtyUsed}";
                }
            } else {
                $links = InvProductItem::where('product_id', $product->id)->get();
                foreach ($links as $link) {
                    $invItem = InvItem::find($link->item_id);
                    if (!$invItem) continue;

                    $qtyUsed = $link->quantity * $orderItem->quantity;

                    $this->deductStock($invItem, $qtyUsed, 'salida', 'sale', $orderItem->pos_order_id,
                        "Venta #{$orderItem->pos_order_id} — {$product->name}");

                    $consumed[] = "{$invItem->name}: -{$qtyUsed}";
                }
            }
        }

        return $consumed;
    }

    public function restoreForOrder($orderItems, $referenceId): array
    {
        $restored = [];
        $skipped = [];

        foreach ($orderItems as $orderItem) {
            $product = Product::find($orderItem->product_id);
            if (!$product || !$product->hasStockTracking()) continue;
            if ($product->isStockPrepared()) {
                $skipped[] = $product->name;
                continue;
            }

            $recipe = InvRecipe::where('product_id', $product->id)->active()->with('items.item')->first();
            if ($recipe) {
                foreach ($recipe->items as $recipeItem) {
                    $invItem = $recipeItem->item;
                    if (!$invItem) continue;

                    $portions = max(0.01, floatval($recipe->portions));
                    $qtyUsed = ($recipeItem->quantity_net / $portions) * $orderItem->quantity;

                    $this->addStock($invItem, $qtyUsed, 'entrada', 'void', $referenceId,
                        "Devolución anulación Receta #{$referenceId} — {$product->name}");

                    $restored[] = "{$invItem->name}: +{$qtyUsed}";
                }
            } else {
                $links = InvProductItem::where('product_id', $product->id)->get();
                foreach ($links as $link) {
                    $invItem = InvItem::find($link->item_id);
                    if (!$invItem) continue;

                    $qtyUsed = $link->quantity * $orderItem->quantity;

                    $this->addStock($invItem, $qtyUsed, 'entrada', 'void', $referenceId,
                        "Devolución anulación #{$referenceId} — {$product->name}");

                    $restored[] = "{$invItem->name}: +{$qtyUsed}";
                }
            }
        }

        return ['restored' => $restored, 'skipped' => $skipped];
    }

    private function deductStock(InvItem $item, float $qty, string $type, string $refType, ?int $refId, string $desc): void
    {
        $newStock = max(0, $item->stock - $qty);
        $item->update(['stock' => $newStock]);

        if ($this->warehouseId) {
            $wStock = InvWarehouseStock::firstOrCreate(
                ['warehouse_id' => $this->warehouseId, 'item_id' => $item->id],
                ['stock' => 0]
            );
            $wStock->decrement('stock', $qty);
        }

        InvKardex::create([
            'seller_id'      => $this->sellerId,
            'item_id'        => $item->id,
            'warehouse_id'   => $this->warehouseId,
            'type'           => $type,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'quantity'       => -$qty,
            'unit_cost'      => $item->last_cost ?? $item->cost ?? 0,
            'total_cost'     => $qty * ($item->last_cost ?? $item->cost ?? 0),
            'balance_stock'  => $newStock,
            'description'    => $desc,
        ]);
    }

    private function addStock(InvItem $item, float $qty, string $type, string $refType, ?int $refId, string $desc): void
    {
        $newStock = $item->stock + $qty;
        $item->update(['stock' => $newStock]);

        if ($this->warehouseId) {
            $wStock = InvWarehouseStock::firstOrCreate(
                ['warehouse_id' => $this->warehouseId, 'item_id' => $item->id],
                ['stock' => 0]
            );
            $wStock->increment('stock', $qty);
        }

        InvKardex::create([
            'seller_id'      => $this->sellerId,
            'item_id'        => $item->id,
            'warehouse_id'   => $this->warehouseId,
            'type'           => $type,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'quantity'       => $qty,
            'unit_cost'      => $item->last_cost ?? $item->cost ?? 0,
            'total_cost'     => $qty * ($item->last_cost ?? $item->cost ?? 0),
            'balance_stock'  => $newStock,
            'description'    => $desc,
        ]);
    }

    public function checkAvailability($orderItems): array
    {
        $errors = [];

        foreach ($orderItems as $oi) {
            $product = Product::find($oi['product_id'] ?? 0);
            if (!$product || !$product->hasStockTracking()) continue;

            $recipe = InvRecipe::where('product_id', $product->id)->active()->with('items.item')->first();
            if ($recipe) {
                foreach ($recipe->items as $ri) {
                    if (!$ri->item) continue;
                    $needed = ($ri->quantity_net / max(0.01, $recipe->portions)) * $oi['quantity'];
                    if ($ri->item->stock < $needed) {
                        $errors[] = "{$ri->item->name} (necesita {$needed}, stock: {$ri->item->stock})";
                    }
                }
            } else {
                $links = InvProductItem::where('product_id', $product->id)->get();
                foreach ($links as $link) {
                    $invItem = InvItem::find($link->item_id);
                    if (!$invItem) continue;
                    $needed = $link->quantity * $oi['quantity'];
                    if ($invItem->stock < $needed) {
                        $errors[] = "{$invItem->name} (necesita {$needed}, stock: {$invItem->stock})";
                    }
                }
            }
        }

        return $errors;
    }
}
