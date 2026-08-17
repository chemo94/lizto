@extends($activeTemplate . 'layouts.frontend')

@section('content')
@php
    $cartItems = array_values($cart);
    $subtotal = collect($cartItems)->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 0));
    $tipAmount = old('tip', 0);
    $total = $subtotal + $deliveryFee + (float) $tipAmount;
@endphp

<main class="checkout-page">
    <div class="container">
        <a class="checkout-back" href="{{ route('delivery.store', $store) }}"><i class="las la-arrow-left"></i> Volver a la tienda</a>

        @if($errors->any())
        <div class="checkout-errors">
            @foreach($errors->all() as $error)<small>{{ $error }}</small>@endforeach
        </div>
        @endif

        <h1 class="checkout-title">Finaliza tu pedido</h1>

        <div class="checkout-layout">
            <div class="checkout-main">
                <section class="checkout-card">
                    <h2><i class="las la-map-marker-alt"></i> Datos de entrega</h2>
                    <form action="{{ route('delivery.checkout.submit') }}" method="POST" id="checkout-form" novalidate>
                        @csrf
                        <input type="hidden" name="store_id" value="{{ $store->id }}">
                        <input type="hidden" name="delivery_lat" id="delivery-lat" value="{{ old('delivery_lat', $location['lat'] ?? '') }}">
                        <input type="hidden" name="delivery_lng" id="delivery-lng" value="{{ old('delivery_lng', $location['lng'] ?? '') }}">
                        <input type="hidden" name="payment_method_code" id="payment-method-code" value="{{ old('payment_method_code', '0') }}">
                        <input type="hidden" name="tip_amount" id="tip-amount" value="{{ old('tip', 0) }}">

                        <div class="checkout-grid">
                            <div class="checkout-field">
                                <label>Nombre</label>
                                <input name="contact_name" value="{{ old('contact_name', auth()->user()->firstname ?? $userInfo['firstname'] ?? '') }}" placeholder="Tu nombre completo" required>
                            </div>
                            <div class="checkout-field">
                                <label>Teléfono</label>
                                <input name="contact_phone" value="{{ old('contact_phone', auth()->user()->mobile ?? $userInfo['mobile'] ?? '') }}" placeholder="Celular / WhatsApp" required>
                            </div>
                        </div>

                        <div class="checkout-field">
                            <label>Dirección de entrega</label>
                            <input name="delivery_address" id="checkout-address" value="{{ old('delivery_address', $location['label'] ?? '') }}" placeholder="Calle, número, referencia" required autocomplete="off">
                        </div>

                        <div class="checkout-field">
                            <label>Notas (opcional)</label>
                            <textarea name="notes" rows="2" placeholder="Timbre no funciona, dejar con el portero...">{{ old('notes') }}</textarea>
                        </div>

                        <h2 style="margin-top:32px;"><i class="las la-credit-card"></i> Método de pago</h2>
                        <div class="payment-methods" id="payment-methods">
                            <label class="payment-option {{ old('payment_method_code', '0') == '0' ? 'selected' : '' }}" data-code="0">
                                <input type="radio" name="pm_code" value="0" {{ old('payment_method_code', '0') == '0' ? 'checked' : '' }}>
                                <span class="pm-icon"><i class="las la-money-bill-wave"></i></span>
                                <span class="pm-info">
                                    <strong>Efectivo</strong>
                                    <small>Paga al recibir tu pedido</small>
                                </span>
                            </label>
                            @foreach($gateways as $gw)
                            <label class="payment-option {{ old('payment_method_code') == $gw['code'] ? 'selected' : '' }}" data-code="{{ $gw['code'] }}">
                                <input type="radio" name="pm_code" value="{{ $gw['code'] }}" {{ old('payment_method_code') == $gw['code'] ? 'checked' : '' }}>
                                <span class="pm-icon">
                                    @if($gw['image'])
                                        <img src="{{ getImage(getFilePath('gateway') . '/' . $gw['image']) }}" alt="{{ $gw['name'] }}" style="width:28px;height:28px;object-fit:contain;">
                                    @else
                                        <i class="las la-credit-card"></i>
                                    @endif
                                </span>
                                <span class="pm-info">
                                    <strong>{{ $gw['name'] }}</strong>
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </form>
                </section>
            </div>

            <aside class="checkout-sidebar">
                <div class="checkout-card checkout-summary">
                    <h2><i class="las la-shopping-bag"></i> Tu pedido</h2>
                    <div class="summary-store">{{ $store->name }}</div>

                    <div class="summary-items">
                        @foreach($cartItems as $item)
                        <div class="summary-row">
                            <span>{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                            <span>S/ {{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 2) }}</span>
                        </div>
                        @endforeach
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>S/ {{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Envío</span>
                        <span id="summary-delivery-fee">S/ {{ number_format($deliveryFee, 2) }}</span>
                    </div>
                    <div id="distance-details" style="{{ isset($estimate['distance_km']) ? '' : 'display:none' }}">
                        <div class="summary-row summary-detail">
                            <span>Distancia</span>
                            <span id="summary-distance">{{ isset($estimate['distance_km']) ? number_format($estimate['distance_km'], 2) . ' km' : '0.00 km' }}</span>
                        </div>
                        <div class="summary-row summary-detail">
                            <span>Tarifa base</span>
                            <span id="summary-base-fare">S/ {{ number_format($estimate['base_fare'] ?? 4, 2) }}</span>
                        </div>
                        <div class="summary-row summary-detail">
                            <span>Distancia extra</span>
                            <span id="summary-distance-fee">S/ {{ number_format($estimate['distance_fee'] ?? 0, 2) }}</span>
                        </div>
                        <div class="summary-row summary-detail">
                            <span>Tiempo est. (<span id="summary-time-label">~{{ round($estimate['time_min'] ?? 0) }} min</span>)</span>
                            <span id="summary-time-fee">S/ {{ number_format($estimate['time_fee'] ?? 0, 2) }}</span>
                        </div>
                        <div class="summary-row surge-row" id="summary-surge-row" style="{{ (($estimate['surge'] ?? 1) > 1) ? '' : 'display:none' }}">
                            <span>Demanda alta (×<span id="summary-surge-mult">{{ number_format($estimate['surge'] ?? 1, 2) }}</span>)</span>
                            <span id="summary-surge-fee">+{{ round((($estimate['base_fare'] ?? 0) + ($estimate['distance_fee'] ?? 0) + ($estimate['time_fee'] ?? 0)) * (($estimate['surge'] ?? 1) - 1), 2) }}</span>
                        </div>
                    </div>

                    <div style="margin-bottom:12px">
                        <label style="font-size:12px;font-weight:700;color:#555;display:block;margin-bottom:4px">¿Tienes un cupón?</label>
                        <div style="display:flex;gap:6px">
                            <input type="text" name="coupon_code" class="coupon-input" placeholder="Código de cupón" style="flex:1" value="{{ old('coupon_code') }}">
                            <button type="button" onclick="applyCoupon()" class="tip-btn" style="white-space:nowrap">Aplicar</button>
                        </div>
                        <div id="coupon-msg" style="font-size:11px;margin-top:4px"></div>
                    </div>

                    <div class="tip-section">
                        <span>Propina para el repartidor</span>
                        <div class="tip-options">
                            @foreach([0, 1, 2, 5] as $t)
                            <button type="button" class="tip-btn {{ old('tip', 0) == $t ? 'active' : '' }}" data-amount="{{ $t }}" onclick="setTip({{ $t }})">S/ {{ $t }}</button>
                            @endforeach
                            <button type="button" class="tip-btn tip-custom {{ !in_array(old('tip', 0), [0,1,2,5]) && old('tip', 0) > 0 ? 'active' : '' }}" data-amount="custom" onclick="setCustomTip()">Otro</button>
                        </div>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-total">
                        <span>Total</span>
                        <span id="summary-total">S/ {{ number_format($total, 2) }}</span>
                    </div>

                    <button type="button" class="checkout-submit" id="submit-order" onclick="submitOrder()">
                        Confirmar pedido <i class="las la-arrow-right"></i>
                    </button>
                </div>
            </aside>
        </div>
    </div>
</main>
@endsection

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script>
(function() {
    var tip = {{ old('tip', 0) }};
    var subtotal = {{ $subtotal }};
    var deliveryFee = {{ $deliveryFee }};

    function money(v) { return 'S/ ' + Number(v).toFixed(2); }

    window.setTip = function(amount) {
        tip = amount;
        document.querySelectorAll('.tip-btn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelector('[data-amount="' + amount + '"]')?.classList.add('active');
        updateTotal();
    };

    window.setCustomTip = function() {
        var val = parseFloat(prompt('Monto de propina (S/):', '0'));
        if (!isNaN(val) && val >= 0) {
            tip = val;
            document.querySelectorAll('.tip-btn').forEach(function(b){ b.classList.remove('active'); });
            document.querySelector('.tip-btn.tip-custom')?.classList.add('active');
            updateTotal();
        }
    };

    window.applyCoupon = function() {
        var code = document.querySelector('[name="coupon_code"]').value.trim();
        var msg = document.getElementById('coupon-msg');
        if (!code) { msg.innerHTML = ''; return; }
        msg.innerHTML = '<span style="color:#3b82f6">Validando...</span>';
        fetch('/api/validate-coupon', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'dev-token': '{{ developerToken() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ code: code, subtotal: subtotal })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'success') {
                discount = d.data.discount;
                msg.innerHTML = '<span style="color:#16a34a">✓ ' + d.data.description + ' — Descuento: S/ ' + discount.toFixed(2) + '</span>';
                updateTotal();
            } else {
                msg.innerHTML = '<span style="color:#dc2626">' + (d.message || 'Cupón inválido') + '</span>';
            }
        });
    };

    function updateTotal() {
        document.getElementById('summary-total').textContent = money(subtotal + deliveryFee + tip);
        document.getElementById('tip-amount').value = tip;
    }

    window.submitOrder = function() {
        var form = document.getElementById('checkout-form');
        var addr = document.getElementById('checkout-address');
        if (!addr.value.trim()) { alert('Ingresa tu dirección de entrega'); addr.focus(); return; }
        var btn = document.getElementById('submit-order');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
        form.submit();
    };

    document.querySelectorAll('.payment-option').forEach(function(opt) {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.payment-option').forEach(function(o){ o.classList.remove('selected'); });
            opt.classList.add('selected');
            opt.querySelector('input').checked = true;
            document.getElementById('payment-method-code').value = opt.dataset.code;
        });
    });

    window.addEventListener('load', function() {
        var address = document.getElementById('checkout-address');
        if (window.google && google.maps && google.maps.places) {
            var autocomplete = new google.maps.places.Autocomplete(address, { componentRestrictions: { country: 'pe' } });
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (place && place.geometry) {
                    var lat = place.geometry.location.lat();
                    var lng = place.geometry.location.lng();
                    document.getElementById('delivery-lat').value = lat;
                    document.getElementById('delivery-lng').value = lng;

                    // Save to session
                    var label = place.formatted_address || (lat.toFixed(6) + ', ' + lng.toFixed(6));
                    fetch('/location/save', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ lat: lat, lng: lng, label: label })
                    }).then(function() {
                        if (typeof window.updateHeaderLocation === 'function') window.updateHeaderLocation();
                    }).catch(function(){});

                    // Update UI delivery fee and total
                    fetch('/delivery/store-fee-estimate?store_id={{ $store->id }}&delivery_lat=' + lat + '&delivery_lng=' + lng)
                        .then(function(r) { return r.json(); })
                        .then(function(d) {
                            if (d && d.delivery_fee != null) {
                                deliveryFee = d.delivery_fee;
                                document.getElementById('summary-delivery-fee').textContent = money(deliveryFee);
                                
                                var details = document.getElementById('distance-details');
                                if (details) details.style.display = 'block';

                                var summaryDist = document.getElementById('summary-distance');
                                if (summaryDist && d.distance_km != null) {
                                    summaryDist.textContent = d.distance_km.toFixed(2) + ' km';
                                }
                                var summaryDistFee = document.getElementById('summary-distance-fee');
                                if (summaryDistFee && d.distance_fee != null) {
                                    summaryDistFee.textContent = money(d.distance_fee);
                                }
                                var summaryTimeFee = document.getElementById('summary-time-fee');
                                if (summaryTimeFee && d.time_fee != null) {
                                    var timeMin = d.time_min != null ? Math.round(d.time_min) : 0;
                                    var labelEl = document.getElementById('summary-time-label');
                                    if (labelEl) labelEl.textContent = '~' + timeMin + ' min';
                                    summaryTimeFee.textContent = money(d.time_fee);
                                }
                                var summaryBaseFee = document.getElementById('summary-base-fare');
                                if (summaryBaseFee && d.base_fare != null) {
                                    summaryBaseFee.textContent = money(d.base_fare);
                                }

                                var surgeRow = document.getElementById('summary-surge-row');
                                if (surgeRow) {
                                    if (d.surge > 1) {
                                        surgeRow.style.display = '';
                                        var multEl = document.getElementById('summary-surge-mult');
                                        if (multEl) multEl.textContent = d.surge.toFixed(2);
                                        var feeEl = document.getElementById('summary-surge-fee');
                                        if (feeEl) {
                                            var surgeFeeVal = (d.base_fare + d.distance_fee + d.time_fee) * (d.surge - 1);
                                            feeEl.textContent = '+' + surgeFeeVal.toFixed(2);
                                        }
                                    } else {
                                        surgeRow.style.display = 'none';
                                    }
                                }
                                
                                updateTotal();
                            }
                        }).catch(function(){});
                }
            });
        }

        address.addEventListener('input', function() {
            document.getElementById('delivery-lat').value = '';
            document.getElementById('delivery-lng').value = '';
            deliveryFee = 0;
            document.getElementById('summary-delivery-fee').textContent = money(0);
            var details = document.getElementById('distance-details');
            if (details) details.style.display = 'none';
            updateTotal();
        });
    });

    updateTotal();
})();
</script>
@endpush

