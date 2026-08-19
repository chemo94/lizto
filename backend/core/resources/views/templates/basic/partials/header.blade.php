@php
    $languages = App\Models\Language::get();
    $selectLang = $languages->where('code', config('app.locale'))->first();
    $isDeliveryRoute = request()->routeIs('delivery.marketplace', 'delivery.store');
    $isLandingRoute = request()->routeIs('home');
    $showLocation = true;
@endphp

<header class="lz-header" id="lz-header">
    {{-- TOP NAVBAR --}}
    <div class="lz-header-top">
        {{-- Left: Brand Logo + Location --}}
        <div class="lz-header-left">
            {{-- Brand / Configured Site Logo Only --}}
            <a href="{{ route('delivery.marketplace') }}" class="lz-brand" title="{{ gs('site_name') }}">
                <img src="{{ siteLogo('dark') }}" alt="{{ gs('site_name') }}" class="lz-brand-logo-img">
            </a>

            <div class="lz-header-divider d-none d-md-block"></div>

            {{-- Location Selector --}}
            <div class="lz-location-chip" onclick="openLocationModal()" title="Cambiar dirección de entrega" role="button" tabindex="0">
                <i class="las la-map-marker-alt lz-loc-pin"></i>
                <div class="lz-loc-info">
                    <span class="lz-loc-label d-none d-xl-block">Entregar en</span>
                    <span class="lz-loc-address" id="header-location">Tarapoto, San Martín</span>
                </div>
                <i class="las la-angle-down lz-loc-arrow"></i>
            </div>
        </div>

        {{-- Center: Universal Search Bar (Desktop: min-width: 992px) --}}
        <div class="lz-header-search d-none d-lg-flex">
            <form action="{{ route('delivery.marketplace') }}" method="GET" id="headerSearchForm" class="lz-search-box" onsubmit="return handleHeaderSearchSubmit(event)">
                <span class="lz-search-brand-icon"><i class="las la-bolt"></i></span>
                <input type="text" name="q" id="headerUniversalSearchInput" class="lz-search-input" value="{{ request('q') }}" placeholder="Comida, restaurantes, tiendas, productos..." autocomplete="off">
                <button type="submit" class="lz-search-submit-btn" aria-label="Buscar">
                    <i class="las la-search"></i>
                </button>
            </form>
        </div>

        {{-- Right: User Auth + Cart Actions --}}
        <div class="lz-header-actions">
            {{-- User Auth Trigger / Profile --}}
            <div id="header-auth-section">
                {{-- Logged Out State --}}
                <div id="header-logged-out" style="display:flex;align-items:center;gap:6px">
                    <button type="button" class="lz-user-btn" onclick="showLoginModal()">
                        <i class="las la-user-circle"></i>
                        <span class="d-none d-sm-inline">Ingreso</span>
                    </button>
                </div>

                {{-- Logged In State --}}
                <div id="header-logged-in" style="display:none;position:relative;">
                    <div class="hdr-user">
                        <button type="button" class="lz-user-btn" id="hdr-user-btn" onclick="toggleUserDropdown(event)">
                            <span class="lz-user-avatar" id="hdr-user-avatar">
                                <i class="las la-user"></i>
                            </span>
                            <span class="d-none d-sm-inline" id="hdr-user-name">Mi Cuenta</span>
                            <i class="las la-angle-down" style="font-size:12px;color:var(--lz-text-subtle)"></i>
                        </button>

                        <div class="hdr-user-menu" id="hdr-user-menu">
                            <div class="hdr-menu-header">
                                <div class="hdr-menu-name" id="hdr-menu-name"></div>
                                <div class="hdr-menu-email" id="hdr-menu-email"></div>
                            </div>
                            <a href="#" onclick="goToDashboard()"><i class="las la-receipt"></i> Mis Pedidos</a>
                            <a href="#" onclick="goToProfile()"><i class="las la-user-circle"></i> Mi Perfil</a>
                            <a href="#" onclick="goToWallet()"><i class="las la-wallet"></i> Billetera</a>
                            <a href="#" id="hdr-menu-driver" style="display:none;" onclick="goToDriverDocs()"><i class="las la-id-card"></i> Panel Conductor</a>
                            <div class="hdr-menu-divider"></div>
                            <a href="{{ route('seller.login') }}" target="_blank"><i class="las la-store-alt"></i> Panel de Negocios</a>
                            <a href="#" onclick="handleLogout()" class="hdr-logout-link"><i class="las la-sign-out-alt"></i> Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cart Button (Always visible / accessible) --}}
            <button type="button" class="lz-action-btn lz-cart-btn" id="header-cart-icon" onclick="openCartDrawer()" title="Ver carrito" aria-label="Carrito de compras">
                <i class="las la-shopping-cart"></i>
                <span class="lz-badge-count" id="hdr-cart-badge" style="display:none;">0</span>
            </button>
        </div>
    </div>

    {{-- Mobile Search Row (Clean full width bar on phones & tablets) --}}
    <div class="lz-mobile-search-row d-lg-none">
        <form action="{{ route('delivery.marketplace') }}" method="GET" class="lz-search-box" onsubmit="return handleHeaderSearchSubmit(event)">
            <span class="lz-search-brand-icon"><i class="las la-search"></i></span>
            <input type="text" name="q" id="mobileHeaderSearchInput" class="lz-search-input" value="{{ request('q') }}" placeholder="Comida, restaurantes, tiendas, productos..." autocomplete="off">
        </form>
    </div>

    {{-- SERVICES PILL BAR (DESKTOP) --}}
    <div class="lz-services-bar">
        <a href="{{ route('delivery.marketplace') }}" class="lz-service-pill {{ request()->routeIs('delivery.marketplace', 'delivery.store') && !request('category') ? 'active' : '' }}">
            <i class="las la-utensils"></i> Delivery de Comida
        </a>
        <a href="{{ route('delivery.marketplace', ['category' => 'markets']) }}" class="lz-service-pill {{ request('category') == 'markets' ? 'active' : '' }}">
            <i class="las la-shopping-basket"></i> Mercados & Tiendas
        </a>
        <a href="{{ route('favor') }}" class="lz-service-pill {{ request()->routeIs('favor*') ? 'active' : '' }}">
            <i class="las la-hand-holding-heart"></i> Lizto Favor
        </a>
        <a href="{{ route('taxi') }}" class="lz-service-pill {{ request()->routeIs('taxi*') ? 'active' : '' }}">
            <i class="las la-taxi"></i> Taxi Seguro
        </a>
    </div>
