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
        {{-- Brand / Logo --}}
        <a href="{{ route('delivery.marketplace') }}" class="lz-brand">
            <div class="lz-brand-icon">L</div>
            <div class="lz-brand-text">liz<span>to</span></div>
            <span class="lz-brand-city"><i class="las la-map-marker-alt"></i> Tarapoto</span>
        </a>

        {{-- Location Selector (Desktop & Mobile) --}}
        <div class="lz-location-chip" onclick="openLocationModal()" title="Cambiar dirección de entrega" role="button" tabindex="0">
            <i class="las la-map-marker-alt lz-loc-pin"></i>
            <div class="lz-loc-info">
                <span class="lz-loc-label">Entregar en</span>
                <span class="lz-loc-address" id="header-location">Mi ubicación</span>
            </div>
            <i class="las la-angle-down lz-loc-arrow"></i>
        </div>

        {{-- Universal Search Bar (Desktop) --}}
        <div class="lz-header-search d-none d-lg-block">
            <form action="{{ route('delivery.marketplace') }}" method="GET" class="lz-search-box">
                <i class="las la-search lz-search-icon"></i>
                <input type="text" name="q" class="lz-search-input" value="{{ request('q') }}" placeholder="¿Qué se te antoja hoy? Busca tiendas o platos..." autocomplete="off">
            </form>
        </div>

        {{-- Header Actions --}}
        <div class="lz-header-actions">
            {{-- Search button for mobile --}}
            <a href="{{ route('delivery.marketplace') }}#buscar" class="lz-action-btn d-lg-none" title="Buscar" aria-label="Buscar">
                <i class="las la-search"></i>
            </a>

            {{-- Cart Button --}}
            <button type="button" class="lz-action-btn" id="header-cart-icon" onclick="scrollToCart()" title="Ver carrito" aria-label="Carrito de compras" style="display:none;">
                <i class="las la-shopping-bag"></i>
                <span class="lz-badge-count" id="hdr-cart-badge">0</span>
            </button>

            {{-- User Auth Trigger / Profile --}}
            <div id="header-auth-section">
                {{-- Logged Out State --}}
                <div id="header-logged-out" style="display:flex;align-items:center;gap:8px">
                    <button type="button" class="lz-user-btn" onclick="showLoginModal()">
                        <i class="las la-user"></i>
                        <span>Ingresar</span>
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
        </div>
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
</script>
@endpush
