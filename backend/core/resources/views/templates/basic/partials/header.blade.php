@php
    $languages = App\Models\Language::get();
    $selectLang = $languages->where('code', config('app.locale'))->first();
    $isDeliveryRoute = request()->routeIs('delivery.marketplace', 'delivery.store');
    $isLandingRoute = request()->routeIs('home');
    $showLocation = request()->routeIs('delivery.marketplace', 'delivery.store', 'taxi', 'favor*');
@endphp

<header class="hdr" id="header">
    <style>
    :root {
        --lz-primary: #22c55e;
        --lz-primary-dark: #16a34a;
        --lz-primary-light: #dcfce7;
        --lz-primary-5: rgba(34,197,94,0.05);
    }
    /* TOPBAR AND HEADER DESIGN STYLE RESTAURANT.PE */
    .hdr {
        background: #F5F7FC;
        padding: 0;
        position: relative;
        box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.08);
        border-bottom: none;
    }
    
    .hdr-topbar {
        background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
        padding: 8px 0;
        font-size: 13px;
        color: #ffffff;
    }
    .topbar-left-text {
        font-weight: 500;
        font-family: 'Montserrat', sans-serif;
    }
    .topbar-right-link {
        color: #ffffff !important;
        font-weight: 700;
        text-decoration: underline !important;
        font-family: 'Montserrat', sans-serif;
        transition: opacity 0.2s;
    }
    .topbar-right-link:hover {
        opacity: 0.85;
    }

    .hdr-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 56px;
        gap: 20px;
    }
    
    .hdr-logo {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        flex-shrink: 0;
    }
    .hdr-logo-icon {
        width: 32px;
        height: 32px;
        background: var(--lz-primary);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 15px;
    }
    .hdr-logo-text {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        font-family: 'Montserrat', sans-serif;
    }
    .hdr-logo-text span {
        color: var(--lz-primary);
    }

    .hdr-nav {
        display: flex;
        align-items: center;
        gap: 4px;
        height: 100%;
    }
    .hdr-nav a {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 12px;
        color: #1e293b;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        font-family: 'Montserrat', sans-serif;
        height: 100%;
        transition: all 0.2s ease;
        letter-spacing: -0.2px;
        border-bottom: 3px solid transparent;
        white-space: nowrap;
    }
    .hdr-nav a:hover {
        color: var(--lz-primary);
    }
    .hdr-nav a.active {
        color: var(--lz-primary);
        border-bottom-color: var(--lz-primary);
    }

    .hdr-actions {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-shrink: 0;
    }

    .hdr-demo-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: var(--lz-primary);
        color: #ffffff !important;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none !important;
        transition: all 0.2s;
        border: none;
    }
    .hdr-demo-btn:hover {
        background: var(--lz-primary-dark);
        transform: translateY(-1px);
    }

    .hdr-country-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        border-left: 1px solid var(--lz-border);
        padding-left: 16px;
    }
    .hdr-country-flag {
        border-radius: 2px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        display: block;
    }
    .hdr-country-info {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }
    .hdr-country-info small {
        font-size: 9px;
        color: var(--lz-text-50);
        font-weight: 500;
    }
    .hdr-country-info span {
        font-size: 11.5px;
        color: var(--lz-text-85);
        font-weight: 700;
    }

    .hdr-location {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #ffffff;
        border-radius: 6px;
        border: 1px solid var(--lz-border);
        font-size: 12px;
        color: var(--lz-text-50);
        max-width: 160px;
    }
    .hdr-location i {
        color: var(--lz-primary);
        font-size: 16px;
    }
    .hdr-location small {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .hdr-cart {
        position: relative;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        border: 1px solid var(--lz-border);
        border-radius: 8px;
        cursor: pointer;
        color: #475569;
        font-size: 18px;
        transition: all 0.15s;
    }
    .hdr-cart:hover {
        background: var(--lz-primary-5);
        color: var(--lz-primary);
    }
    .hdr-cart-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #dc2626;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        min-width: 16px;
        height: 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        border: 2px solid #fff;
    }

    .hdr-login {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: var(--lz-primary);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .hdr-login:hover {
        background: var(--lz-primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(34,197,94,0.25);
    }

    .hdr-seller {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: transparent;
        color: #475569;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s;
    }
    .hdr-seller:hover {
        border-color: var(--lz-primary);
        color: var(--lz-primary);
    }

    .hdr-fleet {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #1e293b;
        color: #ffffff !important;
        border: 1.5px solid #1e293b;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s;
    }
    .hdr-fleet:hover {
        background: #0f172a;
        border-color: #0f172a;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .hdr-user {
        position: relative;
    }
    .hdr-user-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 8px 4px 4px;
        background: #ffffff;
        border: 1px solid #f0f0f0;
        border-radius: 24px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .hdr-user-btn:hover {
        border-color: #d1d5db;
        background: #fff;
    }
    .hdr-user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 18px;
        overflow: hidden;
    }
    .hdr-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .hdr-user-name {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        max-width: 100px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .hdr-chevron {
        font-size: 12px;
        color: #94a3b8;
        transition: transform 0.2s;
    }
    
    .hdr-user-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.12);
        min-width: 200px;
        padding: 8px;
        display: none;
        z-index: 100;
    }
    .hdr-user-menu.open {
        display: block;
    }
    .hdr-menu-header {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 4px;
    }
    .hdr-menu-name {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
    }
    .hdr-menu-email {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }
    .hdr-user-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        color: #475569;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.15s;
    }
    .hdr-user-menu a:hover {
        background: #f8fafc;
        color: var(--lz-primary);
    }
    .hdr-user-menu a i {
        font-size: 16px;
        width: 20px;
        text-align: center;
    }
    .hdr-menu-divider {
        height: 1px;
        background: #f0f0f0;
        margin: 4px 0;
    }
    .hdr-logout-link {
        color: #dc2626!important;
    }
    .hdr-logout-link:hover {
        background: #fef2f2!important;
        color: #dc2626!important;
    }

    .hdr-toggler {
        display: none;
        width: 38px;
        height: 38px;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 20px;
        color: #475569;
        cursor: pointer;
    }
    
    @media(max-width:991px){
        .hdr-toggler { display: flex; }
        .hdr-nav {
            display: none;
            position: absolute;
            top: 56px;
            left: 0; right: 0;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px;
            flex-direction: column;
            gap: 4px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            z-index: 1000;
            height: auto;
        }
        .hdr-nav.show { display: flex; }
        .hdr-nav a {
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            border-bottom: none;
            height: auto;
        }
        .hdr-nav .hdr-mobile-auth {
            display: flex !important;
            flex-direction: column;
            gap: 8px;
            padding: 12px 8px 4px;
            margin-top: 4px;
            border-top: 1px solid #f1f5f9;
        }
        .hdr-nav .hdr-mobile-auth a {
            justify-content: center;
            padding: 10px 14px;
        }
        .hdr-actions .hdr-location,
        .hdr-actions .hdr-seller,
        .hdr-actions .hdr-fleet { display: none; }
    }

    /* PREMIUM LOGIN MODAL STYLING */
    .premium-login-modal {
        border-radius: 20px !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        overflow: hidden;
        background: #fff !important;
    }
    .premium-modal-header {
        position: relative;
        padding: 32px 32px 16px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        border-bottom: none !important;
    }
    .premium-modal-logo {
        margin-bottom: 16px;
    }
    .premium-modal-title {
        font-size: 24px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        margin: 0 !important;
    }
    .premium-modal-subtitle {
        font-size: 13.5px !important;
        color: #64748b !important;
        margin: 4px 0 0 !important;
    }
    .premium-modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f1f5f9 !important;
        border: none !important;
        color: #64748b !important;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .premium-modal-close:hover {
        background: #e2e8f0 !important;
        color: #0f172a !important;
    }
    .premium-modal-body {
        padding: 0 32px 32px !important;
    }
    .user-type-toggle {
        margin-bottom: 24px;
    }
    .user-type-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        text-align: left;
    }
    .user-type-pills {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        gap: 4px;
    }
    .user-type-pill {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px;
        font-size: 13.5px;
        font-weight: 700;
        color: #64748b;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        margin: 0 !important;
    }
    .user-type-pill:hover {
        color: #0f172a;
    }
    .user-type-pill.active {
        background: #fff;
        color: #22c55e !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .premium-input-group {
        position: relative;
        margin-bottom: 16px;
    }
    .premium-input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 18px;
        pointer-events: none;
    }
    .premium-input {
        width: 100%;
        padding: 14px 16px 14px 44px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 12px !important;
        font-size: 14.5px !important;
        color: #0f172a !important;
        background: #fff !important;
        transition: all 0.2s;
    }
    .premium-input:focus {
        border-color: #22c55e !important;
        outline: none !important;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1) !important;
    }
    .premium-input-suffix {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 18px;
        cursor: pointer;
        padding: 4px;
    }
    .premium-input-suffix:hover {
        color: #0f172a;
    }
    .premium-form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        font-size: 13px;
    }
    .premium-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        color: #475569;
        font-weight: 500;
        margin: 0 !important;
    }
    .premium-forgot-link {
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }
    .premium-forgot-link:hover {
        color: #22c55e;
    }
    .premium-btn-submit {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
        border: none !important;
        border-radius: 12px;
        color: #fff !important;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .premium-btn-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
    }
    .premium-divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 12px 0;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .premium-divider::before, .premium-divider::after {
        content: '';
        flex: 1;
        border-bottom: 1.5px solid #e2e8f0;
    }
    .premium-divider:not(:empty)::before {
        margin-right: 16px;
    }
    .premium-divider:not(:empty)::after {
        margin-left: 16px;
    }
    .premium-google-btn {
        width: 100%;
        padding: 12px;
        background: #fff !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 12px;
        color: #334155 !important;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .premium-google-btn:hover {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
    }
    .premium-register-link {
        text-align: center;
        margin-top: 24px;
        font-size: 13.5px;
        color: #64748b;
    }
    .premium-register-link a {
        color: #22c55e !important;
        font-weight: 700;
        text-decoration: none;
    }
    .premium-register-link a:hover {
        text-decoration: underline;
    }    </style>


    <!-- MAIN NAVBAR -->
    <div class="container">
        <div class="hdr-inner">
            <a href="{{ route('home') }}" class="hdr-logo">
                <div class="hdr-logo-icon">L</div>
                <div class="hdr-logo-text">liz<span>to</span></div>
            </a>

            <nav class="hdr-nav">
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Inicio</a>
                <a href="{{ route('home') }}#herramientas">Herramientas <i class="las la-angle-down"></i></a>
                <a href="{{ route('negocios') }}#precios" class="{{ request()->url() == route('negocios').'#precios' ? 'active' : '' }}">Precios</a>
                <a href="{{ route('home') }}#exito">Casos de éxito</a>
                <a href="{{ route('contact') }}" class="{{ menuActive('contact') }}">Contacto</a>
                <div class="hdr-mobile-auth d-lg-none">
                    <a href="{{ route('seller.login') }}" class="hdr-seller">
                        <i class="las la-store-alt"></i> Vendedor
                    </a>
                    <a href="{{ route('fleet.login') }}" class="hdr-fleet">
                        <i class="las la-taxi"></i> Flota
                    </a>
                </div>
            </nav>

            <div class="hdr-actions">
                <button class="hdr-toggler" onclick="document.querySelector('.hdr-nav').classList.toggle('show')">
                    <i class="las la-bars"></i>
                </button>
                @if($showLocation)
                <div class="hdr-location d-none d-lg-flex" style="cursor:pointer" onclick="openLocationModal()" title="Cambiar ubicación">
                    <i class="las la-map-marker-alt"></i>
                    <small id="header-location">Tu ubicación</small>
                </div>
                @endif

                @if($isDeliveryRoute)
                <button type="button" class="hdr-cart" id="header-cart-icon" style="display:none;" onclick="scrollToCart()" title="Ver carrito">
                    <i class="las la-shopping-bag"></i>
                    <span class="hdr-cart-badge" id="hdr-cart-badge">0</span>
                </button>
                @endif

                <!-- Demo CTA Button -->
                <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20solicitar%20una%20demo%20de%20Lizto" target="_blank" class="hdr-demo-btn d-none d-md-inline-flex">
                    Solicita tu demo <i class="las la-angle-right"></i>
                </a>

                <div id="header-auth-section">
                    <div id="header-logged-out" style="display:flex;align-items:center;gap:8px">
                        <button type="button" class="hdr-login" onclick="showLoginModal()">
                            <i class="las la-sign-in-alt"></i> Ingresar
                        </button>
                        <a href="{{ route('seller.login') }}" class="hdr-seller">
                            <i class="las la-store-alt"></i> Vendedor
                        </a>
                        <a href="{{ route('fleet.login') }}" class="hdr-fleet">
                            <i class="las la-taxi"></i> Flota
                        </a>
                    </div>
                    <div id="header-logged-in" style="display:none">
                        <div class="hdr-user">
                            <button class="hdr-user-btn" id="hdr-user-btn" onclick="toggleUserDropdown(event)">
                                <span class="hdr-user-avatar" id="hdr-user-avatar">
                                    <i class="las la-user-circle"></i>
                                </span>
                                <span class="hdr-user-name" id="hdr-user-name">Mi Cuenta</span>
                                <i class="las la-angle-down hdr-chevron"></i>
                            </button>
                            <div class="hdr-user-menu" id="hdr-user-menu">
                                <div class="hdr-menu-header">
                                    <div class="hdr-menu-name" id="hdr-menu-name"></div>
                                    <div class="hdr-menu-email" id="hdr-menu-email"></div>
                                </div>
                                <a href="#" onclick="goToDashboard()"><i class="las la-tachometer-alt"></i> Mi Panel</a>
                                <a href="#" onclick="goToProfile()"><i class="las la-user"></i> Mi Perfil</a>
                                <a href="#" onclick="goToWallet()"><i class="las la-wallet"></i> Billetera</a>
                                <a href="#" id="hdr-menu-driver" style="display:none;" onclick="goToDriverDocs()"><i class="las la-file-contract"></i> Documentos</a>
                                <div class="hdr-menu-divider"></div>
                                <a href="#" onclick="handleLogout()" class="hdr-logout-link"><i class="las la-sign-out-alt"></i> Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

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
    document.getElementById('hdr-user-menu').classList.toggle('open');
}

