@extends($activeTemplate . 'layouts.frontend')

@section('content')
<main class="store-app">
    <section class="store-hero">
        @if($store->cover_image)
            <img src="{{ getImage(getFilePath('store_cover') . '/' . $store->cover_image) }}" alt="{{ $store->name }}">
        @endif
        <div class="container">
            <a class="store-back" href="{{ route('delivery.marketplace') }}"><i class="las la-arrow-left"></i> Volver a tiendas</a>
            <div class="store-hero__card">
                <div class="store-logo">@if($store->image)<img src="{{ getImage(getFilePath('store') . '/' . $store->image) }}" alt="{{ $store->name }}">@else<i class="las la-store"></i>@endif</div>
                <div>
                    <span style="display:inline-flex;align-items:center;gap:6px">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $store->is_open_now ? '#16a34a' : '#dc2626' }};display:inline-block"></span>
                        {{ $store->is_open_now ? 'Lizto para pedir' : 'Cerrado' }}
                    </span>
                    <h1>{{ $store->name }}</h1>
                    <p>{{ $store->description }}</p>
                    <div class="store-stats"><b><i class="las la-star"></i> {{ $store->rating > 0 ? number_format($store->rating, 1) : 'Nuevo' }}</b><b><i class="las la-clock"></i> {{ $store->preparation_time ?? 20 }} min</b><b id="store-hero-fee"><i class="las la-motorcycle"></i> S/ {{ number_format($store->display_fee, 2) }}</b></div>
                </div>
            </div>
        </div>
    </section>

    <section class="store-content">
        <div class="container">
            <div class="store-layout">
                <div>
                    <nav class="store-tabs">
                        @foreach($store->categories as $category)<a href="#category-{{ $category->id }}">{{ $category->name }}</a>@endforeach
                    </nav>
                    @forelse($store->categories as $category)
                    <section class="store-category" id="category-{{ $category->id }}">
                        <div class="store-title"><h2>{{ $category->name }}</h2><small>{{ $category->products->count() }} opciones</small></div>
                        <div class="store-products">
                            @foreach($category->products as $product)
                            <article class="store-product" style="{{ !$store->is_open_now ? 'opacity:.5;filter:grayscale(60%);pointer-events:none' : '' }}">
                                <div class="store-product__info">
                                    <h3>{{ $product->name }}</h3>
                                    <p>{{ $product->description }}</p>
                                    <div class="store-product__price">
                                        <strong>S/ {{ number_format($product->finalPrice(), 2) }}</strong>
                                        @if($product->discount_price)<del>S/ {{ number_format($product->price, 2) }}</del>@endif
                                    </div>
                                </div>
                                <div class="store-product__thumb">
                                    @if($product->image)<img src="{{ getImage(getFilePath('product') . '/' . $product->image) }}" alt="{{ $product->name }}">@else<i class="las la-hamburger"></i>@endif
                                    <button class="add-product" type="button" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->finalPrice() }}" {{ !$store->is_open_now ? 'disabled' : '' }}><i class="las la-plus"></i></button>
                                </div>
                            </article>
                            @endforeach
                        </div>
                    </section>
                    @empty
                        <div class="store-empty">Esta tienda todavía no ha publicado productos.</div>
                    @endforelse
                </div>
                <aside class="store-cart">
                    <div class="store-cart__head"><div><span>Tu pedido</span><h2>Carrito</h2></div><i class="las la-shopping-bag"></i></div>
                    <div id="cart-empty" class="store-cart__empty">
                        <i class="las la-shopping-basket"></i>
                        @if(!$store->is_open_now)
                        <p style="color:#dc2626;font-weight:700">Tienda cerrada</p>
                        <p>Vuelve cuando esté abierta para hacer tu pedido.</p>
                        @else
                        <p>Agrega productos para comenzar tu pedido.</p>
                        @endif
                    </div>
                    <div id="cart-items"></div>
                    <div id="cart-summary" class="store-cart__summary d-none">
                        <div><span>Subtotal</span><b id="cart-subtotal">S/ 0.00</b></div>
                        <div><span>Envío estimado</span><b id="cart-delivery-fee">S/ {{ number_format($store->display_fee, 2) }}</b></div>
                        <div class="store-cart__total"><span>Total</span><b id="cart-total">S/ 0.00</b></div>
                        <button id="open-checkout" type="button" {{ !$store->is_open_now ? 'disabled style="opacity:.4;cursor:not-allowed"' : '' }}>@if(!$store->is_open_now)Tienda cerrada @else Continuar pedido <i class="las la-arrow-right"></i>@endif</button>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <div class="checkout-modal" id="checkout-modal" aria-hidden="true">
        <div class="checkout-modal__overlay"></div>
        <div class="checkout-modal__box">
            <button class="checkout-close" id="close-checkout" type="button"><i class="las la-times"></i></button>
            <span>Finaliza tu pedido</span>
            <h2>Datos de entrega</h2>
            <p>Recibiremos tu orden y te contactaremos por WhatsApp para confirmarla.</p>
            <form action="{{ route('service.request') }}" method="POST" id="checkout-form">
                @csrf
                @if($errors->any())
                    <div class="checkout-errors">
                        @foreach($errors->all() as $error)<small>{{ $error }}</small>@endforeach
                    </div>
                @endif
                <input type="hidden" name="service_type" value="delivery">
                <input type="hidden" name="store_id" value="{{ $store->id }}">
                <input type="hidden" name="pickup" value="{{ $store->address }}">
                <input type="hidden" name="cart_payload" id="cart-payload">
                <input type="hidden" name="delivery_lat" id="delivery-lat">
                <input type="hidden" name="delivery_lng" id="delivery-lng">
                <div class="checkout-grid">
                    <input name="name" placeholder="Tu nombre" required>
                    <input name="phone" placeholder="Celular / WhatsApp" required>
                </div>
                <input name="destination" id="checkout-address" placeholder="Dirección de entrega" required autocomplete="off">
                <textarea name="notes" rows="3" placeholder="Referencia o indicaciones adicionales"></textarea>
                <button type="submit">Enviar pedido <i class="las la-check-circle"></i></button>
            </form>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div class="product-modal" id="product-modal" aria-hidden="true">
        <div class="product-modal__overlay" id="product-modal-overlay"></div>
        <div class="product-modal__box">
            <button class="product-modal__close" id="product-modal-close" type="button"><i class="las la-times"></i></button>
            <div class="product-modal__body">
                <div class="product-modal__image" id="modal-product-image">
                    <i class="las la-hamburger"></i>
                </div>
                <div class="product-modal__info">
                    <h2 id="modal-product-name"></h2>
                    <p id="modal-product-desc"></p>
                    <div class="product-modal__price" id="modal-product-price">S/ 0.00</div>

                    <div id="modal-variations" class="product-modal__section" style="display:none">
                        <label>Variaciones</label>
                        <div id="modal-variations-list"></div>
                    </div>

                    <div id="modal-addons" class="product-modal__section" style="display:none">
                        <label>Extras</label>
                        <div id="modal-addons-list"></div>
                    </div>

                    <div class="product-modal__total">
                        <span>Total</span><strong id="modal-total">S/ 0.00</strong>
                    </div>

                    <button class="product-modal__add" id="modal-add-btn" type="button">
                        <i class="las la-plus"></i> Agregar al carrito
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('style')
<style>
.checkout-errors{display:grid;gap:3px;padding:9px 10px;border-radius:9px;background:#fff1f1;color:#a92525}
:root{--store:#16a34a;--store-dark:#15803d;--store-soft:#effbef;--store-ink:#17221b;--store-muted:#68736c}.header{background:#fff!important;box-shadow:0 4px 22px rgba(8,63,27,.06)}.header .logo img{filter:brightness(0) saturate(100%) invert(43%) sepia(87%) saturate(1371%) hue-rotate(76deg) brightness(83%) contrast(95%)}.store-app{padding-top:0;background:#fbfdfb;color:var(--store-ink);min-height:100vh}.store-hero{position:relative;padding:30px 0 38px;background:linear-gradient(135deg,#ddf6dc,#f6fff5);overflow:hidden}.store-hero>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.2}.store-hero .container{position:relative}.store-back{display:inline-flex;gap:7px;align-items:center;color:var(--store-dark);font-size:13px;font-weight:800;margin-bottom:27px}.store-hero__card{display:flex;gap:20px;align-items:center}.store-logo{display:grid;place-items:center;width:112px;height:112px;border:5px solid #fff;border-radius:25px;background:#fff;color:var(--store);font-size:52px;box-shadow:0 9px 25px rgba(7,83,33,.11);overflow:hidden}.store-logo img{width:100%;height:100%;object-fit:cover}.store-hero__card span,.store-cart__head span,.checkout-modal__box>span{color:var(--store);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1px}.store-hero h1{font-size:42px;font-weight:800;letter-spacing:-1.5px;margin:3px 0}.store-hero p{color:var(--store-muted);margin:0 0 11px}.store-stats{display:flex;gap:9px;flex-wrap:wrap}.store-stats b{padding:6px 9px;border-radius:9px;background:#fff;font-size:11px}.store-stats i{color:var(--store)}.store-content{padding:35px 0 80px}.store-layout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:26px;align-items:start}.store-tabs{display:flex;gap:9px;overflow:auto;margin-bottom:25px}.store-tabs a{padding:9px 13px;border:1px solid #dfeae0;border-radius:20px;background:#fff;color:#45534a;font-size:12px;font-weight:800;white-space:nowrap}.store-tabs a:hover{background:var(--store-soft);border-color:var(--store);color:var(--store)}.store-category{scroll-margin-top:105px;margin-bottom:34px}.store-title{display:flex;align-items:end;gap:10px;margin-bottom:15px}.store-title h2{font-size:25px;font-weight:800;margin:0}.store-title small{color:var(--store-muted)}.store-products{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.store-product{display:flex;justify-content:space-between;gap:10px;min-height:146px;padding:15px;border:1px solid #e1eae2;border-radius:17px;background:#fff}.store-product__info{display:flex;flex-direction:column}.store-product h3{font-size:16px;font-weight:800;margin:0}.store-product p{color:var(--store-muted);font-size:12px;line-height:1.45;margin:7px 0}.store-product__price{display:flex;gap:8px;align-items:center;margin-top:auto}.store-product strong{color:var(--store-dark)}.store-product del{color:#9aa59e;font-size:12px}.store-product__thumb{position:relative;display:grid;place-items:center;flex:0 0 94px;height:94px;border-radius:13px;background:#eff9ee;color:var(--store);font-size:35px;overflow:visible}.store-product__thumb img{width:100%;height:100%;object-fit:cover;border-radius:13px}.add-product{position:absolute;right:-6px;bottom:-7px;display:grid;place-items:center;width:31px;height:31px;border:0;border-radius:10px;background:var(--store);color:#fff;box-shadow:0 5px 13px rgba(21,155,18,.28)}.store-cart{position:sticky;top:102px;padding:19px;border:1px solid #dfe9e0;border-radius:20px;background:#fff;box-shadow:0 16px 30px rgba(7,83,33,.06)}.store-cart__head{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #edf2ed;padding-bottom:13px}.store-cart__head h2{font-size:23px;font-weight:800;margin:1px 0}.store-cart__head i{color:var(--store);font-size:28px}.store-cart__empty{text-align:center;padding:31px 5px 17px;color:var(--store-muted);font-size:13px}.store-cart__empty i{color:#a9d9aa;font-size:43px}.cart-item{display:flex;justify-content:space-between;gap:8px;padding:13px 0;border-bottom:1px solid #edf2ed}.cart-item strong{display:block;font-size:13px}.cart-item small{color:var(--store);font-weight:800}.cart-qty{display:flex;align-items:center;gap:8px}.cart-qty button{display:grid;place-items:center;width:22px;height:22px;border:0;border-radius:7px;background:var(--store-soft);color:var(--store);font-weight:800}.store-cart__summary{padding-top:13px}.store-cart__summary>div{display:flex;justify-content:space-between;margin:7px 0;color:var(--store-muted);font-size:12px}.store-cart__total{padding-top:10px;border-top:1px solid #edf2ed;color:var(--store-ink)!important;font-size:16px!important}.store-cart__summary>button,.checkout-modal form button{display:flex;justify-content:center;gap:8px;width:100%;padding:13px;border:0;border-radius:11px;background:var(--store);color:#fff;font-weight:800;margin-top:13px}.checkout-modal{position:fixed;z-index:9999;inset:0;display:none;place-items:center;padding:15px}.checkout-modal.is-open{display:grid}.checkout-modal__overlay{position:absolute;inset:0;background:rgba(4,28,13,.56)}.checkout-modal__box{position:relative;width:min(530px,100%);padding:28px;border-radius:20px;background:#fff}.checkout-close{position:absolute;top:13px;right:13px;border:0;background:#f0f4f0;width:32px;height:32px;border-radius:50%}.checkout-modal h2{font-size:29px;font-weight:800;margin:3px 0}.checkout-modal p{color:var(--store-muted);font-size:13px}.checkout-modal input,.checkout-modal textarea{width:100%;margin-top:10px;padding:12px;border:1px solid #dde7de;border-radius:10px;outline-color:var(--store)}.checkout-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.store-empty{padding:30px;border:1px dashed #c8d9c9;border-radius:15px;color:var(--store-muted)}@media(max-width:991px){.store-layout{display:block}.store-cart{position:relative;top:auto;margin-top:20px}.store-products{grid-template-columns:1fr}}@media(max-width:767px){.store-app{padding-top:0}.store-hero__card{align-items:flex-start}.store-logo{width:82px;height:82px;flex:0 0 82px}.store-hero h1{font-size:31px}.checkout-grid{display:block}}
</style>
@endpush

@push('style')
<style>
.store-app{background:#fff!important;color:#101828!important}
.store-hero{padding:42px 0 46px!important;background:linear-gradient(180deg,#f6fbf7 0%,#fff 82%)!important}
.store-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 84% 12%,rgba(34,197,94,.2),transparent 30%),linear-gradient(90deg,rgba(22,163,74,.1),transparent 42%);pointer-events:none}
.store-hero>img{opacity:.16!important;filter:saturate(.9)!important}
.store-back{background:#fff!important;border:1px solid rgba(22,163,74,.22)!important;border-radius:999px!important;padding:8px 12px!important;text-decoration:none!important}
.store-hero__card{background:#fff!important;border:1px solid #e7eaee!important;border-radius:8px!important;padding:24px!important;box-shadow:0 18px 45px rgba(16,24,40,.08)!important}
.store-logo{border-radius:8px!important;border-width:0!important;box-shadow:none!important;background:#ecfdf3!important}
.store-hero h1,.store-title h2,.store-cart__head h2,.product-modal__info h2{font-family:Outfit,Inter,sans-serif!important;color:#101828!important;font-weight:900!important;letter-spacing:0!important}
.store-hero h1{font-size:clamp(32px,4vw,48px)!important;line-height:1.05!important}
.store-hero p,.store-product p,.store-empty{color:#667085!important}
.store-stats b,.store-tabs a,.store-product,.store-cart,.product-modal__box{border-radius:8px!important;border-color:#e7eaee!important}
.store-content{background:#fff!important}
.store-tabs a{background:#fff!important;text-decoration:none!important}
.store-tabs a:hover{background:#f0fdf4!important}
.store-product{box-shadow:none!important;transition:.2s ease!important}
.store-product:hover{transform:translateY(-2px);box-shadow:0 18px 36px rgba(16,24,40,.08)!important}
.store-product__thumb,.store-product__thumb img,.add-product,.store-cart__summary>button,.checkout-modal form button,.product-modal__add{border-radius:8px!important}
.store-cart{box-shadow:0 18px 45px rgba(16,24,40,.08)!important}
.store-cart__summary>button,.checkout-modal form button,.product-modal__add{background:#16a34a!important;font-weight:900!important}
@media(max-width:767px){.store-hero__card{padding:18px!important}.store-logo{width:76px!important;height:76px!important;flex-basis:76px!important}.store-hero h1{font-size:30px!important}}
</style>
@endpush

@push('style')
<style>.product-modal{position:fixed;inset:0;z-index:2000;display:flex;align-items:center;justify-content:center;visibility:hidden;opacity:0;transition:.25s}.product-modal.open{visibility:visible;opacity:1}.product-modal__overlay{position:absolute;inset:0;background:rgba(0,0,0,.5)}.product-modal__box{position:relative;background:#fff;border-radius:18px;width:100%;max-width:420px;max-height:90vh;overflow-y:auto;margin:16px;z-index:1}.product-modal__close{position:absolute;top:12px;right:12px;width:32px;height:32px;border:none;background:#f3f4f6;border-radius:50%;font-size:16px;cursor:pointer;display:grid;place-items:center;z-index:2}.product-modal__image{width:100%;height:200px;background:#f8fdf8;display:grid;place-items:center;font-size:64px;color:#16a34a;overflow:hidden;border-radius:18px 18px 0 0}.product-modal__image img{width:100%;height:100%;object-fit:cover}.product-modal__info{padding:20px}.product-modal__info h2{font-size:20px;font-weight:800;color:#1a2e1a;margin:0 0 4px}.product-modal__info>p{font-size:13px;color:#68736c;margin:0 0 12px}.product-modal__price{font-size:18px;font-weight:800;color:#16a34a}.product-modal__section{margin-top:16px}.product-modal__section>label{display:block;font-size:12px;font-weight:800;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}.var-option{display:flex;align-items:center;gap:10px;padding:10px 12px;border:2px solid #e0eee2;border-radius:10px;cursor:pointer;margin-bottom:6px;transition:.2s;font-size:13px}.var-option.selected{border-color:#16a34a;background:#f0fdf4}.var-option input{display:none}.var-option__price{color:#16a34a;font-weight:700;margin-left:auto}.addon-option{display:flex;align-items:center;gap:10px;padding:8px 12px;border:1px solid #e0eee2;border-radius:10px;cursor:pointer;margin-bottom:6px;transition:.2s;font-size:13px}.addon-option.selected{border-color:#16a34a;background:#f0fdf4}.addon-option input{display:none}.addon-option__price{color:#16a34a;font-weight:700;margin-left:auto}.product-modal__total{display:flex;justify-content:space-between;align-items:center;padding:14px 0;margin-top:16px;border-top:2px solid #e0eee2}.product-modal__total span{font-size:14px;font-weight:600;color:#374151}.product-modal__total strong{font-size:22px;font-weight:800;color:#16a34a}.product-modal__add{width:100%;padding:14px;border:none;border-radius:14px;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;transition:.2s}.product-modal__add:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.3)}.product-modal__add:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none}</style>
@endpush

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif
@push('script')
<script>
(function () {
    var STORE_ID = '{{ $store->id }}';
    var STORE_IS_OPEN = {{ $store->is_open_now ? 'true' : 'false' }};
    var PRODUCTS = {!! $productsJson !!};
    var deliveryFee = {{ (float) $store->display_fee }};
    var cartItems = document.getElementById('cart-items');
    var cartEmpty = document.getElementById('cart-empty');
    var cartSummary = document.getElementById('cart-summary');
    var modal = document.getElementById('checkout-modal');
    var payload = document.getElementById('cart-payload');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '';
    var cartData = [];

    @if($errors->any())
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden','false');
    @endif

    function money(value){ return 'S/ ' + Number(value).toFixed(2); }

    function api(method, url, body) {
        var opts = { method: method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } };
        if (body) opts.body = JSON.stringify(body);
        return fetch(url, opts).then(function(r){ return r.json(); }).catch(function(e){ console.error('Cart API error:', e); return {status:'error'}; });
    }

    function updateBadge(count) {
        var badge = document.getElementById('hdr-cart-badge');
        var icon = document.getElementById('header-cart-icon');
        if (badge) badge.textContent = count || 0;
        if (icon) icon.style.display = (count > 0) ? 'inline-block' : 'none';
    }

    function updateFeeDisplay(fee) {
        var heroFee = document.getElementById('store-hero-fee');
        var cartFee = document.getElementById('cart-delivery-fee');
        if (heroFee) heroFee.textContent = 'S/ ' + fee.toFixed(2);
        if (cartFee) cartFee.textContent = 'S/ ' + fee.toFixed(2);
        deliveryFee = fee;
        var totalEl = document.getElementById('cart-total');
        if (totalEl) {
            var subtotal = parseFloat((document.getElementById('cart-subtotal')?.textContent || 'S/ 0.00').replace('S/ ', '')) || 0;
            totalEl.textContent = money(subtotal + deliveryFee);
        }
    }

    function estimateFee() {
        fetch('/location/get', { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(loc) {
                if (!loc.lat || !loc.lng) return;
                var url = '/delivery/store-fee-estimate?store_id=' + STORE_ID + '&delivery_lat=' + loc.lat + '&delivery_lng=' + loc.lng;
                return fetch(url, { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); });
            })
            .then(function(data) {
                if (data && data.delivery_fee != null) {
                    updateFeeDisplay(data.delivery_fee);
                }
            })
            .catch(function(){});
    }

    function render() {
        cartItems.innerHTML = '';
        cartEmpty.classList.toggle('d-none', cartData.length > 0);
        cartSummary.classList.toggle('d-none', cartData.length === 0);
        var subtotal = 0;
        var totalQty = 0;
        cartData.forEach(function(item){
            subtotal += item.price * item.quantity;
            totalQty += item.quantity;
            var row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML = '<div><strong>'+item.name+'</strong><small>'+money(item.price * item.quantity)+'</small></div><div class="cart-qty"><button type="button" data-cart-action="minus" data-id="'+item.product_id+'">-</button><b>'+item.quantity+'</b><button type="button" data-cart-action="plus" data-id="'+item.product_id+'">+</button></div>';
            cartItems.appendChild(row);
        });
        document.getElementById('cart-subtotal').textContent = money(subtotal);
        document.getElementById('cart-total').textContent = money(subtotal + deliveryFee);
        payload.value = JSON.stringify(cartData.map(function(item){ return {product_id:item.product_id, name:item.name, quantity:item.quantity, unit_price:item.price}; }));
        updateBadge(totalQty);
    }

    function loadCart() {
        api('GET', '/cart/' + STORE_ID).then(function(data) {
            if (data.status === 'success') {
                cartData = data.cart || [];
                render();
            }
        });
    }

    document.querySelectorAll('.add-product').forEach(function(button){
        button.addEventListener('click',function(){
            @if(!$store->is_open_now)
            alert('La tienda está cerrada en este momento. No se pueden agregar productos.');
            return;
            @endif
            var pid = Number(button.dataset.id);
            var product = PRODUCTS[pid];
            if (!product) return;
            openProductModal(product);
        });
    });

    // ── Product Modal ──
    var prodModal = document.getElementById('product-modal');
    var selectedVariation = null;
    var selectedAddons = [];
    var currentProduct = null;

    function openProductModal(product) {
        currentProduct = product;
        selectedVariation = null;
        selectedAddons = [];

        document.getElementById('modal-product-name').textContent = product.name;
        document.getElementById('modal-product-desc').textContent = product.description || '';
        document.getElementById('modal-product-price').textContent = 'S/ ' + product.price.toFixed(2);
        document.getElementById('modal-total').textContent = 'S/ ' + product.price.toFixed(2);

        var imgContainer = document.getElementById('modal-product-image');
        if (product.image) {
            imgContainer.innerHTML = '<img src="' + product.image + '" alt="' + product.name + '">';
        } else {
            imgContainer.innerHTML = '<i class="las la-hamburger"></i>';
        }

        // Variations
        var varContainer = document.getElementById('modal-variations');
        var varList = document.getElementById('modal-variations-list');
        if (product.variations && product.variations.length > 0) {
            varContainer.style.display = 'block';
            varList.innerHTML = product.variations.map(function(v) {
                return '<label class="var-option" data-var-id="' + v.id + '" data-var-price="' + v.price + '"><input type="radio" name="variation"><span>' + v.name + '</span><span class="var-option__price">+S/ ' + v.price.toFixed(2) + '</span></label>';
            }).join('');
            varList.querySelectorAll('.var-option').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    varList.querySelectorAll('.var-option').forEach(function(o) { o.classList.remove('selected'); });
                    opt.classList.add('selected');
                    selectedVariation = { id: Number(opt.dataset.varId), name: opt.querySelector('span').textContent, price: Number(opt.dataset.varPrice) };
                    updateModalTotal();
                });
            });
        } else {
            varContainer.style.display = 'none';
            selectedVariation = null;
        }

        // Addons
        var addonContainer = document.getElementById('modal-addons');
        var addonList = document.getElementById('modal-addons-list');
        if (product.addons && product.addons.length > 0) {
            addonContainer.style.display = 'block';
            addonList.innerHTML = product.addons.map(function(a) {
                return '<label class="addon-option" data-addon-id="' + a.id + '" data-addon-price="' + a.price + '"><input type="checkbox"><span>' + a.name + '</span><span class="addon-option__price">+S/ ' + a.price.toFixed(2) + '</span></label>';
            }).join('');
            addonList.querySelectorAll('.addon-option').forEach(function(opt) {
                opt.addEventListener('click', function(e) {
                    e.preventDefault();
                    opt.classList.toggle('selected');
                    opt.querySelector('input').checked = opt.classList.contains('selected');
                    var aid = Number(opt.dataset.addonId);
                    if (opt.classList.contains('selected')) {
                        selectedAddons.push({ id: aid, name: opt.querySelector('span').textContent, price: Number(opt.dataset.addonPrice) });
                    } else {
                        selectedAddons = selectedAddons.filter(function(a) { return a.id !== aid; });
                    }
                    updateModalTotal();
                });
            });
        } else {
            addonContainer.style.display = 'none';
            selectedAddons = [];
        }

        prodModal.classList.add('open');
        prodModal.setAttribute('aria-hidden', 'false');
    }

    function updateModalTotal() {
        var total = currentProduct.price;
        if (selectedVariation) total += selectedVariation.price;
        selectedAddons.forEach(function(a) { total += a.price; });
        document.getElementById('modal-total').textContent = 'S/ ' + total.toFixed(2);
    }

    document.getElementById('modal-add-btn').addEventListener('click', function() {
        if (!currentProduct || !STORE_IS_OPEN) return;
        var total = currentProduct.price;
        if (selectedVariation) total += selectedVariation.price;
        selectedAddons.forEach(function(a) { total += a.price; });

        var body = {
            product_id: currentProduct.id,
            name: currentProduct.name,
            price: total,
            quantity: 1
        };
        api('POST', '/cart/' + STORE_ID + '/add', body).then(function(data) {
            closeProductModal();
            loadCart();
        });
    });

    function closeProductModal() {
        prodModal.classList.remove('open');
        prodModal.setAttribute('aria-hidden', 'true');
    }
    document.getElementById('product-modal-close').addEventListener('click', closeProductModal);
    document.getElementById('product-modal-overlay').addEventListener('click', closeProductModal);

    cartItems.addEventListener('click',function(event){
        var button = event.target.closest('button[data-cart-action]'); if(!button) return;
        var pid = Number(button.dataset.id);
        var isPlus = button.dataset.cartAction === 'plus';
        api('POST', '/cart/' + STORE_ID + (isPlus ? '/add' : '/remove'), { product_id: pid, quantity: 1 }).then(function(data) {
            loadCart();
        });
    });

    document.getElementById('open-checkout').addEventListener('click',function(){
        if (cartData.length === 0) return;

        var token = localStorage.getItem('auth_token');
        if (token) {
            var redirect = '/delivery/checkout?store=' + STORE_ID;
            window.location.href = '/auth/token-login?token=' + encodeURIComponent(token) + '&redirect=' + encodeURIComponent(redirect);
        } else {
            if (typeof showLoginModal === 'function') showLoginModal();
        }
    });

    document.getElementById('close-checkout').addEventListener('click',function(){ modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); });
    modal.querySelector('.checkout-modal__overlay').addEventListener('click',function(){ modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); });

    window.addEventListener('load',function(){
        var address = document.getElementById('checkout-address');
        if(window.google && google.maps && google.maps.places){
            var autocomplete = new google.maps.places.Autocomplete(address,{componentRestrictions:{country:'pe'}});
            autocomplete.addListener('place_changed',function(){
                var place = autocomplete.getPlace();
                if(place.geometry){
                    document.getElementById('delivery-lat').value = place.geometry.location.lat();
                    document.getElementById('delivery-lng').value = place.geometry.location.lng();
                }
            });
        }
        estimateFee();
        loadCart();
    });
})();
</script>
@endpush

@push('json-ld')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "{{ $store->name }}",
    "image": "{{ $store->image ? getImage(getFilePath('store') . '/' . $store->image) : siteLogo() }}",
    "url": "{{ route('delivery.store', $store->slug ?? $store->id) }}",
    "telephone": "{{ $store->phone ?? '+51997428341' }}",
    "description": "{{ Str::limit(strip_tags($store->description ?? ''), 200) }}",
    "@id": "{{ route('delivery.store', $store->slug ?? $store->id) }}",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ $store->address ?? '' }}",
        "addressLocality": "Tarapoto",
        "addressRegion": "San Martin",
        "addressCountry": "PE"
    },
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": {{ $store->latitude ?? -6.4833 }},
        "longitude": {{ $store->longitude ?? -76.3667 }}
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "{{ $store->rating ?? 0 }}",
        "reviewCount": "{{ $store->total_orders ?? 0 }}"
    },
    "priceRange": "$$",
    "openingHoursSpecification": {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "00:00",
        "closes": "23:59"
    }
}
</script>
@endpush
