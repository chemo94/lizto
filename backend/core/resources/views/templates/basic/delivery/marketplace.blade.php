@extends($activeTemplate . 'layouts.frontend')

@section('content')
@php
    $openStores = $stores->filter(fn($s) => $s->is_open_now);
    $closedStores = $stores->filter(fn($s) => !$s->is_open_now);
    $activeCatId = request('category');
    $activeSubCatId = request('subcategory');

    function getCategoryEmoji($slug) {
        return match($slug) {
            'restaurantes' => '🍔',
            'farmacia' => '💊',
            'servicio-de-favores' => '🛵',
            'mascotas' => '🐾',
            'licorerias' => '🍾',
            'super-mini-markets' => '🛒',
            default => '🏪'
        };
    }

    function getSubCatEmoji($name) {
        $n = mb_strtolower($name);
        if (str_contains($n, 'pollo') || str_contains($n, 'broaster') || str_contains($n, 'alita')) return '🍗';
        if (str_contains($n, 'pizza')) return '🍕';
        if (str_contains($n, 'hamburguesa')) return '🍔';
        if (str_contains($n, 'salchipapa')) return '🍟';
        if (str_contains($n, 'parrilla') || str_contains($n, 'carne') || str_contains($n, 'embutido')) return '🥩';
        if (str_contains($n, 'anticucho')) return '🍢';
        if (str_contains($n, 'chifa') || str_contains($n, 'china')) return '🥡';
        if (str_contains($n, 'menú') || str_contains($n, 'menu')) return '🍲';
        if (str_contains($n, 'criolla')) return '🥘';
        if (str_contains($n, 'postre') || str_contains($n, 'helado')) return '🍰';
        if (str_contains($n, 'café') || str_contains($n, 'jugo')) return '☕';
        if (str_contains($n, 'maki') || str_contains($n, 'sushi')) return '🍣';
        if (str_contains($n, 'marisco') || str_contains($n, 'ceviche')) return '🐟';
        if (str_contains($n, 'medicina') || str_contains($n, 'farmacia') || str_contains($n, 'salud')) return '💊';
        if (str_contains($n, 'cuidado') || str_contains($n, 'higiene') || str_contains($n, 'piel')) return '🧴';
        if (str_contains($n, 'bebé') || str_contains($n, 'maternidad')) return '👶';
        if (str_contains($n, 'vitamina') || str_contains($n, 'suplemento')) return '⚡';
        if (str_contains($n, 'auxilio')) return '🩹';
        if (str_contains($n, 'bucal')) return '🪥';
        if (str_contains($n, 'mandado') || str_contains($n, 'envío')) return '🛵';
        if (str_contains($n, 'compra')) return '🛍️';
        if (str_contains($n, 'recojo') || str_contains($n, 'paquete')) return '📦';
        if (str_contains($n, 'trámite') || str_contains($n, 'pago')) return '📝';
        if (str_contains($n, 'perro')) return '🐕';
        if (str_contains($n, 'gato')) return '🐈';
        if (str_contains($n, 'snack') || str_contains($n, 'premio')) return '🦴';
        if (str_contains($n, 'pulga') || str_contains($n, 'veterinaria')) return '🩺';
        if (str_contains($n, 'juguete') || str_contains($n, 'accesorio')) return '🧸';
        if (str_contains($n, 'cerveza')) return '🍺';
        if (str_contains($n, 'vino') || str_contains($n, 'espumante')) return '🍷';
        if (str_contains($n, 'pisco') || str_contains($n, 'trago') || str_contains($n, 'destilado')) return '🍸';
        if (str_contains($n, 'whisky') || str_contains($n, 'ron')) return '🥃';
        if (str_contains($n, 'hielo')) return '🧊';
        if (str_contains($n, 'cóctel') || str_contains($n, 'coctel')) return '🍹';
        if (str_contains($n, 'abarrote') || str_contains($n, 'despensa')) return '🌾';
        if (str_contains($n, 'fruta') || str_contains($n, 'verdura')) return '🥦';
        if (str_contains($n, 'lácteo') || str_contains($n, 'leche') || str_contains($n, 'huevo')) return '🥛';
        if (str_contains($n, 'pan') || str_contains($n, 'desayuno')) return '🥖';
        if (str_contains($n, 'limpieza') || str_contains($n, 'hogar')) return '🧹';
        return '✨';
    }
@endphp

