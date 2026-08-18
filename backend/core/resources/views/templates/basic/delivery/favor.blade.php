@extends($activeTemplate . 'layouts.frontend')

@section('content')
@unless(auth()->check())
    <div class="container py-5" style="min-height:75vh;display:flex;align-items:center;justify-content:center;">
        <div class="lz-order-card text-center p-5" style="max-width: 480px;">
            <div class="lz-order-status-icon-wrap mb-3" style="background:var(--lz-primary-light);color:var(--lz-primary);">
                <i class="las la-hand-holding-heart"></i>
            </div>
            <h2 class="lz-order-headline mb-2">Inicia sesión en Lizto</h2>
            <p class="text-muted mb-4" style="font-size:14px;">Para solicitar mandados, compras o envíos con Lizto Favor, debes ingresar a tu cuenta.</p>
            <button class="lz-btn-cta w-100 justify-content-center" onclick="showLoginModal()">
                <i class="las la-sign-in-alt me-2"></i> Iniciar Sesión o Registrarse
            </button>
        </div>
    </div>
@else
<main class="lz-favor-page">
    <div class="container">
        <a class="lz-back-btn mb-3" href="{{ route('delivery.marketplace') }}">
            <i class="las la-arrow-left"></i> Volver al Marketplace
        </a>

        <div class="row align-items-center mb-4">
            <div class="col-12 text-center text-md-start">
                <span class="lz-brand-city mb-2"><i class="las la-bolt"></i> Mandados & Envíos</span>
                <h1 class="lz-store-title">¿Qué favor necesitas hoy?</h1>
                <p class="lz-store-desc">Compramos por ti o enviamos paquetes en cualquier punto de Tarapoto y alrededores.</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="lz-checkout-card">
                    <form id="favorCreateForm" method="POST" action="{{ route('favor.create') }}">
                        @csrf

                        {{-- Type Selection Cards --}}
                        <label class="lz-form-label">Tipo de servicio</label>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="lz-payment-card selected" id="opt-buy" onclick="setFavorType('buy')">
                                    <input type="radio" name="type" value="buy" checked hidden>
                                    <div class="lz-pm-icon"><i class="las la-shopping-cart"></i></div>
                                    <div class="lz-pm-info">
                                        <strong>Comprar algo para mí</strong>
                                        <small>Farmacia, bodega, mercado, etc.</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <label class="lz-payment-card" id="opt-send" onclick="setFavorType('send')">
                                    <input type="radio" name="type" value="send" hidden>
                                    <div class="lz-pm-icon"><i class="las la-paper-plane"></i></div>
                                    <div class="lz-pm-info">
                                        <strong>Enviar un paquete</strong>
                                        <small>Recogemos y entregamos</small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Buy Specific Fields --}}
                        <div id="buy-fields" class="mb-3">
                            <div class="row g-3 mb-3">
                                <div class="col-sm-7">
                                    <label class="lz-form-label">¿Dónde compramos? (Tienda / Lugar)</label>
                                    <input type="text" name="store_name" class="lz-form-control" placeholder="Ej: InkaFarma Plaza Mayor o Bodega San Juan">
                                </div>
                                <div class="col-sm-5">
                                    <label class="lz-form-label">Presupuesto aprox. (S/)</label>
                                    <input type="number" name="estimated_amount" id="est-amount-input" class="lz-form-control" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="mb-4">
                            <label class="lz-form-label">¿Qué necesitas exactamente? (Lista detallada)</label>
                            <textarea name="description" class="lz-form-control" rows="3" placeholder="Ej: 1 jarabe para la tos, 10 mascarillas, 1 botella de agua mineral de 1L..." required></textarea>
                        </div>

                        {{-- Pickup location --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="lz-form-label mb-0"><i class="las la-map-pin text-success"></i> Punto de Recogida</label>
                                <button type="button" class="btn btn-link btn-sm text-success p-0 fw-bold" onclick="useMyLocation('pickup')">
                                    <i class="las la-crosshairs"></i> Usar mi GPS
                                </button>
                            </div>
                            <div class="lz-input-with-icon">
                                <i class="las la-map-marker-alt lz-field-icon"></i>
                                <input type="text" id="pickup-location" class="lz-form-control" name="pickup_address" placeholder="Dirección de recogida en Tarapoto" required autocomplete="off">
                            </div>
                            <input type="hidden" id="pickup-lat" name="pickup_lat">
                            <input type="hidden" id="pickup-lng" name="pickup_lng">
                        </div>

                        {{-- Delivery location --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="lz-form-label mb-0"><i class="las la-flag-checkered text-success"></i> Punto de Entrega</label>
                                <button type="button" class="btn btn-link btn-sm text-success p-0 fw-bold" onclick="useMyLocation('delivery')">
                                    <i class="las la-crosshairs"></i> Usar mi GPS
                                </button>
                            </div>
                            <div class="lz-input-with-icon">
                                <i class="las la-map-marker-alt lz-field-icon"></i>
                                <input type="text" id="delivery-location" class="lz-form-control" name="delivery_address" placeholder="Dirección donde entregaremos" required autocomplete="off">
                            </div>
                            <input type="hidden" id="delivery-lat" name="delivery_lat">
                            <input type="hidden" id="delivery-lng" name="delivery_lng">
                        </div>

                        {{-- Recipient info --}}
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="lz-form-label">Nombre de quien recibe (opcional)</label>
                                <input type="text" name="recipient_name" class="lz-form-control" placeholder="Ej: Juan Pérez">
                            </div>
                            <div class="col-sm-6">
                                <label class="lz-form-label">Celular de quien recibe (opcional)</label>
                                <input type="tel" name="recipient_phone" class="lz-form-control" placeholder="Ej: 997428341">
                            </div>
                        </div>

                        {{-- Fee Estimate Calculator --}}
                        <div class="p-3 mb-4" style="background:var(--lz-surface-muted);border-radius:14px;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark" style="font-size:13.5px;"><i class="las la-calculator text-success"></i> Estimación de Delivery</span>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold" onclick="estimateFavorFee()">
                                    Calcular Tarifa
                                </button>
                            </div>
                            <div id="favor-fee-box" style="display:none;" class="pt-2 border-top">
                                <div class="d-flex justify-content-between small text-muted py-1">
                                    <span>Distancia calculada:</span>
                                    <strong id="favor-dist-txt" class="text-dark">0 km</strong>
                                </div>
                                <div class="d-flex justify-content-between small text-muted py-1">
                                    <span>Tarifa de delivery estimada:</span>
                                    <strong id="favor-fee-txt" class="text-success font-weight-bold">S/ 6.00</strong>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="lz-btn-cta w-100 justify-content-center py-3" id="btn-submit-favor">
                            <i class="las la-paper-plane me-2"></i> Solicitar Lizto Favor Ahora
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endunless
@endsection

@push('style')
<style>
.lz-favor-page {
    padding: 24px 0 60px;
    background: var(--lz-bg);
    min-height: 100vh;
}
</style>
@endpush

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script>
function setFavorType(type) {
    var buyOpt = document.getElementById('opt-buy');
    var sendOpt = document.getElementById('opt-send');
    var buyFields = document.getElementById('buy-fields');

    if (type === 'buy') {
        buyOpt.classList.add('selected');
        buyOpt.querySelector('input').checked = true;
        sendOpt.classList.remove('selected');
        if (buyFields) buyFields.style.display = 'block';
    } else {
        sendOpt.classList.add('selected');
        sendOpt.querySelector('input').checked = true;
        buyOpt.classList.remove('selected');
        if (buyFields) buyFields.style.display = 'none';
    }
}

function useMyLocation(target) {
    if (!navigator.geolocation) {
        alert("Tu navegador no soporta geolocalización.");
        return;
    }
    navigator.geolocation.getCurrentPosition(function(pos) {
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        var latEl = document.getElementById(target + '-lat');
        var lngEl = document.getElementById(target + '-lng');
        var locEl = document.getElementById(target + '-location');

        if (latEl) latEl.value = lat;
        if (lngEl) lngEl.value = lng;

        if (window.google && google.maps && google.maps.Geocoder) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    locEl.value = results[0].formatted_address;
                } else {
                    locEl.value = "Mi ubicación actual (" + lat.toFixed(4) + ", " + lng.toFixed(4) + ")";
                }
            });
        } else {
            locEl.value = "Mi ubicación actual (" + lat.toFixed(4) + ", " + lng.toFixed(4) + ")";
        }
    }, function() {
        alert("No pudimos obtener tu ubicación GPS.");
    });
}

