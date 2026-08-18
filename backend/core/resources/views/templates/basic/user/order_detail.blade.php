@extends('Template::layouts.frontend')

@section('content')
@php
    $statusMap = [
        'pending'          => ['label' => 'Pedido Recibido', 'step' => 1, 'icon' => 'las la-clock', 'color' => '#f59e0b'],
        'confirmed'        => ['label' => 'Tienda Confirmó', 'step' => 2, 'icon' => 'las la-store', 'color' => '#3b82f6'],
        'preparing'        => ['label' => 'En Preparación', 'step' => 3, 'icon' => 'las la-utensils', 'color' => '#8b5cf6'],
        'driver_assigned'  => ['label' => 'Repartidor Asignado', 'step' => 3, 'icon' => 'las la-user-check', 'color' => '#10b981'],
        'on_the_way'       => ['label' => 'En Camino', 'step' => 4, 'icon' => 'las la-motorcycle', 'color' => '#10b981'],
        'delivered'        => ['label' => 'Entregado con Éxito', 'step' => 5, 'icon' => 'las la-check-circle', 'color' => '#059669'],
        'cancelled'        => ['label' => 'Pedido Cancelado', 'step' => 0, 'icon' => 'las la-times-circle', 'color' => '#ef4444'],
    ];

    $curStatus = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'step' => 1, 'icon' => 'las la-clock', 'color' => '#10b981'];
    $step = $curStatus['step'];
@endphp