<main class="lz-marketplace-page">
    <div class="container">

        {{-- 1. HERO SUPERAPP BANNER --}}
        <section class="lz-hero-section" id="buscar">
            <div class="lz-hero-banner">
                <div class="lz-hero-content">
                    <div class="lz-hero-pill-badge">
                        <span class="lz-pulse-dot"></span>
                        <span>Superapp Oficial de Tarapoto · Todo a tu puerta en minutos</span>
                    </div>
                    <h1 class="lz-hero-main-title">
                        ¿Qué quieres pedir hoy en <span class="lz-hero-city-highlight">Tarapoto</span>?
                    </h1>
                    <p class="lz-hero-main-sub">
                        Comida de tus restaurantes favoritos, compras de minimarkets, favores urgentes y viajes en taxi seguro en una sola plataforma.
                    </p>
                    <div class="lz-hero-value-props">
                        <div class="lz-prop-chip">
                            <i class="las la-bolt text-warning"></i>
                            <span>Entregas en 25 - 40 min</span>
                        </div>
                        <div class="lz-prop-chip">
                            <i class="las la-shield-alt text-success"></i>
                            <span>Conductores & Repartidores Verificados</span>
                        </div>
                        <div class="lz-prop-chip">
                            <i class="las la-map-marker-alt text-danger"></i>
                            <span>Tarapoto · Morales · La Banda</span>
                        </div>
                        <div class="lz-prop-chip">
                            <i class="las la-wallet text-info"></i>
                            <span>Yape, Plin o Efectivo</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. 4 SUPERAPP HUB CARDS --}}
            <div class="lz-services-grid">
                <a href="javascript:void(0)" onclick="selectCategoryDirect('1', 'restaurantes', 'Comida'); document.getElementById('restaurantes').scrollIntoView({behavior:'smooth'});" class="lz-service-card lz-svc-food">
                    <div class="lz-svc-badge">🔥 +50 Restaurantes</div>
                    <div class="lz-service-icon">
                        <i class="las la-utensils"></i>
                    </div>
                    <div class="lz-svc-info">
                        <span class="lz-service-title">Comida</span>
                        <span class="lz-service-sub">Pollerías, Chifas & Pizzas</span>
                    </div>
                    <div class="lz-svc-action">
                        <span>Explorar menú</span>
                        <i class="las la-arrow-right"></i>
                    </div>
                </a>

                <a href="javascript:void(0)" onclick="selectCategoryDirect('markets', 'markets', 'Tiendas'); document.getElementById('restaurantes').scrollIntoView({behavior:'smooth'});" class="lz-service-card lz-svc-stores">
                    <div class="lz-svc-badge lz-badge-green">🥦 Precios de Tienda</div>
                    <div class="lz-service-icon">
                        <i class="las la-shopping-basket"></i>
                    </div>
                    <div class="lz-svc-info">
                        <span class="lz-service-title">Mercados & Tiendas</span>
                        <span class="lz-service-sub">Bodegas, Bebidas & Boticas</span>
                    </div>
                    <div class="lz-svc-action">
                        <span>Hacer compras</span>
                        <i class="las la-arrow-right"></i>
                    </div>
                </a>

                <a href="{{ route('favor') }}" class="lz-service-card lz-svc-favor">
                    <div class="lz-svc-badge lz-badge-purple">⚡ Envíos Express</div>
                    <div class="lz-service-icon">
                        <i class="las la-hand-holding-heart"></i>
                    </div>
                    <div class="lz-svc-info">
                        <span class="lz-service-title">Lizto Favor</span>
                        <span class="lz-service-sub">Mandados, Llaves & Envíos</span>
                    </div>
                    <div class="lz-svc-action">
                        <span>Pedir mandado</span>
                        <i class="las la-arrow-right"></i>
                    </div>
                </a>

                <a href="{{ route('taxi') }}" class="lz-service-card lz-svc-taxi">
                    <div class="lz-svc-badge lz-badge-amber">🚕 Viajes Directos</div>
                    <div class="lz-service-icon">
                        <i class="las la-taxi"></i>
                    </div>
                    <div class="lz-svc-info">
                        <span class="lz-service-title">Taxi Seguro</span>
                        <span class="lz-service-sub">Viajes rápidos y confiables</span>
                    </div>
                    <div class="lz-svc-action">
                        <span>Pedir taxi</span>
                        <i class="las la-arrow-right"></i>
                    </div>
                </a>
            </div>
        </section>

        {{-- 3. EXPLORA POR ANTOJO (CRAVING BUBBLES) --}}
        <section class="lz-cravings-section mb-4" id="seccion-antojos">
            <div class="lz-section-head mb-2">
                <h2 class="lz-section-title">
                    <span>🍽️</span> ¿Qué se te antoja hoy?
                </h2>
                <span class="lz-section-sub-tag">Descubre por plato favorito</span>
            </div>
            <div class="lz-cravings-scroll">
                <a href="javascript:void(0)" onclick="filterByCraving('pollo', '🍗 Pollerías')" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;">🍗</div>
                    <span class="lz-craving-label">Pollerías</span>
                </a>
                <a href="javascript:void(0)" onclick="filterByCraving('pizza', '🍕 Pizzas')" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626;">🍕</div>
                    <span class="lz-craving-label">Pizzas</span>
                </a>
                <a href="javascript:void(0)" onclick="filterByCraving('hamburguesa', '🍔 Burgers')" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #ffedd5, #fed7aa); color: #ea580c;">🍔</div>
                    <span class="lz-craving-label">Burgers</span>
                </a>
                <a href="javascript:void(0)" onclick="filterByCraving('chifa', '🥡 Chifa')" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #db2777;">🥡</div>
                    <span class="lz-craving-label">Chifa</span>
                </a>
                <a href="javascript:void(0)" onclick="filterByCraving('parrilla', '🥩 Parrillas')" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #f3e8ff, #e9d5ff); color: #9333ea;">🥩</div>
                    <span class="lz-craving-label">Parrillas</span>
                </a>
                <a href="javascript:void(0)" onclick="filterByCraving('postre', '🍰 Postres')" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5;">🍰</div>
                    <span class="lz-craving-label">Postres</span>
                </a>
                <a href="javascript:void(0)" onclick="filterByCraving('café', '☕ Cafés')" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #fae8ff, #f5d0fe); color: #a21caf;">☕</div>
                    <span class="lz-craving-label">Cafés</span>
                </a>
                <a href="javascript:void(0)" onclick="selectCategoryDirect('licorerias', 'licorerias', 'Licores'); document.getElementById('restaurantes').scrollIntoView({behavior:'smooth'});" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #ccfbf1, #99f6e4); color: #0d9488;">🍾</div>
                    <span class="lz-craving-label">Licores</span>
                </a>
                <a href="javascript:void(0)" onclick="selectCategoryDirect('farmacia', 'farmacia', 'Farmacia'); document.getElementById('restaurantes').scrollIntoView({behavior:'smooth'});" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #16a34a;">💊</div>
                    <span class="lz-craving-label">Farmacias</span>
                </a>
                <a href="javascript:void(0)" onclick="selectCategoryDirect('super-mini-markets', 'super-mini-markets', 'Supermercados'); document.getElementById('restaurantes').scrollIntoView({behavior:'smooth'});" class="lz-craving-item">
                    <div class="lz-craving-avatar" style="background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0284c7;">🛒</div>
                    <span class="lz-craving-label">Markets</span>
                </a>
            </div>
        </section>

        {{-- 4. BANNERS Y PROMOCIONES DESTACADAS --}}
        @if(isset($banners) && $banners->count() > 0)
        <section class="mb-4">
            <div class="mp-banners-slider">
                @foreach($banners as $banner)
                <a href="{{ $banner->link ?: route('delivery.marketplace') }}" class="mp-banner-slide">
                    <img src="{{ getImage(getFilePath('banner') . '/' . $banner->image) }}" alt="{{ $banner->title ?? 'Promoción Lizto' }}" loading="lazy">
                </a>
                @endforeach
            </div>
        </section>
        @else
        {{-- FALLBACK PROMO CARDS (SIEMPRE ATRACTIVAS) --}}
        <section class="lz-curated-promos mb-5">
            <div class="lz-promo-grid">
                <div class="lz-promo-card lz-promo-welcome">
                    <div class="lz-promo-content">
                        <span class="lz-promo-tag"><i class="las la-gift"></i> Exclusivo</span>
                        <h3 class="lz-promo-title">¡Tu 1er Pedido con Delivery GRATIS!</h3>
                        <p class="lz-promo-desc">Disfruta de Tarapoto a tu puerta sin costo de envío usando tu cupón.</p>
                        <div class="lz-promo-action">
                            <span class="lz-promo-code">BIENVENIDO</span>
                            <button type="button" class="lz-promo-btn" onclick="copyCouponCode('BIENVENIDO', this)">
                                <i class="las la-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                    <div class="lz-promo-visual">
                        <i class="las la-shipping-fast"></i>
                    </div>
                </div>

                <div class="lz-promo-card lz-promo-favor">
                    <div class="lz-promo-content">
                        <span class="lz-promo-tag"><i class="las la-motorcycle"></i> Express</span>
                        <h3 class="lz-promo-title">¿Necesitas un mandado o encargo urgente?</h3>
                        <p class="lz-promo-desc">Compramos o recogemos lo que necesites en cualquier punto de Tarapoto.</p>
                        <div class="lz-promo-action">
                            <a href="{{ route('favor') }}" class="lz-promo-btn-link">
                                <span>Pedir Lizto Favor</span>
                                <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="lz-promo-visual">
                        <i class="las la-hand-holding-heart"></i>
                    </div>
                </div>

                <div class="lz-promo-card lz-promo-food">
                    <div class="lz-promo-content">
                        <span class="lz-promo-tag"><i class="las la-fire"></i> Sabores Locales</span>
                        <h3 class="lz-promo-title">Lo mejor de la gastronomía de Tarapoto</h3>
                        <p class="lz-promo-desc">Pollo a la brasa, tacacho con cecina, pizzas y hamburguesas listas.</p>
                        <div class="lz-promo-action">
                            <a href="javascript:void(0)" onclick="selectCategoryDirect('1', 'restaurantes', 'Comida'); document.getElementById('restaurantes').scrollIntoView({behavior:'smooth'});" class="lz-promo-btn-link">
                                <span>Ver Restaurantes</span>
                                <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="lz-promo-visual">
                        <i class="las la-hamburger"></i>
                    </div>
                </div>
            </div>
        </section>
        @endif

        {{-- 5. LOS MÁS PEDIDOS DE TARAPOTO (TOP TENDENCIA) --}}
        @if(isset($mostOrdered) && $mostOrdered->count() > 0)
        <section class="mb-5" id="seccion-mas-pedidos">
            <div class="lz-section-head">
                <h2 class="lz-section-title">
                    🔥 Los Más Pedidos de Tarapoto
                    <span class="lz-brand-city" style="font-size:10.5px;">TOP FAVORITOS</span>
                </h2>
                <span class="lz-section-sub-tag d-none d-md-inline">Platos y productos más solicitados en la ciudad</span>
            </div>
            <div class="lz-products-grid">
                @foreach($mostOrdered->take(8) as $item)
                @php
                    $prod = $item->product;
                    if (!$prod) continue;
                    $finalPrice = $prod->finalPrice();
                    $hasDiscount = $prod->discount_price > 0 && $prod->price > $prod->discount_price;
                    $saving = $hasDiscount ? ($prod->price - $prod->discount_price) : 0;
                    $prodImage = $prod->image ? getImage(getFilePath('product') . '/' . $prod->image) : '';
                    $storeName = $prod->store ? $prod->store->name : 'Tienda Lizto';
                    $storeId = $prod->store_id;
                    $storeUrl = $prod->store ? route('delivery.store', $prod->store) : '#';
                @endphp
                <div class="lz-product-card" 
                     data-product-id="{{ $prod->id }}"
                     data-product-name="{{ htmlspecialchars($prod->name) }}"
                     data-product-desc="{{ htmlspecialchars($prod->description ?? '') }}"
                     data-product-price="{{ number_format($finalPrice, 2, '.', '') }}"
                     data-product-old-price="{{ $hasDiscount ? number_format($prod->price, 2, '.', '') : '' }}"
                     data-product-saving="{{ number_format($saving, 2, '.', '') }}"
                     data-product-image="{{ $prodImage }}"
                     data-store-id="{{ $storeId }}"
                     data-store-name="{{ htmlspecialchars($storeName) }}"
                     data-store-url="{{ $storeUrl }}"
                     onclick="openProductQuickView(this)">
                    <div class="lz-product-info">
                        <div>
                            <span class="lz-product-store-tag">
                                <i class="las la-store"></i> {{ $storeName }}
                            </span>
                            <h3 class="lz-product-name">{{ $prod->name }}</h3>
                            <p class="lz-product-desc">{{ $prod->description ? \Illuminate\Support\Str::limit($prod->description, 55) : 'El favorito de los usuarios en Tarapoto.' }}</p>
                        </div>
                        <div class="lz-product-price-row">
                            <span class="lz-product-price">S/ {{ number_format($finalPrice, 2) }}</span>
                            @if($hasDiscount)
                                <span class="lz-product-old-price">S/ {{ number_format($prod->price, 2) }}</span>
                                <span class="lz-product-save-pill">-S/ {{ number_format($saving, 2) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="lz-product-thumb">
                        @if($prod->image)
                            <img src="{{ $prodImage }}" alt="{{ $prod->name }}" loading="lazy">
                        @else
                            <div class="lz-product-placeholder-icon">
                                <i class="las la-utensils"></i>
                            </div>
                        @endif
                        <button type="button" class="lz-product-add-btn" title="Pedir producto" onclick="event.stopPropagation(); openProductQuickView(this.closest('.lz-product-card'))">
                            <i class="las la-plus"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 6. HORIZONTAL CATEGORIES CAROUSEL --}}
        <section class="lz-categories-strip" id="seccion-categorias">
            <div class="lz-section-head">
                <h2 class="lz-section-title">
                    <i class="las la-th-large" style="color:var(--lz-primary)"></i> Todas las Categorías
                </h2>
                <a href="javascript:void(0)" onclick="selectCategoryDirect('', '', 'Todo')" id="btnVerTodasCategorias" class="lz-section-link {{ request('category') || request('subcategory') || request('q') ? '' : 'd-none' }}">Ver todas</a>
            </div>

            <div class="lz-chips-container-wrapper">
                <button type="button" class="lz-scroll-nav-btn prev" onclick="scrollChipRow('categoriesScroll', -240)" aria-label="Desplazar a la izquierda">
                    <i class="las la-angle-left"></i>
                </button>
                <div class="lz-chips-scroll" id="categoriesScroll">
                    <a href="javascript:void(0)" onclick="selectCategoryDirect('', '', 'Todo')" data-category-id="" class="lz-cat-chip cat-btn {{ !request('category') && !request('subcategory') && !request('q') ? 'active' : '' }}">
                        <i class="las la-border-all lz-cat-chip-icon"></i>
                        <span>Todo</span>
                    </a>

                    @foreach($categories as $category)
                        @if($category->slug === 'servicio-de-favores')
                            <a href="{{ route('favor') }}" class="lz-cat-chip cat-btn" data-category-id="{{ $category->id }}" data-category-slug="{{ $category->slug }}" data-category-name="{{ $category->name }}">
                                <span class="lz-cat-emoji">{{ getCategoryEmoji($category->slug) }}</span>
                                <span>{{ $category->name }}</span>
                            </a>
                        @else
                            <a href="javascript:void(0)" onclick="selectCategoryDirect('{{ $category->id }}', '{{ $category->slug }}', '{{ addslashes($category->name) }}')" data-category-id="{{ $category->id }}" data-category-slug="{{ $category->slug }}" data-category-name="{{ $category->name }}" class="lz-cat-chip cat-btn {{ (request('category') == $category->id || request('category') == $category->slug) ? 'active' : '' }}">
                                @if($category->image)
                                    <img src="{{ getImage(getFilePath('general_category') . '/' . $category->image) }}" alt="{{ $category->name }}" class="lz-cat-thumb">
                                @else
                                    <span class="lz-cat-emoji">{{ getCategoryEmoji($category->slug) }}</span>
                                @endif
                                <span>{{ $category->name }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
                <button type="button" class="lz-scroll-nav-btn next" onclick="scrollChipRow('categoriesScroll', 240)" aria-label="Desplazar a la derecha">
                    <i class="las la-angle-right"></i>
                </button>
            </div>
        </section>

        {{-- 7. SUBCATEGORÍAS DINÁMICAS POR CATEGORÍA --}}
        @php
            $hasSubcats = false;
            foreach($categories as $c) {
                if ($c->subCategories && $c->subCategories->count() > 0) {
                    $hasSubcats = true; break;
                }
            }
        @endphp
        @if($hasSubcats)
        <section class="lz-categories-strip lz-subcategories-section" id="seccion-subcategorias">
            <div class="lz-section-head">
                <h2 class="lz-section-title" id="subCategoryHeaderTitle" style="font-size:16px;">
                    ✨ Subcategorías
                </h2>
                <div class="d-flex align-items-center gap-2">
                    <span id="activeSubCatBadge" class="lz-filter-active-pill d-none">
                        <span id="activeSubCatName"></span>
                        <i class="las la-times" onclick="clearSubcategoryFilter(event)" title="Quitar filtro"></i>
                    </span>
                    <a href="javascript:void(0)" onclick="clearSubcategoryFilter(event)" id="btnLimpiarSubcat" class="lz-section-link {{ request('subcategory') ? '' : 'd-none' }}">Limpiar</a>
                </div>
            </div>

            <div class="lz-chips-container-wrapper">
                <button type="button" class="lz-scroll-nav-btn prev" onclick="scrollChipRow('subcategoriesScroll', -240)" aria-label="Desplazar a la izquierda">
                    <i class="las la-angle-left"></i>
                </button>
                <div class="lz-chips-scroll" id="subcategoriesScroll">
                    @foreach($categories as $cat)
                        @foreach($cat->subCategories as $subCat)
                            <a href="javascript:void(0)" 
                               onclick="selectSubcategoryDirect('{{ $subCat->id }}', '{{ addslashes($subCat->name) }}', '{{ $cat->id }}')" 
                               data-parent-category="{{ $cat->id }}" 
                               data-parent-slug="{{ $cat->slug }}"
                               data-subcategory-id="{{ $subCat->id }}" 
                               data-subcategory-name="{{ $subCat->name }}"
                               class="lz-cat-chip subcat-chip {{ request('subcategory') == $subCat->id ? 'active' : '' }}">
                                @if($subCat->image)
                                    <img src="{{ getImage('assets/images/sub_category/' . $subCat->image) }}" alt="{{ $subCat->name }}" class="lz-cat-thumb">
                                @else
                                    <span class="lz-cat-emoji">{{ getSubCatEmoji($subCat->name) }}</span>
                                @endif
                                <span>{{ $subCat->name }}</span>
                            </a>
                        @endforeach
                    @endforeach
                </div>
                <button type="button" class="lz-scroll-nav-btn next" onclick="scrollChipRow('subcategoriesScroll', 240)" aria-label="Desplazar a la derecha">
                    <i class="las la-angle-right"></i>
                </button>
            </div>
        </section>
        @endif

        {{-- 8. CUPONES DE DESCUENTO --}}
        @if(isset($coupons) && $coupons->count() > 0)
        <section class="mb-5" id="seccion-cupones">
            <div class="lz-section-head">
                <h2 class="lz-section-title">
                    🎁 Cupones y Descuentos
                    <span class="lz-brand-city" style="font-size:10.5px;">{{ $coupons->count() }} ACTIVOS</span>
                </h2>
                <span class="lz-section-sub-tag">Haz clic en Copiar para usarlo en tu pedido</span>
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

        {{-- 9. OFERTAS DEL DÍA (PRODUCTOS CON DESCUENTO) --}}
        @if(isset($discountedProducts) && $discountedProducts->count() > 0)
        <section class="mb-5" id="seccion-ofertas">
            <div class="lz-section-head">
                <h2 class="lz-section-title">
                    ⚡ Ofertas del Día
                    <span class="mp-badge-tag">AHORRA HOY</span>
                </h2>
            </div>
            <div class="lz-products-grid">
                @foreach($discountedProducts->take(8) as $product)
                @php
                    $finalPrice = $product->finalPrice();
                    $hasDiscount = $product->discount_price > 0 && $product->price > $product->discount_price;
                    $saving = $hasDiscount ? ($product->price - $product->discount_price) : 0;
                    $prodImage = $product->image ? getImage(getFilePath('product') . '/' . $product->image) : '';
                    $storeName = $product->store ? $product->store->name : 'Tienda Lizto';
                    $storeId = $product->store_id;
                    $storeUrl = $product->store ? route('delivery.store', $product->store) : '#';
                @endphp
                <div class="lz-product-card" 
                     data-product-id="{{ $product->id }}"
                     data-product-name="{{ htmlspecialchars($product->name) }}"
                     data-product-desc="{{ htmlspecialchars($product->description ?? '') }}"
                     data-product-price="{{ number_format($finalPrice, 2, '.', '') }}"
                     data-product-old-price="{{ $hasDiscount ? number_format($product->price, 2, '.', '') : '' }}"
                     data-product-saving="{{ number_format($saving, 2, '.', '') }}"
                     data-product-image="{{ $prodImage }}"
                     data-store-id="{{ $storeId }}"
                     data-store-name="{{ htmlspecialchars($storeName) }}"
                     data-store-url="{{ $storeUrl }}"
                     onclick="openProductQuickView(this)">
                    <div class="lz-product-info">
                        <div>
                            <span class="lz-product-store-tag">
                                <i class="las la-store"></i> {{ $storeName }}
                            </span>
                            <h3 class="lz-product-name">{{ $product->name }}</h3>
                            <p class="lz-product-desc">{{ $product->description ?? 'Delicioso producto disponible para entrega inmediata.' }}</p>
                        </div>
                        <div class="lz-product-price-row">
                            <span class="lz-product-price">S/ {{ number_format($finalPrice, 2) }}</span>
                            @if($hasDiscount)
                                <span class="lz-product-old-price">S/ {{ number_format($product->price, 2) }}</span>
                                <span class="lz-product-save-pill">-S/ {{ number_format($saving, 2) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="lz-product-thumb">
                        @if($product->image)
                            <img src="{{ $prodImage }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <div class="lz-product-placeholder-icon">
                                <i class="las la-utensils"></i>
                            </div>
                        @endif
                        <button type="button" class="lz-product-add-btn" title="Pedir producto" onclick="event.stopPropagation(); openProductQuickView(this.closest('.lz-product-card'))">
                            <i class="las la-plus"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- 10. BANNER ESPECIAL LIZTO FAVOR (FEATURE CALLOUT) --}}
        <section class="lz-feature-favor-section mb-5">
            <div class="lz-favor-showcase-box">
                <div class="lz-favor-left">
                    <span class="lz-favor-pill"><i class="las la-hand-holding-heart"></i> Lizto Favor · Mandados Express</span>
                    <h3 class="lz-favor-title">¿Necesitas que compremos, recojamos o enviemos algo por ti?</h3>
                    <p class="lz-favor-desc">
                        Olvídate del tráfico. Desde medicamentos en farmacias hasta llaves, documentos o compras de mercado: un repartidor de Lizto se encarga de inmediato.
                    </p>
                    <div class="lz-favor-steps">
                        <div class="lz-fstep">
                            <span class="lz-fstep-num">1</span>
                            <div>
                                <strong>Pide lo que sea</strong>
                                <span>Dinos qué y dónde recoger</span>
                            </div>
                        </div>
                        <div class="lz-fstep">
                            <span class="lz-fstep-num">2</span>
                            <div>
                                <strong>Repartidor Asignado</strong>
                                <span>Rastreo en tiempo real</span>
                            </div>
                        </div>
                        <div class="lz-fstep">
                            <span class="lz-fstep-num">3</span>
                            <div>
                                <strong>Entrega Directa</strong>
                                <span>En tus manos sin demoras</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('favor') }}" class="lz-btn-cta lz-favor-cta-btn">
                        <span>Solicitar Mandado Express</span>
                        <i class="las la-arrow-right"></i>
                    </a>
                </div>
                <div class="lz-favor-right d-none d-lg-flex">
                    <div class="lz-favor-badge-float top-badge">
                        <i class="las la-clock text-warning"></i>
                        <span>Envíos en menos de 35 min</span>
                    </div>
                    <div class="lz-favor-illustration">
                        <i class="las la-motorcycle"></i>
                    </div>
                    <div class="lz-favor-badge-float bottom-badge">
                        <i class="las la-check-circle text-success"></i>
                        <span>Repartidores 100% verificados</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- 11. RESTAURANTES Y TIENDAS ABIERTAS --}}
        <section class="mb-5" id="restaurantes">
            <div class="lz-section-head">
                <h2 class="lz-section-title" id="storesSectionTitle">
                    🔥 Restaurantes y Tiendas
                    <span class="lz-brand-city" id="openStoresCounter">{{ $openStores->count() }} ABIERTOS</span>
                </h2>
                <div class="d-none d-sm-flex align-items-center gap-2">
                    <span style="font-size:12.5px;color:var(--lz-text-muted);">En Tarapoto, Morales y La Banda</span>
                </div>
            </div>

            <div class="lz-stores-grid" id="storesGridContainer">
                @foreach($openStores as $store)
                @php
                    $storeCatIds = $store->generalCategories->pluck('id')->toArray();
                    $storeCatSlugs = $store->generalCategories->pluck('slug')->toArray();
                    $storeSubCatIds = $store->subCategories->pluck('id')->toArray();
                @endphp
                <a href="{{ route('delivery.store', $store) }}" 
                   class="lz-store-card store-card-item" 
                   data-store-id="{{ $store->id }}"
                   data-categories="{{ json_encode($storeCatIds) }}"
                   data-slugs="{{ json_encode($storeCatSlugs) }}"
                   data-subcategories="{{ json_encode($storeSubCatIds) }}"
                   data-name="{{ mb_strtolower($store->name) }}"
                   data-desc="{{ mb_strtolower($store->description ?? '') }}">
                    {{-- Cover --}}
                    <div class="lz-store-cover">
                        @if($store->cover_image)
                            <img src="{{ getImage(getFilePath('store_cover') . '/' . $store->cover_image) }}" alt="{{ $store->name }}" loading="lazy">
                        @else
                            <div class="lz-store-cover-default">
                                <i class="las la-store"></i>
                            </div>
                        @endif

                        {{-- Open Badge --}}
                        <div class="lz-store-badge-open lz-badge-live">
                            <span class="lz-live-dot"></span>
                            Abierto
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="lz-store-body">
                        <div class="lz-store-logo">
                            @if($store->image)
                                <img src="{{ getImage(getFilePath('store') . '/' . $store->image) }}" alt="{{ $store->name }}" loading="lazy">
                            @else
                                <div class="lz-store-logo-placeholder">
                                    <i class="las la-store"></i>
                                </div>
                            @endif
                        </div>
                        <div class="lz-store-content">
                            <h3 class="lz-store-name">{{ $store->name }}</h3>
                            <div class="lz-store-meta">
                                <span>{{ $store->description ? \Illuminate\Support\Str::limit($store->description, 40) : 'Comida, productos y delivery veloz' }}</span>
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
                                    <span class="lz-eta-time">({{ $store->preparation_time ?? 25 }} min)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            {{-- Empty State (JS / Backend) --}}
            <div id="lzNoStoresFound" class="text-center py-5 {{ $openStores->count() == 0 ? '' : 'd-none' }}" style="background:#fff;border-radius:16px;border:1px solid var(--lz-border);padding:40px 20px;margin-top:16px;">
                <div style="font-size:48px;color:#94a3b8;margin-bottom:12px">
                    <i class="las la-store-alt-slash"></i>
                </div>
                <h3 style="font-size:18px;font-weight:800;color:var(--lz-text);margin-bottom:6px">No encontramos tiendas en esta categoría</h3>
                <p style="color:var(--lz-text-muted);font-size:14px;max-width:420px;margin:0 auto 18px">
                    Prueba cambiando de filtro o solicita un mandado directo por Lizto Favor para que te lo llevemos.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button type="button" onclick="selectCategoryDirect('', '', 'Todo')" class="lz-btn-pill-secondary">
                        <i class="las la-border-all"></i> Ver Todo
                    </button>
                    <a href="{{ route('favor') }}" class="lz-btn-cta d-inline-flex align-items-center gap-2" style="max-width:240px;">
                        <i class="las la-hand-holding-heart"></i> Pedir con Lizto Favor
                    </a>
                </div>
            </div>
        </section>

        {{-- 12. TIENDAS CERRADAS (VISUALMENTE SEPARADAS) --}}
        @if($closedStores->count() > 0)
        <section class="mb-5" id="seccion-cerrados" style="opacity: 0.85;">
            <div class="lz-section-head">
                <h2 class="lz-section-title" style="color:var(--lz-text-muted);font-size:18px;">
                    <i class="las la-clock"></i> Cerrados por ahora · Abren más tarde
                    <span class="lz-brand-city" style="background:#f1f5f9;color:#64748b;">{{ $closedStores->count() }}</span>
                </h2>
            </div>
            <div class="lz-stores-grid">
                @foreach($closedStores as $store)
                <a href="{{ route('delivery.store', $store) }}" class="lz-store-card" style="filter: grayscale(25%);">
                    <div class="lz-store-cover">
                        @if($store->cover_image)
                            <img src="{{ getImage(getFilePath('store_cover') . '/' . $store->cover_image) }}" alt="{{ $store->name }}" loading="lazy">
                        @else
                            <div class="lz-store-cover-default" style="background:#475569;">
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
                                <div class="lz-store-logo-placeholder">
                                    <i class="las la-store"></i>
                                </div>
                            @endif
                        </div>
                        <div class="lz-store-content">
                            <h3 class="lz-store-name">{{ $store->name }}</h3>
                            <div class="lz-store-meta">
                                <span>{{ $store->description ? \Illuminate\Support\Str::limit($store->description, 40) : 'Comida & Delivery' }}</span>
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

        {{-- 13. BANNER ESPECIAL TAXI SEGURO (MOBILITY SHOWCASE) --}}
        <section class="lz-feature-taxi-section mb-5">
            <div class="lz-taxi-showcase-box">
                <div class="lz-taxi-content">
                    <span class="lz-taxi-pill"><i class="las la-taxi"></i> LiztoGo Taxi · Movilidad Segura</span>
                    <h3 class="lz-taxi-title">Viaja cómodo y seguro por toda la ciudad de Tarapoto</h3>
                    <p class="lz-taxi-desc">
                        Conecta con conductores verificados, autos y mototaxis autorizados. Conoce tu precio antes de subir y viaja sin sorpresas ni sobrecostos.
                    </p>
                    <div class="lz-taxi-perks">
                        <div class="lz-perk-item">
                            <i class="las la-check-circle"></i>
                            <span>Tarifas justas y transparentes</span>
                        </div>
                        <div class="lz-perk-item">
                            <i class="las la-id-card"></i>
                            <span>Conductores 100% identificados</span>
                        </div>
                        <div class="lz-perk-item">
                            <i class="las la-map-marked-alt"></i>
                            <span>Cobertura en Tarapoto, Morales y La Banda</span>
                        </div>
                    </div>
                    <a href="{{ route('taxi') }}" class="lz-btn-cta lz-taxi-cta-btn">
                        <i class="las la-taxi"></i>
                        <span>Solicitar Taxi Seguro Ahora</span>
                    </a>
                </div>
                <div class="lz-taxi-icon-art d-none d-md-flex">
                    <i class="las la-car-side"></i>
                </div>
            </div>
        </section>

        {{-- 14. B2B & APPS DOWNLOAD SECTION --}}
        <section class="lz-apps-business-section mb-5">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="lz-b2b-card lz-b2b-merchant">
                        <div class="lz-b2b-icon">
                            <i class="las la-store-alt"></i>
                        </div>
                        <div class="lz-b2b-body">
                            <span class="lz-mini-badge" style="background:rgba(16,185,129,0.15);color:#059669;">Para Negocios</span>
                            <h4 class="lz-b2b-title">¿Tienes un restaurante o tienda?</h4>
                            <p class="lz-b2b-text">Vende a miles de clientes en Tarapoto con nuestra plataforma de pedidos y POS integrado.</p>
                            <a href="{{ route('seller.login') }}" class="lz-b2b-link">
                                <span>Vender en Lizto</span>
                                <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="lz-b2b-card lz-b2b-driver">
                        <div class="lz-b2b-icon">
                            <i class="las la-motorcycle"></i>
                        </div>
                        <div class="lz-b2b-body">
                            <span class="lz-mini-badge" style="background:rgba(245,158,11,0.15);color:#d97706;">Gana Dinero</span>
                            <h4 class="lz-b2b-title">¿Quieres conducir o repartir?</h4>
                            <p class="lz-b2b-text">Genera ingresos diarios con tu moto o auto con horarios flexibles y pagos garantizados.</p>
                            <a href="{{ route('taxi') }}" class="lz-b2b-link">
                                <span>Únete a Lizto Conductor</span>
                                <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
