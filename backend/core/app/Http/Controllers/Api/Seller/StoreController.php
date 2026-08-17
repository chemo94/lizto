<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\GeneralCategory;
use App\Models\Store;
use App\Models\SubCategory;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    public function index()
    {
        $seller = auth()->user();
        $stores = $seller->stores()->with('generalCategories', 'subCategories')->withCount('products')->get();

        return apiResponse('stores', 'success', ['Tiendas del vendedor'], [
            'stores'           => $stores->map(fn($s) => $this->formatStore($s)),
            'store_image_path' => 'assets/images/store',
        ]);
    }

    public function subCategories()
    {
        $generalCategories = GeneralCategory::with('allSubCategories')->where('status', 1)->orderBy('sort_order')->get();
        $subCategories = SubCategory::with('generalCategory')->where('status', 1)->get();

        return apiResponse('subcategories', 'success', ['Categorías'], [
            'general_categories' => $generalCategories->map(fn($gc) => [
                'id'   => $gc->id,
                'name' => $gc->name,
                'sub_categories' => $gc->allSubCategories->map(fn($sc) => [
                    'id'   => $sc->id,
                    'name' => $sc->name,
                ])->values(),
            ]),
            'subcategories' => $subCategories->map(fn($sc) => [
                'id'               => $sc->id,
                'name'             => $sc->name,
                'general_category_id' => $sc->general_category_id,
                'general_category_name' => $sc->generalCategory?->name,
            ]),
        ]);
    }

    public function storeStore(Request $request)
    {
        $seller = auth()->user();

        $validator = Validator::make($request->all(), [
            'general_category_ids' => 'nullable|array',
            'general_category_ids.*' => 'exists:general_categories,id',
            'sub_category_ids'     => 'nullable|array',
            'sub_category_ids.*'   => 'exists:sub_categories,id',
            'name'                 => 'required|max:255',
            'description'          => 'nullable|max:1000',
            'address'              => 'nullable|max:255',
            'delivery_fee'         => 'nullable|numeric|min:0',
            'min_order_amount'     => 'nullable|numeric|min:0',
            'opening_time'         => 'nullable',
            'closing_time'         => 'nullable',
            'preparation_time'     => 'nullable|integer|min:0',
            'latitude'             => 'nullable|numeric',
            'longitude'            => 'nullable|numeric',
            'image'                => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'cover_image'          => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $store = new Store();
        $store->seller_id        = $seller->id;
        $store->name             = $request->name;
        $store->description      = $request->description;
        $store->address          = $request->address;
        $store->delivery_fee     = 0;
        $store->min_order_amount = $request->min_order_amount ?? 0;
        $store->opening_time     = $request->opening_time;
        $store->closing_time     = $request->closing_time;
        $store->preparation_time = $request->preparation_time ?? 15;
        $store->latitude         = $request->latitude;
        $store->longitude        = $request->longitude;
        $store->is_open          = 1;
        $store->status           = 1;

        if ($request->hasFile('image')) {
            $store->image = fileUploader($request->image, 'assets/images/store', getFileSize('store'));
        }
        if ($request->hasFile('cover_image')) {
            $store->cover_image = fileUploader($request->cover_image, 'assets/images/store_cover', getFileSize('store_cover'));
        }

        $store->save();

        if ($request->has('general_category_ids')) {
            $store->generalCategories()->sync($request->general_category_ids);
        }
        if ($request->has('sub_category_ids')) {
            $store->subCategories()->sync($request->sub_category_ids);
        }

        return apiResponse('store_created', 'success', ['Tienda creada correctamente'], [
            'store' => $this->formatStore($store->load('generalCategories', 'subCategories')),
        ]);
    }

    public function updateStore(Request $request, $id)
    {
        $seller = auth()->user();
        $store = $seller->stores()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'general_category_ids' => 'nullable|array',
            'general_category_ids.*' => 'exists:general_categories,id',
            'sub_category_ids'     => 'nullable|array',
            'sub_category_ids.*'   => 'exists:sub_categories,id',
            'name'                 => 'required|max:255',
            'description'          => 'nullable|max:1000',
            'address'              => 'nullable|max:255',
            'delivery_fee'         => 'nullable|numeric|min:0',
            'min_order_amount'     => 'nullable|numeric|min:0',
            'opening_time'         => 'nullable',
            'closing_time'         => 'nullable',
            'preparation_time'     => 'nullable|integer|min:0',
            'latitude'             => 'nullable|numeric',
            'longitude'            => 'nullable|numeric',
            'is_open'              => 'nullable|in:0,1',
            'image'                => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'cover_image'          => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $store->name             = $request->name;
        $store->description      = $request->description;
        $store->address          = $request->address;
        $store->delivery_fee     = 0;
        $store->min_order_amount = $request->min_order_amount ?? $store->min_order_amount;
        $store->opening_time     = $request->opening_time ?? $store->opening_time;
        $store->closing_time     = $request->closing_time ?? $store->closing_time;
        $store->preparation_time = $request->preparation_time ?? $store->preparation_time;
        $store->latitude         = $request->latitude ?? $store->latitude;
        $store->longitude        = $request->longitude ?? $store->longitude;
        if ($request->has('is_open')) { $store->is_open = $request->is_open; }

        if ($request->hasFile('image')) {
            $store->image = fileUploader($request->image, 'assets/images/store', getFileSize('store'), $store->image);
        }
        if ($request->hasFile('cover_image')) {
            $store->cover_image = fileUploader($request->cover_image, 'assets/images/store_cover', getFileSize('store_cover'), $store->cover_image);
        }

        $store->save();

        if ($request->has('general_category_ids')) {
            $store->generalCategories()->sync($request->general_category_ids);
        }
        if ($request->has('sub_category_ids')) {
            $store->subCategories()->sync($request->sub_category_ids);
        }

        return apiResponse('store_updated', 'success', ['Tienda actualizada correctamente'], [
            'store' => $this->formatStore($store->load('generalCategories', 'subCategories')),
        ]);
    }

    public function deleteStore($id)
    {
        $seller = auth()->user();
        $store = $seller->stores()->findOrFail($id);
        $store->delete();

        return apiResponse('store_deleted', 'success', ['Tienda eliminada correctamente']);
    }

    private function formatStore($store)
    {
        return [
            'id'                   => $store->id,
            'name'                 => $store->name,
            'description'          => $store->description,
            'address'              => $store->address,
            'delivery_fee'         => $store->delivery_fee,
            'min_order_amount'     => $store->min_order_amount,
            'opening_time'         => $store->opening_time,
            'closing_time'         => $store->closing_time,
            'preparation_time'     => $store->preparation_time,
            'latitude'             => $store->latitude,
            'longitude'            => $store->longitude,
            'is_open'              => $store->is_open,
            'is_open_now'          => $store->is_open_now,
            'status'               => $store->status,
            'image'                => $store->image,
            'cover_image'          => $store->cover_image,
            'products_count'       => $store->products_count ?? $store->products()->count(),
            'general_categories'   => $store->generalCategories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]),
            'sub_categories'       => $store->subCategories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug]),
            'created_at'           => $store->created_at,
        ];
    }
}
