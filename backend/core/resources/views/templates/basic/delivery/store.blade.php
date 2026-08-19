@extends($activeTemplate . 'layouts.frontend')

@section('content')
<main class="lz-store-page">
    {{-- 1. STORE HERO & BRANDING --}}
    <section class="lz-store-hero">
        <div class="container">
            <a class="lz-back-btn mb-3" href="{{ route('delivery.marketplace') }}">
                <i class="las la-arrow-left"></i> Volver a tiendas
            </a>

            <div class="lz-store-banner">
                @if($store->cover_image)
                    <img src="{{ getImage(getFilePath('store_cover') . '/' . $store->cover_image) }}" alt="{{ $store->name }}" class="lz-store-banner-img">
                @endif
                <div class="lz-store-banner-overlay"></div>

                <div class="lz-store-header-box">
                    <div class="lz-store-hero-logo">
                        @if($store->image)
                            <img src="{{ getImage(getFilePath('store') . '/' . $store->image) }}" alt="{{ $store->name }}">
                        @else
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#ecfdf5;color:var(--lz-primary);font-size:32px;">
                                <i class="las la-store"></i>
                            </div>
                        @endif
                    </div>

                    <div class="lz-store-header-info">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="lz-badge-pill {{ $store->is_open_now ? 'lz-badge-open' : 'lz-badge-closed' }}">
                                <span class="lz-dot-indicator"></span>
                                {{ $store->is_open_now ? 'Abierto para pedidos' : 'Cerrado por ahora' }}
                            </span>
                            @if($store->address)
                                <span class="lz-store-address-tag"><i class="las la-map-pin"></i> {{ Str::limit($store->address, 30) }}</span>
                            @endif
                        </div>

                        <h1 class="lz-store-title">{{ $store->name }}</h1>
                        <p class="lz-store-desc">{{ $store->description ?? 'Especialidades culinarias y delivery rápido en Tarapoto.' }}</p>

                        <div class="lz-store-stats-row">
                            <div class="lz-stat-badge">
                                <i class="las la-star text-warning"></i>
                                <strong>{{ $store->rating > 0 ? number_format($store->rating, 1) : '4.8' }}</strong>
                                <span>(50+)</span>
                            </div>
                            <div class="lz-stat-badge">
                                <i class="las la-clock text-primary"></i>
                                <span>{{ $store->preparation_time ?? 25 }}–{{ ($store->preparation_time ?? 25) + 15 }} min</span>
                            </div>
                            <div class="lz-stat-badge" id="store-hero-fee-wrap">
                                <i class="las la-motorcycle text-success"></i>
                                <span>Envío: <b id="store-hero-fee">S/ {{ number_format($store->display_fee, 2) }}</b></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. STICKY CATEGORY NAV & IN-STORE SEARCH --}}
    <nav class="lz-sticky-cats" id="stickyCategoriesNav">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="lz-sticky-cats-scroll" id="categoryTabsList">
                    @foreach($store->categories as $idx => $category)
                        <button type="button" class="lz-cat-tab {{ $idx === 0 ? 'active' : '' }}" onclick="scrollToCategory('cat-{{ $category->id }}', this)">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
                <div class="lz-store-search-wrap d-none d-md-block">
                    <div class="lz-search-box" style="max-width:220px;">
                        <i class="las la-search lz-search-icon" style="font-size:15px;left:10px;"></i>
                        <input type="text" id="storeInternalSearch" class="lz-search-input" style="padding:6px 12px 6px 32px;font-size:12.5px;" placeholder="Buscar en menú..." oninput="filterStoreProducts(this.value)">
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- 3. STORE MENU & CART LAYOUT --}}
    <section class="lz-store-content">
        <div class="container">
            <div class="row g-4">
                {{-- Left: Products catalog grouped by categories --}}
                <div class="col-lg-8">
                    @forelse($store->categories as $category)
                    <div class="lz-category-section mb-5" id="cat-{{ $category->id }}" data-category-name="{{ $category->name }}">
                        <div class="lz-section-head mb-3">
                            <h2 class="lz-section-title" style="font-size:20px;">{{ $category->name }}</h2>
                            <span class="text-muted" style="font-size:13px;font-weight:600;">{{ $category->products->count() }} opciones</span>
                        </div>

                        <div class="lz-products-grid">
                            @foreach($category->products as $product)
                            <article class="lz-product-card" onclick="openProductById({{ $product->id }})" style="{{ !$store->is_open_now ? 'opacity:0.7;' : '' }}">
                                <div class="lz-product-info">
                                    <div>
                                        <h3 class="lz-product-name">{{ $product->name }}</h3>
                                        <p class="lz-product-desc">{{ $product->description }}</p>
                                    </div>
                                    <div class="lz-product-price-row">
                                        <span class="lz-product-price">S/ {{ number_format($product->finalPrice(), 2) }}</span>
                                        @if($product->discount_price > 0 && $product->price > $product->discount_price)
                                            <span class="lz-product-old-price">S/ {{ number_format($product->price, 2) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="lz-product-thumb">
                                    @if($product->image)
                                        <img src="{{ getImage(getFilePath('product') . '/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy">
                                    @else
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:28px">
                                            <i class="las la-hamburger"></i>
                                        </div>
                                    @endif
                                    <button class="lz-product-add-btn" type="button" aria-label="Agregar">
                                        <i class="las la-plus"></i>
                                    </button>
                                </div>
                            </article>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5" style="background:#fff;border-radius:16px;border:1px solid var(--lz-border);padding:40px;">
                        <i class="las la-utensils" style="font-size:40px;color:#cbd5e1;"></i>
                        <p class="mt-2 text-muted">Esta tienda aún no tiene productos publicados.</p>
                    </div>
                    @endforelse
                </div>

                {{-- Right: Sticky Desktop Cart Drawer --}}
                <div class="col-lg-4 d-none d-lg-block">
                    <aside class="lz-desktop-cart-sidebar" id="desktopCartSidebar">
                        <div class="lz-cart-card">
                            <div class="lz-cart-card-header">
                                <div>
                                    <span class="lz-cart-tag">Tu Pedido</span>
                                    <h3 class="lz-cart-title mb-0">{{ $store->name }}</h3>
                                </div>
                                <div class="lz-cart-icon-wrap">
                                    <i class="las la-shopping-bag"></i>
                                </div>
                            </div>

                            {{-- Empty Cart State --}}
                            <div id="cart-empty" class="lz-cart-empty-box">
                                <i class="las la-shopping-basket"></i>
                                @if(!$store->is_open_now)
                                    <strong class="text-danger d-block mt-2">Tienda cerrada</strong>
                                    <p class="text-muted small">No se pueden agregar productos por ahora.</p>
                                @else
                                    <strong class="d-block mt-2">Tu carrito está vacío</strong>
                                    <p class="text-muted small">Selecciona platos de la carta para empezar.</p>
                                @endif
                            </div>

                            {{-- Active Items List --}}
                            <div id="cart-items" class="lz-cart-items-list"></div>

                            {{-- Summary & Checkout CTA --}}
                            <div id="cart-summary" class="lz-cart-summary-box d-none">
                                <div class="lz-summary-line">
                                    <span>Subtotal</span>
                                    <strong id="cart-subtotal">S/ 0.00</strong>
                                </div>
                                <div class="lz-summary-line">
                                    <span>Envío estimado</span>
                                    <strong id="cart-delivery-fee">S/ {{ number_format($store->display_fee, 2) }}</strong>
                                </div>
                                <div class="lz-summary-line lz-summary-total">
                                    <span>Total</span>
                                    <strong id="cart-total">S/ 0.00</strong>
                                </div>

                                <button type="button" id="open-checkout" class="lz-btn-cta w-100 mt-3" {{ !$store->is_open_now ? 'disabled' : '' }}>
                                    @if(!$store->is_open_now)
                                        <span>Tienda cerrada</span>
                                    @else
                                        <span>Continuar pedido</span>
                                        <i class="las la-arrow-right"></i>
                                    @endif
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. FLOATING STICKY CART BAR (MOBILE) --}}
    <div class="lz-floating-cart-bar" id="lz-floating-cart" style="display:none;" onclick="triggerMobileCheckout()">
        <div class="d-flex align-items-center gap-3">
            <span class="lz-fc-count" id="lz-fc-qty">0</span>
            <span class="lz-fc-title">Ver mi pedido</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <strong class="lz-fc-total" id="lz-fc-total-val">S/ 0.00</strong>
            <i class="las la-arrow-right"></i>
        </div>
    </div>

    {{-- 5. PRODUCT DETAIL BOTTOM SHEET / MODAL (FASE 4) --}}
    <div class="lz-bottom-sheet-backdrop" id="productSheetBackdrop" onclick="closeProductModal()"></div>
    <div class="lz-bottom-sheet" id="productModalSheet" role="dialog" aria-modal="true">
        <div class="lz-sheet-handle"></div>
        <div class="lz-sheet-body">
            <button type="button" class="lz-sheet-close-btn" onclick="closeProductModal()" aria-label="Cerrar">
                <i class="las la-times"></i>
            </button>

            <div class="lz-modal-prod-img-wrap" id="modal-product-image">
                <i class="las la-hamburger"></i>
            </div>

            <div class="lz-modal-prod-details mt-3">
                <h2 id="modal-product-name" class="lz-modal-prod-title">Nombre del Producto</h2>
                <p id="modal-product-desc" class="lz-modal-prod-desc">Descripción del producto</p>
                <div class="lz-modal-prod-base-price" id="modal-product-price">S/ 0.00</div>

                {{-- Variations Section --}}
                <div id="modal-variations" class="lz-modal-section" style="display:none;">
                    <label class="lz-modal-section-title">Elige tu variación <span class="badge bg-light text-dark">Obligatorio</span></label>
                    <div id="modal-variations-list" class="lz-modal-options-list"></div>
                </div>

                {{-- Addons / Extras Section --}}
                <div id="modal-addons" class="lz-modal-section" style="display:none;">
                    <label class="lz-modal-section-title">Agrega complementos / extras <span class="badge bg-light text-secondary">Opcional</span></label>
                    <div id="modal-addons-list" class="lz-modal-options-list"></div>
                </div>
            </div>
        </div>

        {{-- Bottom CTA with Reactive Total & Quantity Selector --}}
        <div class="lz-sheet-footer">
            <div class="lz-qty-selector">
                <button type="button" class="lz-qty-btn" onclick="changeModalQty(-1)" aria-label="Disminuir">-</button>
                <span class="lz-qty-val" id="modal-qty-val">1</span>
                <button type="button" class="lz-qty-btn" onclick="changeModalQty(1)" aria-label="Aumentar">+</button>
            </div>
            <button type="button" class="lz-btn-cta" id="modal-add-btn" onclick="submitModalAddToCart()">
                <span>Agregar</span>
                <span id="modal-total">S/ 0.00</span>
            </button>
        </div>
    </div>