</header>

{{-- GLOBAL REAL CART DRAWER --}}
<div class="lz-cart-drawer-overlay" id="lzCartDrawerOverlay" onclick="closeCartDrawer()"></div>
<aside class="lz-cart-drawer" id="lzCartDrawer" aria-label="Carrito de compras">
    <div class="lz-cart-drawer-header">
        <div class="d-flex align-items-center gap-2">
            <div class="lz-cart-header-icon">
                <i class="las la-shopping-cart"></i>
            </div>
            <div>
                <h3 class="lz-cart-drawer-title">Tu Carrito</h3>
                <span class="lz-cart-drawer-sub" id="lzCartTotalItemsSub">0 productos seleccionados</span>
            </div>
        </div>
        <button type="button" class="lz-cart-drawer-close" onclick="closeCartDrawer()" aria-label="Cerrar">
            <i class="las la-times"></i>
        </button>
    </div>

    <div class="lz-cart-drawer-body" id="lzCartDrawerBody">
        <div class="lz-cart-loading text-center py-5">
            <div class="spinner-border text-success" role="status" style="width: 2rem; height: 2rem;"></div>
            <p class="mt-3 text-muted" style="font-size: 13px; font-weight: 600;">Cargando tus productos...</p>
        </div>
    </div>
</aside>

<style>
/* Responsive Header Styles */
.lz-header {
    position: sticky !important;
    top: 0 !important;
    z-index: 1020 !important;
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    width: 100% !important;
    max-width: 100vw !important;
    overflow-x: hidden !important;
    box-sizing: border-box !important;
}

.lz-header-top {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: space-between !important;
    height: 56px !important;
    padding: 0 12px !important;
    gap: 8px !important;
    max-width: 1400px !important;
    margin: 0 auto !important;
    box-sizing: border-box !important;
    width: 100% !important;
}

@media (min-width: 992px) {
    .lz-header-top {
        height: 66px !important;
        padding: 0 24px !important;
        gap: 16px !important;
    }
}

.lz-header-left {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 8px !important;
    flex: 1 1 auto !important;
    min-width: 0 !important;
    overflow: hidden !important;
}

@media (min-width: 768px) {
    .lz-header-left {
        gap: 12px !important;
        flex: 0 0 auto !important;
    }
}

.lz-brand {
    display: inline-flex !important;
    align-items: center !important;
    text-decoration: none !important;
    flex-shrink: 0 !important;
}

.lz-brand-logo-img {
    height: 32px !important;
    max-width: 135px !important;
    width: auto !important;
    object-fit: contain !important;
    display: block !important;
}

@media (min-width: 992px) {
    .lz-brand-logo-img {
        height: 38px !important;
        max-width: 165px !important;
    }
}

.lz-header-divider {
    width: 1px !important;
    height: 22px !important;
    background: #e2e8f0 !important;
    margin: 0 4px !important;
    flex-shrink: 0 !important;
}

.lz-location-chip {
    display: inline-flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 6px !important;
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 9999px !important;
    padding: 4px 8px !important;
    cursor: pointer !important;
    max-width: 160px !important;
    min-width: 0 !important;
    flex-shrink: 1 !important;
    box-sizing: border-box !important;
}

@media (min-width: 576px) {
    .lz-location-chip {
        max-width: 220px !important;
        padding: 5px 10px !important;
    }
}

@media (min-width: 992px) {
    .lz-location-chip {
        max-width: 260px !important;
        padding: 6px 12px !important;
    }
}

.lz-loc-pin {
    color: #ea580c !important;
    font-size: 16px !important;
    flex-shrink: 0 !important;
}

.lz-loc-info {
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    text-align: left !important;
    line-height: 1.15 !important;
    min-width: 0 !important;
}

.lz-loc-label {
    font-size: 9px !important;
    font-weight: 700 !important;
    color: #94a3b8 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.4px !important;
}

