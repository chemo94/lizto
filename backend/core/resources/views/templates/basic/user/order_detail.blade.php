@extends('Template::layouts.frontend')
@section('content')
<style>
.upanel{padding:100px 0 60px;min-height:100vh;background:#f8fdf8}
.upanel .container{max-width:900px}
.upanel-back{display:inline-flex;align-items:center;gap:6px;color:#16a34a;font-weight:700;font-size:13px;text-decoration:none;margin-bottom:20px}
.upanel-back:hover{color:#15803d}
.upanel-head{margin-bottom:24px}
.upanel-head h1{font-size:26px;font-weight:800;color:#1a2e1a;margin:0 0 4px}
.upanel-head p{color:#68736c;margin:0;font-size:14px}
.upanel-card{background:#fff;border:1px solid #e0eee2;border-radius:16px;padding:24px;margin-bottom:20px}
.upanel-card h3{font-size:16px;font-weight:800;color:#1a2e1a;margin:0 0 16px;display:flex;align-items:center;gap:8px}
.upanel-card h3 i{color:#16a34a}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase}
.status-pending{background:#fef3c7;color:#92400e}
.status-confirmed{background:#dbeafe;color:#1e40af}
.status-preparing{background:#fef3c7;color:#92400e}
.status-on_the_way{background:#dcfce7;color:#166534}
.status-delivered{background:#d1fae5;color:#065f46}
.status-cancelled{background:#fee2e2;color:#991b1b}
.order-map{width:100%;height:350px;border-radius:12px;border:1px solid #e0eee2;margin-bottom:16px}
.order-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.order-info-item{padding:14px;background:#f8fdf8;border-radius:12px}
.order-info-item label{font-size:11px;font-weight:700;color:#68736c;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:4px}
.order-info-item span{font-size:14px;color:#1a2e1a;font-weight:600}
.order-items{list-style:0;padding:0;margin:0}
.order-items li{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f5f0;font-size:14px}
.order-items li:last-child{border:0}
.order-items .item-name{color:#1a2e1a;font-weight:600}
.order-items .item-detail{color:#68736c;font-size:12px}
.order-items .item-price{font-weight:700;color:#1a2e1a}
.order-totals{border-top:2px solid #e0eee2;padding-top:12px;margin-top:8px}
.order-totals div{display:flex;justify-content:space-between;padding:4px 0;font-size:14px}
.order-totals .total-row{font-weight:800;font-size:16px;color:#16a34a;padding-top:8px;border-top:1px solid #e0eee2;margin-top:4px}
.driver-card{display:flex;align-items:center;gap:14px;padding:14px;background:#f0fdf4;border-radius:12px}
.driver-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;display:grid;place-items:center;font-size:20px;overflow:hidden}
.driver-avatar img{width:100%;height:100%;object-fit:cover}
.driver-info strong{font-size:14px;color:#1a2e1a;display:block}
.driver-info small{color:#68736c;font-size:12px}
.no-driver{text-align:center;padding:20px;color:#68736c;font-size:13px}
.no-driver i{font-size:32px;color:#d1d5db;display:block;margin-bottom:8px}
.legend{display:flex;gap:16px;flex-wrap:wrap;margin-top:10px}
.legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:#374151}
.legend-dot{width:12px;height:12px;border-radius:50%}
.dot-store{background:#ea4335}
.dot-user{background:#4285f4}
.dot-driver{background:#16a34a}
</style>

<div class="upanel">
    <div class="container">
        <a href="{{ route('user.dashboard') }}" class="upanel-back"><i class="las la-arrow-left"></i> Volver al Panel</a>

        <div class="upanel-head">
            <h1>Pedido {{ $order->order_no }}</h1>
            <p>{{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>

        <!-- Status -->
        <div class="upanel-card">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                <div>
                    <span class="status-badge status-{{ $order->status }}">
                        @if($order->status === 'pending') <i class="las la-clock"></i>
                        @elseif(in_array($order->status, ['confirmed','preparing'])) <i class="las la-utensils"></i>
                        @elseif($order->status === 'on_the_way') <i class="las la-truck"></i>
                        @elseif($order->status === 'delivered') <i class="las la-check-circle"></i>
                        @elseif($order->status === 'cancelled') <i class="las la-times-circle"></i>
                        @endif
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                </div>
                <div style="text-align:right">
                    <small style="color:#68736c">Total</small><br>
                    <strong style="font-size:20px;color:#16a34a">S/ {{ number_format($order->total, 2) }}</strong>
                </div>
            </div>
        </div>

        <!-- Map -->
        @if($order->delivery_lat && $order->delivery_lng && $order->store?->latitude && $order->store?->longitude)
        <div class="upanel-card">
            <h3><i class="las la-map-marked-alt"></i> Ubicación del Pedido</h3>
            <div id="order-map" class="order-map"></div>
            <div class="legend">
                <div class="legend-item"><div class="legend-dot dot-store"></div> Tienda</div>
                <div class="legend-item"><div class="legend-dot dot-user"></div> Tu ubicación</div>
                @if($order->driver)
                <div class="legend-item"><div class="legend-dot dot-driver"></div> Repartidor</div>
                @endif
            </div>
        </div>
        @endif

        <!-- Driver -->
        <div class="upanel-card">
            <h3><i class="las la-motorcycle"></i> Repartidor</h3>
            @if($order->driver)
            <div class="driver-card">
                <div class="driver-avatar">
                    @if($order->driver->image)
                        <img src="{{ $order->driver->imageSrc }}" alt="{{ $order->driver->fullname }}">
                    @else
                        <i class="las la-user"></i>
                    @endif
                </div>
                <div class="driver-info">
                    <strong>{{ $order->driver->fullname }}</strong>
                    <small><i class="las la-phone"></i> {{ $order->driver->dial_code }}{{ $order->driver->mobile }}</small>
                </div>
            </div>
            @else
            <div class="no-driver">
                <i class="las la-motorcycle"></i>
                <p>Sin repartidor asignado aún</p>
            </div>
            @endif
        </div>

        <!-- Order Info -->
        <div class="upanel-card">
            <h3><i class="las la-info-circle"></i> Detalles del Pedido</h3>
            <div class="order-info-grid">
                <div class="order-info-item">
                    <label>Tienda</label>
                    <span>{{ $order->store?->name ?? 'N/A' }}</span>
                </div>
                <div class="order-info-item">
                    <label>Método de Pago</label>
                    <span>{{ $order->payment_method_name ?? 'Efectivo' }}</span>
                </div>
                <div class="order-info-item">
                    <label>Dirección de Entrega</label>
                    <span>{{ $order->delivery_address }}</span>
                </div>
                <div class="order-info-item">
                    <label>Contacto</label>
                    <span>{{ $order->contact_name }} — {{ $order->contact_phone }}</span>
                </div>
            </div>
            @if($order->notes)
            <div style="margin-top:12px;padding:12px;background:#fffbeb;border-radius:10px;font-size:13px;color:#92400e">
                <strong><i class="las la-sticky-note"></i> Notas:</strong> {{ $order->notes }}
            </div>
            @endif
        </div>

        <!-- Items -->
        <div class="upanel-card">
            <h3><i class="las la-shopping-bag"></i> Productos</h3>
            <ul class="order-items">
                @foreach($order->items as $item)
                <li>
                    <div>
                        <div class="item-name">{{ $item->product_name }}</div>
                        <div class="item-detail">
                            Cant: {{ $item->quantity }}
                            @if($item->variation) &middot; {{ $item->variation->variation_name }} @endif
                            @foreach($item->addons as $a) &middot; +{{ $a->addon_name }} @endforeach
                        </div>
                    </div>
                    <div class="item-price">S/ {{ number_format($item->total_price, 2) }}</div>
                </li>
                @endforeach
            </ul>
            <div class="order-totals">
                <div><span>Subtotal</span><span>S/ {{ number_format($order->subtotal, 2) }}</span></div>
                <div><span>Delivery</span><span>S/ {{ number_format($order->delivery_fee, 2) }}</span></div>
                @if($order->tip > 0)
                <div><span>Propina</span><span>S/ {{ number_format($order->tip, 2) }}</span></div>
                @endif
                @if($order->discount > 0)
                <div><span>Descuento</span><span>-S/ {{ number_format($order->discount, 2) }}</span></div>
                @endif
                <div class="total-row"><span>Total</span><span>S/ {{ number_format($order->total, 2) }}</span></div>
            </div>
        </div>
    </div>
</div>

@if($order->delivery_lat && $order->delivery_lng && $order->store?->latitude && $order->store?->longitude)
@if(gs('google_maps_api'))
<script>
function initOrderMap() {
    var storeLat = {{ $order->store->latitude }};
    var storeLng = {{ $order->store->longitude }};
    var userLat  = {{ $order->delivery_lat }};
    var userLng  = {{ $order->delivery_lng }};

    var map = new google.maps.Map(document.getElementById('order-map'), {
        zoom: 13,
        center: { lat: (storeLat + userLat) / 2, lng: (storeLng + userLng) / 2 },
        styles: [
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] }
        ]
    });

    // Store marker
    new google.maps.Marker({
        position: { lat: storeLat, lng: storeLng },
        map: map,
        title: '{{ addslashes($order->store->name) }}',
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png' }
    });

    // User marker
    new google.maps.Marker({
        position: { lat: userLat, lng: userLng },
        map: map,
        title: 'Tu ubicación',
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png' }
    });

    // Driver marker (if assigned)
    @if($order->driver && $order->driver->current_lat && $order->driver->current_lot)
    var driverMarker = new google.maps.Marker({
        position: { lat: {{ $order->driver->current_lat }}, lng: {{ $order->driver->current_lot }} },
        map: map,
        title: '{{ addslashes($order->driver->fullname) }}',
        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' }
    });
    @endif

    // Fit bounds
    var bounds = new google.maps.LatLngBounds();
    bounds.extend(new google.maps.LatLng(storeLat, storeLng));
    bounds.extend(new google.maps.LatLng(userLat, userLng));
    @if($order->driver && $order->driver->current_lat && $order->driver->current_lot)
    bounds.extend(new google.maps.LatLng({{ $order->driver->current_lat }}, {{ $order->driver->current_lot }}));
    @endif
    map.fitBounds(bounds);

    // Real-time driver location via polling
    @if($order->driver_id && !in_array($order->status, ['delivered', 'cancelled']))
    var driverMarkerRef = driverMarker || null;
    setInterval(function() {
        fetch('/user/order/{{ $order->id }}/driver-location', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.lat && data.lng) {
                var pos = { lat: parseFloat(data.lat), lng: parseFloat(data.lng) };
                if (driverMarkerRef) {
                    driverMarkerRef.setPosition(pos);
                } else {
                    driverMarkerRef = new google.maps.Marker({
                        position: pos,
                        map: map,
                        title: '{{ addslashes($order->driver->fullname ?? "Repartidor") }}',
                        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' }
                    });
                }
            }
        }).catch(function(){});
    }, 5000);
    @endif
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&callback=initOrderMap" async defer></script>
@else
<div style="padding:20px;text-align:center;color:#68736c;font-size:13px">
    <i class="las la-map" style="font-size:32px;color:#d1d5db;display:block;margin-bottom:8px"></i>
    Google Maps no configurado
</div>
@endif
@endif
@endsection