document.addEventListener('click', function(e) {
    var menu = document.getElementById('hdr-user-menu');
    var btn = document.getElementById('hdr-user-btn');
    if (menu && menu.classList.contains('open') && !btn.contains(e.target)) {
        menu.classList.remove('open');
    }
});

function showNotification(type, message) {
    alert(message);
}

function initializeFirebaseMessaging(token) {
    // FCM initialization handled by firebase-messaging-sw.js
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        // Already configured via firebase-app-compat.js
    }
}

function registerFCMToken(apiToken) {
    if (!('serviceWorker' in navigator)) return;
}

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

            const userType = document.querySelector('input[name="user-type"]:checked').value;
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
                    bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
                    showNotification('success', 'Sesión iniciada exitosamente');
                } else {
                    showNotification('error', data.message || 'Error en el login');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'Error al conectar con el servidor');
            } finally {
                setSubmitLoading(false);
            }
        });
    }

    // User type pills toggle
    document.querySelectorAll('input[name="user-type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.user-type-pill').forEach(pill => {
                pill.classList.remove('active');
            });
            if (this.nextElementSibling && this.nextElementSibling.classList.contains('user-type-pill')) {
                this.nextElementSibling.classList.add('active');
            }
        });
    });

    // Close user dropdown on outside click
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('header-auth-section');
        if (dropdown && !dropdown.contains(e.target)) {
            const menu = document.getElementById('hdr-user-menu');
            if (menu) menu.classList.remove('open');
        }
    });

    // Show cart badge on page load
    updateHeaderCartBadge();

    initializeFirebaseMessaging();
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
            if (!icon || !badge) return;
            var total = data.total_items || 0;
            badge.textContent = total;
            icon.style.display = total > 0 ? 'inline-block' : 'none';
        })
        .catch(function(){});
}

