<?php

namespace App\Http\Controllers;

use App\Models\InvItem;
use App\Models\InvKardex;
use App\Models\InvPurchaseOrder;
use App\Models\InvPurchaseOrderItem;
use App\Models\InvReception;
use App\Models\InvReceptionItem;
use App\Models\InvSupplier;
use App\Models\InvWarehouse;
use App\Models\InvWarehouseStock;
use App\Models\InvWarehouseTransfer;
use App\Models\InvWarehouseTransferItem;
use App\Models\SellerCompany;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LogisticsController extends Controller
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
            if (!Session::has('seller_id')) return redirect()->route('seller.login');
            
            // Auto-creación de almacén por defecto si no existe
            $sellerId = Session::get('seller_id');
            if ($sellerId) {
                $hasWarehouse = InvWarehouse::where('seller_id', $sellerId)->exists();
                if (!$hasWarehouse) {
                    InvWarehouse::create([
                        'seller_id'  => $sellerId,
                        'name'       => 'Almacén Principal',
                        'address'    => 'Sede Principal',
                        'is_default' => true,
                        'status'     => 'active'
                    ]);
                }
            }
            return $next($request);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GESTIÓN DE PROVEEDORES (MEJORADO)
    // ─────────────────────────────────────────────────────────────────────────

    public function suppliers()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Proveedores (Logística)';
        
        $suppliers = InvSupplier::where('seller_id', $seller->id)
            ->withCount(['seller as total_purchases' => function($q) use($seller) {
                // Contar órdenes de compra recibidas o compras realizadas
                $q->where('seller_id', $seller->id);
            }])
            ->orderBy('name')
            ->get();

        return view('seller.logistics.suppliers', compact('pageTitle', 'seller', 'store', 'suppliers'));
    }

    public function supplierStore(Request $request)
    {
        $service = new \App\Services\SupplierService($this->seller()->id);
        $supplier = $service->create($request);
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['status' => true, 'supplier' => $supplier]);
        }
        return back()->with('success', 'Proveedor registrado exitosamente.');
    }

    public function supplierUpdate(Request $request, $id)
    {
        $service = new \App\Services\SupplierService($this->seller()->id);
        $service->update($request, $id);
        return back()->with('success', 'Proveedor actualizado correctamente.');
    }

    public function supplierDelete($id)
    {
        $service = new \App\Services\SupplierService($this->seller()->id);
        if (!$service->delete($id)) {
            return back()->with('error', 'No se puede eliminar: el proveedor tiene compras u órdenes asociadas.');
        }
        return back()->with('success', 'Proveedor eliminado.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GESTIÓN DE ALMACENES
    // ─────────────────────────────────────────────────────────────────────────

    public function warehouses()
    {
        $seller     = $this->seller();
        $store      = $this->store();
        $pageTitle  = 'Almacenes y Stock';
        
        $warehouses = InvWarehouse::where('seller_id', $seller->id)->orderBy('is_default', 'desc')->get();
        $items      = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();
        
        // Cargar stock cruzado de almacenes
        $stocks = InvWarehouseStock::whereIn('warehouse_id', $warehouses->pluck('id'))->get();

        return view('seller.logistics.warehouses', compact('pageTitle', 'seller', 'store', 'warehouses', 'items', 'stocks'));
    }

    public function warehouseStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:120',
        ]);

        $seller = $this->seller();
        
        DB::transaction(function () use ($request, $seller) {
            $isDefault = $request->has('is_default');
            
            if ($isDefault) {
                InvWarehouse::where('seller_id', $seller->id)->update(['is_default' => false]);
            }

            InvWarehouse::create([
                'seller_id'  => $seller->id,
                'name'       => $request->name,
                'address'    => $request->address,
                'is_default' => $isDefault,
                'status'     => $request->status ?? 'active',
            ]);
        });

        return back()->with('success', 'Almacén registrado.');
    }

    public function warehouseUpdate(Request $request, $id)
    {
        $seller    = $this->seller();
        $warehouse = InvWarehouse::where('seller_id', $seller->id)->findOrFail($id);
        
        DB::transaction(function () use ($request, $seller, $warehouse) {
            $isDefault = $request->has('is_default');
            
            if ($isDefault) {
                InvWarehouse::where('seller_id', $seller->id)->update(['is_default' => false]);
            }

            $warehouse->update([
                'name'       => $request->name,
                'address'    => $request->address,
                'is_default' => $isDefault || $warehouse->is_default, // Si era default y se desmarca sin otro default, mantener
                'status'     => $request->status ?? $warehouse->status,
            ]);
        });

        return back()->with('success', 'Almacén actualizado.');
    }

    public function warehouseDelete($id)
    {
        $warehouse = InvWarehouse::where('seller_id', $this->seller()->id)->findOrFail($id);
        
        if ($warehouse->is_default) {
            return back()->with('error', 'No se puede eliminar el almacén predeterminado.');
        }

        $warehouse->delete();
        return back()->with('success', 'Almacén eliminado.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ÓRDENES DE COMPRA
    // ─────────────────────────────────────────────────────────────────────────

    public function purchaseOrders()
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Órdenes de Compra';
        
        $orders     = InvPurchaseOrder::where('seller_id', $seller->id)->with('supplier', 'warehouse')->latest()->paginate(15);
        $suppliers  = InvSupplier::where('seller_id', $seller->id)->orderBy('name')->get();
        $warehouses = InvWarehouse::where('seller_id', $seller->id)->active()->get();
        $items      = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();

        return view('seller.logistics.purchase_orders', compact('pageTitle', 'seller', 'store', 'orders', 'suppliers', 'warehouses', 'items'));
    }

    public function purchaseOrderStore(Request $request)
    {
        $request->validate([
            'supplier_id'  => 'required|exists:inv_suppliers,id',
            'warehouse_id' => 'required|exists:inv_warehouses,id',
            'order_date'   => 'required|date',
            'items'        => 'required|json',
        ]);

        $seller = $this->seller();
        $itemsArray = json_decode($request->items, true);

        if (empty($itemsArray)) {
            return back()->with('error', 'Debe agregar al menos un insumo/producto.');
        }

        DB::transaction(function () use ($request, $seller, $itemsArray) {
            $subtotal = 0;
            foreach ($itemsArray as $itm) {
                $subtotal += ($itm['quantity'] ?? 0) * ($itm['unit_cost'] ?? 0);
            }
            $igv   = round($subtotal * 0.18, 2);
            $total = round($subtotal + $igv, 2);

            $orderNo = 'OC-' . time() . '-' . rand(10, 99);

            $order = InvPurchaseOrder::create([
                'seller_id'    => $seller->id,
                'supplier_id'  => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'order_number' => $orderNo,
                'order_date'   => $request->order_date,
                'status'       => 'draft',
                'subtotal'     => $subtotal,
                'igv'          => $igv,
                'total'        => $total,
                'notes'        => $request->notes,
            ]);

            foreach ($itemsArray as $itm) {
                InvPurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'item_id'           => $itm['item_id'],
                    'quantity'          => $itm['quantity'],
                    'unit_cost'         => $itm['unit_cost'],
                    'total'             => $itm['quantity'] * $itm['unit_cost'],
                ]);
            }
        });

        return back()->with('success', 'Orden de compra registrada como borrador.');
    }

    public function purchaseOrderStatus(Request $request, $id)
    {
        $order = InvPurchaseOrder::where('seller_id', $this->seller()->id)->findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->status;

        if ($oldStatus === InvPurchaseOrder::STATUS_RECEIVED || $oldStatus === InvPurchaseOrder::STATUS_CANCELLED) {
            return back()->with('error', 'No se puede cambiar el estado de una orden recibida o anulada.');
        }

        $order->update(['status' => $newStatus]);
        return back()->with('success', 'Estado de la orden de compra actualizado a: ' . InvPurchaseOrder::statuses()[$newStatus]);
    }

    public function purchaseOrderPdf($id)
    {
        $seller = $this->seller();
        $order  = InvPurchaseOrder::where('seller_id', $seller->id)->with('supplier', 'warehouse', 'items.item')->findOrFail($id);
        
        $company = $this->company();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('seller.logistics.purchase_order_pdf', compact('order', 'seller', 'company'));
        return $pdf->download('Orden-Compra-' . $order->order_number . '.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RECEPCIONES DE MERCADERÍA
    // ─────────────────────────────────────────────────────────────────────────

    public function receptions()
    {
        $seller     = $this->seller();
        $store      = $this->store();
        $pageTitle  = 'Recepciones de Mercadería';
        
        $receptions = InvReception::where('seller_id', $seller->id)->with('purchaseOrder', 'warehouse')->latest()->paginate(15);
        $pendingOC  = InvPurchaseOrder::where('seller_id', $seller->id)->whereIn('status', ['approved', 'in_transit'])->get();
        $warehouses = InvWarehouse::where('seller_id', $seller->id)->active()->get();
        $items      = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();

        return view('seller.logistics.receptions', compact('pageTitle', 'seller', 'store', 'receptions', 'pendingOC', 'warehouses', 'items'));
    }

    public function receptionCreate($orderId = null)
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Nueva Recepción';
        
        $order = null;
        if ($orderId) {
            $order = InvPurchaseOrder::where('seller_id', $seller->id)->with('items.item', 'supplier')->findOrFail($orderId);
        }
        
        $items      = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();
        $warehouses = InvWarehouse::where('seller_id', $seller->id)->active()->get();

        return view('seller.logistics.reception_create', compact('pageTitle', 'seller', 'store', 'order', 'items', 'warehouses'));
    }

    public function receptionStore(Request $request)
    {
        $request->validate([
            'warehouse_id'   => 'required|exists:inv_warehouses,id',
            'reception_date' => 'required|date',
            'items'          => 'required|json',
        ]);

        $seller = $this->seller();
        $itemsArray = json_decode($request->items, true);

        if (empty($itemsArray)) {
            return back()->with('error', 'Debe recepcionar al menos un insumo.');
        }

        DB::transaction(function () use ($request, $seller, $itemsArray) {
            $reception = InvReception::create([
                'seller_id'         => $seller->id,
                'purchase_order_id' => $request->purchase_order_id ?: null,
                'warehouse_id'      => $request->warehouse_id,
                'reception_date'    => $request->reception_date,
                'document_type'     => $request->document_type ?? '09',
                'document_number'   => $request->document_number,
                'notes'             => $request->notes,
                'status'            => 'completed',
            ]);

            foreach ($itemsArray as $ri) {
                $itemId      = $ri['item_id'];
                $qtyReceived = floatval($ri['quantity_received']);
                $qtyDamaged  = floatval($ri['quantity_damaged'] ?? 0);
                $qtyOrdered  = floatval($ri['quantity_ordered'] ?? $qtyReceived);
                
                InvReceptionItem::create([
                    'reception_id'      => $reception->id,
                    'item_id'           => $itemId,
                    'quantity_ordered'  => $qtyOrdered,
                    'quantity_received' => $qtyReceived,
                    'quantity_damaged'  => $qtyDamaged,
                    'expiration_date'   => $ri['expiration_date'] ?: null,
                ]);

                // Actualizar stock del Almacén Específico
                $wStock = InvWarehouseStock::firstOrCreate(
                    ['warehouse_id' => $request->warehouse_id, 'item_id' => $itemId],
                    ['stock' => 0]
                );
                $wStock->increment('stock', $qtyReceived);

                // Actualizar stock global del Insumo
                $item = InvItem::findOrFail($itemId);
                $oldStock = $item->stock;
                $item->increment('stock', $qtyReceived);

                // CPP (Costo Promedio Ponderado)
                $unitCost = floatval($ri['unit_cost'] ?? $item->cost);
                if ($oldStock > 0) {
                    $newCost = (($oldStock * $item->cost) + ($qtyReceived * $unitCost)) / ($oldStock + $qtyReceived);
                } else {
                    $newCost = $unitCost;
                }
                
                $item->update([
                    'cost'      => $newCost,
                    'last_cost' => $unitCost,
                ]);

                // Kardex
                InvKardex::create([
                    'seller_id'      => $seller->id,
                    'item_id'        => $itemId,
                    'warehouse_id'   => $request->warehouse_id,
                    'type'           => 'entrada',
                    'reference_type' => 'reception',
                    'reference_id'   => $reception->id,
                    'quantity'       => $qtyReceived,
                    'unit_cost'      => $unitCost,
                    'total_cost'     => $qtyReceived * $unitCost,
                    'balance_stock'  => $item->stock,
                    'description'    => "Recepción #" . $reception->id . " en " . $reception->warehouse->name,
                ]);
            }

            // Si está vinculada a una OC, actualizar estado a Recibido
            if ($request->purchase_order_id) {
                InvPurchaseOrder::where('id', $request->purchase_order_id)->update(['status' => 'received']);
            }
        });

        return redirect()->route('seller.logistics.receptions')->with('success', 'Recepción de mercadería registrada. Inventario actualizado.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSFERENCIAS ENTRE ALMACENES
    // ─────────────────────────────────────────────────────────────────────────

    public function transfers()
    {
        $seller     = $this->seller();
        $store      = $this->store();
        $pageTitle  = 'Transferencias de Stock';
        
        $transfers  = InvWarehouseTransfer::where('seller_id', $seller->id)->with('fromWarehouse', 'toWarehouse')->latest()->paginate(15);
        $warehouses = InvWarehouse::where('seller_id', $seller->id)->active()->get();
        $items      = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();

        return view('seller.logistics.transfers', compact('pageTitle', 'seller', 'store', 'transfers', 'warehouses', 'items'));
    }

    public function transferStore(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|different:to_warehouse_id|exists:inv_warehouses,id',
            'to_warehouse_id'   => 'required|exists:inv_warehouses,id',
            'items'             => 'required|json',
        ]);

        $seller = $this->seller();
        $itemsArray = json_decode($request->items, true);

        if (empty($itemsArray)) {
            return back()->with('error', 'Debe agregar al menos un insumo para transferir.');
        }

        // Validar stock suficiente en origen
        foreach ($itemsArray as $ti) {
            $itemId = $ti['item_id'];
            $qty    = floatval($ti['quantity']);
            
            $sourceStock = InvWarehouseStock::where('warehouse_id', $request->from_warehouse_id)
                ->where('item_id', $itemId)
                ->value('stock') ?? 0;

            if ($sourceStock < $qty) {
                $item = InvItem::find($itemId);
                return back()->with('error', 'Stock insuficiente para ' . $item->name . ' en el almacén de origen (Disponible: ' . $sourceStock . ').');
            }
        }

        DB::transaction(function () use ($request, $seller, $itemsArray) {
            $transfer = InvWarehouseTransfer::create([
                'seller_id'         => $seller->id,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->to_warehouse_id,
                'status'            => 'pending',
                'notes'             => $request->notes,
            ]);

            foreach ($itemsArray as $ti) {
                InvWarehouseTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'item_id'     => $ti['item_id'],
                    'quantity'    => $ti['quantity'],
                ]);
            }
        });

        return back()->with('success', 'Transferencia registrada como pendiente.');
    }

    public function transferStatus(Request $request, $id)
    {
        $transfer = InvWarehouseTransfer::where('seller_id', $this->seller()->id)->with('items.item')->findOrFail($id);
        $newStatus = $request->status;
        $oldStatus = $transfer->status;

        if ($oldStatus === InvWarehouseTransfer::STATUS_RECEIVED || $oldStatus === InvWarehouseTransfer::STATUS_CANCELLED) {
            return back()->with('error', 'Esta transferencia ya ha finalizado.');
        }

        DB::transaction(function () use ($transfer, $newStatus) {
            if ($newStatus === InvWarehouseTransfer::STATUS_SENT) {
                // Descontar stock del origen al enviar
                foreach ($transfer->items as $ti) {
                    $wStockSrc = InvWarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('item_id', $ti->item_id)
                        ->first();
                    if ($wStockSrc) {
                        $wStockSrc->decrement('stock', $ti->quantity);
                    }
                    
                    // Kardex Salida Origen
                    InvKardex::create([
                        'seller_id'      => $transfer->seller_id,
                        'item_id'        => $ti->item_id,
                        'warehouse_id'   => $transfer->from_warehouse_id,
                        'type'           => 'salida',
                        'reference_type' => 'transfer_sent',
                        'reference_id'   => $transfer->id,
                        'quantity'       => -$ti->quantity,
                        'unit_cost'      => $ti->item->cost,
                        'total_cost'     => $ti->quantity * $ti->item->cost,
                        'balance_stock'  => $ti->item->stock - $ti->quantity, // Ajuste rápido
                        'description'    => "Envío por transferencia #" . $transfer->id,
                    ]);
                }
                $transfer->update(['status' => 'sent', 'sent_at' => now()]);

            } elseif ($newStatus === InvWarehouseTransfer::STATUS_RECEIVED) {
                // Agregar stock al destino al recibir
                foreach ($transfer->items as $ti) {
                    $wStockDst = InvWarehouseStock::firstOrCreate(
                        ['warehouse_id' => $transfer->to_warehouse_id, 'item_id' => $ti->item_id],
                        ['stock' => 0]
                    );
                    $wStockDst->increment('stock', $ti->quantity);

                    // Kardex Entrada Destino
                    InvKardex::create([
                        'seller_id'      => $transfer->seller_id,
                        'item_id'        => $ti->item_id,
                        'warehouse_id'   => $transfer->to_warehouse_id,
                        'type'           => 'entrada',
                        'reference_type' => 'transfer_received',
                        'reference_id'   => $transfer->id,
                        'quantity'       => $ti->quantity,
                        'unit_cost'      => $ti->item->cost,
                        'total_cost'     => $ti->quantity * $ti->item->cost,
                        'balance_stock'  => $ti->item->stock, // Global queda igual
                        'description'    => "Recepción por transferencia #" . $transfer->id,
                    ]);
                }
                $transfer->update(['status' => 'received', 'received_at' => now()]);

            } elseif ($newStatus === InvWarehouseTransfer::STATUS_CANCELLED) {
                // Si ya se había enviado, hay que devolver al origen
                if ($transfer->status === 'sent') {
                    foreach ($transfer->items as $ti) {
                        $wStockSrc = InvWarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                            ->where('item_id', $ti->item_id)
                            ->first();
                        if ($wStockSrc) {
                            $wStockSrc->increment('stock', $ti->quantity);
                        }
                    }
                }
                $transfer->update(['status' => 'cancelled']);
            }
        });

        return back()->with('success', 'Transferencia actualizada correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTES DE COSTOS Y VALORIZACIÓN
    // ─────────────────────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $seller    = $this->seller();
        $store     = $this->store();
        $pageTitle = 'Reportes de Costos y Valorización';

        $items = InvItem::where('seller_id', $seller->id)->orderBy('name')->get();
        
        // 1. Valorización total del Inventario
        $valuation = $items->sum(fn($i) => $i->stock * $i->cost);
        
        // 2. Variación de precios promedio de compras por período
        $compras = DB::table('inv_purchase_items')
            ->join('inv_purchases', 'inv_purchase_items.purchase_id', '=', 'inv_purchases.id')
            ->join('inv_items', 'inv_purchase_items.item_id', '=', 'inv_items.id')
            ->where('inv_purchases.seller_id', $seller->id)
            ->selectRaw('inv_items.name, AVG(inv_purchase_items.unit_cost) as avg_cost, DATE_FORMAT(inv_purchases.document_date, "%Y-%m") as month')
            ->groupBy('inv_items.name', 'month')
            ->orderBy('month', 'desc')
            ->get();

        return view('seller.logistics.reports', compact('pageTitle', 'seller', 'store', 'items', 'valuation', 'compras'));
    }
}
