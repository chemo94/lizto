@extends('admin.layouts.app')

@push('style')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    /* ═════════════════════════════════════════════════════════════
       Sneat Theme Refined Design Tokens for Order Detail
       ═════════════════════════════════════════════════════════════ */
    .sneat-order-header {
        background: #ffffff !important;
        border-radius: 12px !important;
        border: 1px solid #d9dee3 !important;
        box-shadow: 0 2px 10px rgba(67, 89, 113, 0.08) !important;
        margin-bottom: 1.25rem !important;
        padding: 1.2rem 1.5rem !important;
    }
    .sneat-order-title {
        color: #2b2c40 !important;
        font-weight: 700 !important;
        font-size: 1.35rem !important;
        margin: 0 !important;
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }
    .sneat-order-meta {
        color: #697a8d !important;
        font-size: 0.86rem !important;
        margin-top: 0.35rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .sneat-order-meta span {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    /* Sneat Stepper Progress */
    .order-stepper-card {
        background: #ffffff !important;
        border: 1px solid #e0e4e8 !important;
        border-radius: 10px !important;
        box-shadow: 0 2px 6px rgba(67, 89, 113, 0.05) !important;
        margin-bottom: 1.25rem !important;
        padding: 1.25rem 1.5rem !important;
    }
    .stepper-track {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        margin-top: 0.5rem;
    }
    .stepper-track::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 5%;
        right: 5%;
        height: 3px;
        background: #e7eaf0;
        z-index: 1;
    }
    .stepper-progress-bar {
        position: absolute;
        top: 20px;
        left: 5%;
        height: 3px;
        background: #696cff;
        z-index: 2;
        transition: width 0.3s ease;
    }
    .stepper-step {
        position: relative;
        z-index: 3;
        text-align: center;
        flex: 1;
    }
    .step-icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #f8f9fa;
        border: 3px solid #e7eaf0;
        color: #8592a3;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.4rem;
        font-size: 1.2rem;
        transition: all 0.2s ease;
    }
    .stepper-step.completed .step-icon-wrap {
        background: #696cff;
        border-color: #696cff;
        color: #ffffff;
        box-shadow: 0 3px 8px rgba(105, 108, 255, 0.4);
    }
    .stepper-step.active .step-icon-wrap {
        background: #ffffff;
        border-color: #696cff;
        color: #696cff;
        box-shadow: 0 0 0 4px rgba(105, 108, 255, 0.18);
    }
    .step-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #8592a3;
    }
    .stepper-step.completed .step-label,
    .stepper-step.active .step-label {
        color: #2b2c40;
        font-weight: 700;
    }

    /* Cards */
    .sneat-card {
        background: #ffffff !important;
        border-radius: 10px !important;
        border: 1px solid #e0e4e8 !important;
        box-shadow: 0 2px 6px rgba(67, 89, 113, 0.05) !important;
        margin-bottom: 1.25rem !important;
        overflow: hidden;
    }
    .sneat-card-header {
        padding: 1rem 1.35rem !important;
        border-bottom: 1px solid #eceef1 !important;
        background: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .sneat-card-header h6 {
        margin: 0;
        font-size: 0.96rem !important;
        font-weight: 700 !important;
        color: #384551 !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .sneat-card-header h6 i {
        color: #696cff !important;
        font-size: 1.15rem;
    }
    .sneat-card-body {
        padding: 1.25rem 1.35rem !important;
    }

    /* Badges */
    .badge-sneat {
        font-size: 0.76rem !important;
        font-weight: 700 !important;
        padding: 0.35rem 0.7rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
        line-height: 1.2 !important;
    }
    .badge-sneat-primary { background-color: #ecebff !important; color: #5f61e6 !important; border: 1px solid rgba(105, 108, 255, 0.3) !important; }
    .badge-sneat-success { background-color: #e8fadf !important; color: #2e7d32 !important; border: 1px solid rgba(46, 125, 50, 0.3) !important; }
    .badge-sneat-warning { background-color: #fff4e5 !important; color: #d85a00 !important; border: 1px solid rgba(216, 90, 0, 0.3) !important; }
    .badge-sneat-danger  { background-color: #ffebee !important; color: #c62828 !important; border: 1px solid rgba(198, 40, 40, 0.3) !important; }
    .badge-sneat-info    { background-color: #e1f5fe !important; color: #0277bd !important; border: 1px solid rgba(2, 119, 189, 0.3) !important; }
    .badge-sneat-neutral { background-color: #f5f5f9 !important; color: #566a7f !important; border: 1px solid #d9dee3 !important; }

    /* Tables */
    .sneat-table {
        width: 100%;
        margin-bottom: 0;
        vertical-align: middle;
    }
    .sneat-table th {
        background-color: #f8f9fb;
        color: #566a7f;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #e7eaf0;
    }
    .sneat-table td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #eceef1;
        color: #435971;
        font-size: 0.88rem;
    }
    .sneat-table tr:last-child td {
        border-bottom: none;
    }

    /* Product Thumbnail */
    .order-item-img {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #e0e4e8;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #696cff;
        flex-shrink: 0;
    }

    /* Financial Summary */
    .financial-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        color: #566a7f;
        font-size: 0.88rem;
        border-bottom: 1px dashed #eceef1;
    }
    .financial-row:last-child {
        border-bottom: none;
    }
    .financial-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 0 0.2rem;
        border-top: 2px solid #e7eaf0;
        color: #2b2c40;
        font-size: 1.15rem;
        font-weight: 800;
    }

    /* Form Inputs */
    .form-label-sneat {
        color: #566a7f !important;
        font-weight: 600 !important;
        font-size: 0.84rem !important;
        margin-bottom: 0.35rem !important;
        display: block;
    }
    .form-select-sneat {
        border: 1px solid #d9dee3 !important;
        color: #435971 !important;
        border-radius: 6px !important;
        padding: 0.52rem 0.85rem !important;
        font-size: 0.9rem !important;
        background-color: #fff !important;
        width: 100%;
    }
    .form-select-sneat:focus {
        border-color: #696cff !important;
        box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.14) !important;
        outline: none !important;
    }

    /* Buttons */
    .btn-sneat-submit {
        background: #696cff !important;
        background-color: #696cff !important;
        border: 1px solid #696cff !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        padding: 0.55rem 1.25rem !important;
        border-radius: 6px !important;
        box-shadow: 0 3px 10px rgba(105, 108, 255, 0.4) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        cursor: pointer !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
    }
    .btn-sneat-submit:hover {
        background: #5f61e6 !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(105, 108, 255, 0.55) !important;
    }
    .btn-sneat-submit i, .btn-sneat-submit span { color: #ffffff !important; }

    .btn-sneat-cancel {
        background-color: #ffffff !important;
        border: 1px solid #d9dee3 !important;
        color: #566a7f !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        padding: 0.55rem 1.15rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.4rem !important;
        text-decoration: none !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
    }
    .btn-sneat-cancel:hover {
        background-color: #f5f5f9 !important;
        border-color: #b4bdc6 !important;
        color: #384551 !important;
    }

    /* Driver Avatar */
    .driver-avatar-box {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: #e8fadf;
        color: #2e7d32;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        font-weight: 700;
        border: 2px solid #2e7d32;
        overflow: hidden;
        flex-shrink: 0;
    }
    .driver-avatar-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
@endpush

@section('panel')
@php
    $statusMap = [
        'pending'   => ['label' => 'Pendiente',   'badge' => 'badge-sneat-warning', 'icon' => 'la-clock'],
        'confirmed' => ['label' => 'Confirmado',  'badge' => 'badge-sneat-info',    'icon' => 'la-check'],
        'preparing' => ['label' => 'Preparando',  'badge' => 'badge-sneat-primary', 'icon' => 'la-utensils'],
        'ready'     => ['label' => 'Listo',       'badge' => 'badge-sneat-info',    'icon' => 'la-box'],
        'on_way'    => ['label' => 'En Camino',   'badge' => 'badge-sneat-primary', 'icon' => 'la-motorcycle'],
        'delivered' => ['label' => 'Entregado',   'badge' => 'badge-sneat-success', 'icon' => 'la-check-double'],
        'cancelled' => ['label' => 'Cancelado',   'badge' => 'badge-sneat-danger',  'icon' => 'la-times-circle'],
    ];
    $currentStatus = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'badge' => 'badge-sneat-neutral', 'icon' => 'la-question-circle'];

    // Stepper calculation
    $stepIndices = [
        'pending'   => 1,
        'confirmed' => 2,
        'preparing' => 3,
        'ready'     => 4,
        'on_way'    => 4,
        'delivered' => 5,
        'cancelled' => 0,
    ];
    $currentStep = $stepIndices[$order->status] ?? 1;
    $progressPercent = match($currentStep) {
        1 => 10,
        2 => 32,
        3 => 55,
        4 => 78,
        5 => 100,
        default => 0,
    };
@endphp

<!-- Sneat Order Header Card -->
<div class="sneat-order-header d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h4 class="sneat-order-title">
            <i class="las la-receipt text--primary"></i>
            Pedido #{{ $order->order_no }}
            <span class="badge-sneat {{ $currentStatus['badge'] }}">
                <i class="las {{ $currentStatus['icon'] }}"></i> {{ $currentStatus['label'] }}
            </span>
        </h4>
        <div class="sneat-order-meta">
            <span>
                <i class="las la-calendar text-muted"></i> {{ showDateTime($order->created_at, 'd M Y, h:i A') }}
            </span>
            @if($order->store_id)
            <span>
                <i class="las la-store text-muted"></i>
                <a href="{{ route('admin.delivery.store.detail', $order->store_id) }}" class="text-primary font-weight-bold">
                    {{ $order->store?->name ?? 'Tienda' }}
                </a>
            </span>
            @endif
            @if($order->payment_method)
                <span>
                    <i class="las la-credit-card text-muted"></i> {{ strtoupper($order->payment_method) }}
                </span>
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.delivery.orders') }}" class="btn-sneat-cancel">
            <i class="las la-arrow-left"></i> Todos los Pedidos
        </a>
    </div>
</div>

<!-- Order Stepper (Status Timeline) -->
@if($order->status !== 'cancelled')
<div class="order-stepper-card">
    <div class="stepper-track">
        <div class="stepper-progress-bar" style="width: {{ $progressPercent }}%;"></div>

        <div class="stepper-step {{ $currentStep > 1 ? 'completed' : ($currentStep == 1 ? 'active' : '') }}">
            <div class="step-icon-wrap"><i class="las la-receipt"></i></div>
            <div class="step-label">1. Recibido</div>
        </div>

        <div class="stepper-step {{ $currentStep > 2 ? 'completed' : ($currentStep == 2 ? 'active' : '') }}">
            <div class="step-icon-wrap"><i class="las la-check"></i></div>
            <div class="step-label">2. Confirmado</div>
        </div>

        <div class="stepper-step {{ $currentStep > 3 ? 'completed' : ($currentStep == 3 ? 'active' : '') }}">
            <div class="step-icon-wrap"><i class="las la-utensils"></i></div>
            <div class="step-label">3. Cocina</div>
        </div>

        <div class="stepper-step {{ $currentStep > 4 ? 'completed' : ($currentStep == 4 ? 'active' : '') }}">
            <div class="step-icon-wrap"><i class="las la-motorcycle"></i></div>
            <div class="step-label">4. En Camino</div>
        </div>

        <div class="stepper-step {{ $currentStep >= 5 ? 'completed' : '' }}">
            <div class="step-icon-wrap"><i class="las la-box-open"></i></div>
            <div class="step-label">5. Entregado</div>
        </div>
    </div>
</div>
@else
<div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius: 8px;">
    <i class="las la-exclamation-circle font-24"></i>
    <div>
        <strong>Pedido Cancelado</strong>
        @if($order->cancelled_at)
            el {{ showDateTime($order->cancelled_at, 'd M Y, h:i A') }}
        @endif
    </div>
</div>
@endif

<div class="row">
    <!-- Left Column: Products & Destination (8 cols) -->
    <div class="col-lg-8">
        <!-- Order Items Card -->
        <div class="sneat-card">
            <div class="sneat-card-header">
                <h6><i class="las la-box"></i> Ítems del Pedido ({{ $order->items->count() }})</h6>
                <span class="badge-sneat badge-sneat-primary">S/ {{ number_format($order->total, 2) }}</span>
            </div>
            <div class="table-responsive">
                <table class="sneat-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Precio Unit.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-start gap-3">
                                    @if($item->product && $item->product->image)
                                        <img src="{{ getImage(getFilePath('product') . '/' . $item->product->image) }}" class="order-item-img" alt="{{ $item->product_name }}">
                                    @else
                                        <div class="order-item-img">
                                            <i class="las la-utensils"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div style="font-weight: 700; color: #2b2c40; font-size: 0.94rem;">
                                            {{ $item->product_name }}
                                        </div>
                                        @if($item->variation)
                                            <div class="mt-1">
                                                <span class="badge-sneat badge-sneat-primary" style="font-size:0.72rem; padding:0.2rem 0.5rem;">
                                                    <i class="las la-layer-group"></i> {{ $item->variation->variation_name ?? $item->variation->name }}
                                                    @if((float)($item->variation->variation_price ?? $item->variation->price) > 0)
                                                        (+S/ {{ number_format($item->variation->variation_price ?? $item->variation->price, 2) }})
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                        @if($item->addons && $item->addons->count() > 0)
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                @foreach($item->addons as $a)
                                                    <span class="badge-sneat badge-sneat-neutral" style="font-size:0.7rem; padding:0.15rem 0.45rem;">
                                                        + {{ $a->addon_name ?? $a->name }}
                                                        @if((float)($a->addon_price ?? $a->price) > 0)
                                                            (S/ {{ number_format($a->addon_price ?? $a->price, 2) }})
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge-sneat badge-sneat-neutral" style="font-size:0.85rem; font-weight:800;">
                                    x{{ $item->quantity }}
                                </span>
                            </td>
                            <td class="text-end font-weight-bold" style="color:#566a7f;">
                                S/ {{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="text-end font-weight-bold" style="color:#2b2c40;">
                                S/ {{ number_format($item->total_price, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Financial Summary Box -->
            <div class="sneat-card-body border-top" style="background: #fafbfc;">
                <div class="row justify-content-end">
                    <div class="col-md-7">
                        <div class="financial-row">
                            <span>Subtotal productos:</span>
                            <span class="font-weight-bold">S/ {{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="financial-row">
                            <span>Costo de envío (Delivery):</span>
                            <span class="font-weight-bold">S/ {{ number_format($order->delivery_fee, 2) }}</span>
                        </div>
                        @if((float)$order->discount > 0)
                        <div class="financial-row text-success">
                            <span>Descuento aplicado:</span>
                            <span class="font-weight-bold">- S/ {{ number_format($order->discount, 2) }}</span>
                        </div>
                        @endif
                        @if((float)$order->tip > 0)
                        <div class="financial-row">
                            <span>Propina repartidor:</span>
                            <span class="font-weight-bold">+ S/ {{ number_format($order->tip, 2) }}</span>
                        </div>
                        @endif
                        <div class="financial-total">
                            <span>TOTAL GENERAL:</span>
                            <span class="text-primary" style="font-size: 1.4rem;">S/ {{ number_format($order->total, 2) }}</span>
                        </div>

                        <!-- Payment method tag -->
                        <div class="mt-3 p-2 rounded d-flex align-items-center justify-content-between" style="background:#fff; border:1px solid #e7eaf0;">
                            <div>
                                <span class="text-muted" style="font-size:0.8rem;">Método de Pago:</span>
                                <div style="font-weight:700; color:#2b2c40;">{{ strtoupper($order->payment_method ?? 'Efectivo') }}</div>
                            </div>
                            @if($order->cash_pay_amount)
                                <div class="text-end">
                                    <span class="text-muted" style="font-size:0.8rem;">Paga con:</span>
                                    <div style="font-weight:700; color:#2b2c40;">S/ {{ number_format($order->cash_pay_amount, 2) }}</div>
                                    @if($order->change_amount)
                                        <small class="text-success font-weight-bold">Vuelto: S/ {{ number_format($order->change_amount, 2) }}</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Destination Card -->
        <div class="sneat-card">
            <div class="sneat-card-header">
                <h6><i class="las la-map-marked-alt"></i> Destino de Entrega y Cliente</h6>
                @if($order->contact_phone || $order->user?->mobile)
                    @php
                        $rawPhone = preg_replace('/[^0-9]/', '', $order->contact_phone ?: $order->user?->mobile);
                        if (strlen($rawPhone) == 9 && !str_starts_with($rawPhone, '51')) {
                            $rawPhone = '51' . $rawPhone;
                        }
                    @endphp
                    <a href="https://wa.me/{{ $rawPhone }}?text=Hola%20{{ urlencode($order->contact_name ?: ($order->user?->fullname ?? 'Cliente')) }}%2C%20te%20escribimos%20por%20tu%20pedido%20%23{{ $order->order_no }}"
                       target="_blank" class="badge-sneat badge-sneat-success text-decoration-none">
                        <i class="lab la-whatsapp font-16"></i> Contactar por WhatsApp
                    </a>
                @endif
            </div>
            <div class="sneat-card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label-sneat">Destinatario / Cliente</label>
                        <div style="font-weight: 700; color: #2b2c40; font-size: 1rem;">
                            {{ $order->contact_name ?: ($order->user?->fullname ?? 'Cliente no registrado') }}
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.86rem;">
                            <i class="las la-phone"></i>
                            <a href="tel:{{ $order->contact_phone ?: $order->user?->mobile }}" class="text-dark font-weight-bold">
                                {{ $order->contact_phone ?: ($order->user?->mobile ?? 'Sin teléfono') }}
                            </a>
                        </div>
                        @if($order->user?->email)
                            <div class="text-muted" style="font-size: 0.84rem;">
                                <i class="las la-envelope"></i> {{ $order->user->email }}
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label-sneat">Dirección de Entrega</label>
                        <div style="font-weight: 600; color: #2b2c40; font-size: 0.92rem; line-height: 1.4;">
                            <i class="las la-map-marker text-danger"></i>
                            {{ $order->delivery_address ?: 'Dirección no especificada' }}
                        </div>
                        @if($order->delivery_lat && $order->delivery_lng)
                            <div class="text-muted mt-1" style="font-size: 0.8rem;">
                                Coordenadas: {{ number_format($order->delivery_lat, 5) }}, {{ number_format($order->delivery_lng, 5) }}
                            </div>
                        @endif
                    </div>
                </div>

                @if($order->notes)
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" style="border-radius: 8px; font-size: 0.88rem; background: #fff8eb; border-color: #ffd28c;">
                    <i class="las la-sticky-note" style="font-size: 1.3rem; color: #d85a00; margin-top: 2px;"></i>
                    <div>
                        <strong style="color: #d85a00;">Instrucciones / Notas del Pedido:</strong>
                        <div style="color: #4a3821;">{{ $order->notes }}</div>
                    </div>
                </div>
                @endif

                <!-- Interactive Map if coordinates available -->
                @if($order->delivery_lat && $order->delivery_lng)
                <div class="mt-2">
                    <label class="form-label-sneat">Ubicación en el Mapa</label>
                    <div id="orderMap" style="height: 240px; border-radius: 8px; border: 1px solid #d9dee3;"></div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Status, Driver & Store Info (4 cols) -->
    <div class="col-lg-4">
        <!-- Update Status Card -->
        <div class="sneat-card">
            <div class="sneat-card-header">
                <h6><i class="las la-edit"></i> Actualizar Estado</h6>
            </div>
            <div class="sneat-card-body">
                <form method="POST" action="{{ route('admin.delivery.order.status', $order->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-sneat">Selecciona el nuevo estado</label>
                        <select name="status" class="form-select-sneat">
                            @foreach([
                                'pending'   => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'preparing' => 'En Preparación (Cocina)',
                                'ready'     => 'Listo para Recojo',
                                'on_way'    => 'Repartidor en Camino',
                                'delivered' => 'Entregado con Éxito',
                                'cancelled' => 'Cancelado'
                            ] as $k => $v)
                                <option value="{{ $k }}" {{ $order->status == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-sneat-submit w-100">
                        <i class="las la-sync-alt"></i> Guardar Estado
                    </button>
                </form>

                <div class="mt-3 pt-3 border-top" style="font-size: 0.8rem; color: #8592a3;">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Creado el:</span>
                        <strong class="text-dark">{{ showDateTime($order->created_at, 'd/m/Y h:i A') }}</strong>
                    </div>
                    @if($order->driver_assigned_at)
                    <div class="d-flex justify-content-between mb-1">
                        <span>Repartidor asignado:</span>
                        <strong class="text-dark">{{ showDateTime($order->driver_assigned_at, 'd/m/Y h:i A') }}</strong>
                    </div>
                    @endif
                    @if($order->delivered_at)
                    <div class="d-flex justify-content-between">
                        <span>Entregado el:</span>
                        <strong class="text-success">{{ showDateTime($order->delivered_at, 'd/m/Y h:i A') }}</strong>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Driver Assignment Card -->
        <div class="sneat-card">
            <div class="sneat-card-header">
                <h6><i class="las la-motorcycle"></i> Asignación de Repartidor</h6>
            </div>
            <div class="sneat-card-body">
                @if($order->driver)
                    <div class="d-flex align-items-center gap-3 p-3 rounded mb-3" style="background: #f8f9fa; border: 1px solid #e7eaf0;">
                        <div class="driver-avatar-box">
                            @if($order->driver->image)
                                <img src="{{ getImage(getFilePath('driver') . '/' . $order->driver->image) }}" alt="{{ $order->driver->firstname }}">
                            @else
                                {{ strtoupper(substr($order->driver->firstname, 0, 1) . substr($order->driver->lastname, 0, 1)) }}
                            @endif
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #2b2c40; font-size: 0.94rem;">
                                {{ $order->driver->firstname }} {{ $order->driver->lastname }}
                            </div>
                            <div class="text-muted" style="font-size: 0.84rem;">
                                <i class="las la-phone"></i>
                                <a href="tel:{{ $order->driver->mobile }}" class="text-primary font-weight-bold">
                                    {{ $order->driver->mobile }}
                                </a>
                            </div>
                            <div class="mt-1">
                                <span class="badge-sneat badge-sneat-success" style="font-size: 0.68rem; padding: 0.15rem 0.45rem;">
                                    <i class="las la-check"></i> Asignado
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.85rem; border-radius: 6px;">
                        <i class="las la-exclamation-triangle font-20"></i>
                        <span>Aún no hay repartidor asignado para este pedido.</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.delivery.order.assign', $order->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label-sneat">{{ $order->driver ? 'Reasignar Repartidor' : 'Asignar Repartidor' }}</label>
                        <select name="driver_id" class="form-select-sneat" required>
                            <option value="">-- Seleccionar Repartidor --</option>
                            @foreach($drivers as $d)
                                <option value="{{ $d->id }}" {{ $order->driver_id == $d->id ? 'selected' : '' }}>
                                    {{ $d->firstname }} {{ $d->lastname }} ({{ $d->mobile }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-sneat-submit w-100">
                        <i class="las la-user-check"></i> {{ $order->driver ? 'Reasignar Repartidor' : 'Asignar Repartidor' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Store Info Card -->
        <div class="sneat-card">
            <div class="sneat-card-header">
                <h6><i class="las la-store"></i> Información de la Tienda</h6>
                @if($order->store_id)
                <a href="{{ route('admin.delivery.store.detail', $order->store_id) }}" class="badge-sneat badge-sneat-primary text-decoration-none">
                    Ver Tienda <i class="las la-external-link-alt"></i>
                </a>
                @endif
            </div>
            <div class="sneat-card-body">
                <div style="font-weight: 700; color: #2b2c40; font-size: 1rem;">
                    {{ $order->store?->name }}
                </div>
                <div class="text-muted mt-1" style="font-size: 0.85rem;">
                    <i class="las la-map-marker"></i> {{ $order->store?->address ?: 'Sin dirección registrada' }}
                </div>
                @if($order->store?->phone)
                <div class="text-muted mt-1" style="font-size: 0.85rem;">
                    <i class="las la-phone"></i> {{ $order->store->phone }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('script')
@if($order->delivery_lat && $order->delivery_lng)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const destLat = {{ (float)$order->delivery_lat }};
        const destLng = {{ (float)$order->delivery_lng }};
        const storeLat = {{ (float)($order->store?->latitude ?? 0) }};
        const storeLng = {{ (float)($order->store?->longitude ?? 0) }};

        const map = L.map('orderMap').setView([destLat, destLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Destination Marker
        const destMarker = L.marker([destLat, destLng]).addTo(map)
            .bindPopup('<strong>Destino de Entrega</strong><br>{{ addslashes($order->delivery_address) }}')
            .openPopup();

        // Store Marker if exists
        if (storeLat !== 0 && storeLng !== 0) {
            const storeMarker = L.marker([storeLat, storeLng]).addTo(map)
                .bindPopup('<strong>Tienda:</strong> {{ addslashes($order->store?->name ?? "Tienda") }}');

            const bounds = L.latLngBounds([ [destLat, destLng], [storeLat, storeLng] ]);
            map.fitBounds(bounds, { padding: [40, 40] });
        }
    });
</script>
@endif
@endpush
@endsection