function scrollToCart() {
    var cartEl = document.querySelector('.store-cart');
    if (cartEl) {
        cartEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        cartEl.classList.add('cart-highlight');
        setTimeout(function() { cartEl.classList.remove('cart-highlight'); }, 2000);
    }
}

// ===== Auth State & Header Management =====
function updateHeaderUser(user, userType) {
    var loggedOut = document.getElementById('header-logged-out');
    var loggedIn = document.getElementById('header-logged-in');
    if (!loggedOut || !loggedIn) return;

    loggedOut.style.display = 'none';
    loggedIn.style.display = 'block';

    var name = user.firstname || user.username || 'Usuario';
    var el = function(id) { return document.getElementById(id); };
    if (el('hdr-user-name')) el('hdr-user-name').textContent = name;
    if (el('hdr-menu-name')) el('hdr-menu-name').textContent = name;
    if (el('hdr-menu-email')) el('hdr-menu-email').textContent = user.email || '';
    if (el('hdr-menu-driver')) el('hdr-menu-driver').style.display = userType === 'driver' ? '' : 'none';

    var avatar = el('hdr-user-avatar');
    if (avatar && user.image && user.image_path) {
        var src = user.image_path.replace(/\/+$/, '') + '/' + user.image.replace(/^\/+/, '');
        avatar.innerHTML = '<img src="' + src + '" alt="' + name + '" style="width:34px;height:34px;border-radius:50%;object-fit:cover">';
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
        return; // API not loaded yet
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
            
            latInput.value = activeLat;
            lngInput.value = activeLng;
            
            var mapOptions = {
                center: { lat: activeLat, lng: activeLng },
                zoom: 15,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                disableDefaultUI: true,
                zoomControl: true
            };
            
            modalMap = new google.maps.Map(document.getElementById('modal-map'), mapOptions);
            
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
    document.getElementById('modal-lat').value = lat;
    document.getElementById('modal-lng').value = lng;
    
    if (window.google && google.maps && google.maps.Geocoder) {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
            if (status === 'OK' && results[0]) {
                document.getElementById('modal-address-input').value = results[0].formatted_address;
            }
        });
    }
}

