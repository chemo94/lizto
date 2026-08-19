@extends($activeTemplate . 'layouts.frontend')

@section('content')
@php
    $cartItems = array_values($cart);
    $subtotal = collect($cartItems)->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 0));
    $tipAmount = old('tip_amount', 0);
    $total = $subtotal + $deliveryFee + (float) $tipAmount;
@endphp

<main class="lz-checkout-page">
    <div class="container">
        {{-- Back Navigation --}}
        <a class="lz-back-btn mb-3" href="{{ route('delivery.store', $store) }}">
            <i class="las la-arrow-left"></i> Volver al menú de {{ $store->name }}
        </a>

        @if($errors->any())
        <div class="alert alert-danger mb-4" style="border-radius:14px;">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <h1 class="lz-checkout-title mb-4">Finaliza tu pedido</h1>

        <div class="row g-4">
            {{-- Left column: 3 clear step cards --}}
            <div class="col-lg-7">
                <form action="{{ route('delivery.checkout.submit') }}" method="POST" id="checkout-form" novalidate>
                    @csrf
                    <input type="hidden" name="store_id" value="{{ $store->id }}">
                    <input type="hidden" name="delivery_lat" id="delivery-lat" value="{{ old('delivery_lat', $location['lat'] ?? '') }}">
                    <input type="hidden" name="delivery_lng" id="delivery-lng" value="{{ old('delivery_lng', $location['lng'] ?? '') }}">
                    <input type="hidden" name="payment_method_code" id="payment-method-code" value="{{ old('payment_method_code', '0') }}">
                    <input type="hidden" name="tip_amount" id="tip-amount" value="{{ old('tip_amount', 0) }}">
                    <input type="hidden" name="coupon_code" id="hidden_coupon_code" value="{{ old('coupon_code') }}">

                    {{-- PASO 1: ENTREGA --}}
                    <div class="lz-checkout-card mb-4">
                        <div class="lz-checkout-step-header">
                            <span class="lz-step-number">1</span>
                            <div>
                                <h2 class="lz-step-title">Dirección de Entrega</h2>
                                <p class="lz-step-desc">¿Dónde te llevamos tu pedido en Tarapoto?</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="lz-form-label">Dirección exacta</label>
                            <div class="lz-input-with-icon">
                                <i class="las la-map-marker-alt lz-field-icon"></i>
                                <input type="text" name="delivery_address" id="checkout-address" class="lz-form-control" value="{{ old('delivery_address', $location['label'] ?? '') }}" placeholder="Calle, jirón, número o referencia..." required autocomplete="off">
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="lz-form-label">Referencia o indicaciones (opcional)</label>
                            <textarea name="notes" class="lz-form-control" rows="2" placeholder="Ej: Portón negro, segundo piso, tocar el timbre blanco...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- PASO 2: DATOS DE CONTACTO --}}
                    <div class="lz-checkout-card mb-4">
                        <div class="lz-checkout-step-header">
                            <span class="lz-step-number">2</span>
                            <div>
                                <h2 class="lz-step-title">Datos de Contacto</h2>
                                <p class="lz-step-desc">Para coordinar la entrega y avisarte cuando esté en camino</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="lz-form-label">Tu nombre</label>
                                <input type="text" name="contact_name" class="lz-form-control" value="{{ old('contact_name', auth()->user()->firstname ?? $userInfo['firstname'] ?? '') }}" placeholder="Nombre y apellido" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="lz-form-label">Celular / WhatsApp</label>
                                <input type="tel" name="contact_phone" class="lz-form-control" value="{{ old('contact_phone', auth()->user()->mobile ?? $userInfo['mobile'] ?? '') }}" placeholder="Ej: 997428341" required>
                            </div>
                        </div>
                    </div>

                    {{-- PASO 3: MÉTODO DE PAGO --}}
                    <div class="lz-checkout-card mb-4">
                        <div class="lz-checkout-step-header">
                            <span class="lz-step-number">3</span>
                            <div>
                                <h2 class="lz-step-title">Método de Pago</h2>
                                <p class="lz-step-desc">Selecciona cómo deseas pagar tu orden</p>
                            </div>
                        </div>

                        <div class="lz-payment-grid">
                            {{-- Efectivo --}}
                            <label class="lz-payment-card {{ old('payment_method_code', '0') == '0' ? 'selected' : '' }}" data-code="0">
                                <input type="radio" name="pm_code" value="0" {{ old('payment_method_code', '0') == '0' ? 'checked' : '' }} hidden>
                                <div class="lz-pm-icon"><i class="las la-money-bill-wave"></i></div>
                                <div class="lz-pm-info">
                                    <strong>Efectivo al recibir</strong>
                                    <small>Pagas directamente al repartidor</small>
                                </div>
                                <i class="las la-check-circle lz-pm-check"></i>
                            </label>

                            {{-- Pasarelas activas (MercadoPago, Yape, Plin, Tarjeta) --}}
                            @foreach($gateways as $gw)
                            <label class="lz-payment-card {{ old('payment_method_code') == $gw['code'] ? 'selected' : '' }}" data-code="{{ $gw['code'] }}">
                                <input type="radio" name="pm_code" value="{{ $gw['code'] }}" {{ old('payment_method_code') == $gw['code'] ? 'checked' : '' }} hidden>
                                <div class="lz-pm-icon">
                                    @if($gw['image'])
                                        <img src="{{ getImage(getFilePath('gateway') . '/' . $gw['image']) }}" alt="{{ $gw['name'] }}" style="width:28px;height:28px;object-fit:contain;">
                                    @else
                                        <i class="las la-credit-card"></i>
                                    @endif
                                </div>
                                <div class="lz-pm-info">
                                    <strong>{{ $gw['name'] }}</strong>
                                    <small>{{ $gw['description'] ?? 'Pago digital seguro' }}</small>
                                </div>
                                <i class="las la-check-circle lz-pm-check"></i>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            {{-- Right column: Order Summary & Confirmation CTA --}}
            <div class="col-lg-5">
                <aside class="lz-checkout-sidebar">
                    <div class="lz-checkout-card">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <span class="lz-cart-tag">Resumen</span>
                                <h3 class="lz-cart-title mb-0">{{ $store->name }}</h3>
                            </div>
                            <span class="badge bg-light text-dark font-monospace">{{ count($cartItems) }} items</span>
                        </div>

                        {{-- Item list --}}
                        <div class="lz-summary-items-list mb-3">
                            @foreach($cartItems as $item)
                            <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:13.5px;">
                                <div>
                                    <strong class="text-dark">{{ $item['quantity'] }}x</strong> {{ $item['name'] }}
                                </div>
                                <span class="fw-bold text-dark">S/ {{ number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 2) }}</span>
                            </div>
                            @endforeach
                        </div>

                        {{-- Coupon Input --}}
                        <div class="mb-3">
                            <label class="lz-form-label">Cupón de descuento</label>
                            <div class="d-flex gap-2">
                                <input type="text" id="coupon_input" class="lz-form-control" placeholder="Ingresa código..." value="{{ old('coupon_code') }}">
                                <button type="button" class="btn btn-outline-success px-3 fw-bold" onclick="applyCouponCode()">Aplicar</button>
                            </div>
                            <div id="coupon-msg" class="small mt-1"></div>
                        </div>

                        {{-- Tip selector --}}
                        <div class="mb-3">
                            <label class="lz-form-label d-flex justify-content-between">
                                <span>Propina voluntaria al repartidor</span>
                                <strong id="tip-display" class="text-success">+S/ 0.00</strong>
                            </label>
                            <div class="d-flex gap-2">
                                @foreach([0, 1, 2, 5] as $t)
                                <button type="button" class="lz-tip-btn {{ old('tip_amount', 0) == $t ? 'active' : '' }}" onclick="selectTip({{ $t }}, this)">
                                    {{ $t == 0 ? 'Sin propina' : 'S/ ' . $t }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Financial Breakdown --}}
                        <div class="lz-cart-summary-box">
                            <div class="lz-summary-line">
                                <span>Subtotal</span>
                                <strong>S/ {{ number_format($subtotal, 2) }}</strong>
                            </div>
                            <div class="lz-summary-line">
                                <span>Costo de envío</span>
                                <strong id="delivery-fee-val">S/ {{ number_format($deliveryFee, 2) }}</strong>
                            </div>
                            <div class="lz-summary-line text-success" id="discount-row" style="display:none;">
                                <span>Descuento aplicado</span>
                                <strong id="discount-val">-S/ 0.00</strong>
                            </div>
                            <div class="lz-summary-line lz-summary-total">
                                <span>Total a pagar</span>
                                <strong id="total-val" style="color:var(--lz-primary-dark);font-size:22px;">S/ {{ number_format($total, 2) }}</strong>
                            </div>

                            <button type="button" class="lz-btn-cta w-100 mt-4" id="submitOrderBtn" onclick="triggerCheckoutSubmit()">
                                <span>Confirmar pedido</span>
                                <span id="btn-total-display">S/ {{ number_format($total, 2) }}</span>
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</main>
@endsection

