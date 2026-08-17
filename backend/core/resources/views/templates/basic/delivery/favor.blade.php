@extends($activeTemplate . 'layouts.frontend')

@section('content')
@unless(auth()->check())
    <div class="container" style="padding-top: 120px; text-align: center;">
        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 12px; padding: 40px; max-width: 500px; margin: 0 auto;">
            <i class="las la-lock" style="font-size: 48px; color: #ffc107;"></i>
            <h2 style="margin-top: 20px; color: #333;">Inicia Sesión Requerido</h2>
            <p style="color: #666; margin: 16px 0;">Para solicitar un favor, debes estar logueado.</p>
            <button class="btn btn-primary" onclick="showLoginModal()"><i class="las la-sign-in-alt"></i> Inicia Sesión</button>
        </div>
    </div>
@else
<main class="favor-app">
    <section class="favor-hero">
        <div class="container">
            <div class="favor-hero__content">
                <span>Lizto Favor</span>
                <h1>Pide lo que necesites,<br>nosotros lo hacemos Lizto</h1>
                <p>Compramos por ti o enviamos lo que quieras. Rápido y seguro.</p>
            </div>
            <div class="favor-form-wrapper" id="favorFormWrapper">
                <div id="step-form" class="favor-step">
                    <h3><i class="las la-box"></i> Solicitar Favor</h3>

                    <form id="favorCreateForm" method="POST" action="{{ route('favor.create') }}">
                        @csrf

                        <!-- Type toggle -->
                        <div class="form-group">
                            <label>Tipo de Favor</label>
                            <div class="favor-type-toggle">
                                <label class="favor-type-option selected" data-type="buy">
                                    <input type="radio" name="type" value="buy" checked hidden>
                                    <div class="ft-icon"><i class="las la-shopping-cart"></i></div>
                                    <div><strong>Comprar para mí</strong><small>Compramos algo y te lo llevamos</small></div>
                                </label>
                                <label class="favor-type-option" data-type="send">
                                    <input type="radio" name="type" value="send" hidden>
                                    <div class="ft-icon"><i class="las la-paper-plane"></i></div>
                                    <div><strong>Enviar algo</strong><small>Recogemos y entregamos lo que quieras</small></div>
                                </label>
                            </div>
                        </div>

                        <!-- Conditional fields for buy -->
                        <div id="buy-fields">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nombre de la Tienda</label>
                                    <input type="text" name="store_name" class="form-control" placeholder="Ej: Bodega La Esquina">
                                </div>
                                <div class="form-group">
                                    <label>Monto estimado de compra (S/)</label>
                                    <input type="number" name="estimated_amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Dirección de la Tienda</label>
                                <input type="text" name="store_address" class="form-control" placeholder="Dirección donde comprar">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>¿Qué necesitas? <small style="color:#68736c">(descripción detallada)</small></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Ej: 2 panes de molde, 1 leche Gloria, 6 huevos..." required></textarea>
                        </div>

                        <!-- Pickup -->
                        <label style="font-weight:700;font-size:13px;color:#374151;display:block;margin-bottom:4px">
                            <i class="las la-map-pin" style="color:#16a34a"></i> Punto de Recogida
                        </label>
                        <div class="form-group">
                            <input type="text" id="pickup-location" class="form-control" name="pickup_address" placeholder="Dirección de recogida" required autocomplete="off">
                            <input type="hidden" id="pickup-lat" name="pickup_lat">
                            <input type="hidden" id="pickup-lng" name="pickup_lng">
                            <button type="button" class="location-btn" onclick="useMyLocation('pickup')">
                                <i class="las la-crosshairs"></i> Usar mi ubicación
                            </button>
                        </div>

                        <!-- Delivery -->
                        <label style="font-weight:700;font-size:13px;color:#374151;display:block;margin-bottom:4px">
                            <i class="las la-flag-checkered" style="color:#16a34a"></i> Punto de Entrega
                        </label>
                        <div class="form-group">
                            <input type="text" id="delivery-location" class="form-control" name="delivery_address" placeholder="Dirección de entrega" required autocomplete="off">
                            <input type="hidden" id="delivery-lat" name="delivery_lat">
                            <input type="hidden" id="delivery-lng" name="delivery_lng">
                            <button type="button" class="location-btn" onclick="useMyLocation('delivery')">
                                <i class="las la-crosshairs"></i> Usar mi ubicación
                            </button>
                        </div>

                        <!-- Recipient -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre de quien recibe</label>
                                <input type="text" name="recipient_name" class="form-control" placeholder="Opcional">
                            </div>
                            <div class="form-group">
                                <label>Teléfono de quien recibe</label>
                                <input type="text" name="recipient_phone" class="form-control" placeholder="Opcional">
                            </div>
                        </div>

                        <!-- Payment -->
                        <div class="form-group">
                            <label>Método de Pago</label>
                            <div class="payment-methods" id="favor-payment-methods" style="display:grid;gap:8px">
                                <label class="favor-pm selected" data-code="0">
                                    <input type="radio" name="pm_code" value="0" checked hidden>
                                    <i class="las la-money-bill-wave"></i>
                                    <span>Efectivo</span>
                                </label>
                                @foreach($gateways as $gw)
                                <label class="favor-pm" data-code="{{ $gw['code'] }}">
                                    <input type="radio" name="pm_code" value="{{ $gw['code'] }}" hidden>
                                    <img src="{{ $gw['image'] }}" alt="{{ $gw['name'] }}" style="width:22px;height:22px;object-fit:contain">
                                    <span>{{ $gw['name'] }}</span>
                                </label>
                                @endforeach
                            </div>
                            <input type="hidden" name="payment_method_code" id="favor-payment-code" value="0">
                        </div>

                        <!-- Fee estimate -->
                        <button type="button" class="favor-btn favor-btn--outline" id="btn-estimate" onclick="estimateFee()" style="margin-bottom:10px">
                            <i class="las la-calculator"></i> Calcular Envío
                        </button>
                        <div id="fee-estimate" style="display:none;background:#f8fdf8;padding:14px;border-radius:12px;margin-bottom:12px;font-size:14px">
                            <div style="display:flex;justify-content:space-between;padding:6px 0"><span>Distancia</span><b id="est-distance">-</b></div>
                            <div style="display:flex;justify-content:space-between;padding:6px 0"><span>Costo de envío</span><b id="est-fee" style="color:#16a34a">-</b></div>
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-top:1px solid #e0eee2;margin-top:4px;font-weight:800"><span>Total con compra</span><b id="est-total" style="color:#16a34a">-</b></div>
                        </div>

                        <button type="submit" class="favor-btn" id="btn-submit">
                            <i class="las la-paper-plane"></i> Solicitar Favor
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="favor-info">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6"><div class="favor-f"><div class="icon"><i class="las la-shopping-basket"></i></div><h3>Compramos por ti</h3><p>Danos la lista y nosotros la compramos</p></div></div>
                <div class="col-lg-3 col-md-6"><div class="favor-f"><div class="icon"><i class="las la-shipping-fast"></i></div><h3>Envío Express</h3><p>Recogemos y entregamos en minutos</p></div></div>
                <div class="col-lg-3 col-md-6"><div class="favor-f"><div class="icon"><i class="las la-lock"></i></div><h3>Seguro</h3><p>PIN de entrega para verificar</p></div></div>
                <div class="col-lg-3 col-md-6"><div class="favor-f"><div class="icon"><i class="las la-clock"></i></div><h3>24/7</h3><p>Siempre disponible</p></div></div>
            </div>
        </div>
    </section>
