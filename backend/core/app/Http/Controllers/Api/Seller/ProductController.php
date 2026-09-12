<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function products($storeId)
    {
        $seller = auth()->user();
        $store  = $seller->stores()->findOrFail($storeId);

        $products = $store->products()->with('category', 'variations', 'addons', 'invProductItems.item')->orderBy('sort_order')->paginate(getPaginate());

        return apiResponse('products', 'success', ['Productos'], [
            'products'           => $products,
            'store'              => $store,
            'store_categories'   => $store->categories,
            'product_image_path' => getFilePath('product'),
            'store_category_image' => getFilePath('store_category'),
        ]);
    }

    public function storeProduct(Request $request, $storeId)
    {
        $seller = auth()->user();
        $store  = $seller->stores()->findOrFail($storeId);

        $validator = Validator::make($request->all(), [
            'name'              => 'required|max:255',
            'store_category_id' => 'required|exists:store_categories,id',
            'price'             => 'required|numeric|min:0',
            'discount_price'    => 'nullable|numeric|lt:price',
            'description'       => 'nullable|max:1000',
            'image'             => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'status'            => 'nullable|in:0,1',
            'stock_type'        => 'nullable|in:packaged,prepared,none',
            'tax_type'          => 'nullable|in:gravado,exonerado,inafecto',
            'sunat_code'        => 'nullable|string|max:8',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $product = new Product();
        $product->store_id          = $store->id;
        $product->store_category_id = $request->store_category_id;
        $product->name              = $request->name;
        $product->description       = $request->description;
        $product->price             = $request->price;
        $product->discount_price    = $request->discount_price;
        $product->sort_order        = $request->sort_order ?? 0;
        $product->status            = isset($request->status) ? (int)$request->status : 1;
        $product->stock_type        = $request->stock_type ?? 'packaged';
        $product->tax_type          = $request->tax_type ?? 'exonerado';
        $product->sunat_code        = $request->sunat_code ?: null;

        if ($request->hasFile('image')) {
            try {
                $product->image = fileUploader($request->image, getFilePath('product'), getFileSize('product'));
            } catch (\Exception $e) {
                return apiResponse('exception', 'error', ['Error al subir la imagen']);
            }
        }

        $product->save();

        // Create variations
        if ($request->variations && is_array($request->variations)) {
            foreach ($request->variations as $v) {
                $product->variations()->create([
                    'name'       => $v['name'] ?? '',
                    'price'      => $v['price'] ?? 0,
                    'sort_order' => $v['sort_order'] ?? 0,
                ]);
            }
        }

        // Create addons
        if ($request->addons && is_array($request->addons)) {
            foreach ($request->addons as $a) {
                $product->addons()->create([
                    'name'       => $a['name'] ?? '',
                    'price'      => $a['price'] ?? 0,
                    'sort_order' => $a['sort_order'] ?? 0,
                ]);
            }
        }

        // Auto-create InvItem + link for packaged products
        if ($product->stock_type === 'packaged') {
            $invItem = \App\Models\InvItem::create([
                'seller_id'  => $seller->id,
                'name'       => $product->name,
                'item_type'  => 'producto',
                'unit'       => $request->input('unit', 'NIU'),
                'tax_type'   => $request->input('tax_type', 'exonerado'),
                'cost'       => $request->input('cost', 0),
                'sale_price' => $product->price,
                'stock'      => $request->input('initial_stock', 0),
                'min_stock'  => $request->input('min_stock', 5),
                'sunat_code' => $request->input('sunat_code'),
            ]);

            \App\Models\InvProductItem::create([
                'product_id' => $product->id,
                'item_id'    => $invItem->id,
                'quantity'   => 1,
            ]);

            if ($request->input('initial_stock', 0) > 0) {
                $warehouseId = \App\Models\InvWarehouse::where('seller_id', $seller->id)->where('is_default', true)->value('id')
                    ?? \App\Models\InvWarehouse::where('seller_id', $seller->id)->value('id');

                \App\Services\KardexService::entry(
                    $seller->id, $invItem->id, $request->input('initial_stock'), $invItem->cost ?? 0, $request->input('initial_stock'),
                    'Stock inicial auto-creado', null, null, $warehouseId
                );

                if ($warehouseId) {
                    $wStock = \App\Models\InvWarehouseStock::firstOrCreate(
                        ['warehouse_id' => $warehouseId, 'item_id' => $invItem->id],
                        ['stock' => 0]
                    );
                    $wStock->increment('stock', $request->input('initial_stock'));
                }
            }
        }

        $product->load('variations', 'addons', 'invProductItems.item');

        return apiResponse('product_created', 'success', ['Producto creado correctamente'], [
            'product' => $product,
        ]);
    }

    public function updateProduct(Request $request, $productId)
    {
        $seller = auth()->user();
        $product = Product::whereHas('store', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->findOrFail($productId);

        $validator = Validator::make($request->all(), [
            'name'              => 'required|max:255',
            'store_category_id' => 'required|exists:store_categories,id',
            'price'             => 'required|numeric|min:0',
            'discount_price'    => 'nullable|numeric|lt:price',
            'description'       => 'nullable|max:1000',
            'image'             => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'status'            => 'nullable|in:0,1',
            'stock_type'        => 'nullable|in:packaged,prepared,none',
            'tax_type'          => 'nullable|in:gravado,exonerado,inafecto',
            'sunat_code'        => 'nullable|string|max:8',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $product->store_category_id = $request->store_category_id;
        $product->name              = $request->name;
        $product->description       = $request->description;
        $product->price             = $request->price;
        $product->discount_price    = $request->discount_price;
        $product->sort_order        = $request->sort_order ?? $product->sort_order;
        if ($request->has('status')) {
            $product->status        = (int)$request->status;
        }
        if ($request->has('stock_type')) {
            $product->stock_type    = $request->stock_type;
        }
        if ($request->has('tax_type')) {
            $product->tax_type      = $request->tax_type;
        }
        if ($request->has('sunat_code')) {
            $product->sunat_code    = $request->sunat_code;
        }

        if ($request->hasFile('image')) {
            try {
                $product->image = fileUploader($request->image, getFilePath('product'), getFileSize('product'), $product->image);
            } catch (\Exception $e) {
                return apiResponse('exception', 'error', ['Error al subir la imagen']);
            }
        }

        $product->save();

        // Sync variations
        if ($request->variations && is_array($request->variations)) {
            $product->variations()->delete();
            foreach ($request->variations as $v) {
                $product->variations()->create([
                    'name'       => $v['name'] ?? '',
                    'price'      => $v['price'] ?? 0,
                    'sort_order' => $v['sort_order'] ?? 0,
                ]);
            }
        }

        // Sync addons
        if ($request->addons && is_array($request->addons)) {
            $product->addons()->delete();
            foreach ($request->addons as $a) {
                $product->addons()->create([
                    'name'       => $a['name'] ?? '',
                    'price'      => $a['price'] ?? 0,
                    'sort_order' => $a['sort_order'] ?? 0,
                ]);
            }
        }

        // Sync linked inventory item if type is packaged
        if ($product->stock_type === 'packaged') {
            $link = \App\Models\InvProductItem::where('product_id', $product->id)->first();
            if (!$link) {
                $invItem = \App\Models\InvItem::create([
                    'seller_id'  => $seller->id,
                    'name'       => $product->name,
                    'item_type'  => 'producto',
                    'unit'       => $request->input('unit', 'NIU'),
                    'tax_type'   => $request->input('tax_type', 'exonerado'),
                    'cost'       => $request->input('cost', 0),
                    'sale_price' => $product->price,
                    'stock'      => $request->input('initial_stock', 0),
                    'min_stock'  => $request->input('min_stock', 5),
                    'sunat_code' => $request->input('sunat_code'),
                ]);

                \App\Models\InvProductItem::create([
                    'product_id' => $product->id,
                    'item_id'    => $invItem->id,
                    'quantity'   => 1,
                ]);
            } else {
                $item = $link->item;
                if ($item) {
                    $item->update([
                        'name'       => $product->name,
                        'unit'       => $request->input('unit', $item->unit ?? 'NIU'),
                        'tax_type'   => $request->input('tax_type', $product->tax_type ?? 'exonerado'),
                        'cost'       => $request->input('cost', $item->cost ?? 0),
                        'sale_price' => $product->price,
                        'min_stock'  => $request->input('min_stock', $item->min_stock ?? 5),
                        'sunat_code' => $request->input('sunat_code', $product->sunat_code),
                    ]);
                }
            }
        }

        $product->load('variations', 'addons', 'invProductItems.item');

        return apiResponse('product_updated', 'success', ['Producto actualizado correctamente'], [
            'product' => $product,
        ]);
    }

    public function deleteProduct($productId)
    {
        $seller = auth()->user();
        $product = Product::whereHas('store', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->findOrFail($productId);

        $product->delete();

        return apiResponse('product_deleted', 'success', ['Producto eliminado correctamente']);
    }

    public function toggleStatus($productId)
    {
        $seller = auth()->user();
        $product = Product::whereHas('store', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->findOrFail($productId);

        $product->status = $product->status == 1 ? 0 : 1;
        $product->save();

        return apiResponse('product_status_toggled', 'success', ['Estado del producto actualizado'], [
            'product' => $product
        ]);
    }

    public function menuCategories($storeId)
    {
        $seller = auth()->user();
        $store  = $seller->stores()->findOrFail($storeId);
        $categories = $store->categories()->orderBy('sort_order')->get();

        return apiResponse('menu_categories', 'success', ['Categorías de menú'], [
            'categories' => $categories,
            'image_path' => getFilePath('store_category'),
        ]);
    }

    public function storeMenuCategory(Request $request, $storeId)
    {
        $seller = auth()->user();
        $store  = $seller->stores()->findOrFail($storeId);

        $validator = Validator::make($request->all(), [
            'name'  => 'required|max:255',
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $category = new StoreCategory();
        $category->store_id = $store->id;
        $category->name = $request->name;
        $category->sort_order = $request->sort_order ?? 0;
        $category->status = 1;

        if ($request->hasFile('image')) {
            try {
                $category->image = fileUploader($request->image, getFilePath('store_category'), getFileSize('store_category'));
            } catch (\Exception $e) {
                return apiResponse('exception', 'error', ['Error al subir la imagen']);
            }
        }

        $category->save();

        return apiResponse('menu_category_created', 'success', ['Categoría de menú creada correctamente'], [
            'category' => $category,
        ]);
    }

    public function updateMenuCategory(Request $request, $id)
    {
        $seller = auth()->user();
        $category = StoreCategory::whereHas('store', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'  => 'required|max:255',
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $category->name = $request->name;
        $category->sort_order = $request->sort_order ?? $category->sort_order;

        if ($request->hasFile('image')) {
            try {
                $category->image = fileUploader($request->image, getFilePath('store_category'), getFileSize('store_category'), $category->image);
            } catch (\Exception $e) {
                return apiResponse('exception', 'error', ['Error al subir la imagen']);
            }
        }

        $category->save();

        return apiResponse('menu_category_updated', 'success', ['Categoría de menú actualizada correctamente'], [
            'category' => $category,
        ]);
    }

    public function deleteMenuCategory($id)
    {
        $seller = auth()->user();
        $category = StoreCategory::whereHas('store', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->findOrFail($id);

        $category->delete();

        return apiResponse('menu_category_deleted', 'success', ['Categoría de menú correcta']);
    }
}