</main>

{{-- MODAL INTERACTIVO DE PRODUCTO / AGREGAR AL CARRITO --}}
<div class="modal fade lz-modal" id="lzProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content lz-modal-content">
            <div class="modal-header lz-modal-header border-0 pb-0">
                <button type="button" class="btn-close lz-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body lz-modal-body pt-1">
                <div class="lz-modal-img-wrapper mb-3" id="lzModalImgWrapper">
                    <img id="lzModalProductImg" src="" alt="Producto" class="lz-modal-product-img">
                    <div id="lzModalImgPlaceholder" class="lz-modal-placeholder-box d-none">
                        <i class="las la-utensils"></i>
                    </div>
                </div>

                <div class="mb-2">
                    <a href="#" id="lzModalStoreLink" class="lz-modal-store-badge">
                        <i class="las la-store"></i> <span id="lzModalStoreName">Tienda</span>
                    </a>
                </div>

                <h3 class="lz-modal-title" id="lzModalProductName">Nombre del Producto</h3>
                <p class="lz-modal-desc" id="lzModalProductDesc">Descripción del producto</p>

                <div class="lz-modal-price-box mb-3">
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="lz-modal-final-price" id="lzModalPrice">S/ 0.00</span>
                        <span class="lz-modal-old-price d-none" id="lzModalOldPrice">S/ 0.00</span>
                    </div>
                    <span class="lz-modal-save-pill d-none" id="lzModalSavePill">Ahorras S/ 0.00</span>
                </div>

                <div class="mb-3">
                    <label class="lz-modal-label">Instrucciones o notas para el negocio:</label>
                    <input type="text" id="lzModalNotes" class="lz-modal-input" placeholder="Ej: Sin mayonesa, salsas aparte, etc.">
                </div>

                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                    <div class="lz-qty-stepper">
                        <button type="button" class="lz-qty-btn" onclick="updateModalQty(-1)"><i class="las la-minus"></i></button>
                        <span class="lz-qty-val" id="lzModalQty">1</span>
                        <button type="button" class="lz-qty-btn" onclick="updateModalQty(1)"><i class="las la-plus"></i></button>
                    </div>

                    <button type="button" class="lz-btn-cta lz-modal-add-btn" id="lzModalAddBtn" onclick="submitAddToCart()">
                        <span>Agregar</span> • <span id="lzModalBtnTotal">S/ 0.00</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- FLOATING CART BAR (IF CART HAS ITEMS) --}}