.lz-loc-address {
    font-size: 12px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

@media (min-width: 992px) {
    .lz-loc-address {
        font-size: 13px !important;
    }
}

.lz-loc-arrow {
    color: #ea580c !important;
    font-size: 10px !important;
    flex-shrink: 0 !important;
}

/* Center Desktop Search */
.lz-header-search {
    flex: 1 !important;
    max-width: 560px !important;
    position: relative !important;
    align-items: center !important;
    margin: 0 12px !important;
}

.lz-search-box {
    position: relative !important;
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    height: 40px !important;
}

.lz-search-brand-icon {
    position: absolute !important;
    left: 14px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: #ea580c !important;
    font-size: 15px !important;
    pointer-events: none !important;
    display: flex !important;
    align-items: center !important;
    z-index: 3 !important;
}

.lz-search-input {
    width: 100% !important;
    height: 40px !important;
    padding: 0 38px 0 38px !important;
    border-radius: 9999px !important;
    border: 1.5px solid transparent !important;
    background: #f1f5f9 !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    color: #0f172a !important;
    outline: none !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
}

.lz-search-input:focus {
    background: #ffffff !important;
    border-color: #10b981 !important;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15) !important;
}

.lz-search-submit-btn {
    position: absolute !important;
    right: 6px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 28px !important;
    height: 28px !important;
    border-radius: 50% !important;
    border: none !important;
    background: transparent !important;
    color: #64748b !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 15px !important;
    cursor: pointer !important;
    z-index: 3 !important;
}

/* Mobile Search Row */
.lz-mobile-search-row {
    padding: 0 12px 10px 12px !important;
    background: #ffffff !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.lz-mobile-search-row .lz-search-input {
    background: #f1f5f9 !important;
    border-color: #e2e8f0 !important;
    font-size: 13px !important;
}

/* Right Actions */
.lz-header-actions {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 8px !important;
    flex-shrink: 0 !important;
}

@media (min-width: 992px) {
    .lz-header-actions {
        gap: 12px !important;
    }
}

.lz-user-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    height: 36px !important;
    padding: 0 12px !important;
    border-radius: 9999px !important;
    border: 1.5px solid #e2e8f0 !important;
    background: #ffffff !important;
    color: #0f172a !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    white-space: nowrap !important;
}

@media (min-width: 992px) {
    .lz-user-btn {
        height: 38px !important;
        padding: 0 16px !important;
        font-size: 13.5px !important;
    }
}

.lz-user-btn:hover {
    border-color: #10b981 !important;
    color: #047857 !important;
    background: #ecfdf5 !important;
}

.lz-user-btn i {
    font-size: 18px !important;
}

.lz-cart-btn {
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    border: 1.5px solid #e2e8f0 !important;
    background: #ffffff !important;
    color: #0f172a !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 18px !important;
    position: relative !important;
    cursor: pointer !important;
    flex-shrink: 0 !important;
    transition: all 0.15s ease !important;
}

@media (min-width: 992px) {
    .lz-cart-btn {
        width: 38px !important;
        height: 38px !important;
        font-size: 19px !important;
    }
}

.lz-cart-btn:hover {
    border-color: #10b981 !important;
    color: #10b981 !important;
    background: #ecfdf5 !important;
}

.lz-badge-count {
    position: absolute !important;
    top: -4px !important;
    right: -4px !important;
    min-width: 17px !important;
    height: 17px !important;
    padding: 0 3px !important;
    background: #ea580c !important;
    color: #ffffff !important;
    font-size: 10px !important;
    font-weight: 800 !important;
    border-radius: 9999px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: 2px solid #ffffff !important;
}

/* ==========================================================================
   GLOBAL CART OFFCANVAS / DRAWER
   ========================================================================== */
.lz-cart-drawer-overlay {
    position: fixed !important;
    inset: 0 !important;
    background: rgba(15, 23, 42, 0.5) !important;
    backdrop-filter: blur(4px) !important;
    -webkit-backdrop-filter: blur(4px) !important;
    z-index: 9998 !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transition: opacity 0.25s ease, visibility 0.25s ease !important;
}
.lz-cart-drawer-overlay.open {
    opacity: 1 !important;
    visibility: visible !important;
}

.lz-cart-drawer {
    position: fixed !important;
    top: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100% !important;
    max-width: 440px !important;
    background: #ffffff !important;
    z-index: 9999 !important;
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.2) !important;
    transform: translateX(100%) !important;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    display: flex !important;
    flex-direction: column !important;
}
.lz-cart-drawer.open {
    transform: translateX(0) !important;
}

.lz-cart-drawer-header {
    padding: 16px 20px !important;
    border-bottom: 1px solid #e2e8f0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    background: #ffffff !important;
    flex-shrink: 0 !important;
}
.lz-cart-header-icon {
    width: 38px !important;
    height: 38px !important;
    border-radius: 10px !important;
    background: #ecfdf5 !important;
    color: #10b981 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 20px !important;
}
.lz-cart-drawer-title {
    font-size: 16.5px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    margin: 0 !important;
    line-height: 1.2 !important;
}
.lz-cart-drawer-sub {
    font-size: 11.5px !important;
    font-weight: 600 !important;
    color: #64748b !important;
}
.lz-cart-drawer-close {
    width: 34px !important;
    height: 34px !important;
    border-radius: 50% !important;
    border: none !important;
    background: #f1f5f9 !important;
    color: #64748b !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 18px !important;
    cursor: pointer !important;
    transition: background 0.15s ease !important;
}
.lz-cart-drawer-close:hover {
    background: #e2e8f0 !important;
    color: #0f172a !important;
}

.lz-cart-drawer-body {
    flex: 1 1 auto !important;
    overflow-y: auto !important;
    padding: 16px 20px !important;
    background: #f8fafc !important;
}