@push('style')
<style>
.checkout-errors { padding: 12px 16px; border-radius: 12px; background: #fff1f1; color: #a92525; margin-bottom: 20px; display: grid; gap: 4px; }
.checkout-page { padding-top: 110px; padding-bottom: 80px; min-height: 100vh; background: #f8faf8; }
.checkout-back { display: inline-flex; align-items: center; gap: 6px; color: #16a34a; font-weight: 700; font-size: 14px; text-decoration: none; margin-bottom: 20px; }
.checkout-back:hover { color: #15803d; }
.checkout-title { font-size: 32px; font-weight: 800; letter-spacing: -1px; margin-bottom: 32px; }
.checkout-layout { display: grid; grid-template-columns: 1fr 380px; gap: 28px; align-items: start; }
.checkout-main { min-width: 0; }
.checkout-card { background: #fff; border-radius: 20px; padding: 28px; box-shadow: 0 2px 16px rgba(0,0,0,.04); margin-bottom: 24px; }
.checkout-card h2 { font-size: 18px; font-weight: 700; margin: 0 0 20px; display: flex; align-items: center; gap: 8px; }
.checkout-card h2 i { color: #16a34a; }
.checkout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.checkout-field { margin-bottom: 16px; }
.checkout-field label { display: block; font-size: 13px; font-weight: 700; color: #555; margin-bottom: 6px; }
.checkout-field input, .checkout-field textarea { width: 100%; padding: 12px 16px; border: 2px solid #e8e8f0; border-radius: 12px; font-size: 15px; outline: none; transition: border .2s; box-sizing: border-box; }
.checkout-field input:focus, .checkout-field textarea:focus { border-color: #16a34a; }
.checkout-field textarea { resize: vertical; }

.payment-methods { display: grid; gap: 10px; }
.payment-option { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border: 2px solid #e8e8f0; border-radius: 14px; cursor: pointer; transition: all .2s; }
.payment-option:hover { border-color: #c8e6c9; background: #f9fff9; }
.payment-option.selected { border-color: #16a34a; background: #f0fdf0; }
.payment-option input { display: none; }
.pm-icon { width: 44px; height: 44px; border-radius: 12px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #666; flex-shrink: 0; overflow: hidden; }
.payment-option.selected .pm-icon { background: #e8f5e9; color: #16a34a; }
.pm-info strong { display: block; font-size: 14px; color: #333; }
.pm-info small { font-size: 12px; color: #999; }

.checkout-sidebar { position: sticky; top: 110px; }
.summary-store { font-size: 13px; color: #999; margin-bottom: 16px; padding: 8px 12px; background: #f3f4f6; border-radius: 8px; }
.summary-items { margin-bottom: 4px; }
.summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; color: #555; }
.summary-divider { height: 1px; background: #f0f0f0; margin: 12px 0; }
.summary-total { display: flex; justify-content: space-between; padding: 8px 0; font-size: 20px; font-weight: 800; color: #1a1a2e; }
.summary-detail { font-size: 12px; color: #999; padding: 3px 0 3px 12px; }
.summary-detail span:first-child::before { content: '• '; }
.surge-row { color: #e67e22 !important; font-weight: 600 !important; }

.tip-section { margin-top: 12px; }
.tip-section > span { font-size: 13px; font-weight: 600; color: #555; display: block; margin-bottom: 8px; }
.tip-options { display: flex; gap: 6px; flex-wrap: wrap; }
.tip-btn { padding: 8px 16px; border: 2px solid #e8e8f0; border-radius: 10px; background: #fff; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; }
.tip-btn:hover { border-color: #16a34a; }
.tip-btn.active { background: #16a34a; color: #fff; border-color: #16a34a; }

.checkout-submit { width: 100%; margin-top: 20px; padding: 16px; border: none; border-radius: 14px; background: linear-gradient(135deg, #16a34a, #15803d); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all .25s; }
.checkout-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(22,163,74,.35); }
.checkout-submit:disabled { opacity: .7; pointer-events: none; }

.coupon-input {
    padding: 10px 14px !important;
    border: 2px solid #e8e8f0 !important;
    border-radius: 10px !important;
    font-size: 14px !important;
    outline: none !important;
    transition: border-color 0.2s !important;
    background: #fff !important;
    box-sizing: border-box;
}
.coupon-input:focus {
    border-color: #16a34a !important;
}

@media (max-width: 768px) {
    .checkout-layout { grid-template-columns: 1fr; }
    .checkout-sidebar { position: static; }
    .checkout-grid { grid-template-columns: 1fr; }
}
.checkout-page{background:linear-gradient(180deg,#f6fbf7 0%,#fff 360px)!important;color:#101828!important}
.checkout-title{font-family:Outfit,Inter,sans-serif!important;font-size:clamp(32px,4vw,48px)!important;line-height:1.08!important;font-weight:900!important;color:#101828!important;letter-spacing:0!important}
.checkout-back{background:#fff!important;border:1px solid rgba(22,163,74,.22)!important;border-radius:999px!important;padding:8px 12px!important}
.checkout-card{border:1px solid #e7eaee!important;border-radius:8px!important;box-shadow:0 18px 45px rgba(16,24,40,.08)!important}
.checkout-card h2{font-family:Outfit,Inter,sans-serif!important;color:#101828!important;font-weight:900!important}
.checkout-field label{color:#344054!important;font-weight:900!important}
.checkout-field input,.checkout-field textarea,.coupon-input,.payment-option,.tip-btn,.summary-store{border-radius:8px!important}
.checkout-field input,.checkout-field textarea,.coupon-input{border-color:#d8dee6!important;color:#101828!important}
.checkout-field input:focus,.checkout-field textarea:focus,.coupon-input:focus{border-color:#16a34a!important;box-shadow:0 0 0 4px rgba(22,163,74,.1)!important}
.payment-option{border-color:#e7eaee!important}
.payment-option.selected{border-color:#16a34a!important;background:#f0fdf4!important}
.checkout-submit{border-radius:8px!important;background:#16a34a!important;font-weight:900!important;box-shadow:0 14px 30px rgba(22,163,74,.2)!important}
.summary-total{color:#101828!important}
</style>
@endpush
