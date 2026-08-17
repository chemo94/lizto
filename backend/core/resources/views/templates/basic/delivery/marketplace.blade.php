@extends($activeTemplate . 'layouts.frontend')

@section('content')
@php
    $categoryIcons = [
        'restaurantes' => 'las la-utensils',
        'farmacia' => 'las la-prescription-bottle-alt',
        'servicio-de-favores' => 'las la-hand-holding-heart',
        'mascotas' => 'las la-paw',
        'licorerias' => 'las la-wine-bottle',
        'super-mini-markets' => 'las la-shopping-basket',
    ];
@endphp

<main class="mp-marketplace">

    {{-- HERO --}}
    <section class="mp-marketplace-hero">
        <div class="container">
            <div class="mp-hero-copy">
                <span class="mp-hero-kicker"><i class="las la-bolt"></i> Marketplace local conectado</span>
                <h1>Pide comida, compras y servicios cerca de ti.</h1>
                <p>{{ $storeCount }}+ negocios conectados a Lizto para comprar, pagar y recibir sin complicarte.</p>
            </div>
            <h1>¿Qué quieres pedir hoy?</h1>
            <p>{{ $storeCount }}+ tiendas, restaurantes y farmacias cerca de ti. Todo en una sola app.</p>
            <div class="mp-search-bar">
                <div class="mp-search-input">
                    <i class="las la-search"></i>
                    <form action="{{ route('delivery.marketplace') }}" method="GET" style="flex:1;display:flex">
                        <input name="q" value="{{ request('q') }}" placeholder="¿Qué se te antoja? Busca tiendas, platos...">
                        @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                    </form>
                </div>
                <button class="mp-search-btn" onclick="document.querySelector('.mp-search-input form').submit()">Buscar</button>
            </div>
        </div>
    </section>

    {{-- CATEGORÍAS --}}
    <section class="mp-categories">
        <div class="container">
            <h2>Categorías</h2>
            <div class="mp-categories-grid">
                @foreach($categories as $category)
                    @if($category->slug === 'servicio-de-favores')
                    <a class="mp-category-item" href="{{ route('favor') }}">
                        <div class="mp-category-icon">
                            @if($category->image)<img src="{{ getImage(getFilePath('general_category') . '/' . $category->image) }}" alt="{{ $category->name }}" style="width:40px;height:40px;object-fit:cover;border-radius:8px">@else<i class="{{ $categoryIcons[$category->slug] ?? 'las la-store' }}"></i>@endif
                        </div>
                        <span>{{ $category->name }}</span>
                    </a>
                    @else
                    <a class="mp-category-item {{ request('category') == $category->id ? 'is-active' : '' }}" href="{{ route('delivery.marketplace', ['category' => $category->id]) }}">
                        <div class="mp-category-icon">
                            @if($category->image)<img src="{{ getImage(getFilePath('general_category') . '/' . $category->image) }}" alt="{{ $category->name }}" style="width:40px;height:40px;object-fit:cover;border-radius:8px">@else<i class="{{ $categoryIcons[$category->slug] ?? 'las la-store' }}"></i>@endif
                        </div>
                        <span>{{ $category->name }}</span>
                    </a>
                    @endif
                @endforeach
                <a class="mp-category-item {{ request()->is('taxi') ? 'is-active' : '' }}" href="{{ route('taxi') }}">
                    <div class="mp-category-icon">
                        <img src="{{ asset('assets/images/taxi.png') }}" alt="Taxi" style="width:40px;height:40px;object-fit:cover;border-radius:8px">
                    </div>
                    <span>Taxi</span>
                </a>
            </div>
        </div>
    </section>

    {{-- BANNERS DE LA BASE DE DATOS --}}
    @if(isset($banners) && $banners->count() > 0)
    <section class="mp-banners-section">
        <div class="container">
            <div class="mp-banners-slider">
                @foreach($banners as $banner)
                <a href="{{ $banner->link ?? '#' }}" class="mp-banner-slide">
                    <img src="{{ getImage(getFilePath('banner') . '/' . $banner->image) }}" alt="{{ $banner->title }}">
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- CUPONES DE DESCUENTO --}}
    @if(isset($coupons) && $coupons->count() > 0)
    <section class="mp-coupons-section">
        <div class="container">
            <div class="mp-section-header">
                <h2>Cupones de Descuento <span class="mp-badge-count">{{ $coupons->count() }} activos</span></h2>
                <p>Usa estos códigos al finalizar tu pedido para obtener descuentos exclusivos</p>
            </div>
            <div class="mp-coupons-grid">
                @foreach($coupons as $coupon)
                <div class="mp-coupon-card">
                    <div class="coupon-left">
                        <span class="coupon-value">
                            @if($coupon->type === 'percentage')
                                {{ round($coupon->value) }}% OFF
                            @else
                                S/ {{ number_format($coupon->value, 2) }} OFF
                            @endif
                        </span>
                        <span class="coupon-min">Min. S/ {{ number_format($coupon->min_order, 2) }}</span>
                    </div>
                    <div class="coupon-right">
                        <strong>{{ $coupon->name }}</strong>
                        <p class="coupon-desc">{{ $coupon->description ?? '¡Aprovecha este descuento especial!' }}</p>
                        <div class="coupon-code-wrapper">
                            <span class="coupon-code">{{ $coupon->code }}</span>
                            <button class="copy-coupon-btn" onclick="copyCouponCode('{{ $coupon->code }}', this)">
                                <i class="las la-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- OFERTAS DEL DÍA (PRODUCTOS CON DESCUENTO) --}}
    @if(isset($discountedProducts) && $discountedProducts->count() > 0)
    <section class="mp-discounted-products-section">
        <div class="container">
            <div class="mp-section-header">
                <h2>Ofertas del Día <span class="mp-badge-tag">Descuentos</span></h2>
                <p>Ahorra con los mejores precios de nuestras tiendas asociadas</p>
            </div>
            <div class="mp-discounted-products-grid">
                @foreach($discountedProducts as $product)
                <div class="mp-discount-product-card">
                    <div class="product-image-wrapper">
                        @if($product->image)
                            <img src="{{ getImage(getFilePath('product') . '/' . $product->image) }}" alt="{{ $product->name }}">
                        @else
                            <div class="product-placeholder-icon"><i class="las la-hamburger"></i></div>
                        @endif
                        <span class="discount-badge">
                            @if($product->price > 0 && $product->discount_price > 0)
                                -{{ round((($product->price - $product->discount_price) / $product->price) * 100) }}%
                            @endif
                        </span>
                    </div>
                    <div class="product-info-wrapper">
                        <span class="product-store-name"><i class="las la-store"></i> {{ $product->store->name }}</span>
                        <h4 class="product-title">{{ $product->name }}</h4>
                        <p class="product-desc">{{ \Illuminate\Support\Str::limit($product->description, 60) }}</p>
                        <div class="product-price-row">
                            <div class="prices">
                                <span class="price-discount">S/ {{ number_format($product->discount_price, 2) }}</span>
                                <del class="price-original">S/ {{ number_format($product->price, 2) }}</del>
                            </div>
                            <a href="{{ route('delivery.store', $product->store) }}" class="view-store-btn">
                                Pedir <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- TIENDAS POPULARES --}}
    <section class="mp-stores">
        <div class="container">
            <h2>Tiendas populares <small>{{ $stores->count() }} disponibles</small></h2>
            <div class="mp-stores-grid">
                @forelse($stores as $store)
                <a class="mp-store-card" href="{{ route('delivery.store', $store) }}" data-store-id="{{ $store->id }}">
                    <div class="mp-store-cover">
                        @if($store->cover_image)
                            <img src="{{ getImage(getFilePath('store_cover') . '/' . $store->cover_image) }}" alt="{{ $store->name }}">
                        @else
                            <i class="las la-store"></i>
                        @endif
                        <span class="mp-store-badge {{ $store->is_open_now ? 'mp-store-badge--open' : 'mp-store-badge--closed' }}">{{ $store->is_open_now ? 'Abierto' : 'Cerrado' }}</span>
                    </div>
                    <div class="mp-store-info">
                        <div class="mp-store-name">{{ $store->name }}</div>
                        <div class="mp-store-category">{{ $store->description }}</div>
                        <div class="mp-store-meta">
                            <span><i class="las la-star"></i> {{ $store->rating > 0 ? number_format($store->rating, 1) : '—' }}</span>
                            <span><i class="las la-clock"></i> {{ $store->preparation_time ?? 20 }} min</span>
                            <span><i class="las la-motorcycle"></i>
                                @if($hasFreeDelivery)
                                <span style="text-decoration:line-through;color:#999;font-size:11px">S/ {{ number_format($store->display_fee, 2) }}</span>
                                <span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:800;margin-left:4px">GRATIS</span>
                                @else
                                S/ <span class="store-fee-amount">{{ number_format($store->display_fee, 2) }}</span>
                                @endif
                                @if($store->latitude && $store->longitude)<span class="store-distance" style="font-size:10px;color:#64748b;margin-left:4px"></span>@endif
                            </span>
                        </div>
                    </div>
                </a>
                @empty
                <div class="mp-empty">
                    <i class="las la-search"></i>
                    <h3>No encontramos tiendas</h3>
                    <p>Prueba otra búsqueda o revisa todas las categorías.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- CÓMO FUNCIONA --}}
    <section class="mp-how-it-works">
        <div class="container">
            <h2>¿Cómo funciona?</h2>
            <div class="mp-steps">
                <div class="mp-step">
                    <div class="mp-step-number">1</div>
                    <h4>Elige tu tienda</h4>
                    <p>Explora categorías y encuentra lo que buscas</p>
                </div>
                <div class="mp-step">
                    <div class="mp-step-number">2</div>
                    <h4>Arma tu pedido</h4>
                    <p>Agrega productos al carrito y personaliza</p>
                </div>
                <div class="mp-step">
                    <div class="mp-step-number">3</div>
                    <h4>Recibe en minutos</h4>
                    <p>Sigue tu pedido en tiempo real</p>
                </div>
            </div>
        </div>
    </section>

    {{-- BENEFICIOS --}}
    <section class="mp-benefits">
        <div class="container">
            <h2>¿Por qué elegir Lizto Delivery?</h2>
            <div class="mp-benefits-grid">
                <div class="mp-benefit-card">
                    <i class="las la-percent"></i>
                    <h4>0% Comisión</h4>
                    <p>No pagamos comisiones ocultas</p>
                </div>
                <div class="mp-benefit-card">
                    <i class="las la-user-shield"></i>
                    <h4>Repartidores verificados</h4>
                    <p>Tu seguridad es nuestra prioridad</p>
                </div>
                <div class="mp-benefit-card">
                    <i class="las la-bolt"></i>
                    <h4>Entrega express</h4>
                    <p>Tu pedido llega en minutos</p>
                </div>
                <div class="mp-benefit-card">
                    <i class="las la-credit-card"></i>
                    <h4>Paga como quieras</h4>
                    <p>Efectivo, tarjeta o billetera digital</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA FINAL --}}
    <section class="mp-cta-final">
        <div class="container">
            <h2>¿Quieres vender en Lizto?</h2>
            <p>Únete a más de {{ $storeCount }} tiendas que ya venden en nuestra plataforma. Sin comisiones, sin mensualidades.</p>
            <div class="mp-cta-buttons">
                <a href="{{ route('negocios') }}" class="mp-btn-white">
                    <i class="las la-store"></i> Crear mi tienda gratis
                </a>
                <a href="{{ route('seller.login') }}" class="mp-btn-outline">
                    <i class="las la-info-circle"></i> Más información
                </a>
            </div>
        </div>
    </section>