/* Empty State */
.lz-cart-empty {
    text-align: center !important;
    padding: 48px 16px !important;
}
.lz-cart-empty-icon {
    width: 72px !important;
    height: 72px !important;
    border-radius: 50% !important;
    background: #f1f5f9 !important;
    color: #94a3b8 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 36px !important;
    margin: 0 auto 16px !important;
}
.lz-cart-empty-title {
    font-size: 17px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    margin-bottom: 6px !important;
}
.lz-cart-empty-text {
    font-size: 13px !important;
    color: #64748b !important;
    margin-bottom: 20px !important;
    line-height: 1.4 !important;
}

/* Active Store Cart Card */
.lz-cart-store-card {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 14px !important;
    padding: 16px !important;
    margin-bottom: 16px !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03) !important;
}
.lz-cart-store-head {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding-bottom: 12px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    margin-bottom: 12px !important;
}
.lz-cart-store-name {
    font-size: 14.5px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}
.lz-cart-clear-btn {
    font-size: 11.5px !important;
    font-weight: 700 !important;
    color: #ef4444 !important;
    border: none !important;
    background: transparent !important;
    cursor: pointer !important;
    padding: 0 !important;
}
.lz-cart-clear-btn:hover {
    text-decoration: underline !important;
}

/* Cart Item Row */
.lz-cart-item-row {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 8px 0 !important;
    border-bottom: 1px dashed #f1f5f9 !important;
}
.lz-cart-item-info {
    flex: 1 1 auto !important;
    overflow: hidden !important;
    padding-right: 12px !important;
}
.lz-cart-item-name {
    font-size: 13.5px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    margin: 0 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}
.lz-cart-item-price {
    font-size: 12px !important;
    color: #64748b !important;
    font-weight: 600 !important;
}

.lz-cart-stepper {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    background: #f1f5f9 !important;
    border-radius: 9999px !important;
    padding: 2px 6px !important;
}
.lz-cart-stepper-btn {
    width: 24px !important;
    height: 24px !important;
    border-radius: 50% !important;
    border: none !important;
    background: #ffffff !important;
    color: #0f172a !important;
    font-size: 11px !important;
    font-weight: 800 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
}
.lz-cart-stepper-qty {
    font-size: 12.5px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    min-width: 18px !important;
    text-align: center !important;
}

.lz-cart-store-summary {
    margin-top: 14px !important;
    padding-top: 12px !important;
    border-top: 1px solid #f1f5f9 !important;
}
.lz-cart-summary-line {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    font-size: 12.5px !important;
    color: #64748b !important;
    margin-bottom: 4px !important;
}
.lz-cart-summary-line.total {
    font-size: 15px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    margin-top: 8px !important;
    padding-top: 6px !important;
    border-top: 1px solid #e2e8f0 !important;
}

.lz-cart-checkout-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    width: 100% !important;
    background: linear-gradient(135deg, #10b981 0%, #047857 100%) !important;
    color: #ffffff !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    padding: 12px 18px !important;
    border-radius: 12px !important;
    text-decoration: none !important;
    margin-top: 14px !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25) !important;
    transition: transform 0.15s ease !important;
}
.lz-cart-checkout-btn:hover {
    color: #ffffff !important;
    transform: translateY(-1px) !important;
}
</style>

@push('script')
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="{{ asset('assets/global/js/firebase/firebase-app.js') }}"></script>
<script src="{{ asset('assets/global/js/firebase/firebase-messaging.js') }}"></script>

<script>
@php
    $socialite = gs('socialite_credentials');
    if (is_string($socialite)) {
        $socialite = json_decode($socialite);
    }
    $googleClientId = '';
    if (is_object($socialite) && isset($socialite->google->client_id) && $socialite->google->client_id !== '------------') {
        $googleClientId = $socialite->google->client_id;
    }
@endphp
const GOOGLE_CLIENT_ID = '{!! $googleClientId !!}';
const DEV_TOKEN = document.getElementById('loginModal')?.getAttribute('data-dev-token') || '';

window.showLoginModal = function() {
    const modalEl = document.getElementById('loginModal');
    if (modalEl) {
        try {
            if (window.jQuery && typeof jQuery.fn.modal === 'function') {
                jQuery(modalEl).modal('show');
            } else if (window.bootstrap && bootstrap.Modal) {
                let loginModal = bootstrap.Modal.getInstance(modalEl);
                if (!loginModal) {
                    loginModal = new bootstrap.Modal(modalEl);
                }
                loginModal.show();
            }
        } catch (e) {
            console.error('Modal toggle error:', e);
        }
    }
};

function toggleUserDropdown(event) {
    event.stopPropagation();
    const menu = document.getElementById('hdr-user-menu');
    if (menu) menu.classList.toggle('open');
}

document.addEventListener('click', function(e) {
    var menu = document.getElementById('hdr-user-menu');
    var btn = document.getElementById('hdr-user-btn');
    if (menu && menu.classList.contains('open') && btn && !btn.contains(e.target)) {
        menu.classList.remove('open');
    }
});

function showNotification(type, message) {
    if (window.notify) {
        window.notify(type, message);
    } else {
        alert(message);
    }
}

function initializeFirebaseMessaging(token) {}
function registerFCMToken(apiToken) {}

