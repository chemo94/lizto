<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Product;
use App\Models\SellerCompany;
use App\Models\StoreCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExternalApiController extends Controller
{
    private function store(): \App\Models\Store
    {
        return request()->get('external_store');
    }

    // ── Store Info ──

    public function storeInfo()
    {
        $store = $this->store();
        $company = SellerCompany::where('seller_id', $store->seller_id)->where('is_active', true)->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $store->id,
                'name'          => $store->name,
                'slug'          => $store->slug,
                'description'   => $store->description,
                'address'       => $store->address,
                'phone'         => $store->phone,
                'latitude'      => $store->latitude,
                'longitude'     => $store->longitude,
                'is_open'       => (bool) $store->is_open,
                'store_type'    => $store->store_type,
                'created_at'    => $store->created_at,
                'company'       => $company ? [
                    'ruc'           => $company->document_number,
                    'business_name' => $company->business_name,
                    'trade_name'    => $company->trade_name,
                    'address'       => $company->address,
                ] : null,
            ],
        ]);
    }

    // ── Categories ──

    public function categories()
    {
        $categories = StoreCategory::where('store_id', $this->store()->id)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'sort_order', 'status', 'created_at', 'updated_at']);

        return response()->json(['success' => true, 'data' => $categories]);
    }

    // ── Products ──

    public function products(Request $request)
    {
        $query = Product::where('store_id', $this->store()->id)->where('status', 1);

        if ($request->category_id) {
            $query->where('store_category_id', $request->category_id);
        }

        if ($request->updated_since) {
            $query->where('updated_at', '>=', $request->updated_since);
        }

        $products = $query->orderBy('sort_order')->get();

        $data = $products->map(fn($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'description'      => $p->description,
            'price'            => (float) $p->price,
            'discount_price'   => $p->discount_price ? (float) $p->discount_price : null,
            'final_price'      => (float) $p->finalPrice(),
            'image'            => $p->image ? asset('storage/' . $p->image) : null,
            'category_id'      => $p->store_category_id,
            'stock_type'       => $p->stock_type ?? 'none',
            'stock'            => $p->hasStockTracking() ? $p->availableStock() : null,
            'sort_order'       => $p->sort_order,
            'status'           => (bool) $p->status,
            'created_at'       => $p->created_at,
            'updated_at'       => $p->updated_at,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ── Inventory / Stock ──

    public function inventory()
    {
        $store = $this->store();
        $products = Product::where('store_id', $store->id)
            ->whereIn('stock_type', ['packaged', 'prepared'])
            ->get();

        $data = $products->map(fn($p) => [
            'product_id'  => $p->id,
            'product_name'=> $p->name,
            'stock_type'  => $p->stock_type,
            'stock'       => $p->hasStockTracking() ? $p->availableStock() : 0,
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ── Create Order ──

    public function createOrder(Request $request)
    {
        $store = $this->store();

        $validator = Validator::make($request->all(), [
            'external_id'     => 'nullable|string|max:100',
            'order_type'      => 'required|in:dine_in,takeaway,delivery,rappi,pedidosya,llama,daz,lizto_delivery',
            'customer_name'   => 'nullable|string|max:100',
            'customer_phone'  => 'nullable|string|max:20',
            'delivery_address'=> 'nullable|string|max:500',
            'delivery_lat'    => 'nullable|numeric',
            'delivery_lng'    => 'nullable|numeric',
            'notes'           => 'nullable|string|max:500',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name'    => 'required_without:items.*.product_id|string|max:200',
            'items.*.quantity'=> 'required|integer|min:1',
            'items.*.price'   => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $subtotal = 0;
        $orderItems = [];

        foreach ($request->items as $item) {
            $product = isset($item['product_id']) ? Product::find($item['product_id']) : null;
            $name = $product?->name ?? $item['name'];
            $qty = (int) $item['quantity'];
            $price = (float) ($item['price'] ?? $product?->finalPrice() ?? 0);
            $total = $price * $qty;
            $subtotal += $total;

            $orderItems[] = new PosOrderItem([
                'product_id'   => $product?->id,
                'product_name' => $name,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'total_price'  => $total,
                'notes'        => $item['notes'] ?? null,
            ]);
        }

        $orderNo = 'EXT-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $order = PosOrder::create([
            'seller_id'       => $store->seller_id,
            'store_id'        => $store->id,
            'order_no'        => $orderNo,
            'external_id'     => $request->external_id,
            'customer_name'   => $request->customer_name,
            'customer_phone'  => $request->customer_phone,
            'delivery_address'=> $request->delivery_address,
            'delivery_lat'    => $request->delivery_lat,
            'delivery_lng'    => $request->delivery_lng,
            'subtotal'        => $subtotal,
            'total'           => $subtotal,
            'order_type'      => $request->order_type,
            'status'          => 'confirmed',
            'notes'           => $request->notes,
        ]);

        $order->items()->saveMany($orderItems);

        return response()->json([
            'success' => true,
            'message' => 'Pedido creado correctamente',
            'data'    => [
                'id'       => $order->id,
                'order_no' => $order->order_no,
                'total'    => (float) $order->total,
                'status'   => $order->status,
                'items'    => $orderItems->count(),
                'url'      => route('seller.pos.billing'),
            ],
        ], 201);
    }

    // ── List Orders ──

    public function orders(Request $request)
    {
        $source = $request->source ?? 'pos';

        if ($source === 'delivery') {
            return $this->deliveryOrders($request);
        }

        if ($source === 'all') {
            return $this->allOrders($request);
        }

        $query = PosOrder::where('store_id', $this->store()->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->since) {
            $query->where('created_at', '>=', $request->since);
        }

        if ($request->external_id) {
            $query->where('external_id', $request->external_id);
        }

        $orders = $query->withCount('items')->latest()->paginate($request->per_page ?? 50);

        $orders->getCollection()->transform(fn($o) => [
            'id'              => $o->id,
            'source'          => 'pos',
            'order_no'        => $o->order_no,
            'external_id'     => $o->external_id,
            'order_type'      => $o->order_type,
            'customer_name'   => $o->customer_name,
            'subtotal'        => (float) $o->subtotal,
            'delivery_fee'    => (float) ($o->delivery_fee ?? 0),
            'total'           => (float) $o->total,
            'status'          => $o->status,
            'items_count'     => $o->items_count,
            'created_at'      => $o->created_at,
            'updated_at'      => $o->updated_at,
        ]);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    // ── Order Detail ──

    public function orderDetail($id)
    {
        if (request('source') === 'delivery') {
            return $this->deliveryOrderDetail($id);
        }

        $order = PosOrder::where('store_id', $this->store()->id)
            ->with('items')
            ->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Pedido no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'              => $order->id,
                'source'          => 'pos',
                'order_no'        => $order->order_no,
                'external_id'     => $order->external_id,
                'order_type'      => $order->order_type,
                'customer_name'   => $order->customer_name,
                'customer_phone'  => $order->customer_phone,
                'delivery_address'=> $order->delivery_address,
                'subtotal'        => (float) $order->subtotal,
                'delivery_fee'    => (float) ($order->delivery_fee ?? 0),
                'total'           => (float) $order->total,
                'status'          => $order->status,
                'payment_status'  => $order->payment_status,
                'notes'           => $order->notes,
                'items'           => $order->items->map(fn($i) => [
                    'product_id'   => $i->product_id,
                    'product_name' => $i->product_name,
                    'quantity'     => $i->quantity,
                    'unit_price'   => (float) $i->unit_price,
                    'total_price'  => (float) $i->total_price,
                ]),
                'created_at'      => $order->created_at,
                'updated_at'      => $order->updated_at,
            ],
        ]);
    }

    private function deliveryOrders(Request $request)
    {
        $query = DeliveryOrder::where('store_id', $this->store()->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->since) {
            $query->where('created_at', '>=', $request->since);
        }

        $orders = $query->withCount('items')->latest()->paginate($request->per_page ?? 50);

        $orders->getCollection()->transform(fn($o) => [
            'id'              => $o->id,
            'source'          => 'delivery',
            'order_no'        => $o->order_no,
            'order_type'      => 'lizto_delivery',
            'customer_name'   => $o->contact_name,
            'customer_phone'  => $o->contact_phone,
            'delivery_address'=> $o->delivery_address,
            'subtotal'        => (float) $o->subtotal,
            'delivery_fee'    => (float) $o->delivery_fee,
            'discount'        => (float) $o->discount,
            'tip'             => (float) ($o->tip ?? 0),
            'total'           => (float) $o->total,
            'status'          => $o->status,
            'payment_status'  => $o->payment_status,
            'items_count'     => $o->items_count,
            'created_at'      => $o->created_at,
            'updated_at'      => $o->updated_at,
        ]);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    private function allOrders(Request $request)
    {
        $storeId = $this->store()->id;

        $posQuery = PosOrder::where('store_id', $storeId)->withCount('items');
        $deliveryQuery = DeliveryOrder::where('store_id', $storeId)->withCount('items');

        if ($request->status) {
            $posQuery->where('status', $request->status);
            $deliveryQuery->where('status', $request->status);
        }

        if ($request->since) {
            $posQuery->where('created_at', '>=', $request->since);
            $deliveryQuery->where('created_at', '>=', $request->since);
        }

        $posOrders = $posQuery->latest()->limit($request->per_page ?? 50)->get()->map(fn($o) => [
            'id'              => $o->id,
            'source'          => 'pos',
            'order_no'        => $o->order_no,
            'order_type'      => $o->order_type,
            'customer_name'   => $o->customer_name,
            'customer_phone'  => $o->customer_phone,
            'delivery_address'=> $o->delivery_address,
            'subtotal'        => (float) $o->subtotal,
            'delivery_fee'    => (float) ($o->delivery_fee ?? 0),
            'discount'        => 0,
            'tip'             => 0,
            'total'           => (float) $o->total,
            'status'          => $o->status,
            'payment_status'  => $o->payment_status,
            'items_count'     => $o->items_count,
            'created_at'      => $o->created_at,
            'updated_at'      => $o->updated_at,
        ]);

        $deliveryOrders = $deliveryQuery->latest()->limit($request->per_page ?? 50)->get()->map(fn($o) => [
            'id'              => $o->id,
            'source'          => 'delivery',
            'order_no'        => $o->order_no,
            'order_type'      => 'lizto_delivery',
            'customer_name'   => $o->contact_name,
            'customer_phone'  => $o->contact_phone,
            'delivery_address'=> $o->delivery_address,
            'subtotal'        => (float) $o->subtotal,
            'delivery_fee'    => (float) $o->delivery_fee,
            'discount'        => (float) $o->discount,
            'tip'             => (float) ($o->tip ?? 0),
            'total'           => (float) $o->total,
            'status'          => $o->status,
            'payment_status'  => $o->payment_status,
            'items_count'     => $o->items_count,
            'created_at'      => $o->created_at,
            'updated_at'      => $o->updated_at,
        ]);

        $orders = $posOrders->concat($deliveryOrders)->sortByDesc('created_at')->values()->take($request->per_page ?? 50);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    private function deliveryOrderDetail($id)
    {
        $order = DeliveryOrder::where('store_id', $this->store()->id)
            ->with('items.product', 'user', 'driver')
            ->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Pedido delivery no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'              => $order->id,
                'source'          => 'delivery',
                'order_no'        => $order->order_no,
                'order_type'      => 'lizto_delivery',
                'customer_name'   => $order->contact_name,
                'customer_phone'  => $order->contact_phone,
                'delivery_address'=> $order->delivery_address,
                'delivery_lat'    => $order->delivery_lat,
                'delivery_lng'    => $order->delivery_lng,
                'subtotal'        => (float) $order->subtotal,
                'delivery_fee'    => (float) $order->delivery_fee,
                'discount'        => (float) $order->discount,
                'tip'             => (float) ($order->tip ?? 0),
                'total'           => (float) $order->total,
                'status'          => $order->status,
                'payment_status'  => $order->payment_status,
                'notes'           => $order->notes,
                'driver'          => $order->driver ? [
                    'id'    => $order->driver->id,
                    'name'  => $order->driver->fullname ?? $order->driver->name ?? null,
                    'phone' => $order->driver->mobile ?? $order->driver->phone ?? null,
                ] : null,
                'items'           => $order->items->map(fn($i) => [
                    'product_id'   => $i->product_id,
                    'product_name' => $i->product_name,
                    'quantity'     => $i->quantity,
                    'unit_price'   => (float) $i->unit_price,
                    'total_price'  => (float) $i->total_price,
                ]),
                'created_at'      => $order->created_at,
                'updated_at'      => $order->updated_at,
            ],
        ]);
    }

    // ── Bulk Sync Categories ──

    public function syncCategories(Request $request)
    {
        $store = $this->store();

        $validator = Validator::make($request->all(), [
            'categories'              => 'required|array',
            'categories.*.id'         => 'nullable|integer',
            'categories.*.external_id'=> 'nullable|string|max:100',
            'categories.*.name'       => 'required|string|max:100',
            'categories.*.sort_order' => 'nullable|integer',
            'categories.*.status'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $results = [];
        foreach ($request->categories as $cat) {
            $externalId = $cat['external_id'] ?? null;

            // Try to find existing by external_id first, then by local id
            $category = null;
            if ($externalId) {
                $category = StoreCategory::where('store_id', $store->id)->where('external_id', $externalId)->first();
            }
            if (!$category && !empty($cat['id'])) {
                $category = StoreCategory::where('store_id', $store->id)->find($cat['id']);
            }

            if ($category) {
                $category->update([
                    'name'        => $cat['name'],
                    'sort_order'  => $cat['sort_order'] ?? $category->sort_order,
                    'external_id' => $externalId ?? $category->external_id,
                ]);
                $results[] = ['action' => 'updated', 'id' => $category->id, 'external_id' => $category->external_id, 'name' => $category->name];
                continue;
            }

            $category = StoreCategory::create([
                'store_id'    => $store->id,
                'name'        => $cat['name'],
                'sort_order'  => $cat['sort_order'] ?? 0,
                'status'      => $cat['status'] ?? 1,
                'external_id' => $externalId,
            ]);
            $results[] = ['action' => 'created', 'id' => $category->id, 'external_id' => $category->external_id, 'name' => $category->name];
        }

        return response()->json(['success' => true, 'message' => count($results) . ' categorías sincronizadas', 'data' => $results]);
    }

    // ── Bulk Sync Products ──

    public function syncProducts(Request $request)
    {
        $store = $this->store();

        $validator = Validator::make($request->all(), [
            'products'                   => 'required|array',
            'products.*.id'              => 'nullable|integer',
            'products.*.external_id'     => 'nullable|string|max:100',
            'products.*.name'            => 'required|string|max:200',
            'products.*.price'           => 'required|numeric|min:0',
            'products.*.category_id'     => 'nullable|integer',
            'products.*.category_external_id' => 'nullable|string|max:100',
            'products.*.description'     => 'nullable|string',
            'products.*.stock_type'      => 'nullable|in:packaged,prepared,none',
            'products.*.sort_order'      => 'nullable|integer',
            'products.*.status'          => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $results = [];
        foreach ($request->products as $p) {
            // Resolve category: try category_external_id first, then category_id
            $categoryId = null;
            $catExternalId = $p['category_external_id'] ?? null;
            if ($catExternalId) {
                $cat = StoreCategory::where('store_id', $store->id)->where('external_id', $catExternalId)->first();
                if ($cat) $categoryId = $cat->id;
            }
            if (!$categoryId && !empty($p['category_id'])) {
                $cat = StoreCategory::where('store_id', $store->id)->find($p['category_id']);
                if ($cat) $categoryId = $cat->id;
            }
            if (!$categoryId) {
                $categoryId = StoreCategory::where('store_id', $store->id)->first()?->id;
            }

            $externalId = $p['external_id'] ?? null;

            $data = [
                'store_id'          => $store->id,
                'store_category_id' => $categoryId,
                'name'              => $p['name'],
                'price'             => $p['price'],
                'discount_price'    => $p['discount_price'] ?? null,
                'description'       => $p['description'] ?? null,
                'stock_type'        => $p['stock_type'] ?? 'none',
                'sort_order'        => $p['sort_order'] ?? 0,
                'status'            => $p['status'] ?? 1,
            ];

            // Try to find existing by external_id first, then by local id
            $product = null;
            if ($externalId) {
                $product = Product::where('store_id', $store->id)->where('external_id', $externalId)->first();
            }
            if (!$product && !empty($p['id'])) {
                $product = Product::where('store_id', $store->id)->find($p['id']);
            }

            if ($product) {
                $data['external_id'] = $externalId ?? $product->external_id;
                $product->update($data);
                $results[] = ['action' => 'updated', 'id' => $product->id, 'external_id' => $product->external_id, 'name' => $product->name];
                continue;
            }

            $data['external_id'] = $externalId;
            $product = Product::create($data);
            $results[] = ['action' => 'created', 'id' => $product->id, 'external_id' => $product->external_id, 'name' => $product->name];
        }

        return response()->json(['success' => true, 'message' => count($results) . ' productos sincronizados', 'data' => $results]);
    }

    // ── Generate / Regenerate API Token ──

    public function regenerateToken()
    {
        $store = $this->store();
        $token = \Illuminate\Support\Str::random(60);
        $store->update(['api_token' => $token]);

        return response()->json([
            'success' => true,
            'message' => 'Token generado correctamente',
            'data'    => ['api_token' => $token],
        ]);
    }
}
