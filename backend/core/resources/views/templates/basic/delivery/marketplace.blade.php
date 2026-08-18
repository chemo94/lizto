@extends($activeTemplate . 'layouts.frontend')

@section('content')
@php
    $openStores = $stores->filter(fn($s) => $s->is_open_now);
    $closedStores = $stores->filter(fn($s) => !$s->is_open_now);
@endphp

<main class="lz-marketplace-page">
    <div class="container">

        {{-- 1. HERO & UNIVERSAL SEARCH (MOBILE-FIRST) --}}
        <section class="lz-hero-section" id="buscar">
            <div class="row align-items-center mb-3">
                <div class="col-12 col-md-8">
                    <span class="lz-brand-city mb-2"><i class="las la-bolt"></i> Superapp de Tarapoto</span>
                    <h1 style="font-size: clamp(22px, 3.2vw, 34px); font-weight: 800; color: var(--lz-text); margin: 0 0 6px; letter-spacing: -0.5px;">
                        ¿Qué quieres pedir hoy?
                    </h1>
                    <p style="font-size: 14.5px; color: var(--lz-text-muted); margin: 0;">
                        Restaurantes, compras, favores y movilidad en un solo lugar.
                    </p>
                </div>
            </div>

            {{-- Mobile & Tablet Universal Search Bar --}}
            <div class="lz-hero-search-wrapper mb-4">
                <form action="{{ route('delivery.marketplace') }}" method="GET" id="heroSearchForm" class="lz-search-box">
                    <i class="las la-search lz-search-icon"></i>
                    <input type="text" name="q" id="universalSearchInput" class="lz-search-input" value="{{ request('q') }}" placeholder="Busca platos, restaurantes, tiendas o productos..." autocomplete="off">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                    @if(request('subcategory'))
                        <input type="hidden" name="subcategory" value="{{ request('subcategory') }}">
                    @endif
                    @if(request('q') || request('subcategory'))
                        <a href="{{ route('delivery.marketplace') }}" class="lz-search-clear" title="Limpiar búsqueda">
                            <i class="las la-times-circle"></i>
                        </a>
                    @endif
                </form>
            </div>

            {{-- 2. HIGH HIERARCHY SERVICE SHORTCUTS --}}
            <div class="lz-services-grid">
                <a href="{{ route('delivery.marketplace') }}#restaurantes" class="lz-service-card lz-svc-food">
                    <div class="lz-service-icon">
                        <i class="las la-utensils"></i>
                    </div>
                    <span class="lz-service-title">Comida</span>
                    <span class="lz-service-sub">Restaurantes</span>
                </a>

                <a href="{{ route('delivery.marketplace', ['category' => 'markets']) }}" class="lz-service-card lz-svc-stores">
                    <div class="lz-service-icon">
                        <i class="las la-shopping-basket"></i>
                    </div>
                    <span class="lz-service-title">Tiendas</span>
                    <span class="lz-service-sub">Markets & Farmacias</span>
                </a>

                <a href="{{ route('favor') }}" class="lz-service-card lz-svc-favor">
                    <div class="lz-service-icon">
                        <i class="las la-hand-holding-heart"></i>
                    </div>
                    <span class="lz-service-title">Lizto Favor</span>
                    <span class="lz-service-sub">Mandados & Envíos</span>
                </a>

                <a href="{{ route('taxi') }}" class="lz-service-card lz-svc-taxi">
                    <div class="lz-service-icon">
                        <i class="las la-taxi"></i>
                    </div>
                    <span class="lz-service-title">Taxi Seguro</span>
                    <span class="lz-service-sub">Viajes directos</span>
                </a>
            </div>
        </section>

        {{-- 3. HORIZONTAL CATEGORIES CAROUSEL --}}
        <section class="lz-categories-strip">
            <div class="lz-section-head">
                <h2 class="lz-section-title">
                    <i class="las la-th-large" style="color:var(--lz-primary)"></i> Categorías
                </h2>
                @if(request('category') || request('subcategory') || request('q'))
                    <a href="{{ route('delivery.marketplace') }}" class="lz-section-link">Ver todas</a>
                @endif
            </div>

            <div class="lz-chips-scroll">
                <a href="{{ route('delivery.marketplace') }}" class="lz-cat-chip {{ !request('category') && !request('subcategory') && !request('q') ? 'active' : '' }}">
                    <i class="las la-border-all lz-cat-chip-icon"></i>
                    <span>Todo</span>
                </a>

                @foreach($categories as $category)
                    @if($category->slug === 'servicio-de-favores')
                        <a href="{{ route('favor') }}" class="lz-cat-chip">
                            <i class="las la-hand-holding-heart lz-cat-chip-icon"></i>
                            <span>{{ $category->name }}</span>
                        </a>
                    @else
                        <a href="{{ route('delivery.marketplace', ['category' => $category->id]) }}" class="lz-cat-chip {{ request('category') == $category->id ? 'active' : '' }}">
                            @if($category->image)
                                <img src="{{ getImage(getFilePath('general_category') . '/' . $category->image) }}" alt="{{ $category->name }}" style="width:20px;height:20px;object-fit:cover;border-radius:4px">
                            @else
                                <i class="las la-store lz-cat-chip-icon"></i>
                            @endif
                            <span>{{ $category->name }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>

        {{-- 4. ¿QUÉ SE TE ANTOJA? (SUBCATEGORÍAS REALES DEL BACKEND) --}}
        @if(isset($subCategories) && $subCategories->count() > 0)
        <section class="lz-categories-strip" style="margin-top:0;">
            <div class="lz-section-head">
                <h2 class="lz-section-title" style="font-size:16px;">
                    ✨ ¿Qué se te antoja hoy?
                </h2>
                @if(request('subcategory'))
                    <a href="{{ route('delivery.marketplace') }}" class="lz-section-link">Limpiar</a>
                @endif
            </div>
            <div class="lz-chips-scroll">
                @foreach($subCategories as $subCat)
                    <a href="{{ route('delivery.marketplace', ['subcategory' => $subCat->id]) }}" class="lz-cat-chip {{ request('subcategory') == $subCat->id ? 'active' : '' }}">
                        @if($subCat->image)
                            <img src="{{ getImage('assets/images/sub_category/' . $subCat->image) }}" alt="{{ $subCat->name }}" style="width:20px;height:20px;object-fit:cover;border-radius:4px">
                        @else
                            <i class="las la-utensils lz-cat-chip-icon"></i>
                        @endif
                        <span>{{ $subCat->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 5. BANNERS PROMOCIONALES --}}
        @if(isset($banners) && $banners->count() > 0)
        <section class="mb-4">
            <div class="mp-banners-slider">
                @foreach($banners as $banner)
                <a href="{{ $banner->link ?? '#' }}" class="mp-banner-slide">
                    <img src="{{ getImage(getFilePath('banner') . '/' . $banner->image) }}" alt="{{ $banner->title }}" loading="lazy">
                </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 6. CUPONES DE DESCUENTO --}}
        @if(isset($coupons) && $coupons->count() > 0)
        <section class="mb-5">
            <div class="lz-section-head">
                <h2 class="lz-section-title">
                    🎁 Cupones y Promociones
                    <span class="lz-brand-city" style="font-size:10.5px;">{{ $coupons->count() }} ACTIVOS</span>
                </h2>
            </div>
            <div class="mp-coupons-grid">
                @foreach($coupons as $coupon)
                <div class="mp-coupon-card">
                    <div class="coupon-left">
                        <span class="coupon-value">
                            @if($coupon->type === 'percentage')
                                {{ round($coupon->value) }}% OFF
                            @elseif($coupon->type === 'free_delivery')
                                ENVÍO GRATIS
                            @else
                                S/ {{ number_format($coupon->value, 2) }}
                            @endif
                        </span>
                        @if($coupon->min_order > 0)
                            <span class="coupon-min">Min. S/ {{ number_format($coupon->min_order, 2) }}</span>
                        @endif
                    </div>
                    <div class="coupon-right">
                        <strong>{{ $coupon->name }}</strong>
                        <p class="coupon-desc">{{ $coupon->description ?? '¡Válido para tu próximo pedido en Lizto!' }}</p>
                        <div class="coupon-code-wrapper">
                            <span class="coupon-code">{{ $coupon->code }}</span>
                            <button type="button" class="copy-coupon-btn" onclick="copyCouponCode('{{ $coupon->code }}', this)">
                                <i class="las la-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 7. OFERTAS DEL DÍA (PRODUCTOS CON DESCUENTO) --}}
        @if(isset($discountedProducts) && $discountedProducts->count() > 0)
        <section class="mb-5">
            <div class="lz-section-head">
                <h2 class="lz-section-title">
                    ⚡ Ofertas del Día
                    <span class="mp-badge-tag">AHORRA</span>
                </h2>
            </div>
            <div class="lz-products-grid">
                @foreach($discountedProducts->take(6) as $product)
                <div class="lz-product-card" onclick="window.location.href='{{ route('delivery.store', $product->store) }}'">
                    <div class="lz-product-info">
                        <div>
                            <span style="font-size:11px;font-weight:700;color:var(--lz-primary-dark);display:flex;align-items:center;gap:4px;margin-bottom:3px">
                                <i class="las la-store"></i> {{ $product->store->name }}
                            </span>
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
                        <button type="button" class="lz-product-add-btn" title="Ver producto">
                            <i class="las la-arrow-right"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 8. TIENDAS Y RESTAURANTES (ABIERTOS AHORA) --}}
        <section class="mb-5" id="restaurantes">
            <div class="lz-section-head">
                <h2 class="lz-section-title">
                    🔥 Restaurantes y Tiendas
                    <span class="lz-brand-city">{{ $openStores->count() }} ABIERTOS</span>
                </h2>
                <div class="d-none d-sm-flex align-items-center gap-2">
                    <span style="font-size:12.5px;color:var(--lz-text-muted);">En Tarapoto</span>
                </div>
            </div>

            @if($openStores->count() > 0)
            <div class="lz-stores-grid">
                @foreach($openStores as $store)
                <a href="{{ route('delivery.store', $store) }}" class="lz-store-card" data-store-id="{{ $store->id }}">
                    {{-- Cover --}}
                    <div class="lz-store-cover">
                        @if($store->cover_image)
                            <img src="{{ getImage(getFilePath('store_cover') . '/' . $store->cover_image) }}" alt="{{ $store->name }}" loading="lazy">
                        @else
                            <div style="width:100%;height:100%;background:linear-gradient(135deg, #10b981 0%, #047857 100%);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.4);font-size:40px">
                                <i class="las la-utensils"></i>
                            </div>
                        @endif

                        {{-- Open Badge --}}
                        <div class="lz-store-badge-open lz-badge-live">
                            <span style="width:6px;height:6px;border-radius:50%;background:#fff;display:inline-block"></span>
                            Abierto
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="lz-store-body">
                        <div class="lz-store-logo">
                            @if($store->image)
                                <img src="{{ getImage(getFilePath('store') . '/' . $store->image) }}" alt="{{ $store->name }}" loading="lazy">
                            @else
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#ecfdf5;color:var(--lz-primary);font-size:20px">
                                    <i class="las la-store"></i>
                                </div>
                            @endif
                        </div>
                        <div class="lz-store-content">
                            <h3 class="lz-store-name">{{ $store->name }}</h3>
                            <div class="lz-store-meta">
                                <span>{{ $store->description ? \Illuminate\Support\Str::limit($store->description, 35) : 'Comida & Delivery' }}</span>
                            </div>
                            <div class="lz-store-footer">
                                <div class="lz-store-delivery">
                                    <i class="las la-motorcycle"></i>
                                    @if($hasFreeDelivery)
                                        <span style="text-decoration:line-through;color:#94a3b8;font-size:11px">S/ {{ number_format($store->display_fee, 2) }}</span>
                                        <span style="color:var(--lz-primary-dark);font-weight:800">GRATIS</span>
                                    @else
                                        S/ <span class="store-fee-amount">{{ number_format($store->display_fee, 2) }}</span>
                                    @endif
                                    <span class="store-distance" style="font-size:10.5px;color:var(--lz-text-muted);margin-left:4px"></span>
                                </div>
                                <div class="lz-store-rating">
                                    <i class="las la-star"></i>
                                    <span>{{ $store->rating > 0 ? number_format($store->rating, 1) : '4.8' }}</span>
                                    <span style="color:var(--lz-text-subtle);font-weight:400;font-size:11px">({{ $store->preparation_time ?? 25 }} min)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="text-center py-5" style="background:#fff;border-radius:16px;border:1px solid var(--lz-border);padding:40px 20px;">
                <div style="font-size:48px;color:#94a3b8;margin-bottom:12px">
                    <i class="las la-store-alt-slash"></i>
                </div>
                <h3 style="font-size:18px;font-weight:800;color:var(--lz-text);margin-bottom:6px">No encontramos tiendas abiertas en este momento</h3>
                <p style="color:var(--lz-text-muted);font-size:14px;max-width:400px;margin:0 auto 18px">
                    Prueba otra búsqueda, revisa los negocios cerrados abajo o solicita un mandado directo.
                </p>
                <a href="{{ route('favor') }}" class="lz-btn-cta d-inline-flex align-items-center gap-2" style="max-width:240px;margin:0 auto;">
                    <i class="las la-hand-holding-heart"></i> Pedir con Lizto Favor
                </a>
            </div>
            @endif
        </section>

        {{-- 9. TIENDAS CERRADAS (VISUALMENTE SEPARADAS) --}}
        @if($closedStores->count() > 0)
        <section class="mb-5" style="opacity: 0.85;">
            <div class="lz-section-head">
                <h2 class="lz-section-title" style="color:var(--lz-text-muted);font-size:18px;">
                    <i class="las la-clock"></i> Cerrados por ahora · Abren más tarde
                    <span class="lz-brand-city" style="background:#f1f5f9;color:#64748b;">{{ $closedStores->count() }}</span>
                </h2>
            </div>
            <div class="lz-stores-grid">
                @foreach($closedStores as $store)
                <a href="{{ route('delivery.store', $store) }}" class="lz-store-card" style="filter: grayscale(30%);">
                    <div class="lz-store-cover">
                        @if($store->cover_image)
                            <img src="{{ getImage(getFilePath('store_cover') . '/' . $store->cover_image) }}" alt="{{ $store->name }}" loading="lazy">
                        @else
                            <div style="width:100%;height:100%;background:#475569;display:flex;align-items:center;justify-content:center;color:#fff;font-size:40px">
                                <i class="las la-store"></i>
                            </div>
                        @endif
                        <div class="lz-store-badge-open lz-badge-closed">
                            <i class="las la-clock"></i> Cerrado
                        </div>
                    </div>
                    <div class="lz-store-body">
                        <div class="lz-store-logo">
                            @if($store->image)
                                <img src="{{ getImage(getFilePath('store') . '/' . $store->image) }}" alt="{{ $store->name }}" loading="lazy">
                            @else
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f1f5f9;color:#94a3b8;font-size:20px">
                                    <i class="las la-store"></i>
                                </div>
                            @endif
                        </div>
                        <div class="lz-store-content">
                            <h3 class="lz-store-name">{{ $store->name }}</h3>
                            <div class="lz-store-meta">
                                <span>{{ $store->description ? \Illuminate\Support\Str::limit($store->description, 35) : 'Comida & Delivery' }}</span>
                            </div>
                            <div class="lz-store-footer">
                                <span style="font-size:12px;color:var(--lz-text-muted);">Ver menú y horarios</span>
                                <span style="font-size:12px;font-weight:700;color:var(--lz-primary-dark);"><i class="las la-eye"></i> Explorar</span>
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </section>
        @endif

    </div>
