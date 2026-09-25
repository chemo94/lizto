@extends('admin.layouts.app')

@php
    $statusLabels = [
        'searching_courier'  => 'Buscando repartidor',
        'accepted'           => 'Asignado',
        'on_way_to_pickup'   => 'Camino al recojo',
        'at_pickup'          => 'En el recojo',
        'on_way_to_delivery' => 'Camino a entrega',
        'delivered'          => 'Entregado',
        'cancelled'          => 'Cancelado',
    ];
    $steps = ['accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery', 'delivered'];
    $stepLabels = ['Asignado', 'Al recojo', 'En recojo', 'A entrega', 'Entregado'];
    $currentIdx = array_search($favor->status, $steps);
    $statusColors = [
        'delivered' => '#16a34a', 'cancelled' => '#dc2626', 'searching_courier' => '#d97706',
        'accepted' => '#d97706', 'on_way_to_pickup' => '#2563eb', 'at_pickup' => '#2563eb',
        'on_way_to_delivery' => '#7c3aed',
    ];
    $stColor = $statusColors[$favor->status] ?? '#64748b';
@endphp

@push('style')
<style>
    .fav-hero {
        background: linear-gradient(135deg, #111827, #1f2937);
        border-radius: 16px; color: #fff; padding: 20px 24px; margin-bottom: 18px;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
    }
    .fav-hero h4 { margin: 0; font-weight: 800; font-size: 20px; color: #fff; }
    .fav-hero .sub { color: rgba(255,255,255,.6); font-size: 13px; margin-top: 2px; }
    .fav-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px;
        border-radius: 999px; font-size: 12.5px; font-weight: 700;
    }
    .fav-card { border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; margin-bottom: 16px; overflow: hidden; }
    .fav-card-head { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px; color: #111827; }
    .fav-card-head i { color: #16a34a; }
    .fav-card-body { padding: 16px 18px; }
    .fav-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; font-size: 13.5px; border-bottom: 1px dashed #f1f5f9; }
    .fav-row:last-child { border-bottom: none; }
    .fav-row .lbl { color: #6b7280; font-weight: 500; white-space: nowrap; }
    .fav-row .val { color: #111827; font-weight: 600; text-align: right; }
    #favor-map { width: 100%; height: 460px; border-radius: 12px; background: #e5e7eb; }
    .map-legend { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 12px; font-size: 12.5px; color: #475569; }
    .map-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .lg-dot { width: 12px; height: 12px; border-radius: 50%; }
    .pin-box {
        background: linear-gradient(135deg, rgba(139,92,246,.08), rgba(139,92,246,.02));
        border: 1px solid rgba(139,92,246,.25); border-radius: 14px; padding: 16px 18px; text-align: center; margin-bottom: 16px;
    }
    .pin-box .code { font-size: 34px; font-weight: 900; letter-spacing: 10px; color: #7c3aed; margin: 6px 0; }
    .timeline { display: flex; align-items: center; margin-bottom: 16px; }
    .timeline .tl-step { flex: 1; text-align: center; position: relative; }
    .timeline .tl-dot { width: 26px; height: 26px; border-radius: 50%; background: #e5e7eb; color: #94a3b8; margin: 0 auto 6px; display: grid; place-items: center; font-size: 13px; position: relative; z-index: 2; }
    .timeline .tl-step.done .tl-dot { background: #16a34a; color: #fff; }
    .timeline .tl-step.current .tl-dot { background: #2563eb; color: #fff; box-shadow: 0 0 0 4px rgba(37,99,235,.2); }
    .timeline .tl-step:not(:first-child)::before { content: ''; position: absolute; top: 13px; left: -50%; width: 100%; height: 3px; background: #e5e7eb; z-index: 1; }
    .timeline .tl-step.done:not(:first-child)::before, .timeline .tl-step.current:not(:first-child)::before { background: #16a34a; }
    .timeline .tl-label { font-size: 11px; color: #6b7280; font-weight: 600; }
    .eta-pill { display: inline-flex; align-items: center; gap: 6px; background: rgba(34,197,94,.08); color: #16a34a; border: 1px solid rgba(34,197,94,.2); padding: 6px 12px; border-radius: 10px; font-size: 12.5px; font-weight: 700; }
    .fav-form-note { color: #64748b; font-size: 11.5px; line-height: 1.4; margin: -6px 0 12px; }
    .courier-route-marker { width: 52px; height: 52px; transition: transform .22s linear; transform-origin: 50% 50%; }
    .courier-route-marker img { display: block; width: 52px; height: 52px; object-fit: contain; filter: drop-shadow(0 3px 4px rgba(15,23,42,.35)); }
</style>
@endpush

@section('panel')

<div class="fav-hero">
    <div>
        <h4><i class="las la-motorcycle"></i> Favor #{{ $favor->order_no }}</h4>
        <div class="sub">
            Pedido: {{ ($favor->requested_at ?? $favor->created_at)?->format('d M Y, H:i') }}
            @if($favor->requested_at && $favor->requested_at->ne($favor->created_at))
                · Registrado: {{ $favor->created_at?->format('d M Y, H:i') }}
            @endif
            · {{ $favor->type == 'buy' ? 'Compra' : 'Envío' }}
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <button type="button" class="btn btn-sm btn-light" onclick="shareTrackingLink()"><i class="las la-share-alt"></i> Compartir seguimiento</button>
        <span class="fav-chip" style="background:rgba(255,255,255,.12);color:#fff;">S/ {{ number_format($favor->total, 2) }}</span>
        <span class="fav-chip" style="background:{{ $stColor }}22;color:{{ $stColor }};border:1px solid {{ $stColor }}55;">
            <i class="las la-circle" style="font-size:9px;"></i> {{ $statusLabels[$favor->status] ?? $favor->status }}
        </span>
    </div>
</div>

<div class="row g-3">
    <!-- ── LEFT: MAP + TRACKING ── -->
    <div class="col-lg-8">
        <div class="fav-card">
            <div class="fav-card-head"><i class="las la-map-marked-alt"></i> Seguimiento en tiempo real</div>
            <div class="fav-card-body">
                @if(!in_array($favor->status, ['cancelled']))
                <div class="timeline">
                    @foreach($steps as $i => $step)
                    <div class="tl-step {{ $currentIdx !== false && $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'current' : '') }}">
                        <div class="tl-dot">
                            @if($currentIdx !== false && $i < $currentIdx)<i class="las la-check"></i>@else{{ $i + 1 }}@endif
                        </div>
                        <div class="tl-label">{{ $stepLabels[$i] }}</div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($favor->pickup_lat && $favor->delivery_lat && gs('google_maps_api'))
                    <div id="favor-map"></div>
                    <div class="map-legend">
                        <span><span class="lg-dot" style="background:#22c55e;"></span> Recojo</span>
                        <span><span class="lg-dot" style="background:#ef4444;"></span> Entrega</span>
                        <span><span class="lg-dot" style="background:#3b82f6;"></span> Repartidor (en vivo)</span>
                        <span id="route-eta-wrap" style="display:none;"><i class="las la-route"></i> <strong id="route-eta" style="color:#16a34a;"></strong></span>
                    </div>
                @else
                    <div style="padding:40px;text-align:center;color:#94a3b8;background:#f8fafc;border-radius:12px;">
                        <i class="las la-map" style="font-size:40px;opacity:.4;display:block;margin-bottom:8px;"></i>
                        @if(!gs('google_maps_api')) Configura la API de Google Maps para ver el mapa.
                        @else No hay coordenadas de recojo/entrega para este favor. @endif
                    </div>
                @endif
            </div>
        </div>

        @if($favor->bids->count())
        <div class="fav-card">
            <div class="fav-card-head"><i class="las la-hand-holding-usd"></i> Ofertas ({{ $favor->bids->count() }})</div>
            <div class="fav-card-body" style="padding:0;">
                <table class="table table-sm mb-0">
                    <thead><tr><th class="ps-3">Repartidor</th><th>Monto</th><th>Mensaje</th><th class="pe-3">Estado</th></tr></thead>
                    <tbody>
                        @foreach($favor->bids as $b)
                        <tr>
                            <td class="ps-3">{{ $b->courier?->fullname }}</td>
                            <td>S/ {{ number_format($b->bid_amount, 2) }}</td>
                            <td>{{ $b->message }}</td>
                            <td class="pe-3">{{ $b->status }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- ── RIGHT: DETAILS + PIN + ACTIONS ── -->
    <div class="col-lg-4">

        @if($favor->pin_code)
        <div class="pin-box">
            <div style="font-size:12px;color:#7c3aed;font-weight:700;text-transform:uppercase;letter-spacing:1px;"><i class="las la-lock"></i> PIN de entrega</div>
            <div class="code">{{ $favor->pin_code }}</div>
            <div style="font-size:11.5px;color:#6b7280;">El cliente debe darlo al repartidor al recibir.</div>
        </div>
        @endif

        <div class="fav-card">
            <div class="fav-card-head"><i class="las la-info-circle"></i> Detalles del pedido</div>
            <div class="fav-card-body">
                <div class="fav-row"><span class="lbl">Cliente</span><span class="val">{{ $favor->user?->fullname ?? $favor->recipient_name ?? '—' }}</span></div>
                @if($favor->recipient_phone)
                <div class="fav-row"><span class="lbl">Teléfono</span><span class="val">{{ $favor->recipient_phone }}</span></div>
                @endif
                @if($favor->description)
                <div class="fav-row"><span class="lbl">Descripción</span><span class="val">{{ $favor->description }}</span></div>
                @endif
                <div class="fav-row"><span class="lbl">Recogida</span><span class="val">{{ $favor->pickup_address ?? '—' }}</span></div>
                <div class="fav-row"><span class="lbl">Entrega</span><span class="val">{{ $favor->delivery_address ?? '—' }}</span></div>
                @if($favor->shipment_type)
                <div class="fav-row"><span class="lbl">Tipo de envío</span><span class="val">{{ ucfirst($favor->shipment_type) }}</span></div>
                @endif
                @if($favor->evidence_type)
                <div class="fav-row"><span class="lbl">Evidencia</span><span class="val">{{ ['photo'=>'Foto','pin'=>'PIN','both'=>'Foto + PIN'][$favor->evidence_type] ?? $favor->evidence_type }}</span></div>
                @endif
                <div class="fav-row"><span class="lbl">Pago</span><span class="val">{{ $favor->payer_type === 'recipient' ? 'Cobra al destinatario' : ($favor->payment_method_name ?? 'Remitente') }}</span></div>
                @if($favor->payer_type === 'recipient' && $favor->cod_amount)
                <div class="fav-row"><span class="lbl">Monto a cobrar</span><span class="val" style="color:#dc2626;">S/ {{ number_format($favor->cod_amount, 2) }}</span></div>
                @endif
                <div class="fav-row"><span class="lbl">Fecha real del pedido</span><span class="val">{{ ($favor->requested_at ?? $favor->created_at)?->format('d/m/Y H:i') }}</span></div>
                <div class="fav-row"><span class="lbl">Tarifa delivery</span><span class="val">S/ {{ number_format($favor->delivery_fee ?? 0, 2) }}</span></div>
                <div class="fav-row"><span class="lbl">Total</span><span class="val" style="color:#16a34a;font-size:15px;">S/ {{ number_format($favor->total, 2) }}</span></div>
                @if($favor->is_express)
                <div class="fav-row"><span class="lbl">Prioridad</span><span class="val" style="color:#d97706;">⚡ Express</span></div>
                @endif
            </div>
        </div>

        <div class="fav-card">
            <div class="fav-card-head"><i class="las la-user"></i> Repartidor</div>
            <div class="fav-card-body">
                @if($favor->courier)
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:48px;height:48px;border-radius:50%;background:#16a34a1a;color:#16a34a;display:grid;place-items:center;font-size:22px;overflow:hidden;">
                        @if($favor->courier->image_src)
                            <img src="{{ $favor->courier->image_src }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                        @else
                            <i class="las la-motorcycle"></i>
                        @endif
                    </div>
                    <div>
                        <div style="font-weight:700;color:#111827;">{{ $favor->courier->fullname }}</div>
                        <div style="font-size:12.5px;color:#6b7280;">{{ $favor->courier->mobile ?? '—' }}</div>
                        @if($favor->estimated_minutes)
                        <span class="eta-pill" style="margin-top:6px;"><i class="las la-clock"></i> ~{{ $favor->estimated_minutes }} min</span>
                        @endif
                    </div>
                </div>
                @else
                <div style="color:#94a3b8;font-size:13px;text-align:center;padding:10px;">
                    <i class="las la-user-clock" style="font-size:24px;display:block;margin-bottom:6px;opacity:.5;"></i>
                    Esperando asignación de repartidor
                </div>
                @endif

                @if(!in_array($favor->status, ['delivered', 'cancelled']))
                <hr class="my-3">
                <form method="POST" action="{{ route('admin.delivery.favor.courier', $favor->id) }}">
                    @csrf
                    <label class="form-label" style="font-size:12.5px;font-weight:600;">{{ $favor->courier ? 'Cambiar repartidor' : 'Asignar repartidor' }}</label>
                    <select name="courier_id" class="form-select mb-2" required>
                        <option value="">Selecciona un repartidor</option>
                        @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ $favor->courier_id == $driver->id ? 'selected' : '' }}>{{ $driver->fullname }}{{ $driver->mobile ? ' · ' . $driver->mobile : '' }}</option>
                        @endforeach
                    </select>
                    <div class="fav-form-note">El nuevo repartidor recibirá una notificación con los datos del favor.</div>
                    <button type="submit" class="btn btn--primary w-100"><i class="las la-exchange-alt"></i> {{ $favor->courier ? 'Cambiar repartidor' : 'Asignar repartidor' }}</button>
                </form>
                @endif
            </div>
        </div>

        @if(!in_array($favor->status, ['delivered', 'cancelled']))
        <div class="fav-card">
            <div class="fav-card-head"><i class="las la-route"></i> Cambiar destino y recalcular</div>
            <div class="fav-card-body">
                <form method="POST" action="{{ route('admin.delivery.favor.destination', $favor->id) }}">
                    @csrf
                    <label class="form-label" style="font-size:12.5px;font-weight:600;">Nueva dirección de destino</label>
                    <input type="text" name="delivery_address" id="delivery_address" class="form-control mb-2" value="{{ old('delivery_address', $favor->delivery_address) }}" maxlength="500" required>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label" style="font-size:12px;">Latitud</label>
                            <input type="number" name="delivery_lat" id="delivery_lat" class="form-control" value="{{ old('delivery_lat', $favor->delivery_lat) }}" step="any" min="-90" max="90" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:12px;">Longitud</label>
                            <input type="number" name="delivery_lng" id="delivery_lng" class="form-control" value="{{ old('delivery_lng', $favor->delivery_lng) }}" step="any" min="-180" max="180" required>
                        </div>
                    </div>
                    <div class="fav-form-note">La tarifa se calcula nuevamente según las coordenadas del nuevo destino. Si usas el buscador de Google, selecciona una sugerencia para completar las coordenadas.</div>
                    <button type="submit" class="btn btn--primary w-100"><i class="las la-calculator"></i> Guardar y recalcular precio</button>
                </form>
            </div>
        </div>
        @endif

        <div class="fav-card">
            <div class="fav-card-head"><i class="las la-cog"></i> Acciones</div>
            <div class="fav-card-body">
                <form method="POST" action="{{ route('admin.delivery.favor.status', $favor->id) }}">@csrf
                    <label class="form-label" style="font-size:12.5px;font-weight:600;">Cambiar estado</label>
                    <select name="status" class="form-select mb-3">
                        @foreach($statusLabels as $k => $v)
                        <option value="{{ $k }}" {{ $favor->status == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn--primary w-100"><i class="las la-save"></i> Actualizar estado</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places,directions" defer></script>
@endpush

@push('script')
<script>
function shareTrackingLink() {
    fetch('{{ route('admin.delivery.favors.share.tracking', $favor->id) }}', {
        method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
    }).then(r => r.json()).then(({url}) => {
        if (navigator.clipboard) return navigator.clipboard.writeText(url).then(() => alert('Enlace de seguimiento copiado.')).catch(() => window.prompt('Copia este enlace de seguimiento:', url));
        window.prompt('Copia este enlace de seguimiento:', url);
    }).catch(() => alert('No se pudo generar el enlace de seguimiento.'));
}
</script>
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
(function() {
    var favorData = {
        favorId:     {{ $favor->id }},
        status:      '{{ $favor->status }}',
        pickupLat:   {{ $favor->pickup_lat ?: 'null' }},
        pickupLng:   {{ $favor->pickup_lng ?: 'null' }},
        deliveryLat: {{ $favor->delivery_lat ?: 'null' }},
        deliveryLng: {{ $favor->delivery_lng ?: 'null' }},
        courierLat:  {{ $favor->courier?->current_lat ?: 'null' }},
        courierLng:  {{ $favor->courier?->current_lot ?: 'null' }}
    };
    var courierIcon = '{{ asset("assets/images/delivery_man_marker.png") }}';

    var map = null, courierMarker = null, dirRenderer = null, dirService = null;
    var courierPosition = null, courierAnimationFrame = null, courierHeading = 0, routePoints = [];
    var CourierOverlay = null;

    function initMap() {
        if (!window.google || !google.maps) { setTimeout(initMap, 400); return; }
        setupDestinationAutocomplete();
        var mapEl = document.getElementById('favor-map');
        if (!mapEl) return;

        dirService = new google.maps.DirectionsService();
        defineCourierOverlay();
        map = new google.maps.Map(mapEl, {
            center: { lat: favorData.pickupLat, lng: favorData.pickupLng },
            zoom: 14, mapTypeControl: false, streetViewControl: false, fullscreenControl: true,
            styles: [
                { elementType: 'geometry', stylers: [{ color: '#f8fafc' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#cfe8ff' }] },
                { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }
            ]
        });

        new google.maps.Marker({
            position: { lat: favorData.pickupLat, lng: favorData.pickupLng }, map: map,
            label: { text: 'R', color: '#fff', fontWeight: 'bold', fontSize: '12px' }, title: 'Recojo',
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 9, fillColor: '#22c55e', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 }
        });
        new google.maps.Marker({
            position: { lat: favorData.deliveryLat, lng: favorData.deliveryLng }, map: map,
            label: { text: 'E', color: '#fff', fontWeight: 'bold', fontSize: '12px' }, title: 'Entrega',
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 9, fillColor: '#ef4444', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 }
        });

        if (favorData.courierLat && favorData.courierLng) {
            setCourier(favorData.courierLat, favorData.courierLng);
        }

        var bounds = new google.maps.LatLngBounds();
        bounds.extend({ lat: favorData.pickupLat, lng: favorData.pickupLng });
        bounds.extend({ lat: favorData.deliveryLat, lng: favorData.deliveryLng });
        if (favorData.courierLat && favorData.courierLng) bounds.extend({ lat: favorData.courierLat, lng: favorData.courierLng });
        map.fitBounds(bounds);
        if (map.getZoom() > 16) map.setZoom(16);

        drawRoute();
        connectPusher();

        // Fallback live location polling (every 4 seconds) to guarantee real-time updates
        setInterval(function() {
            fetch('{{ route("admin.delivery.favors.live.location", $favor->id) }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.latitude && data.longitude) {
                        setCourier(parseFloat(data.latitude), parseFloat(data.longitude));
                    }
                })
                .catch(err => console.error("Error fetching live courier location fallback:", err));
        }, 4000);
    }

    function setupDestinationAutocomplete() {
        var addressInput = document.getElementById('delivery_address');
        var latInput = document.getElementById('delivery_lat');
        var lngInput = document.getElementById('delivery_lng');
        if (!addressInput || !latInput || !lngInput || !google.maps.places) return;

        var autocomplete = new google.maps.places.Autocomplete(addressInput, {
            fields: ['formatted_address', 'geometry'],
            types: ['geocode']
        });
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry || !place.geometry.location) return;
            addressInput.value = place.formatted_address || addressInput.value;
            latInput.value = place.geometry.location.lat().toFixed(7);
            lngInput.value = place.geometry.location.lng().toFixed(7);
        });
    }

    function setCourier(lat, lng) {
        if (!map) return;
        var target = { lat: lat, lng: lng };
        if (!courierMarker) {
            courierPosition = target;
            courierHeading = routeHeadingAt(target) || 0;
            courierMarker = new CourierOverlay(target, courierHeading);
            courierMarker.setMap(map);
            return;
        }

        animateCourierTo(target);
    }

    function defineCourierOverlay() {
        if (CourierOverlay) return;
        CourierOverlay = function(position, heading) {
            this.position = position;
            this.heading = heading || 0;
            this.element = null;
        };
        CourierOverlay.prototype = new google.maps.OverlayView();
        CourierOverlay.prototype.onAdd = function() {
            this.element = document.createElement('div');
            this.element.title = 'Repartidor';
            this.element.style.cssText = 'position:absolute;left:0;top:0;width:52px;height:52px;will-change:transform;pointer-events:none;z-index:5;';
            this.element.innerHTML = '<div class="courier-route-marker"><img src="' + courierIcon + '" alt="Repartidor"></div>';
            this.getPanes().overlayMouseTarget.appendChild(this.element);
            this.updateVisual();
        };
        CourierOverlay.prototype.draw = function() {
            if (!this.element || !this.position) return;
            var point = this.getProjection().fromLatLngToDivPixel(new google.maps.LatLng(this.position.lat, this.position.lng));
            if (point) {
                this.element.style.transform = 'translate3d(' + (point.x - 26) + 'px,' + (point.y - 26) + 'px,0)';
            }
        };
        CourierOverlay.prototype.onRemove = function() {
            if (this.element) this.element.remove();
            this.element = null;
        };
        CourierOverlay.prototype.setPosition = function(position) {
            this.position = position;
            this.draw();
        };
        CourierOverlay.prototype.setHeading = function(heading) {
            this.heading = heading;
            this.updateVisual();
        };
        CourierOverlay.prototype.updateVisual = function() {
            if (!this.element) return;
            var icon = this.element.querySelector('.courier-route-marker');
            if (icon) icon.style.transform = 'rotate(' + this.heading + 'deg)';
        };
    }

    function animateCourierTo(target) {
        if (courierAnimationFrame) cancelAnimationFrame(courierAnimationFrame);
        var from = { lat: courierPosition.lat, lng: courierPosition.lng };
        var startedAt = null;
        var duration = 3600;
        var targetHeading = routeHeadingAt(target) || bearingBetween(from, target) || courierHeading;
        var initialHeading = courierHeading;
        var movementPath = routeAnimationPath(from, target);
        var pathLengths = movementPath.slice(1).map(function(point, index) { return mapDistance(movementPath[index], point); });
        var totalLength = pathLengths.reduce(function(total, length) { return total + length; }, 0);

        function move(timestamp) {
            if (!startedAt) startedAt = timestamp;
            var progress = Math.min((timestamp - startedAt) / duration, 1);
            var position = pointOnPath(movementPath, pathLengths, totalLength, progress);
            var routeHeading = routeHeadingAt(position);
            var heading = routeHeading || interpolateHeading(initialHeading, targetHeading, progress);
            courierPosition = position;
            courierHeading = heading;
            courierMarker.setPosition(position);
            courierMarker.setHeading(heading);

            if (progress < 1) {
                courierAnimationFrame = requestAnimationFrame(move);
            } else {
                courierAnimationFrame = null;
            }
        }
        courierAnimationFrame = requestAnimationFrame(move);
    }

    function routeHeadingAt(position) {
        if (routePoints.length < 2) return null;
        var nearest = nearestRouteIndex(position);
        var next = routePoints[Math.min(nearest + 1, routePoints.length - 1)];
        var previous = routePoints[Math.max(nearest - 1, 0)];
        return bearingBetween(routePoints[nearest], next) || bearingBetween(previous, routePoints[nearest]);
    }

    function nearestRouteIndex(position) {
        var nearest = 0, nearestDistance = Infinity;
        routePoints.forEach(function(point, index) {
            var distance = mapDistance(point, position);
            if (distance < nearestDistance) { nearestDistance = distance; nearest = index; }
        });
        return nearest;
    }

    function routeAnimationPath(from, target) {
        if (routePoints.length < 2) return [from, target];
        var startIndex = nearestRouteIndex(from);
        var targetIndex = nearestRouteIndex(target);
        if (targetIndex <= startIndex) return [from, target];
        return [from].concat(routePoints.slice(startIndex + 1, targetIndex + 1), [target]);
    }

    function mapDistance(from, to) {
        var latDelta = from.lat - to.lat;
        var lngDelta = (from.lng - to.lng) * Math.cos(((from.lat + to.lat) / 2) * Math.PI / 180);
        return Math.sqrt(latDelta * latDelta + lngDelta * lngDelta);
    }

    function pointOnPath(path, lengths, totalLength, progress) {
        if (!totalLength) return path[path.length - 1];
        var remaining = totalLength * progress;
        for (var index = 0; index < lengths.length; index++) {
            if (remaining <= lengths[index]) {
                var ratio = lengths[index] ? remaining / lengths[index] : 1;
                var pointFrom = path[index], pointTo = path[index + 1];
                return { lat: pointFrom.lat + (pointTo.lat - pointFrom.lat) * ratio, lng: pointFrom.lng + (pointTo.lng - pointFrom.lng) * ratio };
            }
            remaining -= lengths[index];
        }
        return path[path.length - 1];
    }

    function bearingBetween(from, to) {
        if (!from || !to || (from.lat === to.lat && from.lng === to.lng)) return null;
        var lat1 = from.lat * Math.PI / 180, lat2 = to.lat * Math.PI / 180;
        var lngDelta = (to.lng - from.lng) * Math.PI / 180;
        var y = Math.sin(lngDelta) * Math.cos(lat2);
        var x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(lngDelta);
        return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
    }

    function interpolateHeading(from, to, progress) {
        var difference = ((to - from + 540) % 360) - 180;
        return (from + difference * progress + 360) % 360;
    }

    function drawRoute() {
        if (!dirService || !map) return;
        if (dirRenderer) dirRenderer.setMap(null);
        var routeOrigin = { lat: favorData.pickupLat, lng: favorData.pickupLng };
        var routeDestination = { lat: favorData.deliveryLat, lng: favorData.deliveryLng };
        if (courierPosition && ['accepted', 'on_way_to_pickup'].indexOf(favorData.status) !== -1) {
            routeOrigin = courierPosition;
            routeDestination = { lat: favorData.pickupLat, lng: favorData.pickupLng };
        }
        dirService.route({
            origin: routeOrigin,
            destination: routeDestination,
            travelMode: google.maps.TravelMode.DRIVING
        }, function(result, status) {
            if (status === 'OK') {
                routePoints = (result.routes[0].overview_path || []).map(function(point) {
                    return { lat: point.lat(), lng: point.lng() };
                });
                if (courierMarker && courierPosition) {
                    courierHeading = routeHeadingAt(courierPosition) || courierHeading;
                    courierMarker.setHeading(courierHeading);
                }
                dirRenderer = new google.maps.DirectionsRenderer({
                    map: map, directions: result, suppressMarkers: true,
                    polylineOptions: { strokeColor: '#16a34a', strokeWeight: 4, strokeOpacity: 0.8 }
                });
                var leg = result.routes[0] && result.routes[0].legs[0];
                if (leg) {
                    document.getElementById('route-eta').textContent = leg.distance.text + ' · ' + leg.duration.text;
                    document.getElementById('route-eta-wrap').style.display = 'inline-flex';
                }
            }
        });
    }

    function connectPusher() {
        var pusherKey = '{{ $pusherConfig["key"] }}';
        if (!pusherKey || !window.Pusher) return;

        var pusher = new Pusher(pusherKey, {
            wsHost: '{{ $pusherConfig["host"] }}',
            wsPort: {{ $pusherConfig["port"] }},
            wssPort: {{ $pusherConfig["port"] }},
            forceTLS: {{ $pusherConfig["scheme"] === 'https' ? 'true' : 'false' }},
            cluster: 'mt1',
            enabledTransports: ['ws', 'wss'],
            authorizer: function(channel) {
                return {
                    authorize: function(socketId, callback) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '{{ route("admin.delivery.broadcasting.auth") }}', true);
                        xhr.setRequestHeader('Content-Type', 'application/json');
                        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                if (xhr.status === 200) callback(null, JSON.parse(xhr.responseText));
                                else callback(new Error('Auth failed'), null);
                            }
                        };
                        xhr.send(JSON.stringify({ socket_id: socketId, channel_name: channel.name }));
                    }
                };
            }
        });

        var ch = pusher.subscribe('private-favor.' + favorData.favorId);
        ch.bind('location_update', function(data) {
            if (data.latitude && data.longitude) setCourier(parseFloat(data.latitude), parseFloat(data.longitude));
        });
        ch.bind('favor_status_updated', function(data) {
            if (!data || !data.status) return;
            favorData.status = data.status;
            if (courierPosition) drawRoute();
        });
    }

    if (document.readyState === 'loading') {
        window.addEventListener('load', initMap);
    } else {
        initMap();
    }
})();
</script>
@endpush
@endif