</main>
@endsection

@push('style')
<style>
/* MARKETPLACE - Tal cual prototipo-marketplace.html */
.mp-marketplace{background:#f8fafc;color:#334155;padding-top:44px}
.mp-marketplace *{box-sizing:border-box}

/* HERO */
.mp-marketplace-hero{background:linear-gradient(135deg,#effbef 0%,#fff 100%);padding:60px 0;text-align:center}
.mp-marketplace-hero h1{font-size:2.5rem;font-weight:700;color:#0f172a;margin-bottom:1rem}
.mp-marketplace-hero p{font-size:1.1rem;color:#64748b;margin-bottom:2rem;max-width:600px;margin-left:auto;margin-right:auto}

/* SEARCH */
.mp-search-bar{max-width:600px;margin:0 auto;background:#fff;border-radius:12px;padding:8px;box-shadow:0 4px 20px rgba(0,0,0,.1);display:flex;gap:8px}
.mp-search-input{flex:1;display:flex;align-items:center;gap:12px;padding:12px 16px;background:#f8fafc;border-radius:8px}
.mp-search-input i{color:#64748b;font-size:18px}
.mp-search-input input{border:none;background:none;font-size:1rem;color:#334155;outline:none;width:100%}
.mp-search-input input::placeholder{color:#64748b}
.mp-search-btn{background:#16a34a;color:#fff;border:none;padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;font-size:14px;transition:background .3s}
.mp-search-btn:hover{background:#0a4d19}

/* CATEGORIES */
.mp-categories{padding:40px 0}
.mp-categories h2{font-size:1.3rem;font-weight:600;color:#0f172a;margin-bottom:24px}
.mp-categories-grid{display:flex;gap:24px;overflow-x:auto;padding-bottom:16px;scrollbar-width:none}
.mp-categories-grid::-webkit-scrollbar{display:none}
.mp-category-item{flex-shrink:0;text-align:center;cursor:pointer;transition:transform .3s;text-decoration:none;color:#334155}
.mp-category-item:hover{transform:translateY(-5px)}
.mp-category-item.is-active .mp-category-icon{border:2px solid #16a34a}
.mp-category-icon{width:80px;height:80px;background:#fff;border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;box-shadow:0 4px 15px rgba(0,0,0,.08);border:2px solid transparent}
.mp-category-icon i{font-size:2rem;color:#16a34a}
.mp-category-item span{font-size:.85rem;font-weight:500}

/* STORES */
.mp-stores{padding:40px 0}
.mp-stores h2{font-size:1.3rem;font-weight:600;color:#0f172a;margin-bottom:24px}
.mp-stores h2 small{font-size:.85rem;font-weight:400;color:#64748b;margin-left:8px}
.mp-stores-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px}
.mp-store-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.08);transition:transform .3s,box-shadow .3s;cursor:pointer;text-decoration:none;color:#334155;display:block}
.mp-store-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,.12)}
.mp-store-cover{height:150px;background:linear-gradient(135deg,#16a34a 0%,#0a4d19 100%);position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden}
.mp-store-cover img{width:100%;height:100%;object-fit:cover}
.mp-store-cover i{font-size:3rem;color:rgba(255,255,255,.3)}
.mp-store-badge{position:absolute;top:10px;right:10px;background:#fff;padding:4px 10px;border-radius:20px;font-size:.7rem;font-weight:600}
.mp-store-badge--open{color:#16a34a}
.mp-store-badge--closed{color:#dc2626}
.mp-store-info{padding:18px}
.mp-store-name{font-size:1.1rem;font-weight:600;color:#0f172a;margin-bottom:4px}
.mp-store-category{font-size:.85rem;color:#64748b;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden}
.mp-store-meta{display:flex;gap:16px;font-size:.8rem;color:#64748b;flex-wrap:wrap}
.mp-store-meta span{display:flex;align-items:center;gap:4px}
.mp-store-meta i{color:#16a34a}

/* EMPTY */
.mp-empty{grid-column:1/-1;text-align:center;padding:60px 20px}
.mp-empty i{font-size:48px;color:#e2e8f0;margin-bottom:16px;display:block}
.mp-empty h3{font-size:20px;font-weight:700;color:#0f172a;margin-bottom:8px}
.mp-empty p{color:#64748b}

/* HOW IT WORKS */
.mp-how-it-works{padding:60px 0;background:#fff;margin-top:32px}
.mp-how-it-works h2{font-size:1.5rem;font-weight:700;color:#0f172a;text-align:center;margin-bottom:32px}
.mp-steps{display:flex;justify-content:center;gap:48px;flex-wrap:wrap}
.mp-step{text-align:center;max-width:200px}
.mp-step-number{width:60px;height:60px;background:#effbef;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.5rem;font-weight:700;color:#16a34a}
.mp-step h4{font-size:1rem;font-weight:600;color:#0f172a;margin-bottom:8px}
.mp-step p{font-size:.9rem;color:#64748b}

/* BENEFITS */
.mp-benefits{padding:60px 0;background:#f8fafc}
.mp-benefits h2{font-size:1.5rem;font-weight:700;color:#0f172a;text-align:center;margin-bottom:32px}
.mp-benefits-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px;max-width:1000px;margin:0 auto}
.mp-benefit-card{background:#fff;border-radius:16px;padding:24px;text-align:center;box-shadow:0 4px 15px rgba(0,0,0,.05)}
.mp-benefit-card i{font-size:2rem;color:#16a34a;margin-bottom:16px;display:block}
.mp-benefit-card h4{font-size:1rem;font-weight:600;color:#0f172a;margin-bottom:8px}
.mp-benefit-card p{font-size:.9rem;color:#64748b}

/* CTA */
.mp-cta-final{padding:64px 0;background:#16a34a;text-align:center;color:#fff}
.mp-cta-final h2{font-size:2rem;font-weight:700;margin-bottom:16px;color:#fff}
.mp-cta-final p{font-size:1.1rem;opacity:.9;margin-bottom:32px;max-width:600px;margin-left:auto;margin-right:auto}
.mp-cta-buttons{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}
.mp-btn-white{padding:16px 32px;border-radius:10px;font-weight:600;text-decoration:none;transition:all .3s;display:inline-flex;align-items:center;gap:8px;background:#fff;color:#16a34a}
.mp-btn-white:hover{background:#f1f5f9;transform:translateY(-2px)}
.mp-btn-outline{padding:16px 32px;border-radius:10px;font-weight:600;text-decoration:none;transition:all .3s;display:inline-flex;align-items:center;gap:8px;background:transparent;color:#fff;border:2px solid rgba(255,255,255,.5)}
.mp-btn-outline:hover{background:rgba(255,255,255,.1);border-color:#fff}

/* BANNER SECTION */
.mp-banners-section {
    padding: 20px 0;
}
.mp-banners-slider {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scrollbar-width: none;
    padding-bottom: 8px;
}
.mp-banners-slider::-webkit-scrollbar {
    display: none;
}
.mp-banner-slide {
    flex: 0 0 45%;
    min-width: 320px;
    height: 180px;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    transition: transform 0.2s;
}
.mp-banner-slide:hover {
    transform: translateY(-2px);
}
.mp-banner-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* SECTION HEADER */
.mp-section-header {
    margin-bottom: 24px;
}
.mp-section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 6px 0 !important;
}
.mp-section-header p {
    font-size: 0.95rem;
    color: #64748b;
    margin: 0 !important;
}
.mp-badge-count {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
}
.mp-badge-tag {
    background: #fee2e2;
    color: #dc2626;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
}

/* COUPONS */
.mp-coupons-section {
    padding: 40px 0;
}
.mp-coupons-grid {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    scrollbar-width: none;
    padding-bottom: 12px;
}
.mp-coupons-grid::-webkit-scrollbar {
    display: none;
}
.mp-coupon-card {
    flex: 0 0 320px;
    display: flex;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}
.coupon-left {
    background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
    color: #fff;
    width: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px;
    text-align: center;
    position: relative;
}
.coupon-left::after {
    content: '';
    position: absolute;
    right: -6px;
    top: 50%;
    transform: translateY(-50%);
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
    border-left: 6px solid #15803d;
}
.coupon-value {
    font-size: 18px;
    font-weight: 800;
    line-height: 1.1;
}
.coupon-min {
    font-size: 9px;
    opacity: 0.9;
    margin: 4px 0 0 0;
    font-weight: 600;
    display: block;
}
.coupon-right {
    flex: 1;
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.coupon-right strong {
    font-size: 14px;
    color: #0f172a;
    display: block;
    margin-bottom: 2px;
}
.coupon-desc {
    font-size: 11px;
    color: #64748b;
    margin: 0 0 8px 0;
}
.coupon-code-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 4px 8px;
}
.coupon-code {
    font-family: monospace;
    font-weight: 700;
    font-size: 12px;
    color: #334155;
}
.copy-coupon-btn {
    background: none;
    border: none;
    color: #16a34a;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    padding: 2px 6px;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: color 0.2s;
}
.copy-coupon-btn:hover {
    color: #15803d;
}

/* DISCOUNTED PRODUCTS */
.mp-discounted-products-section {
    padding: 40px 0;
}
.mp-discounted-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}
.mp-discount-product-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}
.mp-discount-product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.05);
}
.product-image-wrapper {
    height: 160px;
    position: relative;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.product-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.product-placeholder-icon {
    font-size: 48px;
    color: #cbd5e1;
}
.discount-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #dc2626;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 8px;
    border-radius: 6px;
}
.product-info-wrapper {
    padding: 16px;
}
.product-store-name {
    font-size: 11px;
    font-weight: 700;
    color: #16a34a;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 6px;
}
.product-title {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 4px 0;
}
.product-desc {
    font-size: 12px;
    color: #64748b;
    margin: 0 0 16px 0;
    height: 36px;
    overflow: hidden;
}
.product-price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.prices {
    display: flex;
    flex-direction: column;
}
.price-discount {
    font-size: 15px;
    font-weight: 800;
    color: #dc2626;
}
.price-original {
    font-size: 11px;
    color: #94a3b8;
    text-decoration: line-through;
}
.view-store-btn {
    padding: 8px 14px;
    background: #16a34a;
    color: #fff !important;
    font-size: 12px;
    font-weight: 700;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.2s;
}
.view-store-btn:hover {
    background: #15803d;
}

.mp-marketplace{background:#fff;color:#101828}
.mp-marketplace-hero{position:relative;overflow:hidden;padding:96px 0 68px!important;background:linear-gradient(180deg,#f6fbf7 0%,#fff 82%)!important}
.mp-marketplace-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 84% 12%,rgba(34,197,94,.2),transparent 30%),linear-gradient(90deg,rgba(22,163,74,.1),transparent 42%);pointer-events:none}
.mp-marketplace-hero .container{position:relative;z-index:1}
.mp-marketplace-hero>.container>h1,.mp-marketplace-hero>.container>p{display:none}
.mp-hero-kicker{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid rgba(22,163,74,.22);color:#137b3b;border-radius:999px;padding:9px 14px;font-size:13px;font-weight:900;margin-bottom:18px}
.mp-hero-copy h1{font-family:Outfit,Inter,sans-serif;font-size:clamp(36px,4.8vw,62px)!important;line-height:1!important;font-weight:900!important;max-width:780px;margin:0 auto 18px!important;color:#101828!important;letter-spacing:0!important}
.mp-hero-copy p{font-size:18px!important;line-height:1.65!important;color:#475467!important;max-width:680px!important;margin:0 auto 28px!important}
.mp-search-bar{border:1px solid #e7eaee!important;border-radius:8px!important;box-shadow:0 18px 45px rgba(16,24,40,.1)!important;max-width:760px!important}
.mp-search-input input{font-size:15px!important;color:#101828!important}
.mp-search-btn{border-radius:8px!important;background:#16a34a!important;font-weight:900!important}
.mp-categories,.mp-stores,.mp-how-it-works,.mp-benefits{background:#fff!important}
.mp-categories h2,.mp-section-header h2,.mp-stores h2,.mp-how-it-works h2,.mp-benefits h2{font-family:Outfit,Inter,sans-serif;color:#101828!important;font-weight:900!important;letter-spacing:0!important}
.mp-category-icon,.mp-store-card,.mp-coupon-card,.mp-product-card{border-radius:8px!important}
.mp-category-item:hover .mp-category-icon,.mp-store-card:hover,.mp-product-card:hover{box-shadow:0 18px 36px rgba(16,24,40,.09)!important}
.mp-store-card{border:1px solid #e7eaee!important;box-shadow:none!important}
.mp-cta-final{background:linear-gradient(135deg,#16a34a,#0f7a39)!important}

/* RESPONSIVE */
@media(max-width:768px){
    .mp-marketplace-hero h1{font-size:1.8rem}
    .mp-search-bar{flex-direction:column}
    .mp-steps{flex-direction:column;align-items:center}
    .mp-stores-grid{grid-template-columns:1fr}
    .mp-banner-slide {
        flex: 0 0 80%;
    }
}
</style>
@endpush

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Lizto Delivery",
  "url": "{{ route('delivery.marketplace') }}",
  "description": "Delivery en Tarapoto. Pide comida, restaurantes, farmacia, licores y más.",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "{{ route('delivery.marketplace') }}?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Lizto Delivery Tarapoto",
  "image": "{{ siteLogo() }}",
  "@id": "{{ route('delivery.marketplace') }}",
  "url": "{{ route('delivery.marketplace') }}",
  "telephone": "+51997428341",
  "description": "Plataforma de delivery y taxi en Tarapoto.",
  "address": { "@type": "PostalAddress", "addressLocality": "Tarapoto", "addressRegion": "San Martín", "addressCountry": "PE" },
  "geo": { "@type": "GeoCoordinates", "latitude": -6.4833, "longitude": -76.3667 },
  "openingHoursSpecification": { "@type": "OpeningHoursSpecification", "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"], "opens": "00:00", "closes": "23:59" }
}
</script>
<script>
window.addEventListener('load', function () {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '';
    var locationLat = null;
    var locationLng = null;

    function saveLocation(lat, lng, label) {
        locationLat = lat;
        locationLng = lng;
        fetch('/location/save', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ lat: lat, lng: lng, label: label })
        }).then(function(){ 
            refreshFees(); 
            if (typeof window.updateHeaderLocation === 'function') window.updateHeaderLocation();
        }).catch(function(){});
    }

    function refreshFees() {
        if (!locationLat || !locationLng) return;
        document.querySelectorAll('.mp-store-card').forEach(function(card) {
            var storeId = card.getAttribute('data-store-id');
            var feeEl = card.querySelector('.store-fee-amount');
            var distEl = card.querySelector('.store-distance');
            if (!storeId || !feeEl) return;
            fetch('/delivery/store-fee-estimate?store_id=' + storeId + '&delivery_lat=' + locationLat + '&delivery_lng=' + locationLng)
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d && d.delivery_fee != null) {
                        feeEl.textContent = d.delivery_fee.toFixed(2);
                        if (distEl && d.distance_km != null) distEl.textContent = d.distance_km.toFixed(1) + ' km';
                    }
                }).catch(function(){});
        });
    }

    fetch('/location/get', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.lat && data.lng) { locationLat = data.lat; locationLng = data.lng; refreshFees(); }
        }).catch(function(){});
});

function copyCouponCode(code, element) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(function() {
            var originalHTML = element.innerHTML;
            element.innerHTML = '<i class="las la-check"></i> ¡Copiado!';
            element.style.color = '#15803d';
            setTimeout(function() {
                element.innerHTML = originalHTML;
                element.style.color = '';
            }, 2000);
        });
    } else {
        var textArea = document.createElement("textarea");
        textArea.value = code;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            var originalHTML = element.innerHTML;
            element.innerHTML = '<i class="las la-check"></i> ¡Copiado!';
            element.style.color = '#15803d';
            setTimeout(function() {
                element.innerHTML = originalHTML;
                element.style.color = '';
            }, 2000);
        } catch (err) {}
        document.body.removeChild(textArea);
    }
}
</script>
@endpush