@push('style')
<style>
.lz-checkout-page {
    padding: 20px 0 60px;
    background: var(--lz-bg);
}
.lz-checkout-title {
    font-size: clamp(24px, 3.5vw, 32px);
    font-weight: 800;
    color: var(--lz-text);
}
.lz-checkout-card {
    background: var(--lz-surface);
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-lg);
    padding: 24px;
    box-shadow: var(--lz-shadow-sm);
}
.lz-checkout-step-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}
.lz-step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--lz-primary);
    color: #fff;
    font-size: 15px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lz-step-title {
    font-size: 17px;
    font-weight: 800;
    color: var(--lz-text);
    margin: 0;
}
.lz-step-desc {
    font-size: 12.5px;
    color: var(--lz-text-muted);
    margin: 2px 0 0;
}
.lz-form-label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--lz-text);
    margin-bottom: 6px;
}
.lz-form-control {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-sm);
    font-size: 14px;
    color: var(--lz-text);
    outline: none;
    transition: var(--lz-transition);
}
.lz-form-control:focus {
    border-color: var(--lz-primary);
    box-shadow: 0 0 0 3px var(--lz-primary-glow);
}
.lz-input-with-icon {
    position: relative;
}
.lz-input-with-icon .lz-field-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--lz-primary);
    font-size: 18px;
    pointer-events: none;
}
.lz-input-with-icon .lz-form-control {
    padding-left: 42px;
}
.lz-payment-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}
.lz-payment-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-md);
    cursor: pointer;
    transition: var(--lz-transition);
    background: var(--lz-surface);
}
.lz-payment-card:hover {
    border-color: var(--lz-primary);
}
.lz-payment-card.selected {
    border-color: var(--lz-primary);
    background: var(--lz-primary-light);
}
.lz-pm-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--lz-r-sm);
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: var(--lz-primary-dark);
    flex-shrink: 0;
    box-shadow: var(--lz-shadow-xs);
}
.lz-pm-info {
    flex: 1;
}
.lz-pm-info strong {
    display: block;
    font-size: 14px;
    color: var(--lz-text);
}
.lz-pm-info small {
    font-size: 11.5px;
    color: var(--lz-text-muted);
}
.lz-pm-check {
    font-size: 20px;
    color: var(--lz-border);
    transition: var(--lz-transition);
}
.lz-payment-card.selected .lz-pm-check {
    color: var(--lz-primary-dark);
}
.lz-tip-btn {
    flex: 1;
    padding: 8px 10px;
    border: 1px solid var(--lz-border);
    border-radius: var(--lz-r-sm);
    background: var(--lz-surface);
    font-size: 12px;
    font-weight: 700;
    color: var(--lz-text);
    cursor: pointer;
    transition: var(--lz-transition);
}
.lz-tip-btn:hover {
    border-color: var(--lz-primary);
}
.lz-tip-btn.active {
    background: var(--lz-primary);
    border-color: var(--lz-primary);
    color: #fff;
}
.lz-checkout-sidebar {
    position: sticky;
    top: 100px;
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
(function() {
    var subtotal = {{ (float) $subtotal }};
    var deliveryFee = {{ (float) $deliveryFee }};
    var currentTip = {{ (float) $tipAmount }};
    var currentDiscount = 0;

    function money(v) { return 'S/ ' + Number(v).toFixed(2); }

    function recalculateTotal() {
        var total = Math.max(0, subtotal + deliveryFee + currentTip - currentDiscount);
        document.getElementById('total-val').textContent = money(total);
        document.getElementById('btn-total-display').textContent = money(total);
        document.getElementById('tip-amount').value = currentTip;
    }

    window.selectTip = function(amount, el) {
        currentTip = amount;
        document.querySelectorAll('.lz-tip-btn').forEach(function(b){ b.classList.remove('active'); });
        el.classList.add('active');
        document.getElementById('tip-display').textContent = '+S/ ' + amount.toFixed(2);
        recalculateTotal();
    };

    window.applyCouponCode = function() {
        var code = document.getElementById('coupon_input').value.trim();
        var msgEl = document.getElementById('coupon-msg');
        if (!code) { msgEl.innerHTML = ''; return; }

        msgEl.innerHTML = '<span class="text-primary">Validando cupón...</span>';
        fetch('/api/validate-coupon', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'dev-token': '{{ developerToken() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ code: code, subtotal: subtotal })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.status === 'success') {
                currentDiscount = res.data.discount || 0;
                document.getElementById('hidden_coupon_code').value = code;
                document.getElementById('discount-row').style.display = 'flex';
                document.getElementById('discount-val').textContent = '-' + money(currentDiscount);
                msgEl.innerHTML = '<span class="text-success fw-bold">✓ Cupón aplicado: ' + res.data.description + '</span>';
                recalculateTotal();
            } else {
                currentDiscount = 0;
                document.getElementById('hidden_coupon_code').value = '';
                document.getElementById('discount-row').style.display = 'none';
                msgEl.innerHTML = '<span class="text-danger">' + (res.message || 'Cupón no válido') + '</span>';
                recalculateTotal();
            }
        });
    };

    // Payment Selection
    document.querySelectorAll('.lz-payment-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.lz-payment-card').forEach(function(c){ c.classList.remove('selected'); });
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
            document.getElementById('payment-method-code').value = card.dataset.code;
        });
    });

    window.triggerCheckoutSubmit = function() {
        var form = document.getElementById('checkout-form');
        var addressInput = document.getElementById('checkout-address');
        var nameInput = document.querySelector('[name="contact_name"]');
        var phoneInput = document.querySelector('[name="contact_phone"]');

        if (!addressInput.value.trim()) {
            alert('Por favor ingresa la dirección de entrega.');
            addressInput.focus();
            return;
        }
        if (!nameInput.value.trim()) {
            alert('Por favor ingresa tu nombre de contacto.');
            nameInput.focus();
            return;
        }
        if (!phoneInput.value.trim()) {
            alert('Por favor ingresa tu número de celular / WhatsApp.');
            phoneInput.focus();
            return;
        }

        var btn = document.getElementById('submitOrderBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando pedido...';
        form.submit();
    };

    // Geocomplete if google places loaded
    window.addEventListener('load', function() {
        var address = document.getElementById('checkout-address');
        if (window.google && google.maps && google.maps.places && address) {
            var autocomplete = new google.maps.places.Autocomplete(address, { componentRestrictions: { country: 'pe' } });
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (place.geometry) {
                    document.getElementById('delivery-lat').value = place.geometry.location.lat();
                    document.getElementById('delivery-lng').value = place.geometry.location.lng();
                }
            });
        }
    });
})();
</script>
@endpush