</main>
@endsection

@push('style')
<style>
.lz-store-page {
    background: var(--lz-bg);
    min-height: 100vh;
    padding-bottom: 90px;
}
.lz-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: var(--lz-surface);
    border: 1px solid var(--lz-border);
    border-radius: var(--lz-r-full);
    font-size: 13px;
    font-weight: 700;
    color: var(--lz-text);
}
.lz-back-btn:hover {
    border-color: var(--lz-primary);
    color: var(--lz-primary-dark);
}
.lz-store-banner {
    position: relative;
    border-radius: var(--lz-r-xl);
    overflow: hidden;
    background: var(--lz-surface);
    border: 1px solid var(--lz-border);
    box-shadow: var(--lz-shadow-sm);
    min-height: 160px;
}
.lz-store-banner-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.25;
}
.lz-store-banner-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0.95) 80%);
}
.lz-store-header-box {
    position: relative;
    z-index: 2;
    padding: 24px;
    display: flex;
    gap: 20px;
    align-items: flex-start;
}
@media (max-width: 640px) {
    .lz-store-header-box {
        flex-direction: column;
        padding: 16px;
        gap: 12px;
    }
}
.lz-store-hero-logo {
    width: 84px;
    height: 84px;
    border-radius: var(--lz-r-lg);
    border: 2px solid #fff;
    box-shadow: var(--lz-shadow-md);
    overflow: hidden;
    background: #fff;
    flex-shrink: 0;
}
.lz-store-hero-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.lz-store-title {
    font-size: clamp(22px, 3.2vw, 32px);
    font-weight: 800;
    color: var(--lz-text);
    margin: 0 0 4px;
}
.lz-store-desc {
    font-size: 13.5px;
    color: var(--lz-text-muted);
    margin: 0 0 12px;
    max-width: 600px;
}
.lz-badge-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: var(--lz-r-full);
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.lz-badge-open {
    background: #ecfdf5;
    color: var(--lz-primary-dark);
}
.lz-badge-closed {
    background: #fee2e2;
    color: var(--lz-danger);
}
.lz-dot-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}
.lz-store-address-tag {
    font-size: 11.5px;
    color: var(--lz-text-muted);
    font-weight: 600;
}
.lz-store-stats-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.lz-stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--lz-surface);
    border: 1px solid var(--lz-border);
    border-radius: var(--lz-r-full);
    padding: 4px 12px;
    font-size: 12.5px;
    font-weight: 600;
}