<div id="lzFloatingCartBar" class="lz-floating-cart-bar d-none">
    <div class="lz-floating-cart-inner container">
        <div class="d-flex align-items-center gap-3">
            <div class="lz-floating-cart-icon">
                <i class="las la-shopping-bag"></i>
                <span class="lz-floating-cart-badge" id="lzFloatingCartCount">0</span>
            </div>
            <div>
                <span class="lz-floating-cart-title">Tu Pedido</span>
                <span class="lz-floating-cart-sub" id="lzFloatingCartStore">Productos listos</span>
            </div>
        </div>
        <button type="button" onclick="openCartDrawer()" class="lz-floating-cart-btn" style="border:none; cursor:pointer;">
            <span>Ver Carrito</span>
            <i class="las la-arrow-right"></i>
        </button>
    </div>
</div>

{{-- TOAST NOTIFICATION CONTAINER --}}
<div id="lzToastContainer" class="lz-toast-container"></div>

@endsection

@push('style')
<style>
.lz-marketplace-page {
    padding: 16px 0 60px;
    background-color: var(--lz-bg);
    overflow-x: hidden;
}

/* Typography & Superapp Hero */
.lz-hero-banner {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.07) 0%, rgba(255, 255, 255, 0.98) 50%, rgba(59, 130, 246, 0.05) 100%);
    border: 1.5px solid rgba(16, 185, 129, 0.18);
    border-radius: 20px;
    padding: 26px 22px 22px;
    margin-bottom: 18px;
    box-shadow: 0 6px 24px -4px rgba(0, 0, 0, 0.04);
}
.lz-hero-pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.28);
    color: #047857;
    font-size: 11.5px;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 9999px;
    margin-bottom: 10px;
}
.lz-pulse-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulseGreen 1.8s infinite;
}
@keyframes pulseGreen {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
.lz-hero-main-title {
    font-size: clamp(22px, 3.4vw, 34px);
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 8px;
    letter-spacing: -0.6px;
    line-height: 1.18;
}
.lz-hero-city-highlight {
    background: linear-gradient(135deg, #059669 0%, #10b981 60%, #0284c7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.lz-hero-main-sub {
    font-size: 14.5px;
    color: #64748b;
    margin: 0 0 16px;
    max-width: 680px;
    line-height: 1.45;
}
.lz-hero-value-props {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 12px;
    align-items: center;
}
.lz-prop-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 5px 11px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

/* Universal Search */
.lz-hero-search-wrapper {
    max-width: 100%;
    position: relative;
}
.lz-search-clear {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--lz-text-subtle);
    font-size: 20px;
    display: none;
    cursor: pointer;
    transition: var(--lz-transition);
}
.lz-search-clear.visible {
    display: block;
}
.lz-search-clear:hover {
    color: var(--lz-danger);
}

/* Enhanced 4 Superapp Hub Cards */
.lz-services-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 28px;
}
@media (min-width: 992px) {
    .lz-services-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
}
.lz-service-card {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px 15px;
    position: relative;
    text-decoration: none !important;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    overflow: hidden;
    min-width: 0;
}
.lz-service-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px -4px rgba(0,0,0,0.08);
    border-color: var(--lz-primary);
}
.lz-svc-food { border-bottom: 3.5px solid #f97316; }
.lz-svc-stores { border-bottom: 3.5px solid #10b981; }
.lz-svc-favor { border-bottom: 3.5px solid #8b5cf6; }
.lz-svc-taxi { border-bottom: 3.5px solid #f59e0b; }

.lz-svc-badge {
    font-size: 10px;
    font-weight: 800;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #ffedd5;
    padding: 2px 8px;
    border-radius: 9999px;
    align-self: flex-start;
    margin-bottom: 12px;
}
.lz-badge-green { background: #ecfdf5; color: #047857; border-color: #d1fae5; }
.lz-badge-purple { background: #f5f3ff; color: #6d28d9; border-color: #ede9fe; }
.lz-badge-amber { background: #fffbeb; color: #b45309; border-color: #fef3c7; }

.lz-service-icon {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 12px;
    transition: transform 0.2s ease;
}
.lz-svc-food .lz-service-icon { background: linear-gradient(135deg, #ffedd5, #fed7aa); color: #ea580c; }
.lz-svc-stores .lz-service-icon { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
.lz-svc-favor .lz-service-icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed; }
.lz-svc-taxi .lz-service-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
.lz-service-card:hover .lz-service-icon {
    transform: scale(1.1);
}
.lz-service-title {
    font-size: 15.5px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lz-service-sub {
    font-size: 11.5px;
    color: #64748b;
    line-height: 1.35;
    margin-bottom: 12px;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lz-svc-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 700;
    color: var(--lz-primary-dark);
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
    transition: gap 0.2s;
}
.lz-svc-action i {
    transition: transform 0.2s;
}
.lz-service-card:hover .lz-svc-action i {
    transform: translateX(4px);
}

/* Craving Bubbles */
.lz-cravings-section {
    position: relative;
}
.lz-cravings-scroll {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    scrollbar-width: none;
    padding: 6px 2px 14px;
    scroll-behavior: smooth;
}
.lz-cravings-scroll::-webkit-scrollbar {
    display: none;
}
.lz-craving-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 7px;
    text-decoration: none !important;
    flex-shrink: 0;
    width: 68px;
    transition: transform 0.2s;
}
.lz-craving-item:hover {
    transform: translateY(-3px);
}
.lz-craving-avatar {
    width: 54px;
    height: 54px;
    border-radius: 17px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
}
.lz-craving-label {
    font-size: 11px;
    font-weight: 700;
    color: #334155;
    text-align: center;
    white-space: nowrap;
}

/* Curated Promos */
.lz-curated-promos {
    position: relative;
}
.lz-promo-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
}
@media (min-width: 768px) {
    .lz-promo-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
.lz-promo-card {
    border-radius: 18px;
    padding: 22px 20px;
    position: relative;
    overflow: hidden;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
    min-height: 150px;
}
.lz-promo-welcome { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; }
.lz-promo-favor { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; }
.lz-promo-food { background: linear-gradient(135deg, #ea580c 0%, #f97316 100%); color: #ffffff; }

.lz-promo-content {
    position: relative;
    z-index: 2;
    max-width: 80%;
}
.lz-promo-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    background: rgba(255,255,255,0.22);
    color: #fff;
    padding: 3px 8px;
    border-radius: 9999px;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
}
.lz-promo-title {
    font-size: 15.5px;
    font-weight: 800;
    line-height: 1.25;
    margin-bottom: 6px;
    color: #ffffff;
}
.lz-promo-desc {
    font-size: 11px;
    opacity: 0.92;
    margin-bottom: 12px;
    line-height: 1.35;
    color: #ffffff;
}
.lz-promo-action {
    display: flex;
    align-items: center;
    gap: 8px;
}
.lz-promo-code {
    font-family: monospace;
    font-size: 11.5px;
    font-weight: 800;
    background: rgba(0,0,0,0.25);
    padding: 4px 9px;
    border-radius: 6px;
    letter-spacing: 0.5px;
    color: #ffffff;
}
.lz-promo-btn, .lz-promo-btn-link {
    background: #ffffff;
    color: #0f172a !important;
    font-size: 11.5px;
    font-weight: 800;
    border: none;
    padding: 6px 14px;
    border-radius: 9999px;
    cursor: pointer;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: transform 0.15s;
}
.lz-promo-btn:hover, .lz-promo-btn-link:hover {
    transform: scale(1.04);
}
.lz-promo-visual {
    font-size: 68px;
    opacity: 0.18;
    position: absolute;
    right: 10px;
    bottom: 6px;
    pointer-events: none;
    color: #ffffff;
}

/* Feature Showcase Lizto Favor */
.lz-favor-showcase-box {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    border-radius: 20px;
    padding: 32px 26px;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    box-shadow: 0 10px 30px -5px rgba(49, 46, 129, 0.3);
}
.lz-favor-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(139, 92, 246, 0.25);
    border: 1px solid rgba(139, 92, 246, 0.4);
    color: #c4b5fd;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 9999px;
    margin-bottom: 12px;
}
.lz-favor-title {
    font-size: clamp(19px, 2.4vw, 25px);
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 10px;
    line-height: 1.25;
}
.lz-favor-desc {
    font-size: 13.5px;
    color: #cbd5e1;
    line-height: 1.5;
    margin-bottom: 20px;
    max-width: 580px;
}
.lz-favor-steps {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 22px;
}
.lz-fstep {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.08);
    padding: 8px 14px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
}
.lz-fstep-num {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #8b5cf6;
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lz-fstep strong {
    display: block;
    font-size: 12px;
    color: #ffffff;
    line-height: 1.2;
}
.lz-fstep span {
    font-size: 10px;
    color: #94a3b8;
}
.lz-favor-cta-btn {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: #ffffff !important;
    font-size: 13.5px;
    font-weight: 800;
    padding: 11px 22px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none !important;
    box-shadow: 0 4px 18px rgba(124, 58, 237, 0.4);
    transition: transform 0.15s;
}
.lz-favor-cta-btn:hover {
    transform: scale(1.04);
}
.lz-favor-right {
    position: relative;
    width: 220px;
    height: 180px;
    align-items: center;
    justify-content: center;
}
.lz-favor-illustration {
    font-size: 96px;
    color: rgba(255,255,255,0.15);
}
.lz-favor-badge-float {
    position: absolute;
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.3);
}
.lz-favor-badge-float.top-badge { top: 0; right: 0; }
.lz-favor-badge-float.bottom-badge { bottom: 0; left: 0; }

/* Feature Showcase Taxi Seguro */
.lz-taxi-showcase-box {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1.5px solid #fde68a;
    border-radius: 20px;
    padding: 30px 26px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    box-shadow: 0 4px 20px rgba(245, 158, 11, 0.08);
}
.lz-taxi-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fef08a;
    border: 1px solid #facc15;
    color: #854d0e;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 9999px;
    margin-bottom: 12px;
}
.lz-taxi-title {
    font-size: clamp(19px, 2.4vw, 25px);
    font-weight: 800;
    color: #78350f;
    margin-bottom: 10px;
    line-height: 1.25;
}
.lz-taxi-desc {
    font-size: 13.5px;
    color: #92400e;
    line-height: 1.5;
    margin-bottom: 18px;
    max-width: 580px;
}
.lz-taxi-perks {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}
.lz-perk-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #78350f;
}
.lz-perk-item i {
    color: #059669;
    font-size: 16px;
}
.lz-taxi-cta-btn {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #ffffff !important;
    font-size: 13.5px;
    font-weight: 800;
    padding: 11px 22px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none !important;
    box-shadow: 0 4px 18px rgba(217, 119, 6, 0.35);
    transition: transform 0.15s;
}
.lz-taxi-cta-btn:hover {
    transform: scale(1.04);
}
.lz-taxi-icon-art {
    font-size: 100px;
    color: #fbbf24;
    opacity: 0.55;
}

/* B2B / Driver Section */
.lz-b2b-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    padding: 22px 20px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    height: 100%;
}
.lz-b2b-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: var(--lz-primary);
}
.lz-b2b-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #334155;
    flex-shrink: 0;
}
.lz-b2b-title {
    font-size: 15.5px;
    font-weight: 800;
    color: #0f172a;
    margin: 6px 0 4px;
}
.lz-b2b-text {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 12px;
    line-height: 1.4;
}
.lz-b2b-link {
    font-size: 13px;
    font-weight: 800;
    color: var(--lz-primary-dark);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none !important;
}
.lz-section-sub-tag {
    font-size: 12.5px;
    color: var(--lz-text-muted);
    font-weight: 500;
    margin-left: 10px;
}

/* Horizontal Chip Scroller With Nav Buttons & Edge Protection */
.lz-chips-container-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    margin-bottom: 8px;
}
.lz-chips-scroll {
    display: flex;
    align-items: center;
    gap: 10px;
    overflow-x: auto;
    padding: 6px 4px 14px;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    width: 100%;
}
.lz-chips-scroll::-webkit-scrollbar {
    display: none;
}
.lz-scroll-nav-btn {
    display: none;
    position: absolute;
    top: 50%;
    transform: translateY(-60%);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #ffffff;
    border: 1.5px solid var(--lz-border);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    color: var(--lz-text);
    z-index: 10;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.lz-scroll-nav-btn:hover {
    background: var(--lz-primary-light);
    color: var(--lz-primary-dark);
    border-color: var(--lz-primary);
}
.lz-scroll-nav-btn.prev {
    left: -12px;
}
.lz-scroll-nav-btn.next {
    right: -12px;
}
@media (min-width: 992px) {
    .lz-scroll-nav-btn {
        display: flex;
    }
}

/* Chips Styling - Out of Frame text protection */
.lz-cat-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    background: var(--lz-surface);
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-full);
    white-space: nowrap;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--lz-text);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: pointer;
    box-shadow: var(--lz-shadow-xs);
    flex-shrink: 0;
    text-decoration: none !important;
    user-select: none;
}
.lz-cat-chip span {
    white-space: nowrap;
    overflow: visible;
}
.lz-cat-emoji {
    font-size: 16px;
    line-height: 1;
    display: inline-block;
}
.lz-cat-thumb {
    width: 20px;
    height: 20px;
    object-fit: cover;
    border-radius: 4px;
}
.lz-cat-chip:hover {
    border-color: var(--lz-primary);
    background: var(--lz-primary-light);
    color: var(--lz-primary-dark);
    transform: translateY(-2px);
}
.lz-cat-chip.active {
    background: var(--lz-primary);
    border-color: var(--lz-primary);
    color: #fff;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}

/* Active filter pill */
.lz-filter-active-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--lz-primary-light);
    border: 1px solid var(--lz-primary);
    color: var(--lz-primary-dark);
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: var(--lz-r-full);
}
.lz-filter-active-pill i {
    cursor: pointer;
    font-size: 12px;
}