async function initGoogleSignIn() {
    if (typeof google === 'undefined') {
        showNotification('error', 'Google no está disponible en este momento');
        return;
    }
    try {
        const auth = new google.accounts.oauth2.initCodeClient({
            client_id: GOOGLE_CLIENT_ID,
            scope: 'email profile',
            ux_mode: 'popup',
            callback: async function(response) {
                if (response.code) {
                    try {
                        const res = await fetch('/api/social-login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'dev-token': DEV_TOKEN },
                            body: JSON.stringify({ provider: 'google', code: response.code })
                        });
                        const data = await res.json();
                        if (data.status === 'success' && data.data?.access_token) {
                            const token = data.data.access_token;
                            const user = data.data.user || data.data.driver || {};
                            localStorage.setItem('auth_token', token);
                            localStorage.setItem('user_type', 'customer');
                            localStorage.setItem('user_info', JSON.stringify({
                                firstname: user.firstname || user.username || '',
                                email: user.email || '',
                                mobile: user.mobile || '',
                                image: user.image || '',
                                image_path: data.data.image_path || data.data.driver_image_path || ''
                            }));
                            window.location.href = '/auth/token-login?token=' + encodeURIComponent(token) + '&guard=web&redirect=' + encodeURIComponent(window.location.href);
                        } else {
                            showNotification('error', data.message || 'Error al iniciar con Google');
                        }
                    } catch (e) {
                        showNotification('error', 'Error de conexión');
                    }
                }
            }
        });
        auth.requestCode();
    } catch (e) {
        showNotification('error', 'Error al iniciar Google Sign-In');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const emailLoginForm = document.getElementById('emailLoginForm');
    const submitBtn = document.getElementById('loginSubmitBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('loginPassword');

    // Password visibility toggle
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.querySelector('i').className = isPassword ? 'las la-eye-slash' : 'las la-eye';
        });
    }

    // Email Login Form handler
    if (emailLoginForm) {
        emailLoginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const userType = document.querySelector('input[name="user-type"]:checked')?.value || 'customer';
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;

            if (!email || !password) {
                showNotification('error', 'Por favor completa todos los campos');
                return;
            }

            setSubmitLoading(true);

            const endpoint = userType === 'driver' ? '/api/driver/login' : '/api/login';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'dev-token': DEV_TOKEN },
                    body: JSON.stringify({ username: email, password: password })
                });

                const data = await response.json();

                if (data.status === 'success' && data.data?.access_token) {
                    const token = data.data.access_token;
                    localStorage.setItem('auth_token', token);
                    localStorage.setItem('user_type', userType);

                    const user = data.data.user || data.data.driver || {};
                    localStorage.setItem('user_info', JSON.stringify({
                        firstname: user.firstname || user.username || '',
                        email: user.email || '',
                        mobile: user.mobile || '',
                        image: user.image || '',
                        image_path: data.data.image_path || ''
                    }));
                    updateHeaderUser({
                        firstname: user.firstname || user.username,
                        email: user.email || user.mobile,
                        image: user.image,
                        image_path: data.data.image_path
                    }, userType);

                    registerFCMToken(token);
                    
                    const modalEl = document.getElementById('loginModal');
                    if (window.bootstrap && bootstrap.Modal) {
                        const m = bootstrap.Modal.getInstance(modalEl);
                        if (m) m.hide();
                    } else if (window.jQuery) {
                        jQuery(modalEl).modal('hide');
                    }
                    
                    window.location.reload();
                } else {
                    showNotification('error', data.message || 'Credenciales incorrectas');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'Error al conectar con el servidor');
            } finally {
                setSubmitLoading(false);
            }
        });
    }

    // Show cart badge on page load
    updateHeaderCartBadge();
});

function setSubmitLoading(loading) {
    const btn = document.getElementById('loginSubmitBtn');
    if (!btn) return;
    const text = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.btn-spinner');
    if (loading) {
        btn.classList.add('loading');
        if (text) text.style.display = 'none';
        if (spinner) spinner.style.display = 'inline';
    } else {
        btn.classList.remove('loading');
        if (text) text.style.display = '';
        if (spinner) spinner.style.display = 'none';
    }
}

// ===== Cart Badge =====
function updateHeaderCartBadge() {
    fetch('/cart-count', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var icon = document.getElementById('header-cart-icon');
            var badge = document.getElementById('hdr-cart-badge');
            var total = data.total_items || 0;
            if (badge) badge.textContent = total;
            if (icon) icon.style.display = total > 0 ? 'flex' : 'none';
            
            // Also update floating bottom bar if present
            var floatingCart = document.getElementById('lz-floating-cart');
            var floatingCount = document.getElementById('lz-fc-qty');
            if (floatingCart && floatingCount) {
                floatingCount.textContent = total;
                floatingCart.style.display = total > 0 ? 'flex' : 'none';
            }
        })
        .catch(function(){});
}
window.updateHeaderCartBadge = updateHeaderCartBadge;

function scrollToCart() {
    var cartEl = document.querySelector('.store-cart') || document.querySelector('.store-layout');
    if (cartEl) {
        cartEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        cartEl.classList.add('cart-highlight');
        setTimeout(function() { cartEl.classList.remove('cart-highlight'); }, 2000);
    } else {
        window.location.href = "{{ route('delivery.marketplace') }}";
    }
}
window.scrollToCart = scrollToCart;

