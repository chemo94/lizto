<?php

namespace App\Http\Controllers;

use App\Models\InvItem;
use App\Models\InvKardex;
use App\Models\InvProduction;
use App\Models\InvPurchase;
use App\Models\InvPurchaseItem;
use App\Models\InvRecipe;
use App\Models\InvRecipeItem;
use App\Models\InvSale;
use App\Models\InvSaleItem;
use App\Models\InvSupplier;
use App\Models\InvWaste;
use App\Models\PosCashSession;
use App\Models\Product;
use App\Models\SellerCompany;
use App\Models\Store;
use App\Models\SunatInvoice;
use App\Services\KardexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class InventoryController extends Controller
{
    private function seller()
    {
        return \App\Models\Seller::find(Session::get('seller_id'));
    }

    private function store()
    {
        return Store::where('seller_id', $this->seller()->id)->first();
    }

    private function company()
    {
        return SellerCompany::where('seller_id', $this->seller()->id)->first();
    }

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $seller = \App\Models\Seller::where('status', 1)->find(Session::get('seller_id'));
            if (!$seller) {
                Session::forget(['seller_id', 'seller_staff_id']);
                return redirect()->route('seller.login');
            }
            $staffId = Session::get('seller_staff_id');
            if ($staffId) {
                $staff = \App\Models\PosStaff::where('seller_id', $seller->id)->where('status', 'active')->find($staffId);
                if (!$staff || (!$staff->isAdmin() && !$staff->hasPermission('inventory'))) {
                    return $request->expectsJson()
                        ? response()->json(['message' => 'No tienes permiso para gestionar inventario.'], 403)
                        : redirect()->route('seller.pos')->with('error', 'No tienes permiso para gestionar inventario.');
                }
            }
            return $next($request);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ITEMS / INSUMOS
    // ─────────────────────────────────────────────────────────────────────────

    public function items()
    {
        $seller     = $this->seller();
        $store      = $this->store();
        $company    = $this->company();
        $pageTitle  = 'Insumos y Stock';

        $storeCategories = $store->subCategories->pluck('generalCategory.name')->filter()->values()->toArray();

        $items      = InvItem::where('seller_id', $seller->id)
                        ->insumos()
                        ->orderBy('category')->orderBy('name')->get();
        $categories = $items->pluck('category')->unique()->filter()->values();
        $lowStock   = $items->filter(fn($i) => $i->isLowStock());

        return view('seller.inventory.items', compact(
            'pageTitle', 'seller', 'store', 'company', 'items', 'categories', 'lowStock', 'storeCategories'
        ));
    }

    public function stockAlerts()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Alertas de Stock';

        $lowStockItems = InvItem::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('min_stock', '>', 0)
            ->orderByRaw('(stock / NULLIF(min_stock, 0)) ASC')
            ->get();

        $outOfStockItems = InvItem::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->where('stock', '<=', 0)
            ->get();

        $overStockItems = InvItem::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->whereColumn('stock', '>=', 'max_stock')
            ->where('max_stock', '>', 0)
            ->get();

        $totalValue = InvItem::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->selectRaw('SUM(stock * cost) as total')
            ->value('total') ?? 0;

        return view('seller.inventory.alerts', compact(
            'pageTitle', 'seller', 'store', 'lowStockItems', 'outOfStockItems', 'overStockItems', 'totalValue'
        ));
    }

    public function itemStore(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:120',
            'unit'          => 'required|string|max:20',
            'tax_type'      => 'required|in:gravado,exonerado,inafecto',
            'initial_stock' => 'nullable|numeric|min:0',
            'cost'          => 'nullable|numeric|min:0',
            'min_stock'     => 'nullable|numeric|min:0',
        ]);

        $initialStock = floatval($request->initial_stock ?? 0);
        $cost         = floatval($request->cost ?? 0);
        $sellerId     = $this->seller()->id;

        $item = DB::transaction(function () use ($request, $initialStock, $cost, $sellerId) {
            $item = InvItem::create($request->only([
                'name', 'category', 'unit', 'min_stock', 'cost',
                'tax_type', 'is_bar_item', 'bar_category', 'sunat_code',
            ]) + ['seller_id' => $sellerId, 'stock' => $initialStock, 'item_type' => 'insumo']);

            if ($initialStock > 0) {
                // Find default warehouse or any warehouse
                $warehouseId = \App\Models\InvWarehouse::where('seller_id', $sellerId)->where('is_default', true)->value('id')
                    ?? \App\Models\InvWarehouse::where('seller_id', $sellerId)->value('id');

                if ($warehouseId) {
                    \App\Models\InvWarehouseStock::create([
                        'warehouse_id' => $warehouseId,
                        'item_id'      => $item->id,
                        'stock'        => $initialStock,
                    ]);
                }

                KardexService::entry(
                    $sellerId,
                    $item->id,
                    $initialStock,
                    $cost,
                    $initialStock,
                    'Stock inicial en creación',
                    'manual',
                    null,
                    $warehouseId
                );
            }

            return $item;
        });

        return back()->with('success', 'Insumo creado');
    }

    public function itemUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:120', 'unit' => 'required|string|max:20',
            'min_stock' => 'nullable|numeric|min:0', 'cost' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0', 'item_type' => 'nullable|in:insumo,producto',
            'tax_type' => 'required|in:gravado,exonerado,inafecto',
        ]);
        InvItem::where('seller_id', $this->seller()->id)->findOrFail($id)
            ->update($request->only([
                'name', 'category', 'unit', 'min_stock', 'cost', 'sale_price',
                'item_type', 'tax_type', 'is_bar_item', 'bar_category', 'sunat_code',
            ]));
        return back()->with('success', 'Actualizado');
    }

    public function itemDelete($id)
    {
        InvItem::where('seller_id', $this->seller()->id)->findOrFail($id)->delete();
        return back()->with('success', 'Eliminado');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUPPLIERS / PROVEEDORES
    // ─────────────────────────────────────────────────────────────────────────

    public function suppliers()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Proveedores';
        $suppliers = InvSupplier::where('seller_id', $seller->id)->orderBy('name')->get();
        return view('seller.inventory.suppliers', compact('pageTitle', 'seller', 'store', 'suppliers'));
    }

    public function supplierStore(Request $request)
    {
        $service = new \App\Services\SupplierService($this->seller()->id);
        $supplier = $service->create($request);
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['status' => true, 'supplier' => $supplier]);
        }
        return back()->with('success', 'Proveedor creado');
    }

    public function supplierUpdate(Request $request, $id)
    {
        $service = new \App\Services\SupplierService($this->seller()->id);
        $service->update($request, $id);
        return back()->with('success', 'Actualizado');
    }

    public function supplierDelete($id)
    {
        $service = new \App\Services\SupplierService($this->seller()->id);
        if (!$service->delete($id)) {
            return back()->with('error', 'No se puede eliminar: el proveedor tiene compras u órdenes asociadas.');
        }
        return back()->with('success', 'Eliminado');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PURCHASES / COMPRAS
    // ─────────────────────────────────────────────────────────────────────────

    public function purchases()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Compras';
        $purchases = InvPurchase::where('seller_id', $seller->id)
                        ->with('supplier', 'items.item')->latest()->paginate(15);
        $suppliers = InvSupplier::where('seller_id', $seller->id)->orderBy('name')->get();
        $items     = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();
        return view('seller.inventory.purchases', compact(
            'pageTitle', 'seller', 'store', 'purchases', 'suppliers', 'items'
        ));
    }

    public function purchaseStore(Request $request)
    {
        $seller = $this->seller();
        $request->validate([
            'document_date' => 'required|date',
            'supplier_id'   => 'required|integer',
            'items'         => 'required|json',
            'document_type' => 'nullable|in:01,03',
            'payment_method' => 'nullable|in:cash,card,yape,plin,transfer,credit',
            'document_series' => 'nullable|string|max:20',
            'document_number' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:1000',
        ]);

        $supplier = InvSupplier::where('seller_id', $seller->id)->findOrFail($request->supplier_id);

        $purchaseItems = json_decode($request->items, true);
        if (!is_array($purchaseItems) || count($purchaseItems) === 0) {
            return back()->with('error', 'Agrega al menos un producto a la compra');
        }

        $subtotal = 0;
        $igv = 0;
        $validatedItems = [];
        foreach ($purchaseItems as $pi) {
            $item = InvItem::where('seller_id', $seller->id)->findOrFail($pi['item_id'] ?? 0);
            $qty = (float) ($pi['quantity'] ?? 0);
            $cost = (float) ($pi['unit_cost'] ?? 0);
            if (!is_finite($qty) || $qty <= 0 || $qty > 1000000 || !is_finite($cost) || $cost < 0) {
                return back()->with('error', 'Revisa las cantidades y costos de la compra.')->withInput();
            }
            $lineSubtotal = round($qty * $cost, 2);
            $lineIgv = $item->tax_type === 'gravado' ? round($lineSubtotal * 0.18, 2) : 0;
            $subtotal += $lineSubtotal;
            $igv += $lineIgv;
            $validatedItems[] = ['item' => $item, 'quantity' => $qty, 'unit_cost' => $cost, 'subtotal' => $lineSubtotal, 'igv' => $lineIgv];
        }
        $subtotal = round($subtotal, 2);
        $igv = round($igv, 2);
        $total = round($subtotal + $igv, 2);

        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
        if (!$cashSession) {
            return back()->with('error', 'Debe abrir una caja antes de registrar una compra de insumos.');
        }

        $warehouseId = $request->warehouse_id
            ? \App\Models\InvWarehouse::where('seller_id', $seller->id)->findOrFail($request->warehouse_id)->id
            : (\App\Models\InvWarehouse::where('seller_id', $seller->id)->where('is_default', true)->value('id')
            ?? \App\Models\InvWarehouse::where('seller_id', $seller->id)->value('id'));

        \Illuminate\Support\Facades\DB::transaction(function () use ($seller, $request, $validatedItems, $supplier, $subtotal, $igv, $total, $cashSession, $warehouseId) {
            $purchase = InvPurchase::create([
                'seller_id'       => $seller->id,
                'supplier_id'     => $supplier->id,
                'cash_session_id' => $cashSession->id,
                'warehouse_id'    => $warehouseId,
                'document_type'   => $request->document_type ?? '01',
                'document_series' => $request->document_series,
                'document_number' => $request->document_number,
                'document_date'   => $request->document_date,
                'subtotal'        => $subtotal,
                'igv'             => $igv,
                'total'           => $total,
                'payment_method'  => $request->payment_method ?? 'cash',
                'notes'           => $request->notes,
            ]);

            foreach ($validatedItems as $line) {
                $item = InvItem::where('seller_id', $seller->id)->lockForUpdate()->findOrFail($line['item']->id);
                $qty = $line['quantity'];
                $cost = $line['unit_cost'];

                InvPurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id'     => $item->id,
                    'quantity'    => $qty,
                    'unit_cost'   => $cost,
                    'tax_type'    => $item->tax_type,
                    'total'       => $line['subtotal'] + $line['igv'],
                ]);

                $oldStock = $item->stock;
                $currentCost = $item->cost ?? 0;
                $newCost = ($oldStock > 0)
                    ? (($oldStock * $currentCost) + ($qty * $cost)) / ($oldStock + $qty)
                    : $cost;
                $newStock = $oldStock + $qty;
                $item->update(['stock' => $newStock, 'last_cost' => $cost, 'cost' => round($newCost, 6)]);

                if ($warehouseId) {
                    $wStock = \App\Models\InvWarehouseStock::firstOrCreate(
                        ['warehouse_id' => $warehouseId, 'item_id' => $item->id],
                        ['stock' => 0]
                    );
                    $wStock->increment('stock', $qty);
                }

                KardexService::entry(
                    $seller->id, $item->id, $qty, $cost, $newStock,
                    'Compra #' . $purchase->id, 'purchase', $purchase->id, $warehouseId
                );
            }

            $cashSession->increment('total_expenses', $total);
        });

        return back()->with('success', 'Compra registrada. Stock actualizado.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KARDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function kardex($itemId = null)
    {
        $seller       = $this->seller();
        $store        = $this->store();
        $pageTitle    = 'Kardex';
        $items        = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();
        $movements    = null;
        $selectedItem = null;

        if ($itemId) {
            $selectedItem = InvItem::where('seller_id', $seller->id)->findOrFail($itemId);
            $movements    = InvKardex::where('seller_id', $seller->id)
                            ->where('item_id', $itemId)->latest()->paginate(30);
        }

        return view('seller.inventory.kardex', compact(
            'pageTitle', 'seller', 'store', 'items', 'movements', 'selectedItem'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STOCK ADJUST (manual)
    // ─────────────────────────────────────────────────────────────────────────

    public function stockAdjust(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer', 'quantity' => 'required|numeric|gt:0',
            'type' => 'required|in:entrada,salida', 'description' => 'nullable|string|max:255',
        ]);
        $seller   = $this->seller();
        $item     = InvItem::where('seller_id', $seller->id)->findOrFail($request->item_id);
        $qty      = floatval($request->quantity ?? 0);
        $type     = $request->type; // entrada | salida

        DB::transaction(function () use ($seller, $item, $qty, $type, $request) {
            $newStock = $type === 'entrada' ? $item->stock + abs($qty) : $item->stock - abs($qty);
            $item->update(['stock' => max(0, $newStock)]);

            if ($type === 'entrada') {
                KardexService::entry(
                    $seller->id, $item->id, $qty, $item->cost, max(0, $newStock),
                    $request->description ?? 'Ajuste manual', 'manual', null
                );
            } else {
                KardexService::exit(
                    $seller->id, $item->id, $qty, $item->cost, max(0, $newStock),
                    $request->description ?? 'Ajuste manual', 'manual', null
                );
            }
        });

        return back()->with('success', 'Stock ajustado');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RECETAS (Cocina + Bar)
    // ─────────────────────────────────────────────────────────────────────────

    public function recipes()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $company   = $this->company();
        $pageTitle = 'Recetas';

        $kitchenRecipes = InvRecipe::where('seller_id', $seller->id)
                            ->kitchen()->active()->with('product', 'items.item')->get();
        $barRecipes     = InvRecipe::where('seller_id', $seller->id)
                            ->bar()->active()->with('product', 'items.item')->get();

        // Calculate cost/margin data for each recipe
        foreach (collect()->merge($kitchenRecipes)->merge($barRecipes) as $recipe) {
            $recipe->total_cost = 0;
            foreach ($recipe->items as $ri) {
                $itemCost = $ri->item->cost ?? 0;
                $recipe->total_cost += $ri->quantity_net * $itemCost;
            }
            $recipe->cost_per_portion = $recipe->portions > 0 ? $recipe->total_cost / $recipe->portions : 0;
            $productPrice = $recipe->product->finalPrice() ?? $recipe->product->sale_price ?? 0;
            $recipe->product_price = $productPrice;
            $recipe->margin = $productPrice > 0 ? round(($productPrice - $recipe->cost_per_portion) / $productPrice * 100, 1) : 0;
            $recipe->suggested_price = $recipe->cost_per_portion > 0 ? round($recipe->cost_per_portion / (1 - 0.35), 2) : 0; // 35% margin target
        }

        $items    = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();
        $products = Product::whereHas('store', fn($q) => $q->where('seller_id', $seller->id))
                        ->orderBy('name')->get(['id', 'name']);

        $productions = InvProduction::where('seller_id', $seller->id)
                        ->with('recipe')->latest()->limit(20)->get();

        return view('seller.inventory.recipes', compact(
            'pageTitle', 'seller', 'store', 'company',
            'kitchenRecipes', 'barRecipes', 'items', 'products', 'productions'
        ));
    }

    public function recipeStore(Request $request)
    {
        $seller = $this->seller();
        $request->validate([
            'name'        => 'required|string|max:120',
            'recipe_type' => 'required|in:kitchen,bar',
            'portions'    => 'required|numeric|min:0.01',
            'ingredients' => 'required|json',
        ]);

        $ingredients = json_decode($request->ingredients, true);
        if (empty($ingredients)) {
            return back()->with('error', 'Agrega al menos un ingrediente a la receta');
        }
        if ($request->filled('product_id') && !Product::whereHas('store', fn($q) => $q->where('seller_id', $seller->id))->whereKey($request->product_id)->exists()) {
            return back()->with('error', 'El producto seleccionado no pertenece a este negocio.');
        }
        foreach ($ingredients as $ingredient) {
            $itemId = $ingredient['item_id'] ?? 0;
            $qty = (float) ($ingredient['quantity_gross'] ?? 0);
            $waste = (float) ($ingredient['waste_pct'] ?? 0);
            if (!InvItem::where('seller_id', $seller->id)->whereKey($itemId)->exists() || !is_finite($qty) || $qty <= 0 || !is_finite($waste) || $waste < 0 || $waste > 100) {
                return back()->with('error', 'Revisa los insumos y mermas de la receta.');
            }
        }

        DB::transaction(function () use ($request, $seller, $ingredients) {
            $recipeData = [
                    'seller_id'     => $seller->id,
                    'product_id'    => $request->product_id ?: null,
                    'name'          => $request->name,
                    'recipe_type'   => $request->recipe_type,
                    'portions'      => $request->portions,
                    'unit_produced' => $request->unit_produced ?? 'porcion',
                    'notes'         => $request->notes,
                ];
            if ($request->filled('recipe_id')) {
                $recipe = InvRecipe::where('seller_id', $seller->id)->findOrFail($request->recipe_id);
                $recipe->update($recipeData);
            } else {
                $recipe = InvRecipe::create($recipeData);
            }

            // Reemplazar ingredientes
            $recipe->items()->delete();
            foreach ($ingredients as $ing) {
                $itemId       = $ing['item_id'] ?? null;
                $qtyGross     = floatval($ing['quantity_gross'] ?? 0);
                $wastePct     = floatval($ing['waste_pct'] ?? 0);
                $qtyNet       = round($qtyGross * (1 - $wastePct / 100), 8);

                InvRecipeItem::create([
                    'recipe_id'      => $recipe->id,
                    'item_id'        => $itemId,
                    'quantity_gross' => $qtyGross,
                    'waste_pct'      => $wastePct,
                    'quantity_net'   => $qtyNet,
                    'unit'           => $ing['unit'] ?? 'UNIDAD',
                    'notes'          => $ing['notes'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Receta guardada correctamente');
    }

    public function recipeDelete($id)
    {
        InvRecipe::where('seller_id', $this->seller()->id)->findOrFail($id)->delete();
        return back()->with('success', 'Receta eliminada');
    }

    /**
     * Registrar producción → descuenta insumos del stock por cada porción producida.
     */
    public function recipeProduction(Request $request)
    {
        $seller = $this->seller();
        $request->validate([
            'recipe_id'        => 'required|exists:inv_recipes,id',
            'portions_produced' => 'required|integer|min:1',
        ]);

        $recipe = InvRecipe::where('seller_id', $seller->id)
                    ->with('items.item')->findOrFail($request->recipe_id);

        // Verificar stock suficiente para cada insumo
        $errors = [];
        foreach ($recipe->items as $ri) {
            $needed = round($ri->quantity_net * $request->portions_produced, 8);
            if ($ri->item->stock < $needed) {
                $errors[] = "Stock insuficiente de '{$ri->item->name}' (disponible: {$ri->item->stock} {$ri->item->unit}, necesario: {$needed})";
            }
        }
        if ($errors) {
            return back()->with('error', implode(' | ', $errors));
        }

        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();

        DB::transaction(function () use ($seller, $recipe, $request, $cashSession) {
            $production = InvProduction::create([
                'seller_id'        => $seller->id,
                'recipe_id'        => $recipe->id,
                'product_id'       => $recipe->product_id,
                'cash_session_id'  => $cashSession?->id,
                'portions_produced' => $request->portions_produced,
                'produced_at'      => now(),
                'notes'            => $request->notes,
            ]);

            foreach ($recipe->items as $ri) {
                $consumed = round($ri->quantity_net * $request->portions_produced, 8);
                $item     = $ri->item;
                $newStock = max(0, $item->stock - $consumed);

                $item->update(['stock' => $newStock]);

                InvKardex::create([
                    'seller_id'      => $seller->id,
                    'item_id'        => $item->id,
                    'type'           => 'salida',
                    'reference_type' => 'produccion',
                    'reference_id'   => $production->id,
                    'quantity'       => -$consumed,
                    'unit_cost'      => $item->cost,
                    'total_cost'     => $consumed * $item->cost,
                    'balance_stock'  => $newStock,
                    'description'    => "Producción: {$recipe->name} × {$request->portions_produced}",
                ]);
            }
        });

        return back()->with('success', "Producción registrada: {$recipe->name} × {$request->portions_produced} porciones. Stock de insumos actualizado.");
    }

    public function recipeProductionVoid($id)
    {
        $seller = $this->seller();
        $production = InvProduction::where('seller_id', $seller->id)->findOrFail($id);

        if ($production->status !== 'completed') {
            return back()->with('error', 'Esta producción ya fue anulada.');
        }

        $recipe = $production->recipe;
        if (!$recipe) {
            return back()->with('error', 'Receta no encontrada.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($seller, $production, $recipe) {
            foreach ($recipe->items as $ri) {
                $item = $ri->item;
                if (!$item) continue;

                $consumed = $ri->quantity_net * $production->portions_produced;
                $newStock = $item->stock + $consumed;
                $item->update(['stock' => $newStock]);

                InvKardex::create([
                    'seller_id'      => $seller->id,
                    'item_id'        => $item->id,
                    'type'           => 'entrada',
                    'reference_type' => 'produccion_anulada',
                    'reference_id'   => $production->id,
                    'quantity'       => $consumed,
                    'unit_cost'      => $item->cost,
                    'total_cost'     => $consumed * $item->cost,
                    'balance_stock'  => $newStock,
                    'description'    => "Anulación Producción #{$production->id}: {$recipe->name}",
                ]);
            }

            $production->update(['status' => 'voided']);
        });

        return back()->with('success', 'Producción #' . $production->id . ' anulada. Stock de insumos restaurado.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MERMAS
    // ─────────────────────────────────────────────────────────────────────────

    public function wastes()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Mermas';
        $wastes    = InvWaste::where('seller_id', $seller->id)
                        ->with('item')->latest()->paginate(20);
        $items     = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();
        return view('seller.inventory.wastes', compact('pageTitle', 'seller', 'store', 'wastes', 'items'));
    }

    public function wasteStore(Request $request)
    {
        $seller = $this->seller();
        $request->validate([
            'item_id'    => 'required|exists:inv_items,id',
            'quantity'   => 'required|numeric|min:0.01',
            'reason'     => 'required|string|max:120',
            'waste_date' => 'required|date',
        ]);

        $item        = InvItem::where('seller_id', $seller->id)->findOrFail($request->item_id);
        $qty         = floatval($request->quantity);
        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
        $newStock    = max(0, $item->stock - $qty);

        DB::transaction(function () use ($seller, $item, $qty, $newStock, $request, $cashSession) {
            $waste = InvWaste::create([
                'seller_id'       => $seller->id,
                'item_id'         => $item->id,
                'cash_session_id' => $cashSession?->id,
                'quantity'        => $qty,
                'unit'            => $item->unit,
                'reason'          => $request->reason,
                'waste_date'      => $request->waste_date,
                'notes'           => $request->notes,
            ]);

            $item->update(['stock' => $newStock]);

            InvKardex::create([
                'seller_id'      => $seller->id,
                'item_id'        => $item->id,
                'type'           => 'salida',
                'reference_type' => 'merma',
                'reference_id'   => $waste->id,
                'quantity'       => -$qty,
                'unit_cost'      => $item->cost,
                'total_cost'     => $qty * $item->cost,
                'balance_stock'  => $newStock,
                'description'    => 'Merma: ' . $request->reason,
            ]);
        });

        return back()->with('success', "Merma registrada. Stock de '{$item->name}' actualizado a {$newStock} {$item->unit}.");
    }

    public function wasteVoid($id)
    {
        $seller = $this->seller();
        $waste  = InvWaste::where('seller_id', $seller->id)->where('status', 'active')->findOrFail($id);
        $item   = $waste->item;

        DB::transaction(function () use ($seller, $waste, $item) {
            $newStock = $item->stock + $waste->quantity;
            $item->update(['stock' => $newStock]);
            $waste->update(['status' => 'voided']);

            InvKardex::create([
                'seller_id'      => $seller->id,
                'item_id'        => $item->id,
                'type'           => 'entrada',
                'reference_type' => 'merma_anulada',
                'reference_id'   => $waste->id,
                'quantity'       => $waste->quantity,
                'unit_cost'      => $item->cost,
                'total_cost'     => $waste->quantity * $item->cost,
                'balance_stock'  => $newStock,
                'description'    => 'Anulación merma #' . $waste->id,
            ]);
        });

        return back()->with('success', 'Merma anulada. Stock devuelto.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VENTAS DIRECTAS (Tienda / Negocio no-restaurante)
    // ─────────────────────────────────────────────────────────────────────────

    public function sales()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Ventas Directas';
        $sales     = InvSale::where('seller_id', $seller->id)
                        ->with('items.item', 'supplier')->latest()->paginate(20);
        $items     = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();
        $suppliers = InvSupplier::where('seller_id', $seller->id)->orderBy('name')->get();
        return view('seller.inventory.sales', compact(
            'pageTitle', 'seller', 'store', 'sales', 'items', 'suppliers'
        ));
    }

    public function saleStore(Request $request)
    {
        $seller = $this->seller();
        $request->validate([
            'document_date' => 'required|date',
            'items'         => 'required|json',
            'supplier_id'   => 'nullable|integer',
            'document_type' => 'nullable|in:00,01,03,07',
            'payment_method' => 'nullable|in:cash,card,yape,plin,transfer,credit',
            'notes' => 'nullable|string|max:1000',
        ]);
        if ($request->filled('supplier_id')) {
            InvSupplier::where('seller_id', $seller->id)->findOrFail($request->supplier_id);
        }

        $saleItems = json_decode($request->items, true);
        if (empty($saleItems)) {
            return back()->with('error', 'Agrega al menos un producto a la venta');
        }

        $subtotalGravado   = 0;
        $subtotalExonerado = 0;
        $subtotalInafecto  = 0;
        $igvTotal          = 0;

        $validatedItems = [];
        // Validate the complete document and reject foreign or malformed lines.
        foreach ($saleItems as $si) {
            $item = InvItem::where('seller_id', $seller->id)->findOrFail($si['item_id'] ?? 0);
            $qty = floatval($si['quantity'] ?? 0);
            $price = array_key_exists('unit_price', $si) ? (float) $si['unit_price'] : (float) $item->sale_price;
            if (!is_finite($qty) || $qty <= 0 || $qty > 1000000 || !is_finite($price) || $price < 0) {
                return back()->with('error', 'Revisa las cantidades y precios de la venta.');
            }
            if ($item->stock < $qty) {
                return back()->with('error', "Stock insuficiente de '{$item->name}' (disponible: {$item->stock} {$item->unit})");
            }
            $validatedItems[] = ['item_id' => $item->id, 'quantity' => $qty, 'unit_price' => $price];
        }
        foreach (collect($validatedItems)->groupBy('item_id') as $itemId => $lines) {
            $item = InvItem::where('seller_id', $seller->id)->findOrFail($itemId);
            $needed = $lines->sum('quantity');
            if ($item->stock < $needed) return back()->with('error', "Stock insuficiente de '{$item->name}' (disponible: {$item->stock} {$item->unit})");
        }

        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
        if (!$cashSession) {
            return back()->with('error', 'Debe abrir una caja antes de registrar una venta de insumos.');
        }

        try { DB::transaction(function () use ($seller, $request, $validatedItems, $cashSession,
            &$subtotalGravado, &$subtotalExonerado, &$subtotalInafecto, &$igvTotal)
        {
            foreach (collect($validatedItems)->groupBy('item_id') as $itemId => $lines) {
                $lockedItem = InvItem::where('seller_id', $seller->id)->lockForUpdate()->findOrFail($itemId);
                if ($lockedItem->stock < $lines->sum('quantity')) {
                    throw new \RuntimeException('El stock cambió mientras se registraba la venta.');
                }
            }
            // Compute totals
            foreach ($validatedItems as $si) {
                $item = InvItem::where('seller_id', $seller->id)->lockForUpdate()->findOrFail($si['item_id']);
                $qty = $si['quantity'];
                $price = $si['unit_price'];
                $taxType = in_array($item->tax_type, \App\Support\TaxCalculator::TYPES, true) ? $item->tax_type : 'gravado';
                $parts = \App\Support\TaxCalculator::line($qty * $price, $taxType);
                $subtotal = $parts['base'];
                $igv = $parts['igv'];

                match($taxType) {
                    'exonerado' => $subtotalExonerado += $subtotal,
                    'inafecto'  => $subtotalInafecto  += $subtotal,
                    default     => $subtotalGravado   += $subtotal,
                };
                $igvTotal += $igv;
            }

            $sale = InvSale::create([
                'seller_id'           => $seller->id,
                'supplier_id'         => $request->supplier_id ?: null,
                'cash_session_id'     => $cashSession?->id,
                'document_type'       => $request->document_type ?? '00',
                'document_series'     => $request->document_series,
                'document_number'     => $request->document_number,
                'document_date'       => $request->document_date,
                'subtotal_gravado'    => round($subtotalGravado, 2),
                'subtotal_exonerado'  => round($subtotalExonerado, 2),
                'subtotal_inafecto'   => round($subtotalInafecto, 2),
                'igv'                 => round($igvTotal, 2),
                'total'               => round($subtotalGravado + $subtotalExonerado + $subtotalInafecto + $igvTotal, 2),
                'payment_method'      => $request->payment_method ?? 'cash',
                'notes'               => $request->notes,
            ]);

            foreach ($validatedItems as $si) {
                $item = InvItem::where('seller_id', $seller->id)->lockForUpdate()->findOrFail($si['item_id']);
                $qty = $si['quantity'];
                $price = $si['unit_price'];
                $taxType = in_array($item->tax_type, \App\Support\TaxCalculator::TYPES, true) ? $item->tax_type : 'gravado';
                $parts = \App\Support\TaxCalculator::line($qty * $price, $taxType);
                $sub = $parts['base'];
                $igv = $parts['igv'];

                InvSaleItem::create([
                    'sale_id'    => $sale->id,
                    'item_id'    => $item->id,
                    'quantity'   => $qty,
                    'unit_price' => $price,
                    'tax_type'   => $taxType,
                    'subtotal'   => $sub,
                    'igv'        => $igv,
                    'total'      => $parts['total'],
                ]);

                $newStock = max(0, $item->stock - $qty);
                $item->update(['stock' => $newStock]);

                InvKardex::create([
                    'seller_id'      => $seller->id,
                    'item_id'        => $item->id,
                    'type'           => 'salida',
                    'reference_type' => 'venta',
                    'reference_id'   => $sale->id,
                    'quantity'       => -$qty,
                    'unit_cost'      => $item->cost,
                    'total_cost'     => $qty * $item->cost,
                    'balance_stock'  => $newStock,
                    'description'    => 'Venta directa #' . $sale->id,
                ]);
            }

            if ($cashSession) {
                $cashSession->increment('total_income', $sale->total);
            }
        }); } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No se pudo registrar la venta. Verifica el stock disponible e inténtalo nuevamente.');
        }

        return back()->with('success', 'Venta registrada. Stock actualizado.');
    }

    public function saleVoid($id)
    {
        $seller = $this->seller();
        $sale   = InvSale::where('seller_id', $seller->id)
                    ->where('status', 'completed')
                    ->with('items.item')
                    ->findOrFail($id);

        DB::transaction(function () use ($seller, $sale) {
            foreach ($sale->items as $si) {
                $item     = $si->item;
                $newStock = $item->stock + $si->quantity;
                $item->update(['stock' => $newStock]);

                InvKardex::create([
                    'seller_id'      => $seller->id,
                    'item_id'        => $item->id,
                    'type'           => 'entrada',
                    'reference_type' => 'venta_anulada',
                    'reference_id'   => $sale->id,
                    'quantity'       => $si->quantity,
                    'unit_cost'      => $item->cost,
                    'total_cost'     => $si->quantity * $item->cost,
                    'balance_stock'  => $newStock,
                    'description'    => 'Anulación venta #' . $sale->id,
                ]);
            }
            $sale->update(['status' => 'voided']);
        });

        return back()->with('success', 'Venta anulada. Stock revertido.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTE TRIBUTARIO
    // ─────────────────────────────────────────────────────────────────────────

    public function taxReport(Request $request)
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Reporte Tributario';
        $month     = $request->month ?? now()->format('Y-m');

        [$year, $mon] = explode('-', $month);
        $start = "{$year}-{$mon}-01";
        $end   = date('Y-m-t', strtotime($start));

        // Ventas manuales registradas desde inventario.
        $salesData = InvSale::where('seller_id', $seller->id)
            ->where('status', 'completed')
            ->whereBetween('document_date', [$start, $end])
            ->selectRaw('
                SUM(subtotal_gravado)   as total_gravado,
                SUM(subtotal_exonerado) as total_exonerado,
                SUM(subtotal_inafecto)  as total_inafecto,
                SUM(igv)               as total_igv_ventas,
                SUM(total)             as total_ventas,
                COUNT(*)               as num_ventas
            ')->first();

        // Los comprobantes emitidos desde el POS no crean registros en inv_sales;
        // sus importes tributarios se conservan en sunat_invoices.
        $electronicSales = SunatInvoice::where('seller_id', $seller->id)
            ->whereIn('tipo_doc', ['01', '03', '07', '08'])
            ->where('fecha_emision', '>=', $start)
            ->where('fecha_emision', '<', date('Y-m-d', strtotime($end . ' +1 day')))
            ->where(function ($query) {
                $query->whereNull('cdr_status')
                    ->orWhereNotIn('cdr_status', ['rejected', 'error', 'cancelled', 'voided']);
            })
            ->selectRaw("\n                SUM(CASE WHEN tipo_doc = '07' THEN -total_gravada ELSE total_gravada END) as total_gravado,\n                SUM(CASE WHEN tipo_doc = '07' THEN -total_exonerada ELSE total_exonerada END) as total_exonerado,\n                SUM(CASE WHEN tipo_doc = '07' THEN -total_inafecta ELSE total_inafecta END) as total_inafecto,\n                SUM(CASE WHEN tipo_doc = '07' THEN -total_igv ELSE total_igv END) as total_igv_ventas,\n                SUM(CASE WHEN tipo_doc = '07' THEN -total ELSE total END) as total_ventas,\n                COUNT(*) as num_ventas\n            ")->first();

        foreach (['total_gravado', 'total_exonerado', 'total_inafecto', 'total_igv_ventas', 'total_ventas', 'num_ventas'] as $field) {
            $salesData->{$field} = (float) ($salesData->{$field} ?? 0) + (float) ($electronicSales->{$field} ?? 0);
        }

        // Compras (IGV pagado)
        $purchasesData = InvPurchase::where('seller_id', $seller->id)
            ->whereBetween('document_date', [$start, $end])
            ->selectRaw('SUM(igv) as total_igv_compras, SUM(total) as total_compras, COUNT(*) as num_compras')
            ->first();

        // Mermas (costo)
        $wastesData = InvWaste::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->whereBetween('waste_date', [$start, $end])
            ->with('item')
            ->get();

        $totalWasteCost = $wastesData->sum(fn($w) => $w->quantity * $w->item->cost);

        return view('seller.inventory.tax-report', compact(
            'pageTitle', 'seller', 'store', 'month', 'start', 'end',
            'salesData', 'purchasesData', 'wastesData', 'totalWasteCost'
        ));
    }
}