/* Banners */
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

/* Products Grid & Cards */
.lz-products-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
}
@media (min-width: 640px) {
    .lz-products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}
@media (min-width: 1024px) {
    .lz-products-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }
}
.lz-product-card {
    background: var(--lz-surface);
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-md);
    padding: 12px;
    display: flex;
    gap: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: var(--lz-shadow-xs);
}
.lz-product-card:hover {
    border-color: var(--lz-primary);
    transform: translateY(-3px);
    box-shadow: var(--lz-shadow-md);
}
.lz-product-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
}
.lz-product-store-tag {
    font-size: 11px;
    font-weight: 700;
    color: var(--lz-primary-dark);
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lz-product-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--lz-text);
    margin: 0 0 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.25;
}
.lz-product-desc {
    font-size: 11.5px;
    color: var(--lz-text-muted);
    margin: 0 0 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.3;
}
.lz-product-price-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
    flex-wrap: wrap;
}
.lz-product-price {
    font-size: 14px;
    font-weight: 800;
    color: var(--lz-text);
}
.lz-product-old-price {
    font-size: 11px;
    color: #94a3b8;
    text-decoration: line-through;
}
.lz-product-save-pill {
    font-size: 9.5px;
    font-weight: 700;
    color: #dc2626;
    background: #fee2e2;
    padding: 1px 6px;
    border-radius: var(--lz-r-full);
}
.lz-product-thumb {
    width: 85px;
    height: 85px;
    border-radius: var(--lz-r-sm);
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
    background: var(--lz-surface-muted);
}
.lz-product-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.lz-product-placeholder-icon {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    font-size: 32px;
}
.lz-product-add-btn {
    position: absolute;
    right: 4px;
    bottom: 4px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--lz-primary);
    color: #fff;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    cursor: pointer;
    transition: transform 0.15s ease;
}
.lz-product-add-btn:hover {
    transform: scale(1.1);
}

