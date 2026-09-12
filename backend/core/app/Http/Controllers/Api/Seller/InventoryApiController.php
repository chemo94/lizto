<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\InvItem;
use App\Models\InvKardex;
use App\Models\InvProduction;
use App\Models\InvPurchase;
use App\Models\InvPurchaseItem;
use App\Models\InvRecipe;
use App\Models\InvRecipeItem;
use App\Models\InvSupplier;
use App\Models\InvWaste;
use App\Models\InvWarehouse;
use App\Models\InvWarehouseStock;
use App\Models\PosCashSession;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Store;
use App\Services\KardexService;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventoryApiController extends Controller
{
    private function seller()
    {
        $user = auth()->user();
        if ($user instanceof \App\Models\PosStaff) {
            return Seller::find($user->seller_id);
        }
        return $user;
    }

    private function store()
    {
        $seller = $this->seller();
        return $seller ? Store::where('seller_id', $seller->id)->first() : null;
    }

    private function defaultWarehouseId($sellerId)
    {
        return InvWarehouse::where('seller_id', $sellerId)->where('is_default', true)->value('id')
            ?? InvWarehouse::where('seller_id', $sellerId)->value('id');
    }

    private function company()
    {
        $seller = $this->seller();
        return $seller ? \App\Models\SellerCompany::where('seller_id', $seller->id)->first() : null;
    }

    public function getInventoryMetadata()
    {
        $seller  = $this->seller();
        $company = $this->company();

        $units = [
            ['code' => 'NIU', 'name' => 'Unidad (UND)'],
            ['code' => 'KGM', 'name' => 'Kilogramo (KG)'],
            ['code' => 'GRM', 'name' => 'Gramos (GR)'],
            ['code' => 'LTR', 'name' => 'Litro (LT)'],
            ['code' => 'MLT', 'name' => 'Mililitro (ML)'],
            ['code' => 'DZN', 'name' => 'Docena (DOC)'],
            ['code' => 'HD', 'name' => 'Media docena (1/2 DOC)'],
            ['code' => 'QD', 'name' => 'Cuarto de docena (1/4 DOC)'],
            ['code' => 'C62', 'name' => 'Piezas (PZ)'],
            ['code' => 'PR', 'name' => 'Par (PAR)'],
            ['code' => 'SET', 'name' => 'Juego (JGO)'],
            ['code' => 'KT', 'name' => 'Kit (KIT)'],
            ['code' => 'TNE', 'name' => 'Toneladas (TNL)'],
            ['code' => 'LBR', 'name' => 'Libras (LB)'],
            ['code' => 'ONZ', 'name' => 'Onzas (ONZ)'],
            ['code' => 'GLL', 'name' => 'Galón (GL)'],
            ['code' => 'BO', 'name' => 'Botellas (BOT)'],
            ['code' => 'CA', 'name' => 'Latas (LT)'],
            ['code' => 'BX', 'name' => 'Caja (CAJ)'],
            ['code' => 'PK', 'name' => 'Paquete (PQT)'],
            ['code' => 'BG', 'name' => 'Bolsa (BOLS)'],
            ['code' => 'JR', 'name' => 'Frasco (FCO)'],
            ['code' => 'BLL', 'name' => 'Barril (BRL)'],
            ['code' => 'PORCION', 'name' => 'Porción'],
        ];

        $taxTypes = [
            ['code' => 'gravado', 'name' => 'Gravado (IGV)'],
            ['code' => 'exonerado', 'name' => 'Exonerado'],
            ['code' => 'inafecto', 'name' => 'Inafecto'],
        ];

        $documentTypes = [
            ['code' => '01', 'name' => 'Factura'],
            ['code' => '03', 'name' => 'Boleta de Venta'],
            ['code' => '00', 'name' => 'Nota de Venta / Recibo'],
        ];

        $paymentMethods = [
            ['code' => 'cash', 'name' => 'Efectivo'],
            ['code' => 'transfer', 'name' => 'Transferencia Bancaria'],
            ['code' => 'yape', 'name' => 'Yape / Plin'],
            ['code' => 'card', 'name' => 'Tarjeta'],
            ['code' => 'credit', 'name' => 'Crédito Proveedor'],
        ];

        $wasteReasons = [
            'Caducado / Vencido',
            'Quemado / Malogrado en cocción',
            'Derrame / Rotura accidental',
            'Plato cancelado por comensal',
            'Calidad deficiente del insumo',
            'Muestra / Degustación',
            'Otro motivo',
        ];

        $currencySymbol = gs('cur_sym') ?? 'S/';
        $currencyText   = gs('cur_text') ?? 'PEN';
        $taxRatePercent = 18.0;
        $targetMarginPercent = 35.0;

        return [
            'units'                 => $units,
            'tax_types'             => $taxTypes,
            'document_types'        => $documentTypes,
            'payment_methods'       => $paymentMethods,
            'waste_reasons'         => $wasteReasons,
            'currency_symbol'       => $currencySymbol,
            'currency_text'         => $currencyText,
            'tax_rate_percent'      => $taxRatePercent,
            'target_margin_percent' => $targetMarginPercent,
            'default_tax_type'      => $company?->default_tax_type ?? 'gravado',
            'has_bar'               => (bool) ($company?->has_bar ?? true),
        ];
    }

    public function metadata(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }
        return apiResponse('inventory_metadata', 'success', ['Metadatos de inventario'], $this->getInventoryMetadata());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. INSUMOS / ITEMS
    // ─────────────────────────────────────────────────────────────────────────

    public function items(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $query = InvItem::where('seller_id', $seller->id)->insumos();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('sunat_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        $items = $query->orderBy('category')->orderBy('name')->get();

        $categories = InvItem::where('seller_id', $seller->id)
            ->insumos()
            ->pluck('category')
            ->unique()
            ->filter()
            ->values();

        $allItems = InvItem::where('seller_id', $seller->id)->insumos()->get();
        $totalValuation = $allItems->sum(fn($i) => ($i->stock ?? 0) * ($i->cost ?? 0));
        $lowStockCount  = $allItems->filter(fn($i) => $i->isLowStock())->count();
        $outOfStockCount = $allItems->where('stock', '<=', 0)->count();

        return apiResponse('inventory_items', 'success', ['Insumos obtenidos'], [
            'items'             => $items,
            'categories'        => $categories,
            'total_items'       => $allItems->count(),
            'total_valuation'   => round($totalValuation, 2),
            'low_stock_count'   => $lowStockCount,
            'out_of_stock_count'=> $outOfStockCount,
            'metadata'          => $this->getInventoryMetadata(),
        ]);
    }

    public function itemStore(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:120',
            'unit'          => 'required|string|max:20',
            'category'      => 'nullable|string|max:80',
            'tax_type'      => 'required|in:gravado,exonerado,inafecto',
            'cost'          => 'nullable|numeric|min:0',
            'min_stock'     => 'nullable|numeric|min:0',
            'initial_stock' => 'nullable|numeric|min:0',
            'is_bar_item'   => 'nullable|boolean',
            'bar_category'  => 'nullable|string|max:50',
            'sunat_code'    => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $initialStock = floatval($request->initial_stock ?? 0);
        $cost         = floatval($request->cost ?? 0);
        $sellerId     = $seller->id;

        $item = DB::transaction(function () use ($request, $initialStock, $cost, $sellerId) {
            $item = InvItem::create([
                'seller_id'    => $sellerId,
                'name'         => $request->name,
                'category'     => $request->category,
                'unit'         => strtoupper($request->unit),
                'min_stock'    => $request->min_stock ?? 0,
                'cost'         => $cost,
                'last_cost'    => $cost,
                'stock'        => $initialStock,
                'item_type'    => 'insumo',
                'tax_type'     => $request->tax_type,
                'is_bar_item'  => $request->boolean('is_bar_item'),
                'bar_category' => $request->bar_category,
                'sunat_code'   => $request->sunat_code,
                'status'       => 'active',
            ]);

            if ($initialStock > 0) {
                $warehouseId = $this->defaultWarehouseId($sellerId);
                if ($warehouseId) {
                    InvWarehouseStock::create([
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

        return apiResponse('item_created', 'success', ['Insumo creado exitosamente'], ['item' => $item]);
    }

    public function itemUpdate(Request $request, $id)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $item = InvItem::where('seller_id', $seller->id)->find($id);
        if (!$item) {
            return apiResponse('not_found', 'error', ['Insumo no encontrado']);
        }

        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|required|string|max:120',
            'unit'         => 'sometimes|required|string|max:20',
            'category'     => 'nullable|string|max:80',
            'cost'         => 'nullable|numeric|min:0',
            'min_stock'    => 'nullable|numeric|min:0',
            'sale_price'   => 'nullable|numeric|min:0',
            'tax_type'     => 'sometimes|required|in:gravado,exonerado,inafecto',
            'is_bar_item'  => 'nullable|boolean',
            'bar_category' => 'nullable|string|max:50',
            'sunat_code'   => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $data = $request->only([
            'name', 'category', 'unit', 'min_stock', 'cost', 'sale_price',
            'tax_type', 'is_bar_item', 'bar_category', 'sunat_code',
        ]);
        if (isset($data['unit'])) {
            $data['unit'] = strtoupper($data['unit']);
        }

        $item->update($data);

        return apiResponse('item_updated', 'success', ['Insumo actualizado exitosamente'], ['item' => $item]);
    }

    public function itemDelete($id)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $item = InvItem::where('seller_id', $seller->id)->find($id);
        if (!$item) {
            return apiResponse('not_found', 'error', ['Insumo no encontrado']);
        }

        $hasRecipes = InvRecipeItem::where('item_id', $item->id)->exists();
        if ($hasRecipes) {
            return apiResponse('error', 'error', ['No se puede eliminar: el insumo forma parte de una o más recetas activas.']);
        }

        $item->delete();

        return apiResponse('item_deleted', 'success', ['Insumo eliminado exitosamente']);
    }

    public function stockAdjust(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $validator = Validator::make($request->all(), [
            'item_id'     => 'required|exists:inv_items,id',
            'type'        => 'required|in:entrada,salida',
            'quantity'    => 'required|numeric|gt:0',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $item = InvItem::where('seller_id', $seller->id)->find($request->item_id);
        if (!$item) {
            return apiResponse('not_found', 'error', ['Insumo no encontrado']);
        }

        $qty         = floatval($request->quantity);
        $type        = $request->type;
        $description = $request->description ?: ($type === 'entrada' ? 'Ajuste manual de entrada' : 'Ajuste manual de salida');
        $warehouseId = $this->defaultWarehouseId($seller->id);

        DB::transaction(function () use ($seller, $item, $qty, $type, $description, $warehouseId) {
            $newStock = $type === 'entrada' ? ($item->stock + $qty) : max(0, $item->stock - $qty);
            $item->update(['stock' => $newStock]);

            if ($warehouseId) {
                $wStock = InvWarehouseStock::firstOrCreate(
                    ['warehouse_id' => $warehouseId, 'item_id' => $item->id],
                    ['stock' => 0]
                );
                if ($type === 'entrada') {
                    $wStock->increment('stock', $qty);
                } else {
                    $wStock->update(['stock' => max(0, $wStock->stock - $qty)]);
                }
            }

            if ($type === 'entrada') {
                KardexService::entry(
                    $seller->id, $item->id, $qty, $item->cost ?? 0, $newStock,
                    $description, 'manual', null, $warehouseId
                );
            } else {
                KardexService::exit(
                    $seller->id, $item->id, $qty, $item->cost ?? 0, $newStock,
                    $description, 'manual', null, $warehouseId
                );
            }
        });

        $item->refresh();

        return apiResponse('stock_adjusted', 'success', ['Stock ajustado exitosamente'], ['item' => $item]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. RECETAS & PRODUCCIONES
    // ─────────────────────────────────────────────────────────────────────────

    public function recipes(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $typeFilter = $request->type; // kitchen, bar or null

        $query = InvRecipe::where('seller_id', $seller->id)
            ->active()
            ->with(['product:id,name,price,discount_price', 'items.item']);

        if ($typeFilter && in_array($typeFilter, ['kitchen', 'bar'])) {
            $query->where('recipe_type', $typeFilter);
        }

        $recipes = $query->orderBy('name')->get();

        // Calculate costs, portions, margins for each recipe
        foreach ($recipes as $recipe) {
            $totalCost = 0;
            foreach ($recipe->items as $ri) {
                $itemCost = $ri->item->cost ?? 0;
                $totalCost += ($ri->quantity_net ?? $ri->quantity_gross ?? 0) * $itemCost;
            }
            $targetMarginPercent = floatval($request->target_margin_percent ?? 35);
            $targetMargin = $targetMarginPercent / 100;
            $recipe->total_cost        = round($totalCost, 4);
            $recipe->cost_per_portion  = $recipe->portions > 0 ? round($totalCost / $recipe->portions, 4) : 0;
            $productPrice              = $recipe->product ? ($recipe->product->discount_price > 0 ? $recipe->product->discount_price : $recipe->product->price) : 0;
            $recipe->product_price     = floatval($productPrice);
            $recipe->margin            = $productPrice > 0 ? round(($productPrice - $recipe->cost_per_portion) / $productPrice * 100, 1) : 0;
            $recipe->suggested_price   = ($recipe->cost_per_portion > 0 && $targetMargin < 1) ? round($recipe->cost_per_portion / (1 - $targetMargin), 2) : 0;
        }

        $items = InvItem::where('seller_id', $seller->id)->insumos()->orderBy('name')->get(['id', 'name', 'unit', 'cost', 'stock']);

        $products = Product::whereHas('store', fn($q) => $q->where('seller_id', $seller->id))
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'discount_price']);

        $productions = InvProduction::where('seller_id', $seller->id)
            ->with(['recipe:id,name,unit_produced', 'product:id,name'])
            ->latest()
            ->limit(25)
            ->get();

        return apiResponse('recipes', 'success', ['Recetas obtenidas'], [
            'recipes'     => $recipes,
            'items'       => $items,
            'products'    => $products,
            'productions' => $productions,
            'metadata'    => $this->getInventoryMetadata(),
        ]);
    }

    public function recipeStore(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:120',
            'recipe_type'   => 'required|in:kitchen,bar',
            'portions'      => 'required|numeric|min:0.01',
            'unit_produced' => 'nullable|string|max:30',
            'product_id'    => 'nullable|exists:products,id',
            'notes'         => 'nullable|string|max:500',
            'ingredients'   => 'required', // Array or JSON string
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $rawIngredients = $request->ingredients;
        $ingredients = is_array($rawIngredients) ? $rawIngredients : json_decode($rawIngredients, true);

        if (empty($ingredients) || !is_array($ingredients)) {
            return apiResponse('error', 'error', ['Debe incluir al menos un ingrediente para la receta']);
        }

        $recipe = DB::transaction(function () use ($request, $seller, $ingredients) {
            $recipe = InvRecipe::updateOrCreate(
                [
                    'id'        => $request->recipe_id ?? 0,
                    'seller_id' => $seller->id,
                ],
                [
                    'product_id'    => $request->product_id ?: null,
                    'name'          => $request->name,
                    'recipe_type'   => $request->recipe_type,
                    'portions'      => $request->portions,
                    'unit_produced' => $request->unit_produced ?? 'porcion',
                    'notes'         => $request->notes,
                    'status'        => 'active',
                ]
            );

            // Reemplazar ingredientes
            $recipe->items()->delete();
            foreach ($ingredients as $ing) {
                $itemId   = $ing['item_id'] ?? null;
                $qtyGross = floatval($ing['quantity_gross'] ?? 0);
                $wastePct = floatval($ing['waste_pct'] ?? 0);
                $qtyNet   = round($qtyGross * (1 - $wastePct / 100), 8);

                if (!$itemId || $qtyGross <= 0) continue;

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

            return $recipe->load(['items.item', 'product']);
        });

        return apiResponse('recipe_saved', 'success', ['Receta guardada exitosamente'], ['recipe' => $recipe]);
    }

    public function recipeDelete($id)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $recipe = InvRecipe::where('seller_id', $seller->id)->find($id);
        if (!$recipe) {
            return apiResponse('not_found', 'error', ['Receta no encontrada']);
        }

        $recipe->items()->delete();
        $recipe->delete();

        return apiResponse('recipe_deleted', 'success', ['Receta eliminada exitosamente']);
    }

    public function recipeProduction(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $validator = Validator::make($request->all(), [
            'recipe_id'         => 'required|exists:inv_recipes,id',
            'portions_produced' => 'required|numeric|min:0.1',
            'notes'             => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $recipe = InvRecipe::where('seller_id', $seller->id)
            ->with('items.item')
            ->find($request->recipe_id);

        if (!$recipe) {
            return apiResponse('not_found', 'error', ['Receta no encontrada']);
        }

        $portions = floatval($request->portions_produced);

        // Validar existencias de todos los insumos necesarios
        $errors = [];
        foreach ($recipe->items as $ri) {
            $needed = round($ri->quantity_net * $portions, 8);
            if ($ri->item->stock < $needed) {
                $errors[] = "Stock insuficiente de '{$ri->item->name}' (disponible: {$ri->item->stock} {$ri->item->unit}, requerido: {$needed})";
            }
        }

        if (!empty($errors)) {
            return apiResponse('insufficient_stock', 'error', $errors);
        }

        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
        $warehouseId = $this->defaultWarehouseId($seller->id);

        $production = DB::transaction(function () use ($seller, $recipe, $portions, $request, $cashSession, $warehouseId) {
            $production = InvProduction::create([
                'seller_id'         => $seller->id,
                'recipe_id'         => $recipe->id,
                'product_id'        => $recipe->product_id,
                'cash_session_id'   => $cashSession?->id,
                'portions_produced' => $portions,
                'produced_at'       => now(),
                'notes'             => $request->notes,
                'status'            => 'completed',
            ]);

            foreach ($recipe->items as $ri) {
                $consumed = round($ri->quantity_net * $portions, 8);
                $item     = $ri->item;
                $newStock = max(0, $item->stock - $consumed);

                $item->update(['stock' => $newStock]);

                if ($warehouseId) {
                    $wStock = InvWarehouseStock::firstOrCreate(
                        ['warehouse_id' => $warehouseId, 'item_id' => $item->id],
                        ['stock' => 0]
                    );
                    $wStock->update(['stock' => max(0, $wStock->stock - $consumed)]);
                }

                KardexService::exit(
                    $seller->id,
                    $item->id,
                    $consumed,
                    $item->cost ?? 0,
                    $newStock,
                    "Producción: {$recipe->name} × {$portions}",
                    'produccion',
                    $production->id,
                    $warehouseId
                );
            }

            return $production->load('recipe');
        });

        return apiResponse('production_completed', 'success', [
            "Producción registrada exitosamente: {$recipe->name} × {$portions}. Stock de insumos descontado."
        ], ['production' => $production]);
    }

    public function recipeProductionVoid($id)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $production = InvProduction::where('seller_id', $seller->id)->find($id);
        if (!$production) {
            return apiResponse('not_found', 'error', ['Producción no encontrada']);
        }

        if ($production->status === 'voided') {
            return apiResponse('already_voided', 'error', ['Esta producción ya fue anulada previamente.']);
        }

        $recipe = InvRecipe::with('items.item')->find($production->recipe_id);
        if (!$recipe) {
            return apiResponse('not_found', 'error', ['Receta asociada no encontrada']);
        }

        $warehouseId = $this->defaultWarehouseId($seller->id);

        DB::transaction(function () use ($seller, $production, $recipe, $warehouseId) {
            foreach ($recipe->items as $ri) {
                $item = $ri->item;
                if (!$item) continue;

                $consumed = $ri->quantity_net * $production->portions_produced;
                $newStock = $item->stock + $consumed;
                $item->update(['stock' => $newStock]);

                if ($warehouseId) {
                    $wStock = InvWarehouseStock::firstOrCreate(
                        ['warehouse_id' => $warehouseId, 'item_id' => $item->id],
                        ['stock' => 0]
                    );
                    $wStock->increment('stock', $consumed);
                }

                KardexService::entry(
                    $seller->id,
                    $item->id,
                    $consumed,
                    $item->cost ?? 0,
                    $newStock,
                    "Anulación Producción #{$production->id}: {$recipe->name}",
                    'produccion_anulada',
                    $production->id,
                    $warehouseId
                );
            }

            $production->update(['status' => 'voided']);
        });

        return apiResponse('production_voided', 'success', [
            "Producción #{$production->id} anulada exitosamente. Insumos restaurados al stock."
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. COMPRAS / PURCHASES
    // ─────────────────────────────────────────────────────────────────────────

    public function purchases(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $purchases = InvPurchase::where('seller_id', $seller->id)
            ->with(['supplier:id,name,document_number,phone', 'items.item:id,name,unit,cost'])
            ->latest()
            ->paginate(getPaginate());

        $suppliers = InvSupplier::where('seller_id', $seller->id)->orderBy('name')->get(['id', 'name', 'document_number', 'phone']);
        $items     = InvItem::where('seller_id', $seller->id)->insumos()->orderBy('name')->get(['id', 'name', 'unit', 'cost', 'stock']);

        return apiResponse('purchases', 'success', ['Compras obtenidas'], [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'items'     => $items,
            'metadata'  => $this->getInventoryMetadata(),
        ]);
    }

    public function purchaseStore(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $validator = Validator::make($request->all(), [
            'document_date'   => 'required|date',
            'supplier_id'     => 'required|exists:inv_suppliers,id',
            'document_type'   => 'nullable|string|max:10',
            'document_series' => 'nullable|string|max:10',
            'document_number' => 'nullable|string|max:20',
            'payment_method'  => 'nullable|string|max:30',
            'notes'           => 'nullable|string|max:500',
            'items'           => 'required', // array or json string
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $rawItems = $request->items;
        $purchaseItems = is_array($rawItems) ? $rawItems : json_decode($rawItems, true);

        if (empty($purchaseItems) || !is_array($purchaseItems)) {
            return apiResponse('error', 'error', ['Agrega al menos un insumo a la compra']);
        }

        $subtotal = 0;
        foreach ($purchaseItems as $pi) {
            $subtotal += floatval($pi['quantity'] ?? 0) * floatval($pi['unit_cost'] ?? 0);
        }

        $taxRatePercent = floatval($request->tax_rate_percent ?? 18);
        $taxRate = $taxRatePercent / 100;
        $igv   = $request->filled('igv') ? floatval($request->igv) : round($subtotal * $taxRate, 2);
        $total = $request->filled('total') ? floatval($request->total) : round($subtotal + $igv, 2);

        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
        $warehouseId = $request->warehouse_id ?? $this->defaultWarehouseId($seller->id);

        $purchase = DB::transaction(function () use ($seller, $request, $purchaseItems, $subtotal, $igv, $total, $cashSession, $warehouseId) {
            $purchase = InvPurchase::create([
                'seller_id'       => $seller->id,
                'supplier_id'     => $request->supplier_id,
                'cash_session_id' => $cashSession?->id,
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

            foreach ($purchaseItems as $pi) {
                $item = InvItem::where('seller_id', $seller->id)->find($pi['item_id'] ?? 0);
                if (!$item) continue;

                $qty  = floatval($pi['quantity'] ?? 0);
                $cost = floatval($pi['unit_cost'] ?? 0);

                if ($qty <= 0) continue;

                InvPurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id'     => $item->id,
                    'quantity'    => $qty,
                    'unit_cost'   => $cost,
                    'total'       => round($qty * $cost, 2),
                ]);

                $oldStock    = $item->stock ?? 0;
                $currentCost = $item->cost ?? 0;
                $newCost     = ($oldStock > 0)
                    ? (($oldStock * $currentCost) + ($qty * $cost)) / ($oldStock + $qty)
                    : $cost;
                $newStock    = $oldStock + $qty;

                $item->update([
                    'stock'     => $newStock,
                    'last_cost' => $cost,
                    'cost'      => round($newCost, 4),
                ]);

                if ($warehouseId) {
                    $wStock = InvWarehouseStock::firstOrCreate(
                        ['warehouse_id' => $warehouseId, 'item_id' => $item->id],
                        ['stock' => 0]
                    );
                    $wStock->increment('stock', $qty);
                }

                KardexService::entry(
                    $seller->id,
                    $item->id,
                    $qty,
                    $cost,
                    $newStock,
                    'Compra #' . $purchase->id . ($request->document_number ? ' (' . $request->document_number . ')' : ''),
                    'purchase',
                    $purchase->id,
                    $warehouseId
                );
            }

            if ($cashSession) {
                $cashSession->increment('total_expenses', $total);
            }

            return $purchase->load(['supplier', 'items.item']);
        });

        return apiResponse('purchase_created', 'success', ['Compra registrada exitosamente. Stock actualizado.'], [
            'purchase' => $purchase,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. MERMAS / WASTES
    // ─────────────────────────────────────────────────────────────────────────

    public function wastes(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $wastes = InvWaste::where('seller_id', $seller->id)
            ->with('item:id,name,unit,cost,stock')
            ->latest()
            ->paginate(getPaginate());

        $items = InvItem::where('seller_id', $seller->id)->insumos()->orderBy('name')->get(['id', 'name', 'unit', 'stock', 'cost']);

        return apiResponse('wastes', 'success', ['Mermas obtenidas'], [
            'wastes'   => $wastes,
            'items'    => $items,
            'metadata' => $this->getInventoryMetadata(),
        ]);
    }

    public function wasteStore(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $validator = Validator::make($request->all(), [
            'item_id'    => 'required|exists:inv_items,id',
            'quantity'   => 'required|numeric|min:0.01',
            'reason'     => 'required|string|max:120',
            'waste_date' => 'required|date',
            'notes'      => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $item = InvItem::where('seller_id', $seller->id)->find($request->item_id);
        if (!$item) {
            return apiResponse('not_found', 'error', ['Insumo no encontrado']);
        }

        $qty         = floatval($request->quantity);
        $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
        $newStock    = max(0, $item->stock - $qty);
        $warehouseId = $this->defaultWarehouseId($seller->id);

        $waste = DB::transaction(function () use ($seller, $item, $qty, $newStock, $request, $cashSession, $warehouseId) {
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

            if ($warehouseId) {
                $wStock = InvWarehouseStock::firstOrCreate(
                    ['warehouse_id' => $warehouseId, 'item_id' => $item->id],
                    ['stock' => 0]
                );
                $wStock->update(['stock' => max(0, $wStock->stock - $qty)]);
            }

            KardexService::exit(
                $seller->id,
                $item->id,
                $qty,
                $item->cost ?? 0,
                $newStock,
                'Merma: ' . $request->reason,
                'merma',
                $waste->id,
                $warehouseId
            );

            return $waste->load('item');
        });

        return apiResponse('waste_created', 'success', [
            "Merma registrada. Stock de '{$item->name}' actualizado a {$newStock} {$item->unit}."
        ], ['waste' => $waste]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. KARDEX MOVEMENTS
    // ─────────────────────────────────────────────────────────────────────────

    public function kardex(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $query = InvKardex::where('seller_id', $seller->id)->with('item:id,name,unit,cost');

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->filled('type') && in_array($request->type, ['entrada', 'salida'])) {
            $query->where('type', $request->type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $movements = $query->latest()->paginate(getPaginate());
        $items     = InvItem::where('seller_id', $seller->id)->insumos()->orderBy('name')->get(['id', 'name', 'unit']);

        return apiResponse('kardex', 'success', ['Movimientos de kardex obtenidos'], [
            'movements' => $movements,
            'items'     => $items,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. PROVEEDORES / SUPPLIERS
    // ─────────────────────────────────────────────────────────────────────────

    public function suppliers(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $query = InvSupplier::where('seller_id', $seller->id);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('document_number', 'like', "%{$s}%")
                  ->orWhere('contact_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $suppliers = $query->orderBy('name')->get();

        return apiResponse('suppliers', 'success', ['Proveedores obtenidos'], [
            'suppliers' => $suppliers,
        ]);
    }

    public function supplierStore(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:120',
            'document_type'   => 'nullable|string|max:10',
            'document_number' => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:100',
            'contact_name'    => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $supplier = InvSupplier::updateOrCreate(
            [
                'id'        => $request->id ?? 0,
                'seller_id' => $seller->id,
            ],
            [
                'name'            => $request->name,
                'document_type'   => $request->document_type ?? '6', // RUC default
                'document_number' => $request->document_number,
                'phone'           => $request->phone,
                'email'           => $request->email,
                'contact_name'    => $request->contact_name,
                'address'         => $request->address,
                'notes'           => $request->notes,
                'status'          => 'active',
            ]
        );

        return apiResponse('supplier_saved', 'success', ['Proveedor guardado exitosamente'], [
            'supplier' => $supplier,
        ]);
    }

    public function supplierDelete($id)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $supplier = InvSupplier::where('seller_id', $seller->id)->find($id);
        if (!$supplier) {
            return apiResponse('not_found', 'error', ['Proveedor no encontrado']);
        }

        $hasPurchases = InvPurchase::where('supplier_id', $supplier->id)->exists();
        if ($hasPurchases) {
            return apiResponse('error', 'error', ['No se puede eliminar el proveedor porque tiene compras asociadas.']);
        }

        $supplier->delete();

        return apiResponse('supplier_deleted', 'success', ['Proveedor eliminado exitosamente']);
    }
}