// ===== Auth State & Header Management =====
function updateHeaderUser(user, userType) {
    var loggedOut = document.getElementById('header-logged-out');
    var loggedIn = document.getElementById('header-logged-in');
    if (!loggedOut || !loggedIn) return;

    loggedOut.style.display = 'none';
    loggedIn.style.display = 'block';

    var name = user.firstname || user.username || 'Mi Cuenta';
    var el = function(id) { return document.getElementById(id); };
    if (el('hdr-user-name')) el('hdr-user-name').textContent = name;
    if (el('hdr-menu-name')) el('hdr-menu-name').textContent = name;
    if (el('hdr-menu-email')) el('hdr-menu-email').textContent = user.email || '';
    if (el('hdr-menu-driver')) el('hdr-menu-driver').style.display = userType === 'driver' ? '' : 'none';

    var avatar = el('hdr-user-avatar');
    if (avatar && user.image && user.image_path) {
        var src = user.image_path.replace(/\/+$/, '') + '/' + user.image.replace(/^\/+/, '');
        avatar.innerHTML = '<img src="' + src + '" alt="' + name + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover">';
    }
}

// ===== User Location Loader =====
var modalMap, modalMarker, modalAutocomplete;

window.openLocationModal = function() {
    const modalEl = document.getElementById('locationModal');
    if (modalEl) {
        try {
            if (window.jQuery && typeof jQuery.fn.modal === 'function') {
                jQuery(modalEl).modal('show');
            } else if (window.bootstrap && bootstrap.Modal) {
                let locModal = bootstrap.Modal.getInstance(modalEl);
                if (!locModal) {
                    locModal = new bootstrap.Modal(modalEl);
                }
                locModal.show();
            }
        } catch (e) {
            console.error('Modal toggle error:', e);
        }
    }
};

function initModalMap() {
    if (typeof google === 'undefined' || !google.maps) {
        return;
    }
    
    var latInput = document.getElementById('modal-lat');
    var lngInput = document.getElementById('modal-lng');
    var addressInput = document.getElementById('modal-address-input');
    
    var defaultLat = -6.4833;
    var defaultLng = -76.3667;
    
    fetch('/location/get', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var activeLat = data.lat ? parseFloat(data.lat) : defaultLat;
            var activeLng = data.lng ? parseFloat(data.lng) : defaultLng;
            
            if (data.label && addressInput) {
                addressInput.value = data.label;
            }
            
            if (latInput) latInput.value = activeLat;
            if (lngInput) lngInput.value = activeLng;
            
            var mapOptions = {
                center: { lat: activeLat, lng: activeLng },
                zoom: 15,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                disableDefaultUI: true,
                zoomControl: true
            };
            
            var mapContainer = document.getElementById('modal-map');
            if (!mapContainer) return;
            
            modalMap = new google.maps.Map(mapContainer, mapOptions);
            
            modalMarker = new google.maps.Marker({
                position: { lat: activeLat, lng: activeLng },
                map: modalMap,
                draggable: true
            });
            
            google.maps.event.addListener(modalMarker, 'dragend', function() {
                var position = modalMarker.getPosition();
                updateCoordsAndAddress(position.lat(), position.lng());
            });
            
            google.maps.event.addListener(modalMap, 'click', function(event) {
                modalMarker.setPosition(event.latLng);
                updateCoordsAndAddress(event.latLng.lat(), event.latLng.lng());
            });
            
            if (google.maps.places && addressInput) {
                modalAutocomplete = new google.maps.places.Autocomplete(addressInput, {
                    componentRestrictions: { country: 'pe' }
                });
                modalAutocomplete.addListener('place_changed', function() {
                    var place = modalAutocomplete.getPlace();
                    if (place && place.geometry) {
                        var loc = place.geometry.location;
                        modalMap.setCenter(loc);
                        modalMarker.setPosition(loc);
                        latInput.value = loc.lat();
                        lngInput.value = loc.lng();
                    }
                });
            }
        });
}

function updateCoordsAndAddress(lat, lng) {
    var latInput = document.getElementById('modal-lat');
    var lngInput = document.getElementById('modal-lng');
    if (latInput) latInput.value = lat;
    if (lngInput) lngInput.value = lng;
    
    if (window.google && google.maps && google.maps.Geocoder) {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
            if (status === 'OK' && results[0]) {
                var addrInput = document.getElementById('modal-address-input');
                if (addrInput) addrInput.value = results[0].formatted_address;
            }
        });
    }
}

window.useGPSLocation = function() {
    if (navigator.geolocation) {
        var btn = document.querySelector('.premium-gps-btn');
        var originalText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Obteniendo ubicación...';
            btn.disabled = true;
        }
        
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            var label = "Ubicación actual";
            
            var latInput = document.getElementById('modal-lat');
            var lngInput = document.getElementById('modal-lng');
            if (latInput) latInput.value = lat;
            if (lngInput) lngInput.value = lng;
            
            if (modalMap && modalMarker) {
                var pos = { lat: lat, lng: lng };
                modalMap.setCenter(pos);
                modalMarker.setPosition(pos);
            }
            
            if (window.google && google.maps && google.maps.Geocoder) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
                    if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                    if (status === 'OK' && results[0]) {
                        label = results[0].formatted_address;
                        var addr = document.getElementById('modal-address-input');
                        if (addr) addr.value = label;
                    }
                });
            } else {
                if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
                var addr = document.getElementById('modal-address-input');
                if (addr) addr.value = lat.toFixed(6) + ', ' + lng.toFixed(6);
            }
        }, function(error) {
            if (btn) { btn.innerHTML = originalText; btn.disabled = false; }
            alert("No pudimos obtener tu ubicación GPS. Por favor búscala manualmente.");
        });
    } else {
        alert("Tu navegador no soporta geolocalización.");
    }
};