window.useGPSLocation = function() {
    if (navigator.geolocation) {
        var btn = document.querySelector('.premium-gps-btn');
        var originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Obteniendo ubicación...';
        btn.disabled = true;
        
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            var label = "Ubicación actual";
            
            document.getElementById('modal-lat').value = lat;
            document.getElementById('modal-lng').value = lng;
            
            if (modalMap && modalMarker) {
                var pos = { lat: lat, lng: lng };
                modalMap.setCenter(pos);
                modalMarker.setPosition(pos);
            }
            
            if (window.google && google.maps && google.maps.Geocoder) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    if (status === 'OK' && results[0]) {
                        label = results[0].formatted_address;
                        document.getElementById('modal-address-input').value = label;
                    }
                });
            } else {
                btn.innerHTML = originalText;
                btn.disabled = false;
                document.getElementById('modal-address-input').value = lat.toFixed(6) + ', ' + lng.toFixed(6);
            }
        }, function(error) {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert("No pudimos obtener tu ubicación GPS. Por favor búscala manualmente.");
        });
    } else {
        alert("Tu navegador no soporta geolocalización.");
    }
};

window.confirmSelectedLocation = function() {
    var lat = parseFloat(document.getElementById('modal-lat').value);
    var lng = parseFloat(document.getElementById('modal-lng').value);
    var label = document.getElementById('modal-address-input').value.trim();
    
    if (isNaN(lat) || isNaN(lng) || !label) {
        alert("Por favor selecciona una ubicación válida.");
        return;
    }
    
    var btn = document.getElementById('confirmLocationBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
    
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
                locEl.textContent = "Tu ubicación";
                autoRequestGeolocation();
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
</script>

<style>
@keyframes fadeInDown {
    from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
    to { opacity: 1; transform: translateX(-50%) translateY(0); }
}
</style>
@endpush
