@extends($activeTemplate . 'layouts.app')
@section('app-content')
    <div class="preloader">
        <img src="{{ getImage(getFilePath('preloader') . '/' . gs('preloader_image')) }}" alt="image">
    </div>
    <div class="body-overlay"></div>
    <div class="sidebar-overlay"></div>

    @stack('fbComment')

    {{-- Main B2C Header --}}
    <div class="header-wrapper" id="header-wrapper">
        @include('Template::partials.header')
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
                    <p class="premium-modal-subtitle">Ingresa a tu cuenta de Lizto</p>
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
                            <input type="email" class="premium-input" id="loginEmail" placeholder="Correo electrónico o usuario" required autocomplete="email">
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
                </div>
            </div>
        </div>
    </div>

    <!-- Location Modal Premium (At Layout Root to avoid z-index & clipping issues) -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content premium-location-modal">
                <div class="premium-modal-header" style="border-bottom: none !important; padding: 28px 24px 12px; text-align: center; display: flex; flex-direction: column; align-items: center; position: relative;">
                    <h5 class="premium-modal-title" style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="las la-map-marker-alt" style="color:var(--lz-primary);"></i> Selecciona tu ubicación
                    </h5>
                    <p class="premium-modal-subtitle" style="font-size: 13px; color: #64748b; margin: 4px 0 0;">Indícanos dónde entregar tu pedido en Tarapoto</p>
                    <button type="button" class="premium-modal-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 16px; right: 16px; width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: none; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="premium-modal-body" style="padding: 0 24px 28px !important;">
                    <button type="button" class="premium-gps-btn" onclick="useGPSLocation()">
                        <i class="las la-crosshairs"></i> Usar mi ubicación actual (GPS)
                    </button>

                    <div class="premium-divider">
                        <span>o busca tu dirección</span>
                    </div>

                    <div class="premium-input-group" style="position: relative; margin-bottom: 14px;">
                        <span class="premium-input-icon"><i class="las la-search"></i></span>
                        <input type="text" class="premium-input" id="modal-address-input" placeholder="Escribe tu calle, plaza o referencia..." autocomplete="off">
                    </div>

                    <div id="modal-map" style="width: 100%; height: 200px; border-radius: 14px; margin-bottom: 16px; border: 1.5px solid #e2e8f0; background: #f8fafc; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #94a3b8; font-size: 13px; text-align: center;">
                            <i class="las la-map" style="font-size: 30px; display: block; margin-bottom: 6px;"></i>
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

    {{-- Main Page Content --}}
    @yield('content')

    {{-- Footer --}}
    @include('Template::partials.footer')

    {{-- Mobile Bottom Navigation (Persistent) --}}
    <nav class="lz-bottom-nav">
        <a href="{{ route('delivery.marketplace') }}" class="lz-nav-item {{ request()->routeIs('delivery.marketplace') && !request('tab') ? 'active' : '' }}">
            <i class="las la-home"></i>
            <span>Inicio</span>
        </a>
        <a href="{{ route('delivery.marketplace') }}#buscar" class="lz-nav-item">
            <i class="las la-search"></i>
            <span>Buscar</span>
        </a>
        <a href="javascript:void(0)" onclick="goToDashboard()" class="lz-nav-item {{ request()->routeIs('user.dashboard', 'user.order*') ? 'active' : '' }}">
            <i class="las la-receipt"></i>
            <span>Pedidos</span>
        </a>
        <a href="{{ route('favor') }}" class="lz-nav-item {{ request()->routeIs('favor*') ? 'active' : '' }}">
            <i class="las la-hand-holding-heart"></i>
            <span>Favor</span>
        </a>
        <a href="javascript:void(0)" onclick="goToProfile()" class="lz-nav-item {{ request()->routeIs('user.profile', 'user.wallet') ? 'active' : '' }}">
            <i class="las la-user"></i>
            <span>Perfil</span>
        </a>
    </nav>

    @if(gs('google_maps_api'))
        @push('script-lib')
        <script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
        @endpush
    @endif
@endsection