window.confirmSelectedLocation = function() {
    var latInput = document.getElementById('modal-lat');
    var lngInput = document.getElementById('modal-lng');
    var addrInput = document.getElementById('modal-address-input');
    
    var lat = latInput ? parseFloat(latInput.value) : NaN;
    var lng = lngInput ? parseFloat(lngInput.value) : NaN;
    var label = addrInput ? addrInput.value.trim() : '';
    
    if (isNaN(lat) || isNaN(lng) || !label) {
        alert("Por favor selecciona una ubicación válida.");
        return;
    }
    
    var btn = document.getElementById('confirmLocationBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
    }
    
    saveGlobalLocation(lat, lng, label);
};

function saveGlobalLocation(lat, lng, label) {
    var csrfToken = '{{ csrf_token() }}';
    fetch('/location/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ lat: lat, lng: lng, label: label })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        window.location.reload();
    })
    .catch(function(){});
}

function autoRequestGeolocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            var label = "Ubicación actual";
            
            if (window.google && google.maps && google.maps.Geocoder) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
                    if (status === 'OK' && results[0]) {
                        label = results[0].formatted_address;
                    }
                    saveGlobalLocation(lat, lng, label);
                });
            } else {
                saveGlobalLocation(lat, lng, label);
            }
        }, function(error) {
            console.warn("Geolocation auto-request error:", error);
        });
    }
}

function updateHeaderLocation() {
    var locEl = document.getElementById('header-location');
    if (!locEl) return;
    fetch('/location/get', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data && data.label) {
                locEl.textContent = data.label;
                locEl.title = data.label;
            } else {
                locEl.textContent = "Tarapoto (Centro)";
            }
        })
        .catch(function(){});
}
window.updateHeaderLocation = updateHeaderLocation;

document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('locationModal');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            initModalMap();
        });
    }
});

@if(session()->has('error'))
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_info');
    localStorage.removeItem('user_type');
@endif

(function loadAuthState() {
    const userInfo = localStorage.getItem('user_info');
    const userType = localStorage.getItem('user_type') || 'customer';
    const token = localStorage.getItem('auth_token');
    const serverLoggedIn = @json(auth()->check());
    const isSellerRoute = window.location.pathname.startsWith('/seller');

    if (token && !serverLoggedIn && !isSellerRoute) {
        const guard = userType === 'driver' ? 'driver' : 'web';
        window.location.href = '/auth/token-login?token=' + encodeURIComponent(token) + '&guard=' + guard + '&redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
        return;
    }

    if (userInfo) {
        try {
            updateHeaderUser(JSON.parse(userInfo), userType);
        } catch(e) {}
    }
    updateHeaderLocation();
})();

// Navigation functions
function navTo(url, guard) {
    const token = localStorage.getItem('auth_token');
    if (!token) { showLoginModal(); return; }
    const g = guard || (localStorage.getItem('user_type') === 'driver' ? 'driver' : 'web');
    window.location.href = '/auth/token-login?token=' + encodeURIComponent(token) + '&guard=' + g + '&redirect=' + encodeURIComponent(url);
}

function goToDashboard() { navTo('/user/dashboard', 'web'); }
function goToProfile()   { navTo('/user/profile', 'web'); }
function goToWallet()    { navTo('/user/wallet', 'web'); }
function goToDriverDocs(){ navTo('/inicio', 'driver'); }

function handleLogout() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_type');
        localStorage.removeItem('user_info');
        localStorage.removeItem('fcm_token');
        fetch('/api/logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'dev-token': DEV_TOKEN, 'Accept': 'application/json' }
        }).finally(function() {
            window.location.href = '/';
        });
    }
}

// ==========================================
// REAL CART DRAWER SYSTEM
// ==========================================
window.openCartDrawer = function() {
    const drawer = document.getElementById('lzCartDrawer');
    const overlay = document.getElementById('lzCartDrawerOverlay');
    if (drawer && overlay) {
        drawer.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        loadCartSummary();
    }
};

