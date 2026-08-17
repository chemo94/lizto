@extends($activeTemplate . 'layouts.app')
@section('app-content')
    <div class="preloader">
        <img src="{{ getImage(getFilePath('preloader') . '/' . gs('preloader_image')) }}" alt="image">
    </div>
    <div class="body-overlay"></div>
    <div class="sidebar-overlay"></div>

    @stack('fbComment')

    {{-- Banner del Ecosistema (oculto en home SaaS) --}}
    @if(!request()->routeIs('home'))
    <div class="ecosystem-banner" id="ecosystem-banner">
        <div class="container">
            <div class="ecosystem-banner-content">
                <div class="ecosystem-banner-links">
                    <a href="{{ route('home') }}"><i class="las la-globe"></i> Lizto Ecosistema:</a>
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Ecosistema</a>
                    <a href="{{ route('delivery.marketplace') }}" class="{{ request()->routeIs('delivery.marketplace', 'delivery.store') ? 'active' : '' }}">Delivery</a>
                    <a href="{{ route('taxi') }}" class="{{ request()->routeIs('taxi') ? 'active' : '' }}">Taxi</a>
                    <a href="{{ route('negocios') }}" class="{{ request()->routeIs('negocios') ? 'active' : '' }}">Negocios</a>
                    <a href="{{ route('favor') }}" class="{{ request()->routeIs('favor*') ? 'active' : '' }}">Favor</a>
                </div>
                <a href="{{ route('delivery.marketplace') }}" class="ecosystem-banner-cta">Descubre el ecosistema <i class="las la-arrow-right"></i></a>
            </div>
        </div>
    </div>
    @endif
    <div class="header-wrapper" id="header-wrapper">
        <div class="header-container" id="header-container">
            @include('Template::partials.header')
        </div>
    </div>

    <!-- Login Modal Premium (At Layout Root to avoid z-index & clipping issues) -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true" data-dev-token="{{ developerToken() }}">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content premium-login-modal">
                <!-- Modal Header -->
                <div class="premium-modal-header">
                    <div class="premium-modal-logo">
                        <img src="{{ siteLogo('dark') }}" alt="Logo" style="height: 32px;" onerror="this.style.display='none'">
                    </div>
                    <h5 class="premium-modal-title">Inicia Sesión</h5>
                    <p class="premium-modal-subtitle">Ingresa a tu cuenta</p>
                    <button type="button" class="premium-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="premium-modal-body">
                    <!-- Default User Type Hidden -->
                    <input type="radio" name="user-type" id="ut-customer" value="customer" checked hidden>

                    <!-- Login Form -->
                    <form id="emailLoginForm" novalidate>
                        <div class="premium-input-group">
                            <span class="premium-input-icon"><i class="las la-envelope"></i></span>
                            <input type="email" class="premium-input" id="loginEmail" placeholder="Correo electrónico" required autocomplete="email">
                        </div>

                        <div class="premium-input-group">
                            <span class="premium-input-icon"><i class="las la-lock"></i></span>
                            <input type="password" class="premium-input" id="loginPassword" placeholder="Contraseña" required autocomplete="current-password">
                            <span class="premium-input-suffix" id="togglePassword" role="button" tabindex="0">
                                <i class="las la-eye"></i>
                            </span>
                        </div>

                        <div class="premium-form-options">
                            <label class="premium-checkbox">
                                <input type="checkbox" id="rememberMe">
                                <span class="checkmark"></span>
                                Recordarme
                            </label>
                            <a href="#" class="premium-forgot-link">¿Olvidaste tu contraseña?</a>
                        </div>

                        <button type="submit" class="premium-btn-submit" id="loginSubmitBtn">
                            <span class="btn-text">Ingresar</span>
                            <span class="btn-spinner" style="display:none;">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            </span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="premium-divider">
                        <span>o continúa con</span>
                    </div>



                    <button type="button" class="premium-google-btn" id="googleSignInBtn" onclick="initGoogleSignIn()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Continuar con Google
                    </button>

                    <!-- Register Link -->
                    <div class="premium-register-link">
                        ¿No tienes cuenta?
                        <a href="{{ route('pages', 'register') ?? '#' }}">Regístrate aquí</a>
                    </div>
                    <div style="text-align:center;margin-top:12px;padding-top:12px;border-top:1px solid #eee;font-size:12px">
                        <span style="color:#68736c">¿Eres dueño de un negocio?</span><br>
                        <a href="{{ route('seller.login') }}" style="color:#16a34a;font-weight:700;font-size:13px">
                            <i class="las la-store-alt"></i> Ingresar como Vendedor
                        </a>
                        <br>
                        <span style="color:#68736c" class="mt-2 d-inline-block">¿Tienes una flota de transporte?</span><br>
                        <a href="{{ route('fleet.login') }}" style="color:#1e293b;font-weight:700;font-size:13px">
                            <i class="las la-taxi"></i> Ingresar como Flota
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Modal Premium (At Layout Root to avoid z-index & clipping issues) -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content premium-location-modal">
                <div class="premium-modal-header" style="border-bottom: none !important; padding: 32px 32px 16px; text-align: center; display: flex; flex-direction: column; align-items: center; position: relative;">
                    <h5 class="premium-modal-title" style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="las la-map-marker-alt" style="color:#22c55e;"></i> Selecciona tu ubicación
                    </h5>
                    <p class="premium-modal-subtitle" style="font-size: 13.5px; color: #64748b; margin: 4px 0 0;">Indícanos dónde entregar tu pedido para calcular costos</p>
                    <button type="button" class="premium-modal-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 20px; right: 20px; width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: none; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="premium-modal-body" style="padding: 0 32px 32px !important;">
                    <button type="button" class="premium-gps-btn" onclick="useGPSLocation()">
                        <i class="las la-crosshairs"></i> Usar mi ubicación actual (GPS)
                    </button>

                    <div class="premium-divider" style="display: flex; align-items: center; text-align: center; margin: 16px 0; color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                        <span style="background: #fff; padding: 0 10px; z-index: 1;">o busca tu dirección</span>
                    </div>

                    <div class="premium-input-group" style="position: relative; margin-bottom: 16px;">
                        <span class="premium-input-icon" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px; pointer-events: none;"><i class="las la-search"></i></span>
                        <input type="text" class="premium-input" id="modal-address-input" placeholder="Escribe tu calle, plaza o referencia..." autocomplete="off" style="width: 100%; padding: 14px 16px 14px 44px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14.5px; color: #0f172a; background: #fff; transition: all 0.2s;">
                    </div>

                    <div id="modal-map" style="width: 100%; height: 220px; border-radius: 12px; margin-bottom: 20px; border: 1.5px solid #e2e8f0; background: #f8fafc; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #94a3b8; font-size: 13px; text-align: center;">
                            <i class="las la-map" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            Cargando mapa...
                        </div>
                    </div>

                    <input type="hidden" id="modal-lat">
                    <input type="hidden" id="modal-lng">

                    <button type="button" class="premium-btn-submit" id="confirmLocationBtn" onclick="confirmSelectedLocation()">
                        Confirmar Ubicación
                    </button>
                </div>
            </div>
        </div>
    </div>

    @yield('content')
    @include('Template::partials.footer')

    @push('style')
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* PREMIUM LOCATION MODAL STYLING */
    .premium-location-modal {
        border-radius: 20px !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        background: #fff !important;
    }
    .premium-gps-btn {
        width: 100%;
        padding: 14px 16px;
        background: rgba(34, 197, 94, 0.08) !important;
        border: 1.5px solid rgba(34, 197, 94, 0.2) !important;
        border-radius: 12px;
        color: #16a34a !important;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .premium-gps-btn:hover {
        background: rgba(34, 197, 94, 0.15) !important;
        border-color: #22c55e !important;
    }
    .premium-gps-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    /* GLOBAL FONTS OVERRIDE */
    body, p, span, a, button, input, select, textarea, label {
        font-family: 'Inter', sans-serif !important;
    }
    h1, h2, h3, h4, h5, h6, .hdr-logo-text, .logo, .hero h1 {
        font-family: 'Outfit', sans-serif !important;
    }

    /* BANNER DEL ECOSISTEMA - PREMIUM DARK */
    .ecosystem-banner {
        background: #090d16;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 8px 0;
        position: relative;
        z-index: 1003;
        transition: transform .4s cubic-bezier(0.16, 1, 0.3, 1), opacity .4s ease;
    }
    .ecosystem-banner.ecosystem-banner--hidden {
        transform: translateY(-100%);
        opacity: 0;
        pointer-events: none;
        position: absolute;
    }
    .ecosystem-banner-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
    .ecosystem-banner-links {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .ecosystem-banner-links a {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all .25s ease;
    }
    .ecosystem-banner-links a:first-child {
        color: #fff;
        font-weight: 700;
        margin-right: 6px;
        letter-spacing: -0.2px;
    }
    .ecosystem-banner-links a:hover {
        color: #fff;
    }
    .ecosystem-banner-links a.active {
        color: #22c55e;
        font-weight: 700;
    }
    .ecosystem-banner-cta {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
        text-decoration: none;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all .25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .ecosystem-banner-cta:hover {
        background: #22c55e;
        color: #000;
        border-color: #22c55e;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
    }

    /* HEADER WRAPPER */
    .header-wrapper {
        position: relative;
        z-index: 1002;
        transition: height .3s ease;
    }
    .header-container {
        position: relative;
        z-index: 1002;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(16px);
        border-bottom: 1px solid rgba(0,0,0,0.06);
        transition: all 0.3s;
    }
    .header-container.scrolled .hdr {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
        z-index: 1004;
        background: rgba(255, 255, 255, 0.85) !important;
        backdrop-filter: blur(16px) !important;
        border-bottom: 1px solid rgba(0,0,0,0.06) !important;
    }

    @media(max-width:768px){
        .ecosystem-banner-content {
            flex-direction: column;
            text-align: center;
        }
        .ecosystem-banner-links {
            justify-content: center;
            gap: 12px;
        }
    }
    </style>
    @endpush

    @push('script')
    <script>
    (function(){
        var banner = document.getElementById('ecosystem-banner');
        var headerWrapper = document.getElementById('header-wrapper');
        var headerContainer = document.getElementById('header-container');
        if(!banner || !headerWrapper || !headerContainer) return;

        var bannerHeight = 0;
        var headerHeight = 0;

        function updateHeights(){
            bannerHeight = banner.offsetHeight;
            headerHeight = headerContainer.querySelector('.hdr').offsetHeight;
            headerWrapper.style.height = headerHeight + 'px';
        }

        updateHeights();
        window.addEventListener('resize', updateHeights);

        window.addEventListener('scroll', function(){
            var scrollY = window.scrollY || window.pageYOffset;

            if(scrollY > bannerHeight){
                banner.classList.add('ecosystem-banner--hidden');
                headerContainer.classList.add('scrolled');
            } else {
                banner.classList.remove('ecosystem-banner--hidden');
                headerContainer.classList.remove('scrolled');
            }
        });
    })();
    </script>
    @endpush

    @if(gs('google_maps_api'))
        @push('script-lib')
        <script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
        @endpush
    @endif
@endsection