/* Store cards styling */
.lz-store-cover-default {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.4);
    font-size: 40px;
}
.lz-store-logo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ecfdf5;
    color: var(--lz-primary);
    font-size: 20px;
}
.lz-live-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #fff;
    display: inline-block;
    animation: pulseLive 1.5s infinite;
}
@keyframes pulseLive {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.8); }
    100% { opacity: 1; transform: scale(1); }
}
.lz-eta-time {
    color: var(--lz-text-subtle);
    font-weight: 400;
    font-size: 11px;
}

/* Secondary pill button */
.lz-btn-pill-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: var(--lz-surface);
    border: 1.5px solid var(--lz-border);
    border-radius: var(--lz-r-full);
    font-size: 13.5px;
    font-weight: 700;
    color: var(--lz-text);
    cursor: pointer;
    transition: var(--lz-transition);
}
.lz-btn-pill-secondary:hover {
    background: var(--lz-surface-muted);
    border-color: var(--lz-text);
}

/* Modal styling */
.lz-modal-content {
    border-radius: var(--lz-r-xl);
    border: 1px solid var(--lz-border);
    box-shadow: var(--lz-shadow-lg);
    overflow: hidden;
}
.lz-modal-close {
    background: #f1f5f9;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: var(--lz-text-muted);
    cursor: pointer;
    position: absolute;
    right: 14px;
    top: 14px;
    z-index: 10;
}
.lz-modal-img-wrapper {
    width: 100%;
    height: 190px;
    border-radius: var(--lz-r-md);
    overflow: hidden;
    background: var(--lz-surface-muted);
}
.lz-modal-product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.lz-modal-placeholder-box {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 52px;
    color: #cbd5e1;
}
.lz-modal-store-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 700;
    color: var(--lz-primary-dark);
    text-decoration: none;
}
.lz-modal-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--lz-text);
    margin: 0 0 4px;
}
.lz-modal-desc {
    font-size: 13px;
    color: var(--lz-text-muted);
    margin: 0 0 12px;
    line-height: 1.4;
}
.lz-modal-price-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: var(--lz-surface-muted);
    border-radius: var(--lz-r-sm);
}
.lz-modal-final-price {
    font-size: 18px;
    font-weight: 800;
    color: var(--lz-primary-dark);
}
.lz-modal-old-price {
    font-size: 13px;
    color: #94a3b8;
    text-decoration: line-through;
}
.lz-modal-save-pill {
    font-size: 11px;
    font-weight: 700;
    color: #dc2626;
    background: #fee2e2;
    padding: 3px 8px;
    border-radius: var(--lz-r-full);
}
.lz-modal-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--lz-text);
    margin-bottom: 4px;
    display: block;
}
.lz-modal-input {
    width: 100%;
    padding: 8px 12px;
    border-radius: var(--lz-r-sm);
    border: 1.5px solid var(--lz-border);
    font-size: 13px;
    color: var(--lz-text);
    outline: none;
}
.lz-modal-input:focus {
    border-color: var(--lz-primary);
}
.lz-qty-stepper {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--lz-surface-muted);
    padding: 4px;
    border-radius: var(--lz-r-full);
}
.lz-qty-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fff;
    border: 1px solid var(--lz-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    cursor: pointer;
    color: var(--lz-text);
}
.lz-qty-val {
    font-size: 14px;
    font-weight: 800;
    min-width: 24px;
    text-align: center;
}
.lz-modal-add-btn {
    padding: 10px 22px;
    font-size: 14px;
    font-weight: 700;
}