<main class="lz-order-detail-page">
    <div class="container">
        {{-- Back & Actions --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <a href="{{ route('user.dashboard') }}" class="lz-back-btn">
                <i class="las la-arrow-left"></i> Mis pedidos
            </a>
            @if($order->store)
                <a href="{{ route('delivery.store', $order->store) }}" class="btn btn-outline-success btn-sm rounded-pill fw-bold">
                    <i class="las la-redo"></i> Volver a pedir en esta tienda
                </a>
            @endif
        </div>

        {{-- Top Success Card --}}
        <div class="lz-order-card mb-4 text-center py-4">
            <div class="lz-order-status-icon-wrap" style="background: {{ $curStatus['color'] }}20; color: {{ $curStatus['color'] }};">
                <i class="{{ $curStatus['icon'] }}"></i>
            </div>
            <h1 class="lz-order-headline mt-3 mb-1">¡{{ $curStatus['label'] }}!</h1>
            <p class="text-muted mb-0 font-monospace">Orden #{{ $order->order_no }} · {{ $order->created_at->format('d/m/Y h:i A') }}</p>
            @if($order->status !== 'delivered' && $order->status !== 'cancelled')
                <div class="lz-eta-badge mt-3">
                    <i class="las la-stopwatch"></i> Tiempo estimado de entrega: <strong>25–35 min</strong>
                </div>
            @endif
        </div>

        {{-- Visual Timeline (5 Steps) --}}
        @if($order->status !== 'cancelled')
        <div class="lz-order-card mb-4">
            <h3 class="lz-card-title mb-4"><i class="las la-stream text-success"></i> Estado del Pedido</h3>
            <div class="lz-timeline-steps">
                <div class="lz-t-step {{ $step >= 1 ? 'completed' : '' }} {{ $step == 1 ? 'current' : '' }}">
                    <div class="lz-t-icon"><i class="las la-receipt"></i></div>
                    <span class="lz-t-label">Recibido</span>
                </div>
                <div class="lz-t-line {{ $step >= 2 ? 'completed' : '' }}"></div>

                <div class="lz-t-step {{ $step >= 2 ? 'completed' : '' }} {{ $step == 2 ? 'current' : '' }}">
                    <div class="lz-t-icon"><i class="las la-store"></i></div>
                    <span class="lz-t-label">Confirmado</span>
                </div>
                <div class="lz-t-line {{ $step >= 3 ? 'completed' : '' }}"></div>

                <div class="lz-t-step {{ $step >= 3 ? 'completed' : '' }} {{ $step == 3 ? 'current' : '' }}">
                    <div class="lz-t-icon"><i class="las la-utensils"></i></div>
                    <span class="lz-t-label">Preparando</span>
                </div>
                <div class="lz-t-line {{ $step >= 4 ? 'completed' : '' }}"></div>

                <div class="lz-t-step {{ $step >= 4 ? 'completed' : '' }} {{ $step == 4 ? 'current' : '' }}">
                    <div class="lz-t-icon"><i class="las la-motorcycle"></i></div>
                    <span class="lz-t-label">En camino</span>
                </div>
                <div class="lz-t-line {{ $step >= 5 ? 'completed' : '' }}"></div>

                <div class="lz-t-step {{ $step >= 5 ? 'completed' : '' }} {{ $step == 5 ? 'current' : '' }}">
                    <div class="lz-t-icon"><i class="las la-home"></i></div>
                    <span class="lz-t-label">Entregado</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Tracking Map & Driver Info --}}
        @if($order->delivery_lat && $order->delivery_lng && $order->store?->latitude && $order->store?->longitude)
        <div class="lz-order-card mb-4">
            <h3 class="lz-card-title mb-3"><i class="las la-map-marked-alt text-success"></i> Seguimiento en Mapa</h3>
            <div id="order-map" class="lz-tracking-map"></div>
            <div class="d-flex gap-3 mt-2 flex-wrap" style="font-size:12px;font-weight:600;">
                <span class="d-flex align-items-center gap-1"><span class="lz-dot" style="background:#ea4335"></span> Tienda: {{ $order->store->name }}</span>
                <span class="d-flex align-items-center gap-1"><span class="lz-dot" style="background:#4285f4"></span> Tu ubicación de entrega</span>
                @if($order->driver)
                    <span class="d-flex align-items-center gap-1"><span class="lz-dot" style="background:#10b981"></span> Repartidor: {{ $order->driver->fullname }}</span>
                @endif
            </div>
        </div>
        @endif

        {{-- Repartidor Card --}}
        <div class="lz-order-card mb-4">
            <h3 class="lz-card-title mb-3"><i class="las la-user-astronaut text-success"></i> Repartidor Asignado</h3>
            @if($order->driver)
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 p-3" style="background:var(--lz-surface-muted);border-radius:14px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="lz-driver-avatar">
                        @if($order->driver->image)
                            <img src="{{ $order->driver->imageSrc }}" alt="{{ $order->driver->fullname }}">
                        @else
                            <i class="las la-user"></i>
                        @endif
                    </div>
                    <div>
                        <strong class="d-block text-dark font-weight-bold" style="font-size:15px;">{{ $order->driver->fullname }}</strong>
                        <span class="text-muted small"><i class="las la-motorcycle"></i> Repartidor Lizto</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="tel:{{ $order->driver->dial_code }}{{ $order->driver->mobile }}" class="btn btn-outline-success btn-sm rounded-pill fw-bold px-3">
                        <i class="las la-phone"></i> Llamar
                    </a>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', ($order->driver->dial_code . $order->driver->mobile)) }}" target="_blank" class="btn btn-success btn-sm rounded-pill fw-bold px-3">
                        <i class="lab la-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
            @else
            <div class="text-center py-4" style="background:var(--lz-surface-muted);border-radius:14px;color:var(--lz-text-muted);">
                <i class="las la-motorcycle" style="font-size:36px;color:#cbd5e1;"></i>
                <p class="mb-0 mt-2 font-weight-bold">Asignando al repartidor más cercano...</p>
                <small>Te notificaremos en cuanto acepte tu pedido.</small>
            </div>
            @endif
        </div>

        {{-- Products & Summary --}}
        <div class="lz-order-card mb-4">
            <h3 class="lz-card-title mb-3"><i class="las la-shopping-bag text-success"></i> Detalle de Productos</h3>
            <div class="mb-3">
                @foreach($order->items as $item)
                <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:14px;">
                    <div>
                        <strong class="text-dark">{{ $item->quantity }}x</strong> {{ $item->product_name }}
                    </div>
                    <span class="fw-bold text-dark">S/ {{ number_format($item->total_price, 2) }}</span>
                </div>
                @endforeach
            </div>

            <div class="lz-cart-summary-box pt-2">
                <div class="lz-summary-line">
                    <span>Subtotal</span>
                    <strong>S/ {{ number_format($order->subtotal, 2) }}</strong>
                </div>
                <div class="lz-summary-line">
                    <span>Envío</span>
                    <strong>S/ {{ number_format($order->delivery_fee, 2) }}</strong>
                </div>
                @if($order->tip > 0)
                <div class="lz-summary-line">
                    <span>Propina</span>
                    <strong>S/ {{ number_format($order->tip, 2) }}</strong>
                </div>
                @endif
                @if($order->discount > 0)
                <div class="lz-summary-line text-success">
                    <span>Descuento</span>
                    <strong>-S/ {{ number_format($order->discount, 2) }}</strong>
                </div>
                @endif
                <div class="lz-summary-line lz-summary-total">
                    <span>Total Pagado</span>
                    <strong style="color:var(--lz-primary-dark);font-size:22px;">S/ {{ number_format($order->total, 2) }}</strong>
                </div>
            </div>
        </div>

    </div>