/* Desktop Cart Box */
.lz-desktop-cart-sidebar {
    position: sticky;
    top: 130px;
}
.lz-cart-card {
    background: var(--lz-surface);
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-lg);
    padding: 20px;
    box-shadow: var(--lz-shadow-sm);
}
.lz-cart-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--lz-border-subtle);
}
.lz-cart-tag {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--lz-primary-dark);
    letter-spacing: 0.5px;
}
.lz-cart-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--lz-text);
}
.lz-cart-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--lz-primary-light);
    color: var(--lz-primary-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.lz-cart-empty-box {
    text-align: center;
    padding: 30px 10px;
    color: var(--lz-text-muted);
}
.lz-cart-empty-box i {
    font-size: 40px;
    color: #cbd5e1;
}
.lz-cart-items-list {
    max-height: 280px;
    overflow-y: auto;
    padding: 8px 0;
}
.lz-cart-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--lz-border-subtle);
    font-size: 13px;
}
.lz-cart-item-name {
    font-weight: 700;
    color: var(--lz-text);
    margin-bottom: 2px;
}
.lz-cart-item-price {
    color: var(--lz-primary-dark);
    font-weight: 800;
}
.lz-cart-summary-box {
    padding-top: 14px;
    border-top: 1px solid var(--lz-border);
}
.lz-summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    font-size: 13px;
    color: var(--lz-text-muted);
}
.lz-summary-total {
    font-size: 16px;
    font-weight: 800;
    color: var(--lz-text);
    padding-top: 8px;
    border-top: 1px dashed var(--lz-border);
}