/* Floating Bottom Cart Bar */
.lz-floating-cart-bar {
    position: fixed;
    bottom: 20px;
    left: 0;
    right: 0;
    z-index: 1000;
    padding: 0 16px;
    animation: slideUpFloat 0.3s ease;
}
@keyframes slideUpFloat {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.lz-floating-cart-inner {
    max-width: 540px;
    margin: 0 auto;
    background: var(--lz-surface-dark);
    color: #fff;
    padding: 12px 18px;
    border-radius: var(--lz-r-full);
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 10px 25px rgba(0,0,0,0.35);
}
.lz-floating-cart-icon {
    position: relative;
    font-size: 24px;
    color: var(--lz-primary);
}
.lz-floating-cart-badge {
    position: absolute;
    top: -4px;
    right: -8px;
    background: var(--lz-danger);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.lz-floating-cart-title {
    font-size: 13.5px;
    font-weight: 800;
    display: block;
    line-height: 1.2;
}
.lz-floating-cart-sub {
    font-size: 11px;
    color: #94a3b8;
}
.lz-floating-cart-btn {
    background: var(--lz-primary);
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    padding: 8px 16px;
    border-radius: var(--lz-r-full);
    text-decoration: none !important;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: transform 0.15s ease;
}
.lz-floating-cart-btn:hover {
    color: #fff;
    transform: scale(1.04);
}

/* Custom Toast System */
.lz-toast-container {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}
.lz-toast {
    background: #0f172a;
    color: #ffffff;
    padding: 12px 18px;
    border-radius: var(--lz-r-md);
    box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13.5px;
    font-weight: 600;
    pointer-events: auto;
    animation: toastIn 0.3s ease;
    max-width: 360px;
    border-left: 4px solid var(--lz-primary);
}
.lz-toast.toast-success {
    border-left-color: var(--lz-primary);
}
.lz-toast.toast-coupon {
    border-left-color: #f59e0b;
}
@keyframes toastIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes toastOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>
@endpush

@push('script')
<script>
// State Management
let currentCategory = '{{ request("category") ?? "" }}';
let currentSubcategory = '{{ request("subcategory") ?? "" }}';
let currentSearch = '{{ request("q") ?? "" }}';

let selectedProductData = null;
let currentModalQty = 1;

window.addEventListener('DOMContentLoaded', function () {
    // Initial UI Setup
    updateSubcategoriesVisibility();
    filterStoresDOM();

    // Check cart count on load
    fetch('/cart-count')
        .then(r => r.json())
        .then(d => {
            if (d && d.count > 0) {
                updateCartBadgeUI(d.count);
            }
        })
        .catch(() => {});

    // Live search typing listener for both Desktop & Mobile Header
    ['headerUniversalSearchInput', 'mobileHeaderSearchInput'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            let searchTimeout = null;
            input.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const val = this.value;
                const otherId = id === 'headerUniversalSearchInput' ? 'mobileHeaderSearchInput' : 'headerUniversalSearchInput';
                const otherInput = document.getElementById(otherId);
                if (otherInput && otherInput.value !== val) otherInput.value = val;

                searchTimeout = setTimeout(() => {
                    currentSearch = val.trim().toLowerCase();
                    filterStoresDOM();
                }, 200);
            });
        }
    });

    // Refresh store delivery fee estimates with saved location
    fetch('/location/get', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data && data.lat && data.lng) {
                refreshFeesWithLocation(data.lat, data.lng);
            }
        })
        .catch(() => {});
});

// Craving Quick Filter
function filterByCraving(term, label) {
    currentCategory = '';
    currentSubcategory = '';
    currentSearch = (term || '').trim().toLowerCase();

    // Reset Category and Subcategory active UI
    document.querySelectorAll('#categoriesScroll .cat-btn').forEach(c => c.classList.remove('active'));
    const allBtn = document.querySelector('#categoriesScroll .cat-btn[data-category-id=""]');
    if (allBtn) allBtn.classList.add('active');
    document.querySelectorAll('#subcategoriesScroll .subcat-chip').forEach(c => c.classList.remove('active'));

    const activeBadge = document.getElementById('activeSubCatBadge');
    if (activeBadge) activeBadge.classList.add('d-none');

    // Sync search input
    ['headerUniversalSearchInput', 'mobileHeaderSearchInput'].forEach(id => {
        const inp = document.getElementById(id);
        if (inp) inp.value = term;
    });

    filterStoresDOM();
    updateURLQuery();

    const btnVerTodas = document.getElementById('btnVerTodasCategorias');
    if (btnVerTodas) btnVerTodas.classList.remove('d-none');

    showToast('Buscando: ' + label, 'success');

    const target = document.getElementById('restaurantes');
    if (target) {
        target.scrollIntoView({ behavior: 'smooth' });
    }
}

// Category Switcher
function selectCategoryDirect(catId, catSlug, catName) {
    currentCategory = catId ? String(catId) : '';
    currentSubcategory = ''; // Reset subcategory when switching main category

    // Update Category Chips Active State
    document.querySelectorAll('#categoriesScroll .cat-btn').forEach(chip => {
        const cId = chip.getAttribute('data-category-id') || '';
        const cSlug = chip.getAttribute('data-category-slug') || '';
        if ((!catId && !cId) || (catId && (cId === catId || cSlug === catSlug || (catId === 'markets' && (cSlug === 'super-mini-markets' || cSlug === 'farmacia'))))) {
            chip.classList.add('active');
            chip.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        } else {
            chip.classList.remove('active');
        }
    });

    // Reset Subcategory Chips
    document.querySelectorAll('#subcategoriesScroll .subcat-chip').forEach(chip => {
        chip.classList.remove('active');
    });

    // Update Subcategory Section Title
    updateSubcategoryHeaderTitle(catSlug, catName);

    // Update Subcategories List
    updateSubcategoriesVisibility();

    // Filter Stores in Grid
    filterStoresDOM();

    // Update URL without full page reload
    updateURLQuery();

    // Toggle "Ver todas" button in header
    const btnVerTodas = document.getElementById('btnVerTodasCategorias');
    if (btnVerTodas) {
        if (currentCategory || currentSubcategory || currentSearch) {
            btnVerTodas.classList.remove('d-none');
        } else {
            btnVerTodas.classList.add('d-none');
        }
    }

    // Show quick feedback toast
    if (catName && catName !== 'Todo') {
        showToast('Categoría: ' + catName, 'success');
    }
}

// Subcategory Switcher
function selectSubcategoryDirect(subCatId, subCatName, parentCatId) {
    if (currentSubcategory === String(subCatId)) {
        // Toggle off if already selected
        currentSubcategory = '';
    } else {
        currentSubcategory = String(subCatId);
        if (parentCatId && !currentCategory) {
            currentCategory = String(parentCatId);
            // Sync category chip
            document.querySelectorAll('#categoriesScroll .cat-btn').forEach(chip => {
                if (chip.getAttribute('data-category-id') === String(parentCatId)) {
                    chip.classList.add('active');
                } else {
                    chip.classList.remove('active');
                }
            });
        }
    }

    // Update Subcategory Chips Active State
    document.querySelectorAll('#subcategoriesScroll .subcat-chip').forEach(chip => {
        if (chip.getAttribute('data-subcategory-id') === currentSubcategory) {
            chip.classList.add('active');
            chip.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        } else {
            chip.classList.remove('active');
        }
    });

    // Update Active Badge
    const activeBadge = document.getElementById('activeSubCatBadge');
    const activeName = document.getElementById('activeSubCatName');
    const btnLimpiar = document.getElementById('btnLimpiarSubcat');
    if (currentSubcategory && activeBadge && activeName) {
        activeName.textContent = subCatName;
        activeBadge.classList.remove('d-none');
        if (btnLimpiar) btnLimpiar.classList.remove('d-none');
        showToast('Filtro: ' + subCatName, 'success');
    } else if (activeBadge) {
        activeBadge.classList.add('d-none');
        if (btnLimpiar) btnLimpiar.classList.add('d-none');
    }

    // Filter Stores in Grid
    filterStoresDOM();

    // Update URL
    updateURLQuery();
}

function clearSubcategoryFilter(e) {
    if (e) e.stopPropagation();
    currentSubcategory = '';
    document.querySelectorAll('#subcategoriesScroll .subcat-chip').forEach(chip => chip.classList.remove('active'));
    const activeBadge = document.getElementById('activeSubCatBadge');
    const btnLimpiar = document.getElementById('btnLimpiarSubcat');
    if (activeBadge) activeBadge.classList.add('d-none');
    if (btnLimpiar) btnLimpiar.classList.add('d-none');
    filterStoresDOM();
    updateURLQuery();
}

function clearAllFilters(e) {
    if (e) e.preventDefault();
    currentCategory = '';
    currentSubcategory = '';
    currentSearch = '';
    const searchInput = document.getElementById('universalSearchInput');
    if (searchInput) searchInput.value = '';
    const clearBtn = document.getElementById('searchClearBtn');
    if (clearBtn) clearBtn.classList.remove('visible');
    selectCategoryDirect('', '', 'Todo');
}

function updateSubcategoryHeaderTitle(catSlug, catName) {
    const titleEl = document.getElementById('subCategoryHeaderTitle');
    if (!titleEl) return;

    if (!catSlug || catSlug === 'todo' || !catName || catName === 'Todo') {
        titleEl.innerHTML = '✨ ¿Qué se te antoja hoy?';
    } else if (catSlug === 'farmacia') {
        titleEl.innerHTML = '💊 ¿Qué buscas en Farmacia?';
    } else if (catSlug === 'mascotas') {
        titleEl.innerHTML = '🐾 ¿Qué necesita tu mascota?';
    } else if (catSlug === 'licorerias') {
        titleEl.innerHTML = '🍾 ¿Qué quieres brindar hoy?';
    } else if (catSlug === 'super-mini-markets' || catSlug === 'markets') {
        titleEl.innerHTML = '🛒 ¿Qué te hace falta en casa?';
    } else if (catSlug === 'servicio-de-favores') {
        titleEl.innerHTML = '🛵 ¿Qué mandado necesitas?';
    } else {
        titleEl.innerHTML = '✨ ' + catName;
    }
}

function updateSubcategoriesVisibility() {
    const subChips = document.querySelectorAll('#subcategoriesScroll .subcat-chip');
    let visibleCount = 0;

    subChips.forEach(chip => {
        const parentId = chip.getAttribute('data-parent-category');
        const parentSlug = chip.getAttribute('data-parent-slug');

        let shouldShow = false;
        if (!currentCategory) {
            // Show all subcategories if "Todo" is active
            shouldShow = true;
        } else if (currentCategory === 'markets') {
            shouldShow = (parentSlug === 'super-mini-markets' || parentSlug === 'farmacia' || parentId === '2' || parentId === '6');
        } else if (currentCategory === parentId || currentCategory === parentSlug) {
            shouldShow = true;
        }

        if (shouldShow) {
            chip.style.display = 'inline-flex';
            visibleCount++;
        } else {
            chip.style.display = 'none';
        }
    });

    const subcatSection = document.getElementById('seccion-subcategorias');
    if (subcatSection) {
        if (visibleCount === 0) {
            subcatSection.style.display = 'none';
        } else {
            subcatSection.style.display = 'block';
        }
    }
}