window.closeCartDrawer = function() {
    const drawer = document.getElementById('lzCartDrawer');
    const overlay = document.getElementById('lzCartDrawerOverlay');
    if (drawer && overlay) {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
};

window.updateHeaderCartBadge = function(count) {
    if (count !== undefined && count !== null && !isNaN(count)) {
        setCartBadgeDOM(parseInt(count));
    } else {
        fetch('/cart-count')
            .then(r => r.json())
            .then(data => {
                if (data && data.total_items !== undefined) {
                    setCartBadgeDOM(parseInt(data.total_items));
                }
            })
            .catch(() => {});
    }
};

function setCartBadgeDOM(count) {
    const badge = document.getElementById('hdr-cart-badge');
    const sub = document.getElementById('lzCartTotalItemsSub');
    if (badge) {
        if (count > 0) {
            badge.innerText = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
    if (sub) {
        sub.innerText = count === 1 ? '1 producto seleccionado' : `${count} productos seleccionados`;
    }
}

window.loadCartSummary = function() {
    const body = document.getElementById('lzCartDrawerBody');
    if (!body) return;

    body.innerHTML = `
        <div class="lz-cart-loading text-center py-5">
            <div class="spinner-border text-success" role="status" style="width: 2rem; height: 2rem;"></div>
            <p class="mt-3 text-muted" style="font-size: 13px; font-weight: 600;">Cargando tus productos...</p>
        </div>
    `;

    fetch('/cart-summary')
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success' || !res.carts || res.carts.length === 0) {
                updateHeaderCartBadge(0);
                body.innerHTML = `
                    <div class="lz-cart-empty">
                        <div class="lz-cart-empty-icon">
                            <i class="las la-shopping-basket"></i>
                        </div>
                        <h4 class="lz-cart-empty-title">Tu carrito está vacío</h4>
                        <p class="lz-cart-empty-text">Aún no has agregado productos. Explora nuestros restaurantes y tiendas para comenzar tu pedido.</p>
                        <button type="button" class="btn btn-success rounded-pill px-4 py-2" onclick="closeCartDrawer(); window.location.href='/delivery';" style="font-weight: 700; font-size: 13.5px;">
                            <i class="las la-utensils"></i> Explorar Tiendas
                        </button>
                    </div>
                `;
                return;
            }

            updateHeaderCartBadge(res.total_items);

            let html = '';
            res.carts.forEach(c => {
                let itemsHtml = '';
                c.items.forEach(item => {
                    const itemTotal = (item.price * item.quantity).toFixed(2);
                    itemsHtml += `
                        <div class="lz-cart-item-row" id="cartItem_${c.store_id}_${item.product_id}">
                            <div class="lz-cart-item-info">
                                <h5 class="lz-cart-item-name">${escapeHtml(item.name)}</h5>
                                <span class="lz-cart-item-price">S/ ${parseFloat(item.price).toFixed(2)} c/u</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="lz-cart-stepper">
                                    <button type="button" class="lz-cart-stepper-btn" onclick="modifyCartQty(${c.store_id}, ${item.product_id}, -1)" aria-label="Disminuir"><i class="las la-minus"></i></button>
                                    <span class="lz-cart-stepper-qty">${item.quantity}</span>
                                    <button type="button" class="lz-cart-stepper-btn" onclick="modifyCartQty(${c.store_id}, ${item.product_id}, 1, '${escapeJs(item.name)}', ${item.price})" aria-label="Aumentar"><i class="las la-plus"></i></button>
                                </div>
                                <span style="font-size: 13px; font-weight: 800; color: #0f172a; min-width: 58px; text-align: right;">S/ ${itemTotal}</span>
                            </div>
                        </div>
                    `;
                });

                html += `
                    <div class="lz-cart-store-card">
                        <div class="lz-cart-store-head">
                            <div class="lz-cart-store-name">
                                <i class="las la-store text-success"></i> ${escapeHtml(c.store_name)}
                            </div>
                            <button type="button" class="lz-cart-clear-btn" onclick="clearStoreCart(${c.store_id})">
                                <i class="las la-trash-alt"></i> Vaciar
                            </button>
                        </div>
                        <div class="lz-cart-items-list">
                            ${itemsHtml}
                        </div>
                        <div class="lz-cart-store-summary">
                            <div class="lz-cart-summary-line">
                                <span>Subtotal</span>
                                <span style="font-weight: 700;">S/ ${c.subtotal.toFixed(2)}</span>
                            </div>
                            <div class="lz-cart-summary-line">
                                <span>Envío estimado</span>
                                <span style="font-weight: 700; color: #10b981;">S/ ${c.delivery_fee.toFixed(2)}</span>
                            </div>
                            <div class="lz-cart-summary-line total">
                                <span>Total a Pagar</span>
                                <span>S/ ${c.total.toFixed(2)}</span>
                            </div>
                            <a href="javascript:void(0)" onclick="goToStoreCheckout(${c.store_id}, '${c.checkout_url}')" class="lz-cart-checkout-btn">
                                <span>Continuar al Checkout</span>
                                <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                `;
            });

            body.innerHTML = html;
        })
        .catch(() => {
            body.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <p>No se pudo cargar el carrito. Intenta de nuevo.</p>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadCartSummary()">Reintentar</button>
                </div>
            `;
        });
};

window.goToStoreCheckout = function(storeId, checkoutUrl) {
    const token = localStorage.getItem('auth_token');
    const isServerAuth = {{ auth()->check() ? 'true' : 'false' }};
    if (token) {
        window.location.href = '/auth/token-login?token=' + encodeURIComponent(token) + '&guard=web&redirect=' + encodeURIComponent(checkoutUrl);
    } else if (isServerAuth) {
        window.location.href = checkoutUrl;
    } else {
        closeCartDrawer();
        if (typeof showLoginModal === 'function') {
            showLoginModal();
        }
    }
};

window.modifyCartQty = function(storeId, productId, delta, name, price) {
    const url = delta > 0 ? `/cart/${storeId}/add` : `/cart/${storeId}/remove`;
    const payload = {
        product_id: productId,
        quantity: 1
    };
    if (delta > 0 && name && price) {
        payload.name = name;
        payload.price = price;
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(() => {
        loadCartSummary();
        if (typeof filterStoresDOM === 'function') {
            // refresh page badge if on marketplace
        }
    })
    .catch(() => {});
};

window.clearStoreCart = function(storeId) {
    if (!confirm('¿Deseas vaciar los productos de este negocio?')) return;
    fetch(`/cart/${storeId}/clear`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(() => {
        loadCartSummary();
    });
};

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}

// Initial cart count & login check on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('/cart-count')
        .then(r => r.json())
        .then(data => {
            if (data && data.total_items !== undefined) {
                updateHeaderCartBadge(data.total_items);
            }
        })
        .catch(() => {});

    // Check if redirect triggered require_login
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('require_login') === '1') {
        setTimeout(function() {
            if (typeof showLoginModal === 'function') {
                showLoginModal();
            }
        }, 400);
    }
});
</script>
@endpush