/* Modal Bottom Sheet specific */
.lz-sheet-close-btn {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--lz-surface-muted);
    border: none;
    color: var(--lz-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.lz-modal-prod-img-wrap {
    width: 100%;
    height: 180px;
    border-radius: var(--lz-r-md);
    background: var(--lz-surface-muted);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    font-size: 54px;
}
.lz-modal-prod-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.lz-modal-prod-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--lz-text);
    margin: 0 0 4px;
}
.lz-modal-prod-desc {
    font-size: 13px;
    color: var(--lz-text-muted);
    margin: 0 0 10px;
}
.lz-modal-prod-base-price {
    font-size: 18px;
    font-weight: 800;
    color: var(--lz-primary-dark);
}
.lz-modal-section {
    margin-top: 18px;
}
.lz-modal-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--lz-text);
    margin-bottom: 8px;
}
.lz-option-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-sm);
    margin-bottom: 8px;
    cursor: pointer;
    transition: var(--lz-transition);
    font-size: 13.5px;
}
.lz-option-card:hover {
    border-color: var(--lz-primary);
}
.lz-option-card.selected {
    border-color: var(--lz-primary);
    background: var(--lz-primary-light);
    color: var(--lz-primary-darker);
    font-weight: 700;
}
</style>
@endpush

@push('script')
<script>
(function () {
    var STORE_ID = '{{ $store->id }}';
    var STORE_IS_OPEN = {{ $store->is_open_now ? 'true' : 'false' }};
    var PRODUCTS = {!! $productsJson !!};
    var deliveryFee = {{ (float) $store->display_fee }};
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var cartData = [];

    var currentProduct = null;
    var modalQty = 1;
    var selectedVariation = null;
    var selectedAddons = [];

    function money(v) { return 'S/ ' + Number(v).toFixed(2); }

    function api(method, url, body) {
        var opts = {
            method: method,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        };
        if (body) opts.body = JSON.stringify(body);
        return fetch(url, opts).then(function(r){ return r.json(); }).catch(function(){ return {status:'error'}; });
    }

    function renderCart() {
        var itemsContainer = document.getElementById('cart-items');
        var emptyBox = document.getElementById('cart-empty');
        var summaryBox = document.getElementById('cart-summary');
        var floatingCart = document.getElementById('lz-floating-cart');
        var floatingQty = document.getElementById('lz-fc-qty');
        var floatingTotal = document.getElementById('lz-fc-total-val');

        if (!itemsContainer) return;
        itemsContainer.innerHTML = '';

        var subtotal = 0;
        var totalQty = 0;

        cartData.forEach(function (item) {
            var itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            totalQty += item.quantity;

            var row = document.createElement('div');
            row.className = 'lz-cart-item-row';
            row.innerHTML = `
                <div style="flex:1;overflow:hidden;padding-right:8px;">
                    <div class="lz-cart-item-name">${item.name}</div>
                    <div class="lz-cart-item-price">${money(itemTotal)}</div>
                </div>
                <div class="lz-qty-selector" style="transform:scale(0.85);margin-right:-6px;">
                    <button type="button" class="lz-qty-btn" data-action="minus" data-id="${item.product_id}">-</button>
                    <span class="lz-qty-val">${item.quantity}</span>
                    <button type="button" class="lz-qty-btn" data-action="plus" data-id="${item.product_id}">+</button>
                </div>
            `;
            itemsContainer.appendChild(row);
        });

        if (cartData.length > 0) {
            emptyBox.classList.add('d-none');
            summaryBox.classList.remove('d-none');
            document.getElementById('cart-subtotal').textContent = money(subtotal);
            document.getElementById('cart-total').textContent = money(subtotal + deliveryFee);

            if (floatingCart) {
                floatingCart.style.display = 'flex';
                floatingQty.textContent = totalQty;
                floatingTotal.textContent = money(subtotal + deliveryFee);
            }
        } else {
            emptyBox.classList.remove('d-none');
            summaryBox.classList.add('d-none');
            if (floatingCart) {
                floatingCart.style.display = 'none';
            }
        }

        if (typeof window.updateHeaderCartBadge === 'function') {
            window.updateHeaderCartBadge();
        }
    }

    function loadCart() {
        api('GET', '/cart/' + STORE_ID).then(function (data) {
            if (data.status === 'success') {
                cartData = data.cart || [];
                renderCart();
            }
        });
    }

    // Modal / Bottom Sheet Functions
    window.openProductById = function (pid) {
        if (!STORE_IS_OPEN) {
            alert('La tienda está cerrada en este momento.');
            return;
        }
        var product = PRODUCTS[pid];
        if (!product) return;

        currentProduct = product;
        modalQty = 1;
        selectedVariation = null;
        selectedAddons = [];

        document.getElementById('modal-product-name').textContent = product.name;
        document.getElementById('modal-product-desc').textContent = product.description || '';
        document.getElementById('modal-product-price').textContent = money(product.price);
        document.getElementById('modal-qty-val').textContent = '1';

        var imgWrap = document.getElementById('modal-product-image');
        if (product.image) {
            imgWrap.innerHTML = '<img src="' + product.image + '" alt="' + product.name + '">';
        } else {
            imgWrap.innerHTML = '<i class="las la-hamburger"></i>';
        }

        // Variations
        var varSection = document.getElementById('modal-variations');
        var varList = document.getElementById('modal-variations-list');
        if (product.variations && product.variations.length > 0) {
            varSection.style.display = 'block';
            varList.innerHTML = product.variations.map(function (v, i) {
                return `
                    <div class="lz-option-card ${i === 0 ? 'selected' : ''}" data-var-id="${v.id}" data-var-price="${v.price}" onclick="selectVariation(this, ${v.id}, '${v.name}', ${v.price})">
                        <span>${v.name}</span>
                        <strong class="text-success">+${money(v.price)}</strong>
                    </div>
                `;
            }).join('');
            selectedVariation = { id: product.variations[0].id, name: product.variations[0].name, price: product.variations[0].price };
        } else {
            varSection.style.display = 'none';
            selectedVariation = null;
        }

        // Addons
        var addonSection = document.getElementById('modal-addons');
        var addonList = document.getElementById('modal-addons-list');
        if (product.addons && product.addons.length > 0) {
            addonSection.style.display = 'block';
            addonList.innerHTML = product.addons.map(function (a) {
                return `
                    <div class="lz-option-card" data-addon-id="${a.id}" data-addon-price="${a.price}" onclick="toggleAddon(this, ${a.id}, '${a.name}', ${a.price})">
                        <span>${a.name}</span>
                        <strong class="text-success">+${money(a.price)}</strong>
                    </div>
                `;
            }).join('');
        } else {
            addonSection.style.display = 'none';
        }

        updateModalPrice();

        document.getElementById('productSheetBackdrop').classList.add('active');
        document.getElementById('productModalSheet').classList.add('active');
    };

    window.selectVariation = function (el, vid, name, price) {
        document.querySelectorAll('#modal-variations-list .lz-option-card').forEach(function (c) { c.classList.remove('selected'); });
        el.classList.add('selected');
        selectedVariation = { id: vid, name: name, price: price };
        updateModalPrice();
    };

    window.toggleAddon = function (el, aid, name, price) {
        el.classList.toggle('selected');
        if (el.classList.contains('selected')) {
            selectedAddons.push({ id: aid, name: name, price: price });
        } else {
            selectedAddons = selectedAddons.filter(function (a) { return a.id !== aid; });
        }
        updateModalPrice();
    };

    window.changeModalQty = function (delta) {
        modalQty = Math.max(1, modalQty + delta);
        document.getElementById('modal-qty-val').textContent = modalQty;
        updateModalPrice();
    };

    function updateModalPrice() {
        if (!currentProduct) return;
        var unitPrice = currentProduct.price;
        if (selectedVariation) unitPrice += selectedVariation.price;
        selectedAddons.forEach(function (a) { unitPrice += a.price; });
        var total = unitPrice * modalQty;
        document.getElementById('modal-total').textContent = money(total);
    }

    window.submitModalAddToCart = function () {
        if (!currentProduct || !STORE_IS_OPEN) return;
        var unitPrice = currentProduct.price;
        var suffix = [];
        if (selectedVariation) {
            unitPrice += selectedVariation.price;
            suffix.push(selectedVariation.name);
        }
        selectedAddons.forEach(function (a) {
            unitPrice += a.price;
            suffix.push(a.name);
        });

        var fullName = currentProduct.name;
        if (suffix.length > 0) {
            fullName += ' (' + suffix.join(', ') + ')';
        }

        api('POST', '/cart/' + STORE_ID + '/add', {
            product_id: currentProduct.id,
            name: fullName,
            price: unitPrice,
            quantity: modalQty
        }).then(function () {
            closeProductModal();
            loadCart();
        });
    };

    window.closeProductModal = function () {
        document.getElementById('productSheetBackdrop').classList.remove('active');
        document.getElementById('productModalSheet').classList.remove('active');
    };

    // Cart Events
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-action]');
        if (!btn) return;
        var pid = Number(btn.dataset.id);
        var action = btn.dataset.action;
        api('POST', '/cart/' + STORE_ID + (action === 'plus' ? '/add' : '/remove'), { product_id: pid, quantity: 1 })
            .then(function () { loadCart(); });
    });

    window.triggerMobileCheckout = function () {
        document.getElementById('open-checkout')?.click();
    };

    document.getElementById('open-checkout')?.addEventListener('click', function () {
        if (cartData.length === 0) return;
        var token = localStorage.getItem('auth_token');
        var checkoutUrl = '/delivery/checkout?store=' + STORE_ID;
        if (token) {
            window.location.href = '/auth/token-login?token=' + encodeURIComponent(token) + '&redirect=' + encodeURIComponent(checkoutUrl);
        } else {
            window.location.href = checkoutUrl;
        }
    });

    // Category Tabs Smooth Scroll
    window.scrollToCategory = function (catId, tabEl) {
        document.querySelectorAll('#categoryTabsList .lz-cat-tab').forEach(function (t) { t.classList.remove('active'); });
        if (tabEl) tabEl.classList.add('active');
        var target = document.getElementById(catId);
        if (target) {
            var offset = 120;
            var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
        }
    };

    window.filterStoreProducts = function (query) {
        query = query.toLowerCase().trim();
        document.querySelectorAll('.lz-product-card').forEach(function (card) {
            var title = card.querySelector('.lz-product-name')?.textContent.toLowerCase() || '';
            var desc = card.querySelector('.lz-product-desc')?.textContent.toLowerCase() || '';
            card.style.display = (title.includes(query) || desc.includes(query)) ? 'flex' : 'none';
        });
    };

    loadCart();
})();
</script>
@endpush
