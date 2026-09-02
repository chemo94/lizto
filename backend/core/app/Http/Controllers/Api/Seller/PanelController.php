<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Constants\Status;
use App\Models\DeliveryOrder;
use App\Models\DeviceToken;
use App\Models\PosArea;
use App\Models\PosCashSession;
use App\Models\PosCustomerProfile;
use App\Models\PosExpense;
use App\Models\PosInvoiceSeries;
use App\Models\PosInvoiceType;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosStaff;
use App\Models\PosTable;
use App\Models\PosTransaction;
use App\Models\Seller;
use App\Models\Store;
use App\Models\SunatInvoice;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PanelController extends Controller
{
    private function seller() {
        $user = auth()->user();
        if ($user instanceof \App\Models\PosStaff) {
            return \App\Models\Seller::find($user->seller_id);
        }
        return $user;
    }

    private function staffId() {
        $user = auth()->user();
        if ($user instanceof \App\Models\PosStaff) {
            return $user->id;
        }
        return null;
    }
    private function store() { return Store::where('seller_id', $this->seller()->id)->first(); }

    public function sunatLookup(Request $request)
    {
        // Reuse the normalized RENIEC/SUNAT response while keeping API auth in
        // this controller (the web controller itself authenticates by session).
        $response = app(\App\Http\Controllers\SellerPosController::class)->sunatLookup($request);
        $payload = $response->getData(true);
        if (($payload['status'] ?? false) && filled($payload['nombre'] ?? null)) {
            $document = preg_replace('/\D+/', '', (string) ($payload['numeroDocumento'] ?? $request->numdoc));
            if ($document !== '') {
                $this->syncCustomerProfile(
                    (string) $request->tpdoc,
                    $document,
                    (string) $payload['nombre'],
                    null,
                    (string) ($payload['direccion'] ?? '')
                );
            }
        }
        return $response;
    }

    private function syncCustomerProfile(string $documentType, string $documentNumber, string $name, ?string $phone, ?string $address): PosCustomerProfile
    {
        $seller = $this->seller();
        $documentNumber = preg_replace('/\D+/', '', $documentNumber);
        $profile = PosCustomerProfile::firstOrNew([
            'seller_id' => $seller->id,
            'identity_key' => 'doc:' . $documentNumber,
        ]);
        $profile->document_type = $documentType;
        $profile->document_number = $documentNumber;
        $profile->name = trim($name);
        if (filled($phone)) $profile->phone = trim((string) $phone);
        if (filled($address)) $profile->address = trim((string) $address);
        $profile->save();
        return $profile;
    }

    private function customerAddress(string $documentNumber): string
    {
        return (string) PosCustomerProfile::where('seller_id', $this->seller()->id)
            ->where('document_number', preg_replace('/\D+/', '', $documentNumber))
            ->value('address');
    }

    // ── Dashboard ──
    public function dashboard()
    {
        $seller = $this->seller(); $storeId = $this->store()?->id;
        $posToday = PosOrder::where('seller_id', $seller->id)->whereDate('created_at', today());
        $delToday = DeliveryOrder::where('store_id', $storeId)->whereDate('created_at', today());
        $posMonth = PosOrder::where('seller_id', $seller->id)->whereMonth('created_at', now()->month);
        $delMonth = DeliveryOrder::where('store_id', $storeId)->whereMonth('created_at', now()->month);

        $stats = [
            'pos_today_count'  => (clone $posToday)->count(),
            'pos_today_sales'  => round((clone $posToday)->sum('total'), 2),
            'del_today_count'  => (clone $delToday)->count(),
            'del_today_sales'  => round((clone $delToday)->sum('total'), 2),
            'pos_month_count'  => (clone $posMonth)->count(),
            'pos_month_sales'  => round((clone $posMonth)->sum('total'), 2),
            'del_month_count'  => (clone $delMonth)->count(),
            'del_month_sales'  => round((clone $delMonth)->sum('total'), 2),
            'kitchen_pending'  => PosOrder::where('seller_id', $seller->id)->whereIn('status', ['confirmed','preparing'])->count(),
            'tables_occupied'  => PosTable::where('seller_id', $seller->id)->where('status', 'occupied')->count(),
            'receivable_balance' => (float) $seller->receivable_balance,
        ];

        $wallet = $seller->wallet;
        return apiResponse('dashboard', 'success', ['Estadísticas'], compact('stats', 'wallet'));
    }

    // ── Tables ──
    public function tables()
    {
        $tables = PosTable::where('seller_id', $this->seller()->id)->orderBy('pos_area_id')->orderBy('sort_order')->get();
        $areas = PosArea::where('seller_id', $this->seller()->id)->orderBy('sort_order')->get()->map(fn($a) => [
            'id' => $a->id, 'name' => $a->name,
            'tables' => $a->tables->map(fn($t) => [
                'id' => $t->id, 'name' => $t->name, 'status' => $t->status, 'capacity' => $t->capacity,
                'pos_x' => $t->pos_x, 'pos_y' => $t->pos_y, 'shape' => $t->shape,
            ])
        ]);
        return apiResponse('tables', 'success', ['Mesas y áreas'], compact('tables', 'areas'));
    }

    public function tableStore(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50', 'capacity' => 'integer|min:1']);
        PosTable::create([
            'seller_id' => $this->seller()->id, 'store_id' => $this->store()?->id,
            'name' => $request->name, 'pos_area_id' => $request->pos_area_id,
            'capacity' => $request->capacity ?? 4, 'pos_x' => $request->pos_x ?? 0, 'pos_y' => $request->pos_y ?? 0,
            'status' => 'free',
        ]);
        return apiResponse('table_created', 'success', ['Mesa creada']);
    }

    public function tableUpdate(Request $request, $id)
    {
        $table = PosTable::where('seller_id', $this->seller()->id)->findOrFail($id);
        $table->update($request->only(['name', 'pos_area_id', 'capacity', 'pos_x', 'pos_y', 'status', 'shape']));
        return apiResponse('table_updated', 'success', ['Mesa actualizada']);
    }

    public function tableDelete($id)
    {
        PosTable::where('seller_id', $this->seller()->id)->findOrFail($id)->delete();
        return apiResponse('table_deleted', 'success', ['Mesa eliminada']);
    }

    public function tablePosition(Request $request)
    {
        $table = PosTable::where('seller_id', $this->seller()->id)->findOrFail($request->id);
        $table->update(['pos_x' => (int) $request->x, 'pos_y' => (int) $request->y]);
        return apiResponse('position_saved', 'success', ['Posición guardada']);
    }

    // ── Areas ──
    public function areas()
    {
        $areas = PosArea::where('seller_id', $this->seller()->id)->orderBy('sort_order')->get();
        return apiResponse('areas', 'success', ['Áreas'], compact('areas'));
    }

    public function areaStore(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50']);
        PosArea::create(['seller_id' => $this->seller()->id, 'name' => $request->name]);
        return apiResponse('area_created', 'success', ['Área creada']);
    }

    public function areaDelete($id)
    {
        $area = PosArea::where('seller_id', $this->seller()->id)->findOrFail($id);
        PosTable::where('pos_area_id', $area->id)->update(['pos_area_id' => null]);
        $area->delete();
        return apiResponse('area_deleted', 'success', ['Área eliminada']);
    }

    // ── Kitchen / Orders ──
    public function kitchen()
    {
        $orders = PosOrder::where('seller_id', $this->seller()->id)
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->with('items', 'table')->latest()->get()
            ->map(fn($o) => [
                'id' => $o->id, 'order_no' => $o->order_no, 'order_type' => $o->order_type,
                'table' => $o->table?->name, 'customer_name' => $o->customer_name,
                'status' => $o->status, 'kitchen_notes' => $o->kitchen_notes,
                'total' => $o->total, 'created_at' => $o->created_at->diffForHumans(),
                'items' => $o->items->map(fn($i) => ['id' => $i->id, 'name' => $i->product_name, 'qty' => $i->quantity, 'status' => $i->status]),
            ]);
        return apiResponse('kitchen', 'success', ['Comandas activas'], compact('orders'));
    }

    public function kitchenStatus(Request $request, $id)
    {
        $order = PosOrder::where('seller_id', $this->seller()->id)->findOrFail($id);
        $order->update(['status' => $request->status]);
        if ($request->has('item_id')) {
            PosOrderItem::where('pos_order_id', $order->id)->where('id', $request->item_id)
                ->update(['status' => $request->item_status ?? $request->status]);
        }
        if ($request->status === 'delivered' && $order->pos_table_id) {
            PosTable::where('id', $order->pos_table_id)->update(['status' => 'free']);
        }
        return apiResponse('status_updated', 'success', ['Estado actualizado'], ['order' => $order->fresh('items')]);
    }

    // ── Orders History ──
    public function orders(Request $request)
    {
        $seller = $this->seller(); $storeId = $this->store()?->id;
        $page = $request->page ?? 1;
        $posOrders = PosOrder::where('seller_id', $seller->id)->with('items', 'table')->latest()->paginate(20, ['*'], 'page', $page);
        $posOrders->getCollection()->transform(fn($o) => [
            'id' => $o->id, 'order_no' => $o->order_no, 'source' => 'POS', 'customer_name' => $o->customer_name,
            'order_type' => $o->order_type, 'table' => $o->table?->name, 'total' => $o->total,
            'status' => $o->status, 'payment_status' => $o->payment_status,
            'invoice' => $o->invoice_series ? $o->invoice_series.'-'.$o->invoice_number : null,
            'items' => $o->items, 'created_at' => $o->created_at,
        ]);
        return apiResponse('orders', 'success', ['Pedidos'], ['orders' => $posOrders]);
    }

    // ── Customers ──
    public function customers()
    {
        $customers = PosOrder::where('seller_id', $this->seller()->id)
            ->whereNotNull('customer_phone')
            ->selectRaw('customer_name, customer_phone, COUNT(*) as total_orders, SUM(total) as total_spent, MAX(created_at) as last_order')
            ->groupBy('customer_phone', 'customer_name')->orderByDesc('total_orders')->get();
        return apiResponse('customers', 'success', ['Clientes'], compact('customers'));
    }

    // ── Cash Register ──
    public function cash()
    {
        $seller = $this->seller();
        $openSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
        $transactions = $openSession ? PosTransaction::where('cash_session_id', $openSession->id)->latest()->limit(50)->get() : [];
        $sessions = PosCashSession::where('seller_id', $seller->id)->latest()->limit(10)->get();
        return apiResponse('cash', 'success', ['Caja'], compact('openSession', 'transactions', 'sessions'));
    }

    public function cashOpen(Request $request)
    {
        $seller = $this->seller();
        if (PosCashSession::where('seller_id', $seller->id)->open()->exists()) {
            return apiResponse('cash_already_open', 'error', ['Ya tienes una caja abierta']);
        }
        $s = PosCashSession::create(['seller_id' => $seller->id, 'opening_balance' => $request->opening_balance ?? 0, 'opened_at' => now(), 'status' => 'open']);
        PosTransaction::create(['cash_session_id' => $s->id, 'seller_id' => $seller->id, 'type' => 'cash_in', 'amount' => $request->opening_balance ?? 0, 'description' => 'Apertura de caja']);
        return apiResponse('cash_opened', 'success', ['Caja abierta'], ['session' => $s]);
    }

    public function cashClose(Request $request)
    {
        $s = PosCashSession::where('seller_id', $this->seller()->id)->open()->firstOrFail();
        $s->update(['closing_balance' => $request->closing_balance ?? 0, 'closed_at' => now(), 'status' => 'closed']);
        return apiResponse('cash_closed', 'success', ['Caja cerrada']);
    }

    public function cashTransaction(Request $request)
    {
        $s = PosCashSession::where('seller_id', $this->seller()->id)->open()->firstOrFail();
        $tx = PosTransaction::create([
            'cash_session_id' => $s->id, 'seller_id' => $this->seller()->id,
            'type' => $request->type, 'amount' => abs($request->amount ?? 0),
            'description' => $request->description, 'payment_method' => $request->payment_method ?? 'cash',
        ]);
        $request->type === 'cash_in' ? $s->increment('total_cash_in', $tx->amount) : $s->increment('total_cash_out', $tx->amount);
        return apiResponse('transaction_done', 'success', ['Movimiento registrado'], ['transaction' => $tx]);
    }

    // ── Expenses ──
    public function expenses(Request $request)
    {
        $expenses = PosExpense::where('seller_id', $this->seller()->id)->latest()->paginate(20);
        return apiResponse('expenses', 'success', ['Gastos'], compact('expenses'));
    }

    public function expenseStore(Request $request)
    {
        $request->validate(['category' => 'required', 'amount' => 'required|numeric|min:0', 'description' => 'required']);
        $seller = $this->seller();
        $s = PosCashSession::where('seller_id', $seller->id)->open()->first();
        $expense = PosExpense::create([
            'seller_id' => $seller->id, 'cash_session_id' => $s?->id, 'category' => $request->category,
            'amount' => $request->amount, 'description' => $request->description,
            'provider' => $request->provider, 'invoice_number' => $request->invoice_number,
            'payment_method' => $request->payment_method ?? 'cash',
            'notes' => $request->notes, 'expense_date' => $request->expense_date ?? now(),
        ]);
        if ($s) { $s->increment('total_expenses', $expense->amount); }
        return apiResponse('expense_created', 'success', ['Gasto registrado'], ['expense' => $expense]);
    }

    public function expenseDelete($id)
    {
        PosExpense::where('seller_id', $this->seller()->id)->findOrFail($id)->delete();
        return apiResponse('expense_deleted', 'success', ['Gasto eliminado']);
    }

    // ── Billing / Invoicing ──
    public function billing()
    {
        $seller = $this->seller();
        $isCashOpen = \App\Models\PosCashSession::where('seller_id', $seller->id)->open()->exists();
        $pending = PosOrder::where('seller_id', $seller->id)->whereIn('status', ['delivered','ready'])->where('payment_status', 'pending')->with('items','table')->latest()->get();
        $paid = PosOrder::where('seller_id', $seller->id)->where('payment_status', 'paid')->with('items','table','sunatInvoice')->latest()->limit(20)->get();
        $invoiceTypes = PosInvoiceType::where('seller_id', $seller->id)->with('series')->get();
        return apiResponse('billing', 'success', ['Cobros pendientes'], compact('isCashOpen', 'pending', 'paid', 'invoiceTypes'));
    }

    private function activeCompany()
    {
        $seller = $this->seller();
        if ($seller->document_number && !\App\Models\SellerCompany::where('seller_id', $seller->id)->exists()) {
            $comp = \App\Models\SellerCompany::create([
                'seller_id'       => $seller->id,
                'document_number' => $seller->document_number,
                'business_name'   => $seller->business_name ?? $seller->name,
                'trade_name'      => $seller->trade_name,
                'ubigeo'          => $seller->ubigeo,
                'address'         => $seller->address,
                'sunat_sol_user'  => $seller->sunat_sol_user,
                'sunat_sol_pass'  => $seller->sunat_sol_pass,
                'sunat_env'       => $seller->sunat_env ?? 'beta',
                'sunat_cert_path' => $seller->sunat_cert_path,
                'sunat_cert_pass' => $seller->sunat_cert_pass,
                'is_active'       => true
            ]);
            PosInvoiceType::where('seller_id', $seller->id)->whereNull('seller_company_id')->update(['seller_company_id' => $comp->id]);
            PosInvoiceSeries::where('seller_id', $seller->id)->whereNull('seller_company_id')->update(['seller_company_id' => $comp->id]);
            \App\Models\SunatInvoice::where('seller_id', $seller->id)->whereNull('seller_company_id')->update(['seller_company_id' => $comp->id]);
            
            // Auto-seed default invoice types for this new company if they don't exist yet
            if (!PosInvoiceType::where('seller_company_id', $comp->id)->exists()) {
                $defaults = [
                    ['code' => '01', 'name' => 'Factura Electrónica', 'sunat_code' => '01', 'is_electronic' => true],
                    ['code' => '03', 'name' => 'Boleta de Venta Electrónica', 'sunat_code' => '03', 'is_electronic' => true],
                    ['code' => '07', 'name' => 'Nota de Crédito Electrónica', 'sunat_code' => '07', 'is_electronic' => true],
                    ['code' => '08', 'name' => 'Nota de Débito Electrónica', 'sunat_code' => '08', 'is_electronic' => true],
                    ['code' => 'NV', 'name' => 'Nota de Venta', 'sunat_code' => null, 'is_electronic' => false],
                ];
                foreach ($defaults as $d) {
                    PosInvoiceType::create(array_merge($d, [
                        'seller_id'         => $seller->id,
                        'seller_company_id' => $comp->id
                    ]));
                }
            }

            return $comp;
        }
        return \App\Models\SellerCompany::where('seller_id', $seller->id)->orderBy('is_active', 'desc')->first();
    }

    private function consumeStockForOrder($sellerId, $orderItems): array
    {
        return (new \App\Services\StockService($sellerId))->consumeForOrder($orderItems);
    }

    public function payOrder(Request $request, $id)
    {
        $seller = $this->seller();
        $isCashOpen = \App\Models\PosCashSession::where('seller_id', $seller->id)->open()->exists();
        if (!$isCashOpen) {
            return apiResponse('cash_closed', 'error', ['Debe abrir una caja antes de procesar el pago de un pedido.']);
        }

        $order = PosOrder::where('seller_id', $seller->id)->with('items')->findOrFail($id);
        
        $request->validate([
            'series_id'      => 'nullable|integer',
            'tipo_doc'       => 'nullable|string|in:1,6',
            'num_doc'        => 'nullable|string|max:20',
            'nombre'         => 'nullable|string|max:255',
            'direccion'      => 'nullable|string|max:500',
            'address'        => 'nullable|string|max:500',
            'payment_method' => 'nullable|string',
            'payments'       => 'nullable|array',
            'detail_mode'    => 'nullable|in:detailed,consumption',
            'consumption_description' => 'nullable|string|max:250',
        ]);

        $paymentMethod = $request->payment_method ?? 'cash';
        $paymentDetails = null;

        if ($request->has('payments') && is_array($request->payments)) {
            $splits = [];
            foreach ($request->payments as $method => $amount) {
                if ($amount > 0) {
                    $splits[$method] = (float) $amount;
                }
            }
            if (count($splits) > 0) {
                $paymentDetails = $splits;
                if (count($splits) > 1) {
                    $paymentMethod = 'split';
                } else {
                    $paymentMethod = array_key_first($splits);
                }
            }
        }

        $order->update([
            'payment_status'  => 'paid',
            'paid_at'         => now(),
            'payment_method'  => $paymentMethod,
            'payment_details' => $paymentDetails,
            'customer_doc'      => $request->num_doc ?? $order->customer_doc,
            'customer_doc_type' => $request->tipo_doc ?? $order->customer_doc_type,
            'customer_name'     => $request->nombre ?? $order->customer_name,
        ]);

        $customerAddress = trim((string) ($request->direccion ?? $request->address ?? ''));
        if ($request->filled('num_doc') && $request->filled('nombre')) {
            $profile = $this->syncCustomerProfile(
                (string) ($request->tipo_doc ?: (strlen((string) $request->num_doc) === 11 ? '6' : '1')),
                (string) $request->num_doc,
                (string) $request->nombre,
                $order->customer_phone,
                $customerAddress
            );
            $customerAddress = (string) ($profile->address ?? $customerAddress);
        }

        if ($order->pos_table_id) {
            PosTable::where('id', $order->pos_table_id)
                ->orWhere('linked_to_table_id', $order->pos_table_id)
                ->update([
                    'status' => 'free',
                    'linked_to_table_id' => null
                ]);
        }

        PosTransaction::where('pos_order_id', $order->id)->update([
            'payment_method' => $paymentMethod
        ]);

        $this->consumeStockForOrder($order->seller_id, $order->items);

        $store = $this->store();
        if ($store) {
            $store->dispatchWebhook('order.paid', [
                'id'             => $order->id,
                'order_no'       => $order->order_no,
                'payment_method' => $order->payment_method,
                'total'          => (float) $order->total,
                'paid_at'        => $order->paid_at ? $order->paid_at->toIso8601String() : now()->toIso8601String(),
            ]);
        }

        $invoiceInfo = null;

        $seriesId = $request->series_id;
        if (!$seriesId) {
            $activeCompany = $this->activeCompany();
            if ($activeCompany) {
                $nvSeries = PosInvoiceSeries::where('seller_company_id', $activeCompany->id)
                    ->whereHas('invoiceType', function($q) {
                        $q->where('code', 'NV');
                    })->first();
                if ($nvSeries) {
                    $seriesId = $nvSeries->id;
                }
            }
        }

        if ($seriesId) {
            $detailMode = $request->input('detail_mode', 'detailed');
            $consumptionDescription = $detailMode === 'consumption'
                ? ($request->input('consumption_description') ?: 'Consumo')
                : null;
            $activeCompany = $this->activeCompany();
            if (!$activeCompany) {
                return apiResponse('no_company', 'error', ['Debes configurar una empresa antes de emitir comprobantes.']);
            }

            $series = PosInvoiceSeries::where('id', $seriesId)
                ->where('seller_company_id', $activeCompany->id)
                ->with('invoiceType')
                ->first();

            if (!$series) {
                return apiResponse('invalid_series', 'error', ['La serie de comprobante seleccionada no es válida.']);
            }

            $tipoDoc = $series->invoiceType->sunat_code ?? $series->invoiceType->code;

            $store = $this->store();
            if ($tipoDoc !== 'NV' && $store && $store->hasReachedInvoiceLimit()) {
                $planName = $store->getPlanType() === 'basic' ? 'Emprendedor' : 'Destacado';
                $limit = $store->getPlanType() === 'basic' ? 50 : 100;
                return apiResponse('limit_reached', 'error', ["Has alcanzado el límite de {$limit} comprobantes electrónicos de tu plan {$planName}. Actualiza a un plan superior para facturación ilimitada."]);
            }

            $correlativo = $series->current_number;
            $series->increment('current_number');
            $tipoDoc = $series->invoiceType->sunat_code ?? $series->invoiceType->code;

            $clientData = [
                'tipo_doc' => $request->tipo_doc ?? ($tipoDoc === '01' ? '6' : '1'),
                'num_doc'  => $request->num_doc ?? ($order->customer_doc ?? '0'),
                'nombre'   => $request->nombre ?? ($order->customer_name ?? 'CLIENTE VARIOS'),
                'direccion'=> $customerAddress ?: $this->customerAddress((string) ($request->num_doc ?? $order->customer_doc)),
            ];

            $consumptionTaxType = $detailMode === 'consumption'
                ? ($activeCompany->default_tax_type ?? 'gravado')
                : null;
            if ($consumptionTaxType === 'gravado') {
                $totalGravada = round($order->total / 1.18, 2);
                $totalExonerada = 0;
                $totalInafecta = 0;
                $totalIgv = round($order->total - $totalGravada, 2);
            } elseif ($consumptionTaxType === 'exonerado') {
                $totalGravada = 0;
                $totalExonerada = round($order->total, 2);
                $totalInafecta = 0;
                $totalIgv = 0;
            } elseif ($consumptionTaxType === 'inafecto') {
                $totalGravada = 0;
                $totalExonerada = 0;
                $totalInafecta = round($order->total, 2);
                $totalIgv = 0;
            } else {
                $totalGravada = 0;
                $totalExonerada = 0;
                $totalInafecta = 0;
                $totalIgv = 0;
                foreach ($order->items as $item) {
                    $taxType = $item->tax_type ?? ($item->product?->tax_type ?? 'gravado');
                    $lineTotal = (float) $item->total_price;
                    if ($taxType === 'exonerado') {
                        $totalExonerada += $lineTotal;
                    } elseif ($taxType === 'inafecto') {
                        $totalInafecta += $lineTotal;
                    } else {
                        $base = round($lineTotal / 1.18, 2);
                        $totalGravada += $base;
                        $totalIgv += round($lineTotal - $base, 2);
                    }
                }
                $totalGravada = round($totalGravada, 2);
                $totalExonerada = round($totalExonerada, 2);
                $totalInafecta = round($totalInafecta, 2);
                $totalIgv = round($totalIgv, 2);
            }

            \App\Models\SunatInvoice::create([
                'seller_id'        => $this->seller()->id,
                'seller_company_id'=> $activeCompany->id,
                'pos_order_id'     => $order->id,
                'tipo_doc'         => $tipoDoc,
                'serie'            => $series->series,
                'correlativo'      => $correlativo,
                'cliente_tipo_doc' => $clientData['tipo_doc'],
                'cliente_num_doc'  => $clientData['num_doc'],
                'cliente_nombre'   => $clientData['nombre'],
                'cliente_direccion'=> $clientData['direccion'] ?: null,
                'detail_mode'      => $detailMode,
                'consumption_description' => $consumptionDescription,
                'total_gravada'    => $totalGravada,
                'total_exonerada'  => $totalExonerada,
                'total_inafecta'   => $totalInafecta,
                'total_igv'        => $totalIgv,
                'total'            => $order->total,
                'moneda'           => 'PEN',
                'cdr_status'       => 'pending',
                'fecha_emision'    => now(),
            ]);

            $number = str_pad($correlativo, 8, '0', STR_PAD_LEFT);
            $order->update([
                'invoice_type_id'   => $series->invoiceType->id,
                'invoice_series'    => $series->series,
                'invoice_number'    => $number,
                'invoice_type_code' => $tipoDoc,
                'customer_doc'      => $clientData['num_doc'],
                'customer_doc_type' => $clientData['tipo_doc'],
                'customer_name'     => $clientData['nombre'],
            ]);

            $invoiceInfo = $series->series . '-' . $number;
        }

        return apiResponse('paid', 'success', ['Pedido cobrado exitosamente'], [
            'order' => $order->fresh(['items']),
            'invoice' => $invoiceInfo
        ]);
    }

    public function generateInvoice(Request $request, $id)
    {
        $seller = $this->seller();
        $order = PosOrder::where('seller_id', $seller->id)->with('items')->findOrFail($id);
        
        $request->validate([
            'series_id' => 'required|integer',
            'tipo_doc'  => 'nullable|string|in:1,6',
            'num_doc'   => 'nullable|string|max:20',
            'nombre'    => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'address'   => 'nullable|string|max:500',
            'detail_mode' => 'nullable|in:detailed,consumption',
            'consumption_description' => 'nullable|string|max:250',
        ]);

        $activeCompany = $this->activeCompany();
        if (!$activeCompany) {
            return apiResponse('no_company', 'error', ['Debes configurar una empresa antes de emitir comprobantes.']);
        }

        $store = $this->store();
        if ($store && $store->hasReachedInvoiceLimit()) {
            $planName = $store->getPlanType() === 'basic' ? 'Emprendedor' : 'Destacado';
            $limit = $store->getPlanType() === 'basic' ? 50 : 100;
            return apiResponse('limit_reached', 'error', ["Has alcanzado el límite de {$limit} comprobantes electrónicos de tu plan {$planName}. Actualiza a un plan superior para facturación ilimitada."]);
        }

        $series = PosInvoiceSeries::where('id', $request->series_id)
            ->where('seller_company_id', $activeCompany->id)
            ->with('invoiceType')
            ->firstOrFail();

        $correlativo = $series->current_number;
        $series->increment('current_number');
        $tipoDoc = $series->invoiceType->sunat_code ?? $series->invoiceType->code;

        $clientData = [
            'tipo_doc' => $request->tipo_doc ?? ($tipoDoc === '01' ? '6' : '1'),
            'num_doc'  => $request->num_doc ?? ($order->customer_doc ?? '0'),
            'nombre'   => $request->nombre ?? ($order->customer_name ?? 'CLIENTE VARIOS'),
            'direccion'=> trim((string) ($request->direccion ?? $request->address ?? '')),
        ];
        if ($clientData['num_doc'] && $clientData['nombre']) {
            $profile = $this->syncCustomerProfile(
                (string) $clientData['tipo_doc'],
                (string) $clientData['num_doc'],
                (string) $clientData['nombre'],
                $order->customer_phone,
                (string) $clientData['direccion']
            );
            $clientData['direccion'] = (string) ($profile->address ?? $clientData['direccion']);
        }
        if ($clientData['direccion'] === '') {
            $clientData['direccion'] = $this->customerAddress((string) $clientData['num_doc']);
        }
        $detailMode = $request->input('detail_mode', 'detailed');
        $consumptionDescription = $detailMode === 'consumption'
            ? ($request->input('consumption_description') ?: 'Consumo')
            : null;

        $consumptionTaxType = $detailMode === 'consumption'
            ? ($activeCompany->default_tax_type ?? 'gravado')
            : null;
        if ($consumptionTaxType === 'gravado') {
            $totalGravada = round($order->total / 1.18, 2);
            $totalExonerada = 0;
            $totalInafecta = 0;
            $totalIgv = round($order->total - $totalGravada, 2);
        } elseif ($consumptionTaxType === 'exonerado') {
            $totalGravada = 0;
            $totalExonerada = round($order->total, 2);
            $totalInafecta = 0;
            $totalIgv = 0;
        } elseif ($consumptionTaxType === 'inafecto') {
            $totalGravada = 0;
            $totalExonerada = 0;
            $totalInafecta = round($order->total, 2);
            $totalIgv = 0;
        } else {
            $totalGravada = 0;
            $totalExonerada = 0;
            $totalInafecta = 0;
            $totalIgv = 0;
            foreach ($order->items as $item) {
                $taxType = $item->tax_type ?? ($item->product?->tax_type ?? 'gravado');
                $lineTotal = (float) $item->total_price;
                if ($taxType === 'exonerado') {
                    $totalExonerada += $lineTotal;
                } elseif ($taxType === 'inafecto') {
                    $totalInafecta += $lineTotal;
                } else {
                    $base = round($lineTotal / 1.18, 2);
                    $totalGravada += $base;
                    $totalIgv += round($lineTotal - $base, 2);
                }
            }
            $totalGravada = round($totalGravada, 2);
            $totalExonerada = round($totalExonerada, 2);
            $totalInafecta = round($totalInafecta, 2);
            $totalIgv = round($totalIgv, 2);
        }

        \App\Models\SunatInvoice::create([
            'seller_id'        => $seller->id,
            'seller_company_id'=> $activeCompany->id,
            'pos_order_id'     => $order->id,
            'tipo_doc'         => $tipoDoc,
            'serie'            => $series->series,
            'correlativo'      => $correlativo,
            'cliente_tipo_doc' => $clientData['tipo_doc'],
            'cliente_num_doc'  => $clientData['num_doc'],
            'cliente_nombre'   => $clientData['nombre'],
            'cliente_direccion'=> $clientData['direccion'] ?: null,
            'detail_mode'      => $detailMode,
            'consumption_description' => $consumptionDescription,
            'total_gravada'    => $totalGravada,
            'total_exonerada'  => $totalExonerada,
            'total_inafecta'   => $totalInafecta,
            'total_igv'        => $totalIgv,
            'total'            => $order->total,
            'moneda'           => 'PEN',
            'cdr_status'       => 'pending',
            'fecha_emision'    => now(),
        ]);

        $number = str_pad($correlativo, 8, '0', STR_PAD_LEFT);
        $order->update([
            'invoice_type_id'   => $series->invoiceType->id,
            'invoice_series'    => $series->series,
            'invoice_number'    => $number,
            'invoice_type_code' => $tipoDoc,
            'customer_doc'      => $clientData['num_doc'],
            'customer_doc_type' => $clientData['tipo_doc'],
            'customer_name'     => $clientData['nombre'],
            'payment_status'    => 'paid',
            'paid_at'           => $order->paid_at ?? now(),
        ]);

        if ($order->pos_table_id) {
            PosTable::where('id', $order->pos_table_id)
                ->orWhere('linked_to_table_id', $order->pos_table_id)
                ->update([
                    'status' => 'free',
                    'linked_to_table_id' => null
                ]);
        }

        return apiResponse('invoice_generated', 'success', ['Comprobante emitido'], [
            'order' => $order->fresh(), 
            'invoice' => $series->series.'-'.$number
        ]);
    }

    public function orderPreCheckTicket($id)
    {
        $seller = $this->seller();
        $order = PosOrder::where('seller_id', $seller->id)->with('items', 'table', 'store')->findOrFail($id);
        $store = $order->store ?? $this->store();

        $logoBase64 = null;
        if ($store && $store->image) {
            $logoPath = public_path('assets/images/store/' . $store->image);
            if (!file_exists($logoPath)) {
                $logoPath = base_path('../assets/images/store/' . $store->image);
            }
            if (file_exists($logoPath)) {
                try {
                    $logoData = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
                } catch (\Exception $e) {
                    $logoBase64 = null;
                }
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.pos.ticket', compact('order', 'logoBase64', 'store'));
        $pdf->setPaper([0, 0, 226.77, 600]);

        $filename = 'PreCuenta_' . ($order->order_no ?? $order->id) . '.pdf';
        return $pdf->download($filename);
    }

    public function invoicePdf($id, $format = 'a4')
    {
        $seller = $this->seller();
        $invoice = \App\Models\SunatInvoice::where('seller_id', $seller->id)->findOrFail($id);
        $store = Store::where('seller_id', $seller->id)->first();

        $company = $invoice->company ?? $this->activeCompany();
        $docNumber = $company?->document_number ?? $seller->document_number ?? '';
        $businessName = $company?->business_name ?? $seller->business_name ?? $seller->name ?? '';
        $tradeName = $company?->trade_name ?? $seller->trade_name ?? '';
        $address = $company?->address ?? $seller->address ?? '';
        $customerAddress = trim((string) ($invoice->cliente_direccion ?? ''));
        if ($customerAddress === '') {
            $customerAddress = trim((string) ($this->customerAddress((string) $invoice->cliente_num_doc)
                ?: $invoice->order?->delivery_address));
        }

        $logoBase64 = null;
        if ($store && $store->image) {
            $logoPath = public_path('assets/images/store/' . $store->image);
            if (!file_exists($logoPath)) {
                $logoPath = base_path('../assets/images/store/' . $store->image);
            }
            if (file_exists($logoPath)) {
                try {
                    $logoData = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode($logoData);
                } catch (\Exception $e) {
                    $logoBase64 = null;
                }
            }
        }

        $hashVal = $invoice->hash;
        $xmlDigest = null;
        if (!empty($invoice->xml_content)) {
            if (preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/i', $invoice->xml_content, $matches)) {
                $xmlDigest = trim($matches[1]);
            } elseif (preg_match('/<DigestValue>([^<]+)<\/DigestValue>/i', $invoice->xml_content, $matches)) {
                $xmlDigest = trim($matches[1]);
            }
        }

        if ($xmlDigest) {
            $hashVal = $xmlDigest;
            if ($invoice->hash !== $xmlDigest) {
                $invoice->hash = $xmlDigest;
                try { $invoice->save(); } catch (\Exception $e) {}
            }
        } else {
            if ($hashVal && (str_contains($hashVal, '-') || strlen($hashVal) < 15)) {
                $hashVal = '';
            }
        }
        $hashVal = $hashVal ?? '';

        $totTotal = (double)($invoice->total ?? 0);
        $totIgv = (double)($invoice->total_igv ?? 0);
        if ($totIgv == 0 && ($invoice->total_gravada ?? 0) > 0) {
            $totIgv = round($totTotal - ($invoice->total_gravada ?? 0), 2);
        }

        $qrData = "{$docNumber}|{$invoice->tipo_doc}|{$invoice->serie}|" . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) . "|{$totIgv}|{$totTotal}|" . ($invoice->fecha_emision ? $invoice->fecha_emision->format('Y-m-d') : '') . "|{$invoice->cliente_tipo_doc}|{$invoice->cliente_num_doc}|{$hashVal}|";

        $qrBase64 = null;
        if (class_exists(\chillerlan\QRCode\QRCode::class)) {
            try {
                $options = new \chillerlan\QRCode\QROptions([
                    'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class
                ]);
                $qr = new \chillerlan\QRCode\QRCode($options);
                $qrBase64 = $qr->render($qrData);
            } catch (\Exception $e) {
                try {
                    $options = new \chillerlan\QRCode\QROptions([
                        'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class
                    ]);
                    $qr = new \chillerlan\QRCode\QRCode($options);
                    $qrBase64 = $qr->render($qrData);
                } catch (\Exception $ex) {
                    $qrBase64 = null;
                }
            }
        }

        $montoLetras = $this->numeroALetrasPdf($invoice->total);

        $itemDetails = [];
        if ($invoice->isConsumptionSummary()) {
            $itemDetails[] = [
                'quantity' => 1,
                'unit_code' => 'NIU',
                'sunat_code' => null,
                'name' => $invoice->consumption_description ?: 'Consumo',
                'tax_type' => $invoice->total_exonerada > 0 ? 'exonerado' : ($invoice->total_inafecta > 0 ? 'inafecto' : 'gravado'),
                'unit_price' => $invoice->total,
            ];
        } elseif ($invoice->order && $invoice->order->items->count()) {
            foreach ($invoice->order->items as $item) {
                $product = $item->product;
                $unitCode = 'NIU';
                $sunatCode = null;

                if ($product) {
                    if ($product->stock_type === 'packaged') {
                        $link = \App\Models\InvProductItem::where('product_id', $product->id)->first();
                        if ($link && $link->item) {
                            $unitCode = strtoupper($link->item->unit ?? 'NIU');
                            $sunatCode = $link->item->sunat_code;
                        }
                    } elseif ($product->stock_type === 'prepared') {
                        $recipe = \App\Models\InvRecipe::where('product_id', $product->id)->active()->first();
                        if ($recipe && $recipe->unit_produced) {
                            $unitCode = strtoupper($recipe->unit_produced);
                        }
                    }
                }

                $itemDetails[] = [
                    'quantity'  => $item->quantity,
                    'unit_code' => $unitCode,
                    'sunat_code'=> $sunatCode,
                    'name'      => $item->product_name,
                    'tax_type'  => $item->tax_type ?? ($product?->tax_type ?? 'gravado'),
                    'unit_price'=> $item->unit_price,
                ];
            }
        }

        $viewName = $format === 'ticket' ? 'seller.invoice_pdf_ticket' : ($format === 'a5' ? 'seller.invoice_pdf_a5' : 'seller.invoice_pdf_a4');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, compact(
            'invoice', 'seller', 'company', 'docNumber', 'businessName', 'tradeName',
            'address', 'customerAddress', 'qrBase64', 'qrData', 'montoLetras', 'store', 'logoBase64', 'itemDetails'
        ));

        $paperSize = $format === 'a5' ? 'a5' : ($format === 'ticket' ? [0, 0, 226.77, 600] : 'a4');

        if (is_array($paperSize)) {
            $pdf->setPaper($paperSize);
        } else {
            $pdf->setPaper($paperSize, 'portrait');
        }

        $ruc = $docNumber ?: '00000000000';
        $tipoDoc = $invoice->tipo_doc ?: '03';
        $filename = $ruc . '-' . $tipoDoc . '-' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->stream($filename);
    }

    public function invoiceXml($id)
    {
        $seller = $this->seller();
        $invoice = \App\Models\SunatInvoice::where('seller_id', $seller->id)->findOrFail($id);
        if (!$invoice->xml_content) {
            return response()->json(['status' => false, 'message' => 'No hay XML disponible para este comprobante.'], 404);
        }

        $company = $invoice->company ?? $this->activeCompany();
        $ruc = $company?->document_number ?? $seller->document_number ?? '00000000000';
        $tipoDoc = $invoice->tipo_doc ?: '03';
        $filename = $ruc . '-' . $tipoDoc . '-' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) . '.xml';

        return response()->make($invoice->xml_content, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function invoiceCdr($id)
    {
        $seller = $this->seller();
        $invoice = \App\Models\SunatInvoice::where('seller_id', $seller->id)->findOrFail($id);
        $cdr = $invoice->getRawOriginal('cdr_response');
        if (!$cdr) {
            return response()->json(['status' => false, 'message' => 'No hay CDR disponible para este comprobante.'], 404);
        }

        $company = $invoice->company ?? $this->activeCompany();
        $ruc = $company?->document_number ?? $seller->document_number ?? '00000000000';
        $tipoDoc = $invoice->tipo_doc ?: '03';
        $baseName = 'R-' . $ruc . '-' . $tipoDoc . '-' . $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT);

        if (str_starts_with(trim($cdr), '<')) {
            $filename = $baseName . '.xml';
            return response()->make($cdr, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }
        $data = json_decode($cdr, true);
        $cdrContent = $data['archivedCdr'] ?? $data['cdrContent'] ?? null;
        if ($cdrContent) {
            $filename = $baseName . '.zip';
            return response()->make(base64_decode($cdrContent), 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }
        return response()->json(['status' => false, 'message' => 'No se puede descargar el CDR como archivo.'], 422);
    }

    private function numeroALetrasPdf($num): string
    {
        $num = round((float) $num, 2);
        $entero = (int) floor($num);
        $centimos = (int) round(($num - $entero) * 100);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = ['DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];

        $convertir = function ($n) use (&$convertir, $unidades, $decenas, $especiales) {
            if ($n == 0) return '';
            if ($n < 10) return $unidades[$n];
            if ($n < 20) return $especiales[$n - 10];
            if ($n < 100) {
                $d = (int) floor($n / 10);
                $u = $n % 10;
                return $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
            }
            if ($n < 1000) {
                $c = (int) floor($n / 100);
                $resto = $n % 100;
                $prefix = $c === 1 ? 'CIENTO' : ['','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'][$c];
                return $prefix . ($resto > 0 ? ' ' . $convertir($resto) : '');
            }
            if ($n < 1000000) {
                $miles = (int) floor($n / 1000);
                $resto = $n % 1000;
                $txtMiles = $miles === 1 ? 'MIL' : $convertir($miles) . ' MIL';
                return $txtMiles . ($resto > 0 ? ' ' . $convertir($resto) : '');
            }
            return number_format($n);
        };

        $letras = $entero == 0 ? 'CERO' : $convertir($entero);
        return $letras . ' CON ' . str_pad($centimos, 2, '0', STR_PAD_LEFT) . '/100';
    }

    // ── Invoicing Config ──
    public function invoicing()
    {
        $types = PosInvoiceType::where('seller_id', $this->seller()->id)->with('series')->get();
        return apiResponse('invoicing', 'success', ['Configuración de facturación'], compact('types'));
    }

    public function invoiceSeriesStore(Request $request)
    {
        $type = PosInvoiceType::where('seller_id', $this->seller()->id)->findOrFail($request->invoice_type_id);
        $s = PosInvoiceSeries::create(['invoice_type_id' => $type->id, 'seller_id' => $this->seller()->id, 'series' => strtoupper($request->series), 'current_number' => $request->current_number ?? 1]);
        return apiResponse('series_created', 'success', ['Serie agregada'], ['series' => $s]);
    }

    // ── Mass Notifications ──
    public function sendNotification(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('API sendNotification: reached method', [
            'seller_id' => $this->seller()?->id,
            'store_id' => $this->store()?->id,
            'title' => $request->input('title'),
            'body' => $request->input('body'),
        ]);
        $request->validate(['title' => 'required|string|max:100', 'body' => 'required|string|max:255']);
        $users = \App\Models\User::where('status', 1)->get();
        \Illuminate\Support\Facades\Log::info('API sendNotification: users found', ['count' => $users->count()]);
        $count = 0;
        foreach ($users as $user) {
            FcmService::sendToUser($user, $request->title, $request->body, ['store_id' => (string) ($this->store()?->id ?? ''), 'type' => 'seller_promotion']);
            $count++;
        }
        return apiResponse('notification_sent', 'success', ['Notificación enviada a ' . $count . ' usuarios'], ['recipients' => $count]);
    }

    // ── QR Menu ──
    public function qrMenu()
    {
        $store = $this->store();
        $storeUrl = route('delivery.store', $store ?? 1);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&color=16a34a&data=' . urlencode($storeUrl);
        return apiResponse('qr_menu', 'success', ['QR de carta'], compact('storeUrl', 'qrUrl'));
    }

    // ── Reports ──
    public function reports(Request $request)
    {
        $seller = $this->seller(); $storeId = $this->store()?->id;
        $from = $request->from ?? now()->startOfMonth()->format('Y-m-d');
        $to = $request->to ?? now()->format('Y-m-d');
        $pos = PosOrder::where('seller_id', $seller->id)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        $del = DeliveryOrder::where('store_id', $storeId)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        $report = [
            'from' => $from, 'to' => $to,
            'pos_count' => (clone $pos)->count(), 'pos_sales' => round((clone $pos)->sum('total'), 2),
            'del_count' => (clone $del)->count(), 'del_sales' => round((clone $del)->sum('total'), 2),
            'total_orders' => (clone $pos)->count() + (clone $del)->count(),
            'total_sales' => round((clone $pos)->sum('total') + (clone $del)->sum('total'), 2),
        ];
        return apiResponse('report', 'success', ['Reporte'], compact('report'));
    }

    // ── Register Seller (web-compatible) ──
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100', 'email' => 'required|email|unique:sellers,email',
            'password' => 'required|min:6', 'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500', 'zone_id' => 'required|exists:zones,id',
        ]);
        $seller = Seller::create($request->only(['name','email','phone','address','zone_id','business_name','trade_name']) + ['password' => Hash::make($request->password), 'status' => 1]);
        $subCat = \App\Models\SubCategory::first();
        $store = Store::create(['seller_id' => $seller->id, 'sub_category_id' => $subCat?->id ?? 1, 'name' => $request->trade_name ?: $request->business_name ?: $request->name, 'address' => $request->address, 'latitude' => $request->latitude, 'longitude' => $request->longitude, 'status' => 1, 'is_open' => 1]);
        $token = $seller->createToken('auth_token')->plainTextToken;

        $deviceToken = $request->device_token ?? $request->fcm_token;
        if ($deviceToken) {
            DeviceToken::where('seller_id', $seller->id)->where('token', '!=', $deviceToken)->delete();
            DeviceToken::updateOrCreate(
                ['token' => $deviceToken],
                ['seller_id' => $seller->id, 'user_id' => null, 'driver_id' => null, 'is_app' => Status::YES, 'app_type' => 'seller']
            );
        }

        return apiResponse('registered', 'success', ['Registro exitoso'], compact('seller', 'store', 'token'));
    }

    // ── Products (for mozo ordering) ──
    public function products()
    {
        $store = $this->store();
        if (!$store) {
            return apiResponse('no_store', 'error', ['No se encontró la tienda']);
        }

        $products = \App\Models\Product::where('store_id', $store->id)
            ->where('status', 1)
            ->with(['variations', 'addons'])
            ->orderBy('store_category_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'price'       => $p->price,
                'image'       => $p->image,
                'barcode'     => $p->barcode,
                'category_id' => $p->store_category_id,
                'category'    => $p->category?->name,
                'variations'  => $p->variations->map(fn($v) => [
                    'id'    => $v->id,
                    'name'  => $v->name,
                    'price' => $v->price,
                ]),
                'addons'      => $p->addons->map(fn($a) => [
                    'id'    => $a->id,
                    'name'  => $a->name,
                    'price' => $a->price,
                ]),
            ]);

        $categories = \App\Models\StoreCategory::where('store_id', $store->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name]);

        return apiResponse('products', 'success', ['Productos'], compact('products', 'categories'));
    }

    // ── Create Order (from mozo / seller app) ──
    public function orderCreate(Request $request)
    {
        $seller = $this->seller();
        if (!$seller) {
            return apiResponse('unauthorized', 'error', ['No autorizado']);
        }

        $request->validate([
            'table_id'            => 'nullable|integer',
            'order_type'          => 'nullable|string|in:dine_in,takeaway,delivery,lizto_delivery,daz,llama,courtesy',
            'customer_name'       => 'nullable|string|max:100',
            'customer_phone'      => 'nullable|string|max:20',
            'delivery_address'    => 'nullable|string|max:500',
            'delivery_lat'        => 'nullable|numeric',
            'delivery_lng'        => 'nullable|numeric',
            'kitchen_notes'       => 'nullable|string',
            'courtesy'            => 'nullable|boolean',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|integer',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.price'       => 'nullable|numeric|min:0',
            'items.*.name'        => 'nullable|string',
            'items.*.notes'       => 'nullable|string',
            'items.*.is_takeaway' => 'nullable|boolean',
        ]);

        $table = $request->table_id ? PosTable::where('seller_id', $seller->id)->find($request->table_id) : null;
        $store = $this->store();
        $orderType = $request->order_type ?? ($table ? 'dine_in' : 'takeaway');
        $isCourtesy = $request->boolean('courtesy', false) || $orderType === 'courtesy';

        // Check if table already has an active order
        if ($table) {
            $existingOrder = PosOrder::where('seller_id', $seller->id)
                ->where('pos_table_id', $table->id)
                ->whereIn('status', ['confirmed', 'preparing', 'ready'])
                ->first();

            if ($existingOrder) {
                // Add items to existing order
                $subtotal = 0;
                foreach ($request->items as $item) {
                    $product = \App\Models\Product::find($item['product_id']);
                    $productName = $item['name'] ?? ($product->name ?? 'Producto');
                    
                    $hasTupper = filter_var($item['has_tupper'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($hasTupper && strpos($productName, '(Con Tupper)') === false) {
                        $productName .= ' (Con Tupper)';
                    }

                    $itemCourtesy = filter_var($item['is_courtesy'] ?? false, FILTER_VALIDATE_BOOLEAN) || $isCourtesy;
                    $price = $itemCourtesy ? 0 : ($item['price'] ?? $product->price ?? 0);
                    $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                    $total = $price * $qty;
                    $subtotal += $total;

                    PosOrderItem::create([
                        'pos_order_id' => $existingOrder->id,
                        'product_id'   => $item['product_id'],
                        'product_name' => $productName,
                        'quantity'     => $qty,
                        'unit_price'   => $price,
                        'total_price'  => $total,
                        'tax_type'     => $product->tax_type ?? 'gravado',
                        'notes'        => $item['notes'] ?? null,
                        'is_takeaway'  => filter_var($item['is_takeaway'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'status'       => 'pending',
                    ]);
                }

                $existingOrder->increment('subtotal', $subtotal);
                $existingOrder->increment('total', $subtotal);

                if ($isCourtesy && $existingOrder->total <= 0) {
                    $existingOrder->update(['payment_status' => 'paid', 'payment_method' => 'courtesy', 'paid_at' => now()]);
                }

                if ($request->kitchen_notes) {
                    $existingOrder->update(['kitchen_notes' => $existingOrder->kitchen_notes ? $existingOrder->kitchen_notes . "\n" . $request->kitchen_notes : $request->kitchen_notes]);
                }

                if ($request->customer_name && !$existingOrder->customer_name) {
                    $existingOrder->update(['customer_name' => $request->customer_name]);
                }

                return apiResponse('order_updated', 'success', ['Pedido actualizado'], [
                    'order' => $existingOrder->fresh(['items', 'table']),
                ]);
            }
        }

        // Create new order
        $subtotal = 0;
        $orderItems = [];

        foreach ($request->items as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            $productName = $item['name'] ?? ($product->name ?? 'Producto');

            $hasTupper = filter_var($item['has_tupper'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($hasTupper && strpos($productName, '(Con Tupper)') === false) {
                $productName .= ' (Con Tupper)';
            }

            $itemCourtesy = filter_var($item['is_courtesy'] ?? false, FILTER_VALIDATE_BOOLEAN) || $isCourtesy;
            $price = $itemCourtesy ? 0 : ($item['price'] ?? $product->price ?? 0);
            $qty = $item['quantity'] ?? $item['qty'] ?? 1;
            $total = $price * $qty;
            $subtotal += $total;

            $orderItems[] = [
                'product_id'   => $item['product_id'],
                'product_name' => $productName,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'total_price'  => $total,
                'tax_type'     => $product->tax_type ?? 'gravado',
                'notes'        => $item['notes'] ?? null,
                'is_takeaway'  => filter_var($item['is_takeaway'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'status'       => 'pending',
            ];
        }

        $orderNo = 'POS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $orderData = [
            'seller_id'       => $seller->id,
            'store_id'        => $store?->id,
            'pos_table_id'    => $table?->id,
            'pos_staff_id'    => $this->staffId(),
            'order_no'        => $orderNo,
            'customer_name'   => $request->customer_name,
            'customer_phone'  => $request->customer_phone,
            'delivery_address'=> $request->delivery_address,
            'delivery_lat'    => $request->delivery_lat,
            'delivery_lng'    => $request->delivery_lng,
            'subtotal'        => $subtotal,
            'total'           => $subtotal,
            'order_type'      => $orderType,
            'status'          => 'confirmed',
            'kitchen_notes'   => $request->kitchen_notes,
        ];

        if ($isCourtesy) {
            $orderData['payment_status'] = 'paid';
            $orderData['payment_method'] = 'courtesy';
            $orderData['paid_at'] = now();
        }

        $order = PosOrder::create($orderData);

        foreach ($orderItems as $oi) {
            $oi['pos_order_id'] = $order->id;
            PosOrderItem::create($oi);
        }

        if ($table) {
            $table->update(['status' => 'occupied']);
        }

        // Register in cash session if open (skip for courtesy orders)
        if (!$isCourtesy) {
            $cashSession = PosCashSession::where('seller_id', $seller->id)->open()->first();
            if ($cashSession) {
                PosTransaction::create([
                    'cash_session_id' => $cashSession->id,
                    'seller_id'       => $seller->id,
                    'pos_order_id'    => $order->id,
                    'type'            => 'sale',
                    'amount'          => $subtotal,
                    'description'     => "Pedido #{$orderNo}",
                ]);
                $cashSession->increment('total_sales', $subtotal);
            }
        }

        // Notify couriers if delivery order
        $deliveryTypes = ['delivery', 'lizto_delivery', 'daz', 'llama'];
        if (in_array($orderType, $deliveryTypes)) {
            $drivers = \App\Models\Driver::active()->where('online_status', 1)
                ->whereIn('service_type', ['delivery', 'both'])->get();
            foreach ($drivers as $driver) {
                \App\Services\FcmService::sendToDriver($driver, 'Nuevo pedido delivery',
                    'Pedido #' . $orderNo . ' - ' . ($store?->name ?? 'Tienda') . ' | S/ ' . number_format($subtotal, 2),
                    ['pos_order_id' => (string) $order->id, 'type' => 'pos_delivery', 'store_name' => $store?->name ?? '']
                );
            }
        }

        return apiResponse('order_created', 'success', ['Pedido creado'], [
            'order' => $order->fresh(['items', 'table']),
        ]);
    }

    // ── Get Active Table Order ──
    public function getActiveTableOrder($tableId)
    {
        $seller = $this->seller();
        $order = PosOrder::where('seller_id', $seller->id)
            ->where('pos_table_id', $tableId)
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->with('items')
            ->latest()
            ->first();

        if (!$order) {
            return apiResponse('no_active_order', 'success', ['Sin pedido activo'], ['order' => null]);
        }

        return apiResponse('active_order', 'success', ['Pedido activo'], ['order' => $order]);
    }
}