</main>

<style>
.favor-app {
    padding-top: 110px;
    background: radial-gradient(circle at 10% 20%, rgba(34, 197, 94, 0.08), transparent 40%), 
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.05), transparent 45%), 
                #f8fafc;
    min-height: 100vh;
}
.favor-hero {
    padding: 60px 0;
    text-align: center;
}
.favor-hero__content span {
    display: inline-block;
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 16px;
}
.favor-hero__content h1 {
    font-size: clamp(32px, 5vw, 52px);
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 12px;
    letter-spacing: -1.5px;
}
.favor-hero__content p {
    font-size: 17px;
    color: #475569;
    max-width: 500px;
    margin: 0 auto 36px;
}
.favor-form-wrapper {
    max-width: 580px;
    margin: 0 auto;
    text-align: left;
}
.favor-step {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    padding: 40px;
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.06);
}
.favor-step h3 {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.favor-step h3 i {
    color: #22c55e;
}
.favor-type-toggle {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}
.favor-type-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.25s ease;
    background: #fff;
}
.favor-type-option.selected {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.04);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.05);
}
.ft-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(34, 197, 94, 0.08);
    display: grid;
    place-items: center;
    font-size: 22px;
    color: #15803d;
}
.favor-type-option strong {
    display: block;
    font-size: 14px;
    color: #0f172a;
}
.favor-type-option small {
    font-size: 11px;
    color: #475569;
}
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block;
    font-weight: 700;
    font-size: 13.5px;
    color: #334155;
    margin-bottom: 6px;
}
.form-group input, .form-group textarea, .form-group select {
    width: 100%;
    padding: 14px 18px;
    border: 1px solid #e2e8f0;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 14px;
    font-size: 14.5px;
    color: #0f172a;
    box-sizing: border-box;
    transition: all 0.25s ease;
}
.form-group input:focus, .form-group textarea:focus {
    outline: 0;
    border-color: #22c55e;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.location-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 6px 12px;
    border: 1px solid rgba(34, 197, 94, 0.2);
    border-radius: 20px;
    background: rgba(34, 197, 94, 0.05);
    color: #15803d;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.location-btn:hover {
    background: rgba(34, 197, 94, 0.15);
    color: #166534;
}
.favor-btn {
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.favor-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(34, 197, 94, 0.3);
}
.favor-btn--outline {
    background: transparent;
    border: 2px solid #e2e8f0;
    color: #475569;
}
.favor-btn--outline:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: none;
    box-shadow: none;
}
.favor-pm {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    cursor: pointer;
    font-size: 14.5px;
    font-weight: 600;
    background: #fff;
    transition: all 0.25s;
}
.favor-pm.selected {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.04);
}
.favor-pm i {
    font-size: 22px;
    color: #15803d;
}
.favor-info {
    padding: 80px 0;
    background: #fff;
    border-top: 1px solid rgba(0,0,0,0.04);
}
.favor-f {
    text-align: center;
    padding: 32px 24px;
    border-radius: 20px;
    background: #f8fafc;
    border: 1px solid rgba(0, 0, 0, 0.03);
    height: 100%;
    transition: all 0.25s;
}
.favor-f:hover {
    transform: translateY(-4px);
    border-color: rgba(34, 197, 94, 0.2);
}
.favor-f .icon {
    font-size: 44px;
    color: #22c55e;
    margin-bottom: 16px;
}
.favor-f h3 {
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 8px;
    color: #0f172a;
}
.favor-f p {
    color: #475569;
    font-size: 13.5px;
    margin: 0;
    line-height: 1.5;
}
#buy-fields {
    display: block;
}
</style>
<style>
.favor-app{background:#fff;color:#101828}
.favor-hero{position:relative;overflow:hidden;background:linear-gradient(180deg,#f6fbf7 0%,#fff 82%)!important;padding:104px 0 76px!important}
.favor-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 84% 12%,rgba(34,197,94,.2),transparent 30%),linear-gradient(90deg,rgba(22,163,74,.1),transparent 42%);pointer-events:none}
.favor-hero .container{position:relative;z-index:1}
.favor-hero__content span{display:inline-flex!important;align-items:center;gap:8px;border-radius:999px!important;background:#fff!important;border:1px solid rgba(22,163,74,.22)!important;color:#137b3b!important;font-weight:900!important;padding:9px 14px!important}
.favor-hero__content h1{font-family:Outfit,Inter,sans-serif!important;font-size:clamp(38px,5vw,64px)!important;line-height:1!important;font-weight:900!important;color:#101828!important;letter-spacing:0!important}
.favor-hero__content p{font-size:18px!important;line-height:1.7!important;color:#475467!important}
.favor-form-wrapper,.favor-features .favor-f,.favor-step,.favor-type-option,.favor-pm{border-radius:8px!important;border-color:#e7eaee!important;box-shadow:0 18px 45px rgba(16,24,40,.08)!important}
.favor-type-option.selected,.favor-pm.selected{border-color:#16a34a!important;background:#f0fdf4!important}
.favor-step h3,.favor-f h3{font-family:Outfit,Inter,sans-serif!important;color:#101828!important;font-weight:900!important}
.favor-step .form-control,.location-btn,.favor-btn{border-radius:8px!important}
.favor-btn,.location-btn{font-weight:900!important}
.favor-btn{background:#16a34a!important;box-shadow:0 14px 30px rgba(22,163,74,.2)!important}
body .container[style*="padding-top: 120px"]>div{border-radius:8px!important;border:1px solid #e7eaee!important;box-shadow:0 18px 45px rgba(16,24,40,.08)!important;background:#fff!important}
body .container[style*="padding-top: 120px"] h2{font-family:Outfit,Inter,sans-serif!important;color:#101828!important;font-weight:900!important}
body .container[style*="padding-top: 120px"] .btn{background:#16a34a!important;border-color:#16a34a!important;border-radius:8px!important;font-weight:900!important}
@media(max-width:767px){.favor-hero{padding:82px 0 56px!important}.favor-hero__content h1{font-size:38px!important}}
</style>

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script>
var pickupLat = null, pickupLng = null, deliveryLat = null, deliveryLng = null;

window.addEventListener('load', function() {
    if (window.google && google.maps.places) {
        new google.maps.places.Autocomplete(document.getElementById('pickup-location'), {
            componentRestrictions: {country: 'pe'}
        }).addListener('place_changed', function() {
            var p = this.getPlace();
            if (p.geometry) { pickupLat = p.geometry.location.lat(); pickupLng = p.geometry.location.lng();
                document.getElementById('pickup-lat').value = pickupLat; document.getElementById('pickup-lng').value = pickupLng; }
        });
        new google.maps.places.Autocomplete(document.getElementById('delivery-location'), {
            componentRestrictions: {country: 'pe'}
        }).addListener('place_changed', function() {
            var p = this.getPlace();
            if (p.geometry) { deliveryLat = p.geometry.location.lat(); deliveryLng = p.geometry.location.lng();
                document.getElementById('delivery-lat').value = deliveryLat; document.getElementById('delivery-lng').value = deliveryLng; }
        });
    }

    // Type toggle
    document.querySelectorAll('.favor-type-option').forEach(function(o) {
        o.addEventListener('click', function() {
            document.querySelectorAll('.favor-type-option').forEach(function(x) { x.classList.remove('selected'); });
            o.classList.add('selected');
            o.querySelector('input').checked = true;
            document.getElementById('buy-fields').style.display = o.dataset.type === 'buy' ? 'block' : 'none';
        });
    });

    // Payment toggle
    document.querySelectorAll('.favor-pm').forEach(function(pm) {
        pm.addEventListener('click', function() {
            document.querySelectorAll('.favor-pm').forEach(function(x) { x.classList.remove('selected'); });
            pm.classList.add('selected');
            document.getElementById('favor-payment-code').value = pm.dataset.code;
        });
    });
});

function useMyLocation(type) {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function(pos) {
        if (type === 'pickup') { pickupLat = pos.coords.latitude; pickupLng = pos.coords.longitude;
            document.getElementById('pickup-lat').value = pickupLat; document.getElementById('pickup-lng').value = pickupLng; }
        else { deliveryLat = pos.coords.latitude; deliveryLng = pos.coords.longitude;
            document.getElementById('delivery-lat').value = deliveryLat; document.getElementById('delivery-lng').value = deliveryLng; }
        new google.maps.Geocoder().geocode({location: {lat: pos.coords.latitude, lng: pos.coords.longitude}}, function(r) {
            if (r && r[0]) document.getElementById(type + '-location').value = r[0].formatted_address;
        });
    });
}

function estimateFee() {
    var btn = document.getElementById('btn-estimate');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Calculando...';

    var pLat = pickupLat || parseFloat(document.getElementById('pickup-lat').value) || null;
    var pLng = pickupLng || parseFloat(document.getElementById('pickup-lng').value) || null;
    var dLat = deliveryLat || parseFloat(document.getElementById('delivery-lat').value) || null;
    var dLng = deliveryLng || parseFloat(document.getElementById('delivery-lng').value) || null;

    if (!pLat || !pLng || !dLat || !dLng) {
        alert('Ingresa direcciones válidas para recogida y entrega');
        btn.disabled = false; btn.innerHTML = '<i class="las la-calculator"></i> Calcular Envío';
        return;
    }

    fetch('/delivery/fee-estimate', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
        body: JSON.stringify({pickup_lat:pLat,pickup_lng:pLng,delivery_lat:dLat,delivery_lng:dLng})
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        document.getElementById('est-distance').textContent = (d.distance_km||0).toFixed(1)+' km';
        document.getElementById('est-fee').textContent = 'S/ '+(d.delivery_fee||0).toFixed(2);
        var amt = parseFloat(document.querySelector('[name="estimated_amount"]').value) || 0;
        document.getElementById('est-total').textContent = 'S/ '+((d.delivery_fee||0)+amt).toFixed(2);
        document.getElementById('fee-estimate').style.display = 'block';
        btn.disabled = false; btn.innerHTML = '<i class="las la-calculator"></i> Calcular Envío';
    })
    .catch(function(e) {
        console.error(e);
        alert('Error al calcular tarifa');
        btn.disabled = false; btn.innerHTML = '<i class="las la-calculator"></i> Calcular Envío';
    });
}
</script>
@endpush
@endunless
@endsection