</main>
@endsection

@push('style')
<style>
.lz-marketplace-page {
    padding: 16px 0 40px;
    background-color: var(--lz-bg);
}
.lz-hero-search-wrapper {
    max-width: 100%;
}
.lz-search-clear {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--lz-text-subtle);
    font-size: 20px;
}
.lz-search-clear:hover {
    color: var(--lz-danger);
}
.mp-banners-slider {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    scrollbar-width: none;
    padding-bottom: 4px;
}
.mp-banners-slider::-webkit-scrollbar {
    display: none;
}
.mp-banner-slide {
    flex: 0 0 85%;
    max-width: 480px;
    height: 160px;
    border-radius: var(--lz-r-lg);
    overflow: hidden;
    box-shadow: var(--lz-shadow-sm);
    transition: var(--lz-transition);
}
@media (min-width: 768px) {
    .mp-banner-slide {
        flex: 0 0 45%;
        height: 180px;
    }
}
.mp-banner-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Coupon styling */
.mp-coupons-grid {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    scrollbar-width: none;
    padding-bottom: 8px;
}
.mp-coupons-grid::-webkit-scrollbar {
    display: none;
}
.mp-coupon-card {
    flex: 0 0 290px;
    display: flex;
    background: var(--lz-surface);
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-md);
    overflow: hidden;
    box-shadow: var(--lz-shadow-xs);
}
.coupon-left {
    background: linear-gradient(135deg, var(--lz-primary) 0%, var(--lz-primary-dark) 100%);
    color: #fff;
    width: 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px;
    text-align: center;
}
.coupon-value {
    font-size: 15px;
    font-weight: 800;
    line-height: 1.1;
}
.coupon-min {
    font-size: 9px;
    opacity: 0.9;
    margin-top: 4px;
    font-weight: 600;
}
.coupon-right {
    flex: 1;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.coupon-right strong {
    font-size: 13.5px;
    color: var(--lz-text);
    margin-bottom: 2px;
}
.coupon-desc {
    font-size: 11px;
    color: var(--lz-text-muted);
    margin: 0 0 6px 0;
    line-height: 1.3;
}
.coupon-code-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--lz-surface-muted);
    border-radius: var(--lz-r-xs);
    padding: 4px 8px;
}
.coupon-code {
    font-family: monospace;
    font-weight: 700;
    font-size: 12px;
    color: var(--lz-text);
}
.copy-coupon-btn {
    background: none;
    border: none;
    color: var(--lz-primary-dark);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    padding: 2px 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.mp-badge-tag {
    background: #fee2e2;
    color: #dc2626;
    font-size: 10px;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: var(--lz-r-full);
}
</style>
@endpush

@push('script')
<script>
window.addEventListener('load', function () {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '';
    var locationLat = null;
    var locationLng = null;

    function refreshFees() {
        if (!locationLat || !locationLng) return;
        document.querySelectorAll('.lz-store-card').forEach(function(card) {
            var storeId = card.getAttribute('data-store-id');
            var feeEl = card.querySelector('.store-fee-amount');
            var distEl = card.querySelector('.store-distance');
            if (!storeId || !feeEl) return;
            fetch('/delivery/store-fee-estimate?store_id=' + storeId + '&delivery_lat=' + locationLat + '&delivery_lng=' + locationLng)
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d && d.delivery_fee != null) {
                        feeEl.textContent = d.delivery_fee.toFixed(2);
                        if (distEl && d.distance_km != null) distEl.textContent = '· ' + d.distance_km.toFixed(1) + ' km';
                    }
                }).catch(function(){});
        });
    }

    fetch('/location/get', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.lat && data.lng) {
                locationLat = data.lat;
                locationLng = data.lng;
                refreshFees();
            }
        }).catch(function(){});
});

function copyCouponCode(code, element) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(function() {
            var originalHTML = element.innerHTML;
            element.innerHTML = '<i class="las la-check"></i> ¡Copiado!';
            element.style.color = 'var(--lz-primary-dark)';
            setTimeout(function() {
                element.innerHTML = originalHTML;
                element.style.color = '';
            }, 2000);
        });
    }
}
</script>
@endpush