function estimateFavorFee() {
    var pLat = document.getElementById('pickup-lat').value;
    var pLng = document.getElementById('pickup-lng').value;
    var dLat = document.getElementById('delivery-lat').value;
    var dLng = document.getElementById('delivery-lng').value;

    if (!pLat || !pLng || !dLat || !dLng) {
        alert("Por favor selecciona los puntos de recogida y entrega para calcular la distancia.");
        return;
    }

    fetch('/delivery/fee-estimate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ pickup_lat: pLat, pickup_lng: pLng, delivery_lat: dLat, delivery_lng: dLng })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        var feeBox = document.getElementById('favor-fee-box');
        if (feeBox) feeBox.style.display = 'block';
        if (res.distance_km != null) document.getElementById('favor-dist-txt').textContent = res.distance_km.toFixed(1) + ' km';
        if (res.delivery_fee != null) document.getElementById('favor-fee-txt').textContent = 'S/ ' + Number(res.delivery_fee).toFixed(2);
    })
    .catch(function(){});
}

window.addEventListener('load', function() {
    var pInput = document.getElementById('pickup-location');
    var dInput = document.getElementById('delivery-location');

    if (window.google && google.maps && google.maps.places) {
        if (pInput) {
            var autoP = new google.maps.places.Autocomplete(pInput, { componentRestrictions: { country: 'pe' } });
            autoP.addListener('place_changed', function() {
                var p = autoP.getPlace();
                if (p.geometry) {
                    document.getElementById('pickup-lat').value = p.geometry.location.lat();
                    document.getElementById('pickup-lng').value = p.geometry.location.lng();
                }
            });
        }
        if (dInput) {
            var autoD = new google.maps.places.Autocomplete(dInput, { componentRestrictions: { country: 'pe' } });
            autoD.addListener('place_changed', function() {
                var p = autoD.getPlace();
                if (p.geometry) {
                    document.getElementById('delivery-lat').value = p.geometry.location.lat();
                    document.getElementById('delivery-lng').value = p.geometry.location.lng();
                }
            });
        }
    }
});
</script>
@endpush
