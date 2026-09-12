<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralCategory;
use App\Models\SubCategory;
use App\Models\Store;
use App\Models\Product;
use App\Support\DeliveryPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
    public function generalCategories(Request $request)
    {
        $notify = ['General categories and premium sections'];
        $categories = GeneralCategory::active()->orderBy('sort_order')->get();
        
        [$lat, $lng] = DeliveryPricing::coordinateFromRequest($request, ['lat', 'latitude'], ['lng', 'longitude', 'long']);
        $radius = (float) ($request->radius ?? gs('delivery_coverage_radius') ?? 15);

        $storesQuery = Store::active()->open()->with('subCategories')->withCount('products');
        if (DeliveryPricing::hasCoordinates($lat, $lng)) {
            $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
            $storesQuery->selectRaw("stores.*, $haversine AS distance", [$lat, $lng, $lat])
                  ->having('distance', '<=', $radius)
                  ->orderBy('distance');
        } else {
            $storesQuery->orderBy('id', 'desc');
        }
        $stores = $storesQuery->get();

        $stores->transform(function ($store) use ($lat, $lng) {
            $estimate = DeliveryPricing::estimateForStore($store, $lat, $lng);
            $store->delivery_fee = $estimate['delivery_fee'];
            $store->delivery_fee_estimate = $estimate;
            if (isset($estimate['distance_km'])) {
                $store->distance = $estimate['distance_km'];
            }
            $store->distance_formatted = isset($store->distance) ? number_format($store->distance, 1) . ' km' : 'Cerca';
            return $store;
        });

        $settings = gs();
        $sectionsConfig = $settings->delivery_sections_config;
        if (!$sectionsConfig || !is_array($sectionsConfig)) {
            $sectionsConfig = [
                ['key' => 'populares_cerca_ti', 'title' => 'Populares cerca de ti', 'subtitle' => 'Restaurantes favoritos en tu zona', 'is_enabled' => 1, 'sort_order' => 1],
                ['key' => 'los_mas_vendidos', 'title' => 'Los más vendidos', 'subtitle' => 'Lo que la gente está pidiendo más', 'is_enabled' => 1, 'sort_order' => 2],
                ['key' => 'marcas_descuento', 'title' => 'Marcas con descuento', 'subtitle' => 'Ahorra con súper marcas hoy', 'is_enabled' => 1, 'sort_order' => 3],
                ['key' => 'recomendados_ti', 'title' => 'Recomendados para ti', 'subtitle' => 'Nuestra selección especial', 'is_enabled' => 1, 'sort_order' => 4],
                ['key' => 'cuidamos_bolsillo', 'title' => 'Cuidamos tu bolsillo', 'subtitle' => 'Envíos gratis y opciones económicas', 'is_enabled' => 1, 'sort_order' => 5],
                ['key' => 'promos_cerca_ti', 'title' => 'Promos cerca de ti', 'subtitle' => 'Grandes ofertas a un clic', 'is_enabled' => 1, 'sort_order' => 6],
            ];
        }

        usort($sectionsConfig, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        $formattedSections = [];
        $storeIds = $stores->pluck('id')->toArray();

        foreach ($sectionsConfig as $sec) {
            if (empty($sec['is_enabled'])) continue;

            $data = [];
            $type = 'store';

            switch ($sec['key']) {
                case 'populares_cerca_ti':
                    $data = $stores->take(8);
                    $type = 'store';
                    break;
                case 'los_mas_vendidos':
                    $data = Product::active()
                        ->whereIn('store_id', $storeIds)
                        ->orderBy('sort_order')
                        ->limit(8)
                        ->get();
                    $type = 'product';
                    break;
                case 'marcas_descuento':
                    $discountStoreIds = Product::active()
                        ->whereNotNull('discount_price')
                        ->whereIn('store_id', $storeIds)
                        ->pluck('store_id')
                        ->unique()
                        ->toArray();
                    $data = $stores->whereIn('id', $discountStoreIds)->take(8);
                    $type = 'store';
                    break;
                case 'recomendados_ti':
                    $data = $stores->shuffle()->take(8);
                    $type = 'store';
                    break;
                case 'cuidamos_bolsillo':
                    $cheapProducts = Product::active()
                        ->whereIn('store_id', $storeIds)
                        ->where('price', '<', 15)
                        ->limit(8)
                        ->get();
                    $data = $cheapProducts;
                    $type = 'product';
                    break;
                case 'promos_cerca_ti':
                    $promoProducts = Product::active()
                        ->whereIn('store_id', $storeIds)
                        ->whereNotNull('discount_price')
                        ->limit(8)
                        ->get();
                    $data = $promoProducts;
                    $type = 'product';
                    break;
            }

            $formattedData = [];
            if ($type === 'store') {
                foreach ($data as $store) {
                    $formattedData[] = [
                        'id' => $store->id,
                        'name' => $store->name,
                        'image' => $store->image,
                        'cover_image' => $store->cover_image,
                        'cover_video' => $store->cover_video,
                        'cover_video_url' => $store->cover_video_url,
                        'description' => $store->description,
                        'address' => $store->address,
                        'latitude' => $store->latitude,
                        'longitude' => $store->longitude,
                        'delivery_fee' => (float)$store->delivery_fee,
                        'preparation_time' => $store->preparation_time ?? 20,
                        'distance_formatted' => $store->distance_formatted ?? 'Cerca',
                        'is_open' => $store->is_open,
                    ];
                }
            } else {
                foreach ($data as $prod) {
                    $formattedData[] = [
                        'id' => $prod->id,
                        'store_id' => $prod->store_id,
                        'name' => $prod->name,
                        'image' => $prod->image,
                        'description' => $prod->description,
                        'price' => (float)$prod->price,
                        'discount_price' => $prod->discount_price ? (float)$prod->discount_price : null,
                        'store_name' => $prod->store?->name ?? '',
                    ];
                }
            }

            $formattedSections[] = [
                'key' => $sec['key'],
                'title' => $sec['title'],
                'subtitle' => $sec['subtitle'] ?? '',
                'type' => $type,
                'data' => $formattedData,
            ];
        }

        return apiResponse('general_categories', 'success', $notify, [
            'general_categories'          => $categories,
            'general_category_image_path' => getFilePath('general_category'),
            'sub_category_image_path'     => getFilePath('sub_category'),
            'sections'                    => $formattedSections,
            'store_image_path'            => getFilePath('store'),
            'product_image_path'          => getFilePath('product'),
        ]);
    }

    public function subCategories($generalCategoryId)
    {
        $notify = ['Sub categories'];
        $category = GeneralCategory::active()->findOrFail($generalCategoryId);
        $subCategories = $category->subCategories()->active()->orderBy('sort_order')->get();

        return apiResponse('sub_categories', 'success', $notify, [
            'general_category'    => $category,
            'sub_categories'      => $subCategories,
            'sub_category_image_path' => getFilePath('sub_category'),
        ]);
    }

    /**
     * Home data for one marketplace category. Filters are applied at query time
     * so the mobile app never has to infer premium, rating, or delivery data.
     */
    public function categoryHome(Request $request, $categoryId)
    {
        $category = GeneralCategory::active()->findOrFail($categoryId);
        $stores = $this->categoryStoresQuery($request, $categoryId)->get();
        [$lat, $lng] = DeliveryPricing::coordinateFromRequest($request, ['lat', 'latitude'], ['lng', 'longitude', 'long']);

        $stores->each(function ($store) use ($lat, $lng) {
            $estimate = DeliveryPricing::estimateForStore($store, $lat, $lng);
            $store->delivery_fee = $estimate['delivery_fee'];
            if (isset($estimate['distance_km'])) $store->distance = $estimate['distance_km'];
        });

        $storeIds = $stores->pluck('id');
        $products = Product::active()
            ->whereIn('store_id', $storeIds)
            ->with('store')
            ->orderByDesc('is_promoted')
            ->orderBy('sort_order')
            ->limit(12)
            ->get();
        $discountedProducts = Product::active()
            ->whereIn('store_id', $storeIds)
            ->whereNotNull('discount_price')
            ->whereColumn('discount_price', '<', 'price')
            ->with('store')
            ->orderByDesc('is_promoted')
            ->limit(12)
            ->get();
        $featuredStores = $stores->filter(fn($store) => $store->is_premium || $store->is_featured)->values();
        $fastStores = $stores->filter(fn($store) => $store->preparation_time && $store->preparation_time <= 35)->values();
        // This limit is managed centrally by the administrator. `delivery_fee`
        // above is always the route estimate from DeliveryPricing, never a fee
        // saved by an individual store.
        $convenientFeeLimit = (float) (gs('delivery_convenient_fee_limit') ?? 0);
        $convenientStores = $convenientFeeLimit > 0
            ? $stores->filter(fn($store) => (float) $store->delivery_fee <= $convenientFeeLimit)->values()
            : collect();
        $subCategories = $category->subCategories()->active()->orderBy('sort_order')->get();

        $title = str_contains(strtolower($category->name ?? ''), 'farmacia') ? 'Farmacias disponibles' : 'Restaurantes para ti';
        $sections = [];
        if ($featuredStores->isNotEmpty()) $sections[] = ['key' => 'featured_stores', 'title' => 'Destacados para ti', 'subtitle' => 'Comercios premium de esta categoría', 'type' => 'store', 'data' => $featuredStores->take(12)->values()];
        if ($fastStores->isNotEmpty()) $sections[] = ['key' => 'fast_delivery', 'title' => 'Entrega ágil', 'subtitle' => 'Opciones listas en poco tiempo', 'type' => 'store', 'data' => $fastStores->take(12)->values()];
        if ($discountedProducts->isNotEmpty()) $sections[] = ['key' => 'product_offers', 'title' => 'Ofertas en productos', 'subtitle' => 'Precios especiales disponibles hoy', 'type' => 'product', 'data' => $discountedProducts->values()];
        if ($products->isNotEmpty()) $sections[] = ['key' => 'category_products', 'title' => 'Productos destacados', 'subtitle' => 'Selección real del catálogo', 'type' => 'product', 'data' => $products->values()];
        if ($convenientStores->isNotEmpty()) $sections[] = [
            'key' => 'convenient_delivery',
            'title' => 'Delivery conveniente',
            'subtitle' => 'Tarifa automática hasta ' . gs('cur_sym') . ' ' . number_format($convenientFeeLimit, 2),
            'max_delivery_fee' => $convenientFeeLimit,
            'type' => 'store',
            'data' => $convenientStores->take(12)->values(),
        ];
        if ($stores->isNotEmpty()) $sections[] = ['key' => 'category_stores', 'title' => $title, 'subtitle' => 'Opciones disponibles cerca de ti', 'type' => 'store', 'data' => $stores->take(12)->values()];

        return apiResponse('category_home', 'success', ['Información de categoría'], [
            'general_category' => $category,
            'sub_categories' => $subCategories,
            'stores' => $stores,
            'sections' => $sections,
            'sub_category_image_path' => getFilePath('sub_category'),
            'store_image_path' => getFilePath('store'),
            'store_cover_path' => getFilePath('store_cover'),
            'product_image_path' => getFilePath('product'),
        ]);
    }

    /** Search and filter stores belonging to a general category. */
    public function categoryStores(Request $request, $categoryId)
    {
        GeneralCategory::active()->findOrFail($categoryId);
        $stores = $this->categoryStoresQuery($request, $categoryId)->paginate((int) ($request->per_page ?? 20));
        return apiResponse('category_stores', 'success', ['Tiendas filtradas'], [
            'stores' => $stores,
            'store_image_path' => getFilePath('store'),
            'store_cover_path' => getFilePath('store_cover'),
        ]);
    }

    private function categoryStoresQuery(Request $request, int $categoryId)
    {
        [$lat, $lng] = DeliveryPricing::coordinateFromRequest($request, ['lat', 'latitude'], ['lng', 'longitude', 'long']);
        $radius = (float) ($request->radius ?? gs('delivery_coverage_radius') ?? 15);
        $query = Store::active()->open()
            ->whereHas('generalCategories', fn($q) => $q->where('general_categories.id', $categoryId))
            ->with(['subCategories'])
            ->withAvg('deliveryReviews', 'rating');

        if ($request->boolean('top')) {
            $query->whereHas('activePackagesRelation');
        }
        if ($request->boolean('fast')) {
            $query->whereNotNull('preparation_time')->where('preparation_time', '<=', 35);
        }
        if ($request->boolean('high_rating')) {
            $query->having('delivery_reviews_avg_rating', '>=', 4.5);
        }
        if ($search = trim((string) $request->q)) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
        }
        if (DeliveryPricing::hasCoordinates($lat, $lng)) {
            $haversine = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';
            $query->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])->having('distance', '<=', $radius);
        }

        if ($request->sort === 'rating') {
            $query->orderByDesc('delivery_reviews_avg_rating');
        } elseif ($request->sort === 'fast') {
            $query->orderBy('preparation_time');
        } elseif (DeliveryPricing::hasCoordinates($lat, $lng)) {
            $query->orderBy('distance');
        } else {
            $query->latest('stores.id');
        }
        return $query;
    }

    public function stores(Request $request, $subCategoriesId)
    {
        $notify = ['Stores'];
        $subCategories = SubCategory::active()->findOrFail($subCategoriesId);
        $stores = $subCategories->stores()->open()->orderBy('name')->paginate(getPaginate());
        [$lat, $lng] = DeliveryPricing::coordinateFromRequest($request, ['lat', 'latitude'], ['lng', 'longitude', 'long']);
        $stores->getCollection()->transform(function ($store) use ($lat, $lng) {
            $estimate = DeliveryPricing::estimateForStore($store, $lat, $lng);
            $store->delivery_fee = $estimate['delivery_fee'];
            $store->delivery_fee_estimate = $estimate;
            if (isset($estimate['distance_km'])) {
                $store->distance = $estimate['distance_km'];
            }
            return $store;
        });

        return apiResponse('stores', 'success', $notify, [
            'sub_category'      => $subCategories,
            'stores'            => $stores,
            'store_image_path'  => getFilePath('store'),
            'store_cover_path'  => getFilePath('store_cover'),
        ]);
    }

    public function nearbyStores(Request $request)
    {
        $notify = ['Nearby stores'];
        [$lat, $lng] = DeliveryPricing::coordinateFromRequest($request, ['lat', 'latitude'], ['lng', 'longitude', 'long']);
        $radius = (float) ($request->radius ?? gs('delivery_coverage_radius') ?? 10);
        $search = trim((string) $request->q);

        $query = Store::active()->open()->withActivePackages()->with('subCategories')->withCount('products');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%")
                    ->orWhereHas('subCategories', fn($sub) => $sub->where('name', 'like', "%$search%"))
                    ->orWhereHas('categories.products', fn($product) => $product->where('name', 'like', "%$search%"));
            });
        }

        if (DeliveryPricing::hasCoordinates($lat, $lng)) {
            $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
            $query->select('stores.*')
                  ->selectRaw("$haversine AS distance", [$lat, $lng, $lat])
                  ->having('distance', '<=', $radius)
                  ->orderBy('distance');
        }

        $stores = $query->paginate($request->per_page ?? 20);
        $stores->getCollection()->transform(function ($store) use ($lat, $lng) {
            $estimate = DeliveryPricing::estimateForStore($store, $lat, $lng);
            $store->delivery_fee = $estimate['delivery_fee'];
            $store->delivery_fee_estimate = $estimate;
            if (isset($estimate['distance_km'])) {
                $store->distance = $estimate['distance_km'];
            }
            return $store;
        });

        return apiResponse('nearby_stores', 'success', $notify, [
            'stores'            => $stores,
            'store_image_path'  => getFilePath('store'),
            'store_cover_path'  => getFilePath('store_cover'),
            'has_free_delivery' => $this->userHasFreeDelivery(),
        ]);
    }

    public function feeEstimate(Request $request)
    {
        [$deliveryLat, $deliveryLng] = DeliveryPricing::deliveryCoordinatesFromRequest($request);
        $request->merge([
            'delivery_lat' => $deliveryLat,
            'delivery_lng' => $deliveryLng,
        ]);

        $validator = Validator::make($request->all(), [
            'store_id'     => 'required|exists:stores,id',
            'delivery_lat' => 'required|numeric',
            'delivery_lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $store = Store::active()->findOrFail($request->store_id);
        $estimate = DeliveryPricing::estimateForStore(
            $store,
            $deliveryLat,
            $deliveryLng
        );

        return apiResponse('delivery_fee_estimate', 'success', ['Tarifa de delivery calculada'], [
            'estimate' => $estimate,
        ]);
    }

    public function storeDetail(Request $request, $storeId)
    {
        $notify = ['Store detail'];
        $store = Store::with(['categories.products.variations', 'categories.products.addons'])->active()->findOrFail($storeId);

        [$lat, $lng] = DeliveryPricing::coordinateFromRequest($request, ['lat', 'latitude'], ['lng', 'longitude', 'long']);
        $estimate = DeliveryPricing::estimateForStore($store, $lat, $lng);
        if (isset($estimate['distance_km'])) {
            $store->distance = $estimate['distance_km'];
            $store->distance_formatted = number_format($store->distance, 1) . ' km';
        } elseif (DeliveryPricing::hasCoordinates($lat, $lng) && $store->latitude && $store->longitude) {
            $store->distance = $this->haversine($lat, $lng, $store->latitude, $store->longitude);
            $store->distance_formatted = number_format($store->distance, 1) . ' km';
        }
        $store->delivery_fee = $estimate['delivery_fee'];
        $store->delivery_fee_estimate = $estimate;

        return apiResponse('store_detail', 'success', $notify, [
            'store'                => $store,
            'has_free_delivery'    => $this->userHasFreeDelivery(),
            'store_image_path'     => getFilePath('store'),
            'store_cover_path'     => getFilePath('store_cover'),
            'store_category_image' => getFilePath('store_category'),
            'product_image_path'   => getFilePath('product'),
        ]);
    }

    public function toggleFavorite(Request $request, $storeId)
    {
        $user = auth()->user();
        $exists = \DB::table('user_favorite_stores')
            ->where('user_id', $user->id)
            ->where('store_id', $storeId)
            ->exists();

        if ($exists) {
            \DB::table('user_favorite_stores')
                ->where('user_id', $user->id)
                ->where('store_id', $storeId)
                ->delete();
            return apiResponse('favorite_removed', 'success', ['Tienda quitada de favoritos'], ['is_favorite' => false]);
        }

        \DB::table('user_favorite_stores')->insert([
            'user_id' => $user->id,
            'store_id' => $storeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return apiResponse('favorite_added', 'success', ['Tienda agregada a favoritos'], ['is_favorite' => true]);
    }

    public function favoriteStores()
    {
        $user = auth()->user();
        $favorites = \DB::table('user_favorite_stores')
            ->where('user_id', $user->id)
            ->pluck('store_id');

        $stores = Store::whereIn('id', $favorites)->get();

        return apiResponse('favorite_stores', 'success', ['Tiendas favoritas'], [
            'stores' => $stores,
            'store_image_path' => getFilePath('store'),
            'store_cover_path' => getFilePath('store_cover'),
        ]);
    }

    private function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function userHasFreeDelivery(): array
    {
        $user = auth()->user();
        if (!$user) return ['active' => false, 'remaining' => 0];

        $free = \App\Models\FreeDelivery::where('user_id', $user->id)
            ->where('status', 1)->where('remaining', '>', 0)->first();

        return $free
            ? ['active' => true, 'remaining' => (int) $free->remaining]
            : ['active' => false, 'remaining' => 0];
    }
}
