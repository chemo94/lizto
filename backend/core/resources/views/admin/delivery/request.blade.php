@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-7">
        <div class="card" id="request-card">
            <div class="card-header">
                <h5 class="card-title"><i class="las la-motorcycle"></i> @lang('Solicitar Envío')</h5>
            </div>
            <div class="card-body">
                <form id="request-form" method="POST" action="{{ route('admin.delivery.request.submit') }}">
                    @csrf

                    <div class="form-group">
                        <label>@lang('Seleccionar Tienda')</label>
                        <select class="form-control select2" name="store_id" id="store-select" required>
                            <option value="">@lang('-- Selecciona una tienda --')</option>
                            @foreach($stores as $store)
                            <option value="{{ $store->id }}"
                                data-address="{{ $store->address }}"
                                data-lat="{{ $store->latitude }}"
                                data-lng="{{ $store->longitude }}"
                                {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>@lang('Asignar Repartidor')</label>
                        <select class="form-control select2" name="driver_id" id="driver-select">
                            <option value="all">@lang('Enviar a todos los repartidores')</option>
                            @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->firstname }} {{ $driver->lastname }} ({{ $driver->username }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="store-info" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="mb-0">@lang('Dirección de recogida (pickup)')</label>
                            <button type="button" class="btn btn--primary btn-sm" id="select-pickup-map-btn" style="padding: 2px 8px; font-size: 11px; height: auto;">
                                <i class="las la-map-marker"></i> o seleccionar del mapa
                            </button>
                        </div>
                        <input type="text" class="form-control" id="store-address" placeholder="@lang('Busca una dirección de recogida...')" autocomplete="off">
                        <small class="text-muted mt-1 d-block">@lang('Coordenadas:') <span id="store-coords">--</span></small>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="mb-0">@lang('Dirección de destino')</label>
                            <button type="button" class="btn btn--primary btn-sm" id="select-dest-map-btn" style="padding: 2px 8px; font-size: 11px; height: auto;">
                                <i class="las la-map-marker"></i> o seleccionar del mapa
                            </button>
                        </div>
                        <input type="text" class="form-control" name="delivery_address" id="dest-address"
                            value="{{ old('delivery_address') }}" placeholder="@lang('Calle, número, distrito...')" required autocomplete="off">
                    </div>

                    <input type="hidden" name="pickup_lat" id="pickup-lat" value="{{ old('pickup_lat') }}">
                    <input type="hidden" name="pickup_lng" id="pickup-lng" value="{{ old('pickup_lng') }}">
                    <input type="hidden" name="delivery_lat" id="delivery-lat" value="{{ old('delivery_lat') }}">
                    <input type="hidden" name="delivery_lng" id="delivery-lng" value="{{ old('delivery_lng') }}">

                    <div class="form-group">
                        <label>@lang('Descripción del envío')</label>
                        <textarea class="form-control" name="description" rows="3"
                            placeholder="@lang('Ej: Recoger documentos en la tienda y entregar en la dirección indicada')" required>{{ old('description') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>@lang('Teléfono del destinatario')</label>
                        <input type="text" class="form-control" name="recipient_phone"
                            value="{{ old('recipient_phone') }}" placeholder="@lang('+51 999 888 777')">
                    </div>

                    <button type="submit" class="btn btn--primary w-100" id="submit-btn">
                        <i class="las la-paper-plane"></i> @lang('Enviar solicitud a repartidores')
                    </button>
                </form>
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div class="card" id="waiting-card" style="display:none;">
            <div class="card-body text-center py-5">
                <div id="waiting-animation" style="margin-bottom: 20px;">
                    <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <h5 id="waiting-title">Buscando repartidor...</h5>
                <p class="text-muted" id="waiting-subtitle">Solicitud <strong id="waiting-order-no">--</strong></p>
                <div id="waiting-timer" class="mt-2">
                    <span class="badge badge--primary" style="font-size:14px;padding:8px 16px;">
                        <i class="las la-clock"></i> <span id="elapsed-time">0:00</span>
                    </span>
                </div>
                <div class="mt-3">
                    <button class="btn btn--danger btn-sm" id="cancel-btn" style="display:none;">
                        <i class="las la-times"></i> Cancelar solicitud
                    </button>
                </div>
            </div>
        </div>

        {{-- Driver Found --}}
        <div class="card" id="driver-card" style="display:none;">
            <div class="card-body text-center py-4">
                <div id="driver-found-animation" style="margin-bottom: 15px;">
                    <div style="width:80px;height:80px;border-radius:50%;background:rgba(16,185,129,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <i class="las la-check-circle" style="font-size:40px;color:#10b981;"></i>
                    </div>
                </div>
                <h5 class="text--success">Repartidor encontrado</h5>
                <p class="text-muted">Solicitud <strong id="driver-order-no">--</strong></p>

                <div class="mt-3" style="max-width:350px;margin:0 auto;">
                    <div class="d-flex align-items-center p-3 rounded" style="background:rgba(16,185,129,0.05);border:1px solid rgba(16,185,129,0.15);">
                        <div id="driver-avatar" style="width:56px;height:56px;border-radius:50%;background:rgba(16,185,129,0.1);display:flex;align-items:center;justify-content:center;margin-right:12px;flex-shrink:0;overflow:hidden;">
                            <i class="las la-user" style="font-size:24px;color:#10b981;"></i>
                        </div>
                        <div class="text-left">
                            <div style="font-weight:700;font-size:15px;" id="driver-name">--</div>
                            <div class="text-muted" style="font-size:13px;" id="driver-phone">--</div>
                            <div class="mt-1" id="driver-distance-badge" style="display:none;">
                                <span class="badge badge--success" style="font-size:12px;">
                                    <i class="las la-route"></i> <span id="driver-distance">--</span> km
                                </span>
                                <span class="badge badge--primary ml-1" style="font-size:12px;">
                                    <i class="las la-clock"></i> ~<span id="driver-time">--</span> min
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="#" class="btn btn--primary" id="view-detail-btn" target="_blank">
                        <i class="las la-eye"></i> Ver detalles del envío
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="las la-calculator"></i> @lang('Tarifa Estimada')</h5>
            </div>
            <div class="card-body">
                <div id="fee-breakdown">
                    <p class="text-muted text-center py-4">@lang('Selecciona una tienda y destino para calcular')</p>
                </div>

                <div id="fee-details" style="display:none;">
                    <!-- Short Distance Warning Alert -->
                    <div id="short-distance-warning" style="display:none;margin-bottom:12px;padding:12px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;font-size:12px;color:#d97706;">
                        <div style="font-weight:700;margin-bottom:2px;"><i class="las la-exclamation-triangle" style="color:#f59e0b;font-size:15px;"></i> Distancia muy corta (<span id="short-dist-km">--</span> km)</div>
                        <span>¿Es correcta la dirección? Se aplicó automáticamente la <strong>tarifa corta fija de S/ 4.00</strong>.</span>
                    </div>

                    <table class="table table-sm">
                        <tr>
                            <td>@lang('Distancia')</td>
                            <td class="text-end"><strong id="fee-distance">--</strong> km</td>
                        </tr>
                        <tr>
                            <td>@lang('Tarifa base')</td>
                            <td class="text-end">S/ <span id="fee-base">--</span></td>
                        </tr>
                        <tr>
                            <td>@lang('Distancia extra')</td>
                            <td class="text-end">S/ <span id="fee-distance-fee">--</span></td>
                        </tr>
                        <tr>
                            <td>@lang('Tiempo estimado')</td>
                            <td class="text-end"><span id="fee-time-min">--</span> min (S/ <span id="fee-time">--</span>)</td>
                        </tr>
                        <tr id="surge-row" style="display:none;">
                            <td class="text-warning">@lang('Demanda (surge)')</td>
                            <td class="text-end text-warning">×<span id="fee-surge">--</span></td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>@lang('TOTAL ESTIMADO')</strong></td>
                            <td class="text-end"><strong class="text--base" style="font-size:1.3rem;">S/ <span id="fee-total">--</span></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title"><i class="las la-info-circle"></i> @lang('Información')</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>@lang('Repartidores delivery activos')</span>
                        <span class="badge badge--success" id="active-drivers">{{ $activeDrivers }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>@lang('Tarifa mínima')</span>
                        <span>S/ {{ number_format(gs('delivery_min_fee') ?? 4, 2) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>@lang('Km incluidos en base')</span>
                        <span>{{ gs('delivery_base_km') ?? 3 }} km</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>@lang('Tarifa por km extra')</span>
                        <span>S/ {{ number_format(gs('delivery_fee_per_km') ?? 1.5, 2) }}/km</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>@lang('Tarifa por minuto')</span>
                        <span>S/ {{ number_format(gs('delivery_time_rate') ?? 0.30, 2) }}/min</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>@lang('Multiplicador actual')</span>
                        <span>×{{ number_format(gs('delivery_surge') ?? 1.00, 2) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    <!-- Map Selector Modal (Destino) -->
    <div id="map-selector-modal" class="map-selector-overlay">
        <div class="map-selector-content">
            <h4 class="mb-2" style="font-weight: 800; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="las la-map-marked-alt text--primary" style="font-size: 20px;"></i> @lang('Seleccionar Ubicación de Destino')
            </h4>
            <p class="text-muted mb-2" style="font-size: 13px;">@lang('Escribe en el buscador o arrastra el marcador para fijar el destino exacto.')</p>
            <div class="mb-3">
                <input type="text" id="modal-admin-dest-search" class="form-control" placeholder="🔍 Buscar dirección o referencia..." autocomplete="off">
            </div>
            <div id="selector-map" style="width: 100%; height: 380px; border-radius: 12px; margin-bottom: 16px; border: 1px solid #ddd; background: #e9ecef;"></div>
            <div class="d-flex justify-content-end" style="gap: 10px;">
                <button type="button" class="btn btn--dark" id="close-map-selector-btn">@lang('Cancelar')</button>
                <button type="button" class="btn btn--primary" id="confirm-map-selector-btn">@lang('Confirmar Ubicación')</button>
            </div>
        </div>
    </div>

    <!-- Map Selector Modal (Recogida / Pickup) -->
    <div id="pickup-map-modal" class="map-selector-overlay">
        <div class="map-selector-content">
            <h4 class="mb-2" style="font-weight: 800; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="las la-store text--success" style="font-size: 20px;"></i> @lang('Seleccionar Dirección de Recogida')
            </h4>
            <p class="text-muted mb-2" style="font-size: 13px;">@lang('Escribe en el buscador o arrastra el marcador para fijar el punto de recogida.')</p>
            <div class="mb-3">
                <input type="text" id="modal-admin-pickup-search" class="form-control" placeholder="🔍 Buscar dirección de recogida..." autocomplete="off">
            </div>
            <div id="pickup-selector-map" style="width: 100%; height: 380px; border-radius: 12px; margin-bottom: 16px; border: 1px solid #ddd; background: #e9ecef;"></div>
            <div class="d-flex justify-content-end" style="gap: 10px;">
                <button type="button" class="btn btn--dark" id="close-pickup-map-btn">@lang('Cancelar')</button>
                <button type="button" class="btn btn--success" id="confirm-pickup-map-btn">@lang('Confirmar Recogida')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
.map-selector-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}
.map-selector-overlay.active {
    opacity: 1;
    pointer-events: all;
}
.map-selector-content {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
    .pac-container {
        z-index: 9999999 !important;
    }
    .courier-inquiry-animation {
        width: 82px; height: 82px; margin: 0 auto; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; background: #4f46e5; position: relative;
        animation: courierInquiryPulse 1.4s ease-in-out infinite;
    }
    .courier-inquiry-animation::before, .courier-inquiry-animation::after {
        content: ''; position: absolute; inset: -9px; border-radius: 50%;
        border: 2px solid rgba(79, 70, 229, .35); animation: courierInquiryRing 1.4s ease-out infinite;
    }
    .courier-inquiry-animation::after { animation-delay: .7s; }
    @keyframes courierInquiryPulse { 50% { transform: scale(.94); } }
    @keyframes courierInquiryRing { from { transform: scale(.75); opacity: 1; } to { transform: scale(1.25); opacity: 0; } }
</style>
@endpush

@push('breadcrumb-plugins')
<a href="{{ route('admin.delivery.favors') }}" class="btn btn-sm btn-outline--primary">
    <i class="las la-list"></i> @lang('Ver envíos solicitados')
</a>
@endpush

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script>
(function() {
    var storeSelect = document.getElementById('store-select');
    var storeInfo = document.getElementById('store-info');
    var storeAddr = document.getElementById('store-address');
    var storeCoords = document.getElementById('store-coords');
    var destAddr = document.getElementById('dest-address');
    var feeBreakdown = document.getElementById('fee-breakdown');
    var feeDetails = document.getElementById('fee-details');
    var requestForm = document.getElementById('request-form');
    var requestCard = document.getElementById('request-card');
    var waitingCard = document.getElementById('waiting-card');
    var driverCard = document.getElementById('driver-card');
    var submitBtn = document.getElementById('submit-btn');
    var cancelBtn = document.getElementById('cancel-btn');

    var pickupLat = null, pickupLng = null;
    var deliveryLat = null, deliveryLng = null;
    var pollingInterval = null;
    var pollStartTime = null;
    var maxPollTime = 180000; // 3 minutes
    var timerInterval = null;

    // Store selection
    storeSelect.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        if (opt.value) {
            storeInfo.style.display = 'block';
            storeAddr.value = opt.dataset.address || '';
            storeCoords.textContent = (opt.dataset.lat || '--') + ', ' + (opt.dataset.lng || '--');
            pickupLat = parseFloat(opt.dataset.lat) || null;
            pickupLng = parseFloat(opt.dataset.lng) || null;
            document.getElementById('pickup-lat').value = pickupLat || '';
            document.getElementById('pickup-lng').value = pickupLng || '';
        } else {
            storeInfo.style.display = 'none';
            pickupLat = null; pickupLng = null;
        }
        calculateFee();
    });

    // Reset pickup coords when user types manually in pickup address
    storeAddr.addEventListener('input', function() {
        pickupLat = null; pickupLng = null;
        document.getElementById('pickup-lat').value = '';
        document.getElementById('pickup-lng').value = '';
        storeCoords.textContent = '--';
    });

    // Trigger on old value
    if (storeSelect.value) storeSelect.dispatchEvent(new Event('change'));

    // Google Maps autocomplete for destination AND pickup
    function initAutocomplete() {
        if (!window.google || !google.maps || !google.maps.places) {
            setTimeout(initAutocomplete, 300);
            return;
        }

        // Destination autocomplete
        var autocomplete = new google.maps.places.Autocomplete(destAddr, {
            componentRestrictions: { country: 'pe' }
        });
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (place.geometry) {
                deliveryLat = place.geometry.location.lat();
                deliveryLng = place.geometry.location.lng();
                document.getElementById('delivery-lat').value = deliveryLat;
                document.getElementById('delivery-lng').value = deliveryLng;
                calculateFee();
            }
        });

        // Pickup autocomplete
        var pickupAutocomplete = new google.maps.places.Autocomplete(storeAddr, {
            componentRestrictions: { country: 'pe' }
        });
        pickupAutocomplete.addListener('place_changed', function() {
            var place = pickupAutocomplete.getPlace();
            if (place.geometry) {
                pickupLat = place.geometry.location.lat();
                pickupLng = place.geometry.location.lng();
                document.getElementById('pickup-lat').value = pickupLat;
                document.getElementById('pickup-lng').value = pickupLng;
                storeCoords.textContent = pickupLat.toFixed(6) + ', ' + pickupLng.toFixed(6);
                calculateFee();
            }
        });
    }

    // ════════════════════════════════
    //  MAP SELECTOR MODAL — DESTINO
    // ════════════════════════════════
    var mapSelectorModal = document.getElementById('map-selector-modal');
    var selectDestMapBtn = document.getElementById('select-dest-map-btn');
    var closeMapSelectorBtn = document.getElementById('close-map-selector-btn');
    var confirmMapSelectorBtn = document.getElementById('confirm-map-selector-btn');

    var selectorMap = null;
    var selectorMarker = null;
    var tempLat = null;
    var tempLng = null;

    selectDestMapBtn.addEventListener('click', function() {
        mapSelectorModal.classList.add('active');
        initSelectorMap('dest');
    });

    closeMapSelectorBtn.addEventListener('click', function() {
        mapSelectorModal.classList.remove('active');
    });

    confirmMapSelectorBtn.addEventListener('click', function() {
        if (tempLat && tempLng) {
            deliveryLat = tempLat;
            deliveryLng = tempLng;
            document.getElementById('delivery-lat').value = tempLat;
            document.getElementById('delivery-lng').value = tempLng;
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: { lat: tempLat, lng: tempLng } }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    destAddr.value = results[0].formatted_address;
                } else {
                    destAddr.value = tempLat.toFixed(6) + ', ' + tempLng.toFixed(6);
                }
                calculateFee();
                mapSelectorModal.classList.remove('active');
            });
        } else {
            mapSelectorModal.classList.remove('active');
        }
    });

    // ════════════════════════════════
    //  MAP SELECTOR MODAL — RECOGIDA
    // ════════════════════════════════
    var pickupMapModal = document.getElementById('pickup-map-modal');
    var selectPickupMapBtn = document.getElementById('select-pickup-map-btn');
    var closePickupMapBtn = document.getElementById('close-pickup-map-btn');
    var confirmPickupMapBtn = document.getElementById('confirm-pickup-map-btn');

    var pickupSelectorMap = null;
    var pickupSelectorMarker = null;
    var tempPickupLat = null;
    var tempPickupLng = null;

    selectPickupMapBtn.addEventListener('click', function() {
        pickupMapModal.classList.add('active');
        initSelectorMap('pickup');
    });

    closePickupMapBtn.addEventListener('click', function() {
        pickupMapModal.classList.remove('active');
    });

    confirmPickupMapBtn.addEventListener('click', function() {
        if (tempPickupLat && tempPickupLng) {
            pickupLat = tempPickupLat;
            pickupLng = tempPickupLng;
            document.getElementById('pickup-lat').value = tempPickupLat;
            document.getElementById('pickup-lng').value = tempPickupLng;
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: { lat: tempPickupLat, lng: tempPickupLng } }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    storeAddr.value = results[0].formatted_address;
                } else {
                    storeAddr.value = tempPickupLat.toFixed(6) + ', ' + tempPickupLng.toFixed(6);
                }
                storeCoords.textContent = tempPickupLat.toFixed(6) + ', ' + tempPickupLng.toFixed(6);
                calculateFee();
                pickupMapModal.classList.remove('active');
            });
        } else {
            pickupMapModal.classList.remove('active');
        }
    });

    function initSelectorMap(mode) {
        if (!window.google || !google.maps) return;

        if (mode === 'pickup') {
            var centerLat = pickupLat || deliveryLat || -6.4916;
            var centerLng = pickupLng || deliveryLng || -76.3724;
            tempPickupLat = centerLat;
            tempPickupLng = centerLng;

            setTimeout(function() {
                if (!pickupSelectorMap) {
                    pickupSelectorMap = new google.maps.Map(document.getElementById('pickup-selector-map'), {
                        center: { lat: centerLat, lng: centerLng },
                        zoom: 15,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false
                    });
                    pickupSelectorMarker = new google.maps.Marker({
                        position: { lat: centerLat, lng: centerLng },
                        map: pickupSelectorMap,
                        draggable: true,
                        animation: google.maps.Animation.DROP,
                        icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
                    });
                    pickupSelectorMap.addListener('click', function(e) {
                        var ll = e.latLng;
                        pickupSelectorMarker.setPosition(ll);
                        tempPickupLat = ll.lat();
                        tempPickupLng = ll.lng();
                    });
                    pickupSelectorMarker.addListener('dragend', function() {
                        var pos = pickupSelectorMarker.getPosition();
                        tempPickupLat = pos.lat();
                        tempPickupLng = pos.lng();
                    });
                } else {
                    pickupSelectorMap.setCenter({ lat: centerLat, lng: centerLng });
                    pickupSelectorMarker.setPosition({ lat: centerLat, lng: centerLng });
                    google.maps.event.trigger(pickupSelectorMap, 'resize');
                }
            }, 200);
        } else {
            var centerLat = deliveryLat || pickupLat || -6.4916;
            var centerLng = deliveryLng || pickupLng || -76.3724;
            tempLat = centerLat;
            tempLng = centerLng;

            setTimeout(function() {
                if (!selectorMap) {
                    selectorMap = new google.maps.Map(document.getElementById('selector-map'), {
                        center: { lat: centerLat, lng: centerLng },
                        zoom: 15,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false
                    });
                    selectorMarker = new google.maps.Marker({
                        position: { lat: centerLat, lng: centerLng },
                        map: selectorMap,
                        draggable: true,
                        animation: google.maps.Animation.DROP
                    });
                    selectorMap.addListener('click', function(e) {
                        var latLng = e.latLng;
                        selectorMarker.setPosition(latLng);
                        tempLat = latLng.lat();
                        tempLng = latLng.lng();
                    });
                    selectorMarker.addListener('dragend', function() {
                        var position = selectorMarker.getPosition();
                        tempLat = position.lat();
                        tempLng = position.lng();
                    });
                    var adminDestSearch = document.getElementById('modal-admin-dest-search');
                    if (adminDestSearch && !adminDestSearch.dataset.acBound && window.google && google.maps && google.maps.places) {
                        adminDestSearch.dataset.acBound = '1';
                        var acD = new google.maps.places.Autocomplete(adminDestSearch, { componentRestrictions: { country: 'pe' } });
                        acD.addListener('place_changed', function() {
                            var p = acD.getPlace();
                            if (p.geometry) {
                                selectorMap.setCenter(p.geometry.location);
                                selectorMap.setZoom(17);
                                selectorMarker.setPosition(p.geometry.location);
                                tempLat = p.geometry.location.lat();
                                tempLng = p.geometry.location.lng();
                            }
                        });
                    }
                } else {
                    selectorMap.setCenter({ lat: centerLat, lng: centerLng });
                    selectorMarker.setPosition({ lat: centerLat, lng: centerLng });
                    google.maps.event.trigger(selectorMap, 'resize');
                }
            }, 200);
        }
    }

    window.addEventListener('load', initAutocomplete);

    // Manual address change reset
    destAddr.addEventListener('input', function() {
        deliveryLat = null; deliveryLng = null;
        document.getElementById('delivery-lat').value = '';
        document.getElementById('delivery-lng').value = '';
    });

    function calculateFee() {
        if (!pickupLat || !pickupLng || !deliveryLat || !deliveryLng) return;

        fetch('/admin/delivery/request/fee-calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                pickup_lat: pickupLat, pickup_lng: pickupLng,
                delivery_lat: deliveryLat, delivery_lng: deliveryLng
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                feeBreakdown.style.display = 'none';
                feeDetails.style.display = 'block';

                var distKm = parseFloat(data.distance_km) || 0;
                var isShort = distKm > 0 && distKm < 1.0;
                var feeTotal = (isShort || data.is_short_distance) ? 4.0 : (parseFloat(data.delivery_fee) || 0);

                document.getElementById('fee-distance').textContent = data.distance_km || '--';
                document.getElementById('fee-base').textContent = (isShort || data.is_short_distance) ? '4.00 (Corta)' : (data.base_fare || '--');
                document.getElementById('fee-distance-fee').textContent = (isShort || data.is_short_distance) ? '0.00' : (data.distance_fee || '0.00');
                document.getElementById('fee-time-min').textContent = data.time_min || '--';
                document.getElementById('fee-time').textContent = (isShort || data.is_short_distance) ? '0.00' : (data.time_fee || '0.00');
                document.getElementById('fee-total').textContent = feeTotal.toFixed(2);

                var shortAlert = document.getElementById('short-distance-warning');
                if (shortAlert) {
                    if (isShort || data.is_short_distance) {
                        shortAlert.style.display = 'block';
                        var distEl = document.getElementById('short-dist-km');
                        if (distEl) distEl.textContent = distKm.toFixed(2);
                    } else {
                        shortAlert.style.display = 'none';
                    }
                }

                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Enviar solicitud a repartidores · S/ ' + feeTotal.toFixed(2);
                }

                if (parseFloat(data.surge) > 1) {
                    document.getElementById('surge-row').style.display = '';
                    document.getElementById('fee-surge').textContent = data.surge;
                } else {
                    document.getElementById('surge-row').style.display = 'none';
                }
            }
        });
    }

    // AJAX Form Submit
    requestForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!destAddr.value || !document.getElementById('delivery-lat').value) {
            alert('Selecciona una dirección de destino válida usando la sugerencia de Google Maps.');
            return;
        }

        if (!document.getElementById('pickup-lat').value || !document.getElementById('pickup-lng').value) {
            alert('Selecciona una dirección de recogida válida. Usa el autocompletado de Google Maps o el selector de mapa.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="las la-spinner la-spin"></i> Procesando...';

        var formData = new FormData(requestForm);

        fetch(requestForm.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                requestCard.style.display = 'none';
                waitingCard.style.display = 'block';
                document.getElementById('waiting-order-no').textContent = data.order_no;
                cancelBtn.style.display = 'inline-block';
                startTimer();
                startStatusPolling(data.favor_id);
            } else {
                alert(data.message || 'Ocurrió un error al procesar la solicitud.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="las la-paper-plane"></i> @lang("Enviar solicitud a repartidores")';
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('Error de conexión al servidor.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="las la-paper-plane"></i> @lang("Enviar solicitud a repartidores")';
        });
    });

    // Timer
    function startTimer() {
        pollStartTime = Date.now();
        timerInterval = setInterval(function() {
            var elapsed = Math.floor((Date.now() - pollStartTime) / 1000);
            var min = Math.floor(elapsed / 60);
            var sec = elapsed % 60;
            document.getElementById('elapsed-time').textContent = min + ':' + (sec < 10 ? '0' : '') + sec;
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) clearInterval(timerInterval);
    }

    // Polling
    function startStatusPolling(favorId) {
        if (pollingInterval) clearInterval(pollingInterval);
        pollStartTime = Date.now();

        pollingInterval = setInterval(function() {
            if (Date.now() - pollStartTime > maxPollTime) {
                clearInterval(pollingInterval);
                stopTimer();
                alert('Tiempo de espera agotado. No se encontraron repartidores disponibles.');
                window.location.reload();
                return;
            }

            var url = '{{ route("admin.delivery.request.status.show", ":id") }}'.replace(':id', favorId);
            fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    if (data.favor_status === 'accepted' || data.favor_status === 'on_way_to_pickup' || data.favor_status === 'at_pickup' || data.favor_status === 'on_way_to_delivery') {
                        clearInterval(pollingInterval);
                        stopTimer();
                        showDriverFound(data);
                    } else if (data.favor_status === 'cancelled') {
                        clearInterval(pollingInterval);
                        stopTimer();
                        alert('La solicitud ha sido cancelada.');
                        window.location.reload();
                    } else {
                        showWaitingDispatch(data.dispatch);
                    }
                }
            })
            .catch(function(err) { console.error('Poll error:', err); });
        }, 5000);
    }

    function showWaitingDispatch(dispatch) {
        if (!dispatch || dispatch.mode !== 'admin_targeted' || !dispatch.driver_name) return;

        document.getElementById('waiting-animation').innerHTML =
            '<div class="courier-inquiry-animation"><i class="las la-motorcycle" style="font-size:36px;"></i></div>';
        document.getElementById('waiting-title').innerHTML = 'Esperando respuesta de <strong>' + escapeHtml(dispatch.driver_name) + '</strong>';
        document.getElementById('waiting-subtitle').innerHTML =
            'Solicitud <strong id="waiting-order-no">' + escapeHtml(document.getElementById('waiting-order-no').textContent) + '</strong> · consulta individual por 30 segundos';
    }

    function escapeHtml(value) {
        var element = document.createElement('div');
        element.textContent = value || '';
        return element.innerHTML;
    }

    function showDriverFound(data) {
        waitingCard.style.display = 'none';
        driverCard.style.display = 'block';
        document.getElementById('driver-order-no').textContent = data.favor.order_no;

        if (data.courier) {
            var c = data.courier;
            document.getElementById('driver-name').textContent = c.name || 'Repartidor asignado';
            document.getElementById('driver-phone').textContent = c.phone || 'Sin teléfono';

            if (c.image) {
                document.getElementById('driver-avatar').innerHTML = '<img src="' + c.image + '" style="width:56px;height:56px;object-fit:cover;border-radius:50%;" alt="Foto">';
            } else {
                var initials = (c.name || 'R').split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2).toUpperCase();
                document.getElementById('driver-avatar').innerHTML = '<div style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#10b981;font-size:18px;">' + initials + '</div>';
            }

            if (c.distance_km && c.distance_km !== '--') {
                document.getElementById('driver-distance').textContent = c.distance_km;
                document.getElementById('driver-time').textContent = c.time_min || '--';
                document.getElementById('driver-distance-badge').style.display = 'block';
            }
        }

        document.getElementById('view-detail-btn').href = '{{ url("admin/delivery/favors") }}';
    }

    // Cancel button
    cancelBtn.addEventListener('click', function() {
        if (confirm('¿Estás seguro de cancelar esta solicitud?')) {
            clearInterval(pollingInterval);
            stopTimer();
            window.location.reload();
        }
    });
})();
</script>
@endpush