// Client-side instant store grid filtering
function filterStoresDOM() {
    const storeCards = document.querySelectorAll('#storesGridContainer .store-card-item');
    let visibleCount = 0;

    storeCards.forEach(card => {
        const cats = JSON.parse(card.getAttribute('data-categories') || '[]');
        const slugs = JSON.parse(card.getAttribute('data-slugs') || '[]');
        const subcats = JSON.parse(card.getAttribute('data-subcategories') || '[]');
        const name = card.getAttribute('data-name') || '';
        const desc = card.getAttribute('data-desc') || '';

        let matchCategory = true;
        if (currentCategory) {
            if (currentCategory === 'markets') {
                matchCategory = (slugs.includes('super-mini-markets') || slugs.includes('farmacia') || cats.includes(2) || cats.includes(6));
            } else if (!isNaN(currentCategory)) {
                matchCategory = cats.includes(parseInt(currentCategory));
            } else {
                matchCategory = slugs.includes(currentCategory);
            }
        }

        let matchSubcategory = true;
        if (currentSubcategory) {
            matchSubcategory = subcats.includes(parseInt(currentSubcategory));
        }

        let matchSearch = true;
        if (currentSearch) {
            matchSearch = name.includes(currentSearch) || desc.includes(currentSearch);
        }

        if (matchCategory && matchSubcategory && matchSearch) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Update open counter
    const counterEl = document.getElementById('openStoresCounter');
    if (counterEl) {
        counterEl.textContent = visibleCount + ' ABIERTOS';
    }

    // Empty State Toggle
    const emptyState = document.getElementById('lzNoStoresFound');
    if (emptyState) {
        if (visibleCount === 0) {
            emptyState.classList.remove('d-none');
        } else {
            emptyState.classList.add('d-none');
        }
    }
}

// URL query manager
function updateURLQuery() {
    const params = new URLSearchParams();
    if (currentCategory) params.set('category', currentCategory);
    if (currentSubcategory) params.set('subcategory', currentSubcategory);
    if (currentSearch) params.set('q', currentSearch);

    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState(null, '', newUrl);

    // Sync search form hidden fields
    const searchCatInput = document.getElementById('searchCategoryInput');
    if (searchCatInput) searchCatInput.value = currentCategory;
    const searchSubcatInput = document.getElementById('searchSubcategoryInput');
    if (searchSubcatInput) searchSubcatInput.value = currentSubcategory;
}

function handleSearchSubmit(e) {
    e.preventDefault();
    const searchInput = document.getElementById('universalSearchInput');
    if (searchInput) {
        currentSearch = searchInput.value.trim().toLowerCase();
        filterStoresDOM();
        updateURLQuery();
    }
    return false;
}

// Smooth Horizontal Scroll navigation for Chip rows
function scrollChipRow(elementId, distance) {
    const container = document.getElementById(elementId);
    if (container) {
        container.scrollBy({ left: distance, behavior: 'smooth' });
    }
}

// Product Quick View Modal & Add to Cart
function openProductQuickView(cardElement) {
    if (!cardElement) return;

    selectedProductData = {
        id: cardElement.getAttribute('data-product-id'),
        name: cardElement.getAttribute('data-product-name'),
        desc: cardElement.getAttribute('data-product-desc'),
        price: parseFloat(cardElement.getAttribute('data-product-price')) || 0,
        oldPrice: cardElement.getAttribute('data-product-old-price'),
        saving: cardElement.getAttribute('data-product-saving'),
        image: cardElement.getAttribute('data-product-image'),
        storeId: cardElement.getAttribute('data-store-id'),
        storeName: cardElement.getAttribute('data-store-name'),
        storeUrl: cardElement.getAttribute('data-store-url'),
    };

    currentModalQty = 1;
    document.getElementById('lzModalQty').textContent = '1';

    // Populate Modal
    document.getElementById('lzModalProductName').textContent = selectedProductData.name;
    document.getElementById('lzModalProductDesc').textContent = selectedProductData.desc || 'Producto fresco y garantizado por Lizto.';
    document.getElementById('lzModalStoreName').textContent = selectedProductData.storeName;
    document.getElementById('lzModalStoreLink').setAttribute('href', selectedProductData.storeUrl || '#');

    const imgEl = document.getElementById('lzModalProductImg');
    const placeholderEl = document.getElementById('lzModalImgPlaceholder');
    if (selectedProductData.image) {
        imgEl.src = selectedProductData.image;
        imgEl.classList.remove('d-none');
        placeholderEl.classList.add('d-none');
    } else {
        imgEl.classList.add('d-none');
        placeholderEl.classList.remove('d-none');
    }

    const oldPriceEl = document.getElementById('lzModalOldPrice');
    const savePillEl = document.getElementById('lzModalSavePill');
    if (selectedProductData.oldPrice && parseFloat(selectedProductData.oldPrice) > selectedProductData.price) {
        oldPriceEl.textContent = 'S/ ' + parseFloat(selectedProductData.oldPrice).toFixed(2);
        oldPriceEl.classList.remove('d-none');
        savePillEl.textContent = 'Ahorras S/ ' + selectedProductData.saving;
        savePillEl.classList.remove('d-none');
    } else {
        oldPriceEl.classList.add('d-none');
        savePillEl.classList.add('d-none');
    }

    document.getElementById('lzModalNotes').value = '';
    updateModalPriceCalculation();

    // Show modal using Bootstrap
    const modalEl = document.getElementById('lzProductModal');
    if (window.bootstrap && bootstrap.Modal) {
        const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalObj.show();
    } else if (window.jQuery) {
        jQuery(modalEl).modal('show');
    }
}

function updateModalQty(delta) {
    currentModalQty = Math.max(1, currentModalQty + delta);
    document.getElementById('lzModalQty').textContent = currentModalQty;
    updateModalPriceCalculation();
}

function updateModalPriceCalculation() {
    if (!selectedProductData) return;
    const total = selectedProductData.price * currentModalQty;
    document.getElementById('lzModalPrice').textContent = 'S/ ' + selectedProductData.price.toFixed(2);
    document.getElementById('lzModalBtnTotal').textContent = 'S/ ' + total.toFixed(2);
}

function submitAddToCart() {
    if (!selectedProductData) return;

    const notes = document.getElementById('lzModalNotes').value.trim();
    const btn = document.getElementById('lzModalAddBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="las la-spinner la-spin"></i> Agregando...';

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    const formData = new FormData();
    formData.append('product_id', selectedProductData.id);
    formData.append('name', selectedProductData.name);
    formData.append('price', selectedProductData.price);
    formData.append('quantity', currentModalQty);
    formData.append('notes', notes);
    if (csrfToken) formData.append('_token', csrfToken);

    fetch('/cart/' + selectedProductData.storeId + '/add', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;

        // Close modal
        const modalEl = document.getElementById('lzProductModal');
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        } else if (window.jQuery) {
            jQuery(modalEl).modal('hide');
        }

        // Update Cart Header Badge and Floating Bar
        const newCount = (data && data.total_items !== undefined) ? data.total_items : (parseInt(document.getElementById('hdr-cart-badge')?.textContent || '0') + currentModalQty);
        if (typeof updateHeaderCartBadge === 'function') {
            updateHeaderCartBadge(newCount);
        }
        updateCartBadgeUI(newCount, selectedProductData.storeName);

        // Toast Feedback with action to open cart
        showToast('🛒 ¡' + selectedProductData.name + ' (' + currentModalQty + ') añadido al carrito!', 'success', true);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        const fallbackCount = parseInt(document.getElementById('hdr-cart-badge')?.textContent || '0') + currentModalQty;
        if (typeof updateHeaderCartBadge === 'function') {
            updateHeaderCartBadge(fallbackCount);
        }
        updateCartBadgeUI(fallbackCount, selectedProductData.storeName);
        showToast('🛒 ¡' + selectedProductData.name + ' agregado!', 'success', true);
        const modalEl = document.getElementById('lzProductModal');
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getInstance(modalEl)?.hide();
        }
    });
}

function updateCartBadgeUI(count, storeName) {
    const hdrBadge = document.getElementById('hdr-cart-badge');
    const hdrIcon = document.getElementById('header-cart-icon');
    if (hdrBadge) hdrBadge.textContent = count;
    if (hdrIcon && count > 0) hdrIcon.style.display = 'inline-flex';

    const floatBar = document.getElementById('lzFloatingCartBar');
    const floatCount = document.getElementById('lzFloatingCartCount');
    const floatStore = document.getElementById('lzFloatingCartStore');
    if (floatBar && count > 0) {
        floatBar.classList.remove('d-none');
        if (floatCount) floatCount.textContent = count;
        if (floatStore && storeName) floatStore.textContent = 'En ' + storeName;
    }
}

// Copy Coupon Code with Feedback
function copyCouponCode(code, element) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code).then(function() {
            const originalHTML = element.innerHTML;
            element.innerHTML = '<i class="las la-check"></i> ¡Copiado!';
            element.style.color = 'var(--lz-primary-dark)';
            showToast('🎁 Cupón "' + code + '" copiado al portapapeles. ¡Úsalo al pagar!', 'coupon');
            setTimeout(function() {
                element.innerHTML = originalHTML;
                element.style.color = '';
            }, 2500);
        });
    }
}

// Floating Toast Feedback System
function showToast(message, type, withCartAction) {
    const container = document.getElementById('lzToastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'lz-toast toast-' + (type || 'success');
    
    let actionBtnHtml = '';
    if (withCartAction) {
        actionBtnHtml = '<a href="javascript:void(0)" onclick="openCartDrawer()" style="color:#10b981;font-weight:800;text-decoration:underline;margin-left:8px;white-space:nowrap">Ver Carrito ➔</a>';
    }

    toast.innerHTML = '<span>' + message + '</span>' + actionBtnHtml;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toastOut 0.3s forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Store Fee Live Calculation
function refreshFeesWithLocation(lat, lng) {
    if (!lat || !lng) return;
    document.querySelectorAll('.store-card-item').forEach(function(card) {
        const storeId = card.getAttribute('data-store-id');
        const feeEl = card.querySelector('.store-fee-amount');
        const distEl = card.querySelector('.store-distance');
        if (!storeId || !feeEl) return;
        fetch('/delivery/store-fee-estimate?store_id=' + storeId + '&delivery_lat=' + lat + '&delivery_lng=' + lng)
            .then(r => r.json())
            .then(d => {
                if (d && d.delivery_fee != null) {
                    feeEl.textContent = d.delivery_fee.toFixed(2);
                    if (distEl && d.distance_km != null) distEl.textContent = '· ' + d.distance_km.toFixed(1) + ' km';
                }
            })
            .catch(() => {});
    });
}
</script>
@endpush