</main>
@endsection

@push('style')
<style>
.lz-order-detail-page {
    padding: 24px 0 60px;
    background: var(--lz-bg);
    min-height: 100vh;
}
.lz-order-card {
    background: var(--lz-surface);
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-lg);
    padding: 24px;
    box-shadow: var(--lz-shadow-sm);
}
.lz-order-status-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}
.lz-order-headline {
    font-size: 24px;
    font-weight: 800;
    color: var(--lz-text);
}
.lz-eta-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--lz-primary-light);
    color: var(--lz-primary-dark);
    padding: 6px 14px;
    border-radius: var(--lz-r-full);
    font-size: 13px;
    font-weight: 600;
}
.lz-card-title {
    font-size: 17px;
    font-weight: 800;
    color: var(--lz-text);
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

/* Timeline */
.lz-timeline-steps {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    padding: 10px 0;
}
.lz-t-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}
.lz-t-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--lz-surface-muted);
    color: var(--lz-text-subtle);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: var(--lz-transition);
    border: 2px solid #fff;
    box-shadow: var(--lz-shadow-xs);
}
.lz-t-step.completed .lz-t-icon {
    background: var(--lz-primary);
    color: #fff;
}
.lz-t-step.current .lz-t-icon {
    background: var(--lz-primary);
    color: #fff;
    box-shadow: 0 0 0 4px var(--lz-primary-glow);
}
.lz-t-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--lz-text-muted);
    margin-top: 6px;
    text-align: center;
}
.lz-t-step.completed .lz-t-label, .lz-t-step.current .lz-t-label {
    color: var(--lz-text);
}
.lz-t-line {
    flex: 1;
    height: 3px;
    background: var(--lz-border);
    margin: 0 8px;
    transform: translateY(-12px);
}
.lz-t-line.completed {
    background: var(--lz-primary);
}

.lz-tracking-map {
    width: 100%;
    height: 280px;
    border-radius: var(--lz-r-md);
    border: 1.5px solid var(--lz-border);
}
.lz-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.lz-driver-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: var(--lz-primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    overflow: hidden;
}
.lz-driver-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
@endpush

@if($order->delivery_lat && $order->delivery_lng && $order->store?->latitude && $order->store?->longitude)
@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&callback=initOrderMap" async defer></script>
@endpush
@push('script')
<script>
function initOrderMap() {
    var storeLat = {{ (float) $order->store->latitude }};
    var storeLng = {{ (float) $order->store->longitude }};
    var userLat  = {{ (float) $order->delivery_lat }};
    var userLng  = {{ (float) $order->delivery_lng }};

    var mapEl = document.getElementById('order-map');
    if (!mapEl) return;

    var map = new google.maps.Map(mapEl, {
        zoom: 14,
        center: { lat: (storeLat + userLat) / 2, lng: (storeLng + userLng) / 2 },
        disableDefaultUI: true,
        zoomControl: true
    });

    new google.maps.Marker({
        position: { lat: storeLat, lng: storeLng },
        map: map,
        title: '{{ addslashes($order->store->name) }}',
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png' }
    });

    new google.maps.Marker({
        position: { lat: userLat, lng: userLng },
        map: map,
        title: 'Tu dirección',
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png' }
    });

    @if($order->driver && $order->driver->current_lat && $order->driver->current_lot)
    var driverMarker = new google.maps.Marker({
        position: { lat: {{ $order->driver->current_lat }}, lng: {{ $order->driver->current_lot }} },
        map: map,
        title: '{{ addslashes($order->driver->fullname) }}',
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' }
    });
    @endif

    var bounds = new google.maps.LatLngBounds();
    bounds.extend(new google.maps.LatLng(storeLat, storeLng));
    bounds.extend(new google.maps.LatLng(userLat, userLng));
    map.fitBounds(bounds);

    @if($order->driver_id && !in_array($order->status, ['delivered', 'cancelled']))
    setInterval(function() {
        fetch('/user/order/{{ $order->id }}/driver-location', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.lat && data.lng) {
                var pos = { lat: parseFloat(data.lat), lng: parseFloat(data.lng) };
                if (typeof driverMarker !== 'undefined' && driverMarker) {
                    driverMarker.setPosition(pos);
                }
            }
        }).catch(function(){});
    }, 5000);
    @endif
}
</script>
@endpush
@endif
@endif
