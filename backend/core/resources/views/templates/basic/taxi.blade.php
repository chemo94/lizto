@extends($activeTemplate . 'layouts.frontend')

@section('content')
<main class="taxi-landing">
    {{-- Hero Section: Split Screen --}}
    <section class="taxi-hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                {{-- Left side: App presentation --}}
                <div class="col-lg-6 taxi-hero-left">
                    <span class="taxi-badge"><i class="las la-taxi"></i> Lizto Taxi Ecosistema</span>
                    <h1 class="taxi-main-title">Viaja seguro, rápido y a <span>precio justo</span></h1>
                    <p class="taxi-hero-desc">
                        Pide tu viaje directamente desde la web en Tarapoto o descarga nuestras aplicaciones oficiales para pasajeros y conductores.
                    </p>
                    
                    {{-- Download Badges Container --}}
                    <div class="taxi-download-group">
                        <div class="download-card">
                            <h6><i class="las la-user"></i> Lizto Taxi (Pasajero)</h6>
                            <div class="badge-row">
                                <a href="https://play.google.com/store/apps/details?id=com.lizto.user" target="_blank" class="store-badge-btn">
                                    <i class="lab la-android"></i>
                                    <div><small>Disponible en</small><span>Google Play</span></div>
                                </a>
                                <a href="#" class="store-badge-btn store-badge-btn--apple" onclick="alert('Muy pronto disponible en App Store'); return false;">
                                    <i class="lab la-apple"></i>
                                    <div><small>Consíguelo en el</small><span>App Store</span></div>
                                </a>
                            </div>
                        </div>

                        <div class="download-card">
                            <h6><i class="las la-steering-wheel"></i> Lizto Conductor</h6>
                            <div class="badge-row">
                                <a href="https://play.google.com/store/apps/details?id=com.lizto.driver" target="_blank" class="store-badge-btn store-badge-btn--driver">
                                    <i class="lab la-android"></i>
                                    <div><small>Disponible en</small><span>Google Play</span></div>
                                </a>
                                <a href="#" class="store-badge-btn store-badge-btn--apple" onclick="alert('Muy pronto disponible en App Store'); return false;">
                                    <i class="lab la-apple"></i>
                                    <div><small>Consíguelo en el</small><span>App Store</span></div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right side: Fare Estimation Widget --}}
                <div class="col-lg-6">
                    <div class="taxi-card-wrapper" id="taxiFormWrapper">
                        <!-- Step 1: Location -->
                        <div id="step-location" class="taxi-step">
                            <div class="taxi-step__badge">Calcula tu tarifa</div>
                            <h3 class="step-title"><i class="las la-map-marker-alt"></i> ¿Dónde te recogemos?</h3>
                            <div class="form-group">
                                <div class="input-with-icon">
                                    <i class="las la-dot-circle pickup-dot"></i>
                                    <input type="text" id="pickup-location" class="form-control-premium" placeholder="Tu ubicación actual" required autocomplete="off">
                                </div>
                                <button type="button" class="location-btn-premium" onclick="useCurrentLocation()">
                                    <i class="las la-crosshairs"></i> Usar mi ubicación actual
                                </button>
                            </div>
                            <input type="hidden" id="pickup-lat">
                            <input type="hidden" id="pickup-lng">

                            <h3 class="step-title" style="margin-top: 24px;"><i class="las la-flag-checkered"></i> ¿A dónde vas?</h3>
                            <div class="form-group">
                                <div class="input-with-icon">
                                    <i class="las la-map-marker destination-dot"></i>
                                    <input type="text" id="destination-location" class="form-control-premium" placeholder="Escribe tu destino" required autocomplete="off">
                                </div>
                            </div>
                            <input type="hidden" id="destination-lat">
                            <input type="hidden" id="destination-lng">

                            <button type="button" class="taxi-btn-premium" id="btn-calculate" onclick="stepCalculateFare()">
                                <i class="las la-calculator"></i> Calcular Tarifa Estimada
                            </button>
                        </div>

                        <!-- Step 2: Fare & Service -->
                        <div id="step-fare" class="taxi-step" style="display:none">
                            <div class="taxi-step__badge">Paso 2: Opciones de viaje</div>
                            <h3 class="step-title"><i class="las la-receipt"></i> Tarifa Estimada</h3>
                            <div class="fare-summary-premium">
                                <div class="fare-row-premium"><span>Distancia</span><b id="fare-distance">-</b></div>
                                <div class="fare-row-premium"><span>Tiempo estimado</span><b id="fare-duration">-</b></div>
                                <div class="fare-row-premium fare-total-premium"><span>Tarifa Total</span><b id="fare-total">S/ 0.00</b></div>
                            </div>

                            <h3 class="step-title" style="margin-top: 24px;"><i class="las la-car"></i> Tipo de Servicio</h3>
                            <div class="form-group">
                                <div id="service-selector" style="display: flex; flex-direction: column; gap: 10px;">
                                    @forelse($taxiServices as $service)
                                        <label class="service-option-premium" data-id="{{ $service->id }}">
                                            <input type="radio" name="service_id" value="{{ $service->id }}" style="display:none;" {{ $loop->first ? 'checked' : '' }}>
                                            <div class="service-option-premium__icon"><i class="las la-taxi"></i></div>
                                            <div class="service-option-premium__info">
                                                <strong>{{ $service->name }}</strong>
                                                <small>{{ $service->description ?? 'Servicio de taxi' }}</small>
                                            </div>
                                        </label>
                                    @empty
                                        <p style="color: #999; text-align: center;">No hay servicios de taxi disponibles</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="action-buttons-row">
                                <button type="button" class="taxi-btn-premium taxi-btn-premium--outline" id="btn-back" onclick="goToStep('step-location')">
                                    <i class="las la-arrow-left"></i> Cambiar destino
                                </button>
                                <button type="button" class="taxi-btn-premium" id="btn-continue" onclick="goToStep('step-terms')">
                                    Continuar <i class="las la-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Terms & Confirmation -->
                        <div id="step-terms" class="taxi-step" style="display:none">
                            <div class="taxi-step__badge">Paso 3: Confirmación</div>
                            <h3 class="step-title"><i class="las la-file-contract"></i> Términos del servicio</h3>
                            <div class="terms-card-premium">
                                <p><strong>Información importante:</strong></p>
                                <ul>
                                    <li>La tarifa es un <strong>estimado</strong> basado en el kilometraje y ruta de conducción.</li>
                                    <li>No incluye variaciones imprevistas por tráfico extremo o peajes de la vía.</li>
                                    <li>Puedes abonar al conductor en efectivo o con tu billetera digital (Yape / Plin) al finalizar el viaje.</li>
                                </ul>
                            </div>
                            <div class="form-group">
                                <label class="terms-checkbox-premium" style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #475569; cursor: pointer;">
                                    <input type="checkbox" id="agree-terms" required>
                                    <span>Acepto las políticas de uso de Lizto Taxi</span>
                                </label>
                            </div>

                            <div class="action-buttons-row">
                                <button type="button" class="taxi-btn-premium taxi-btn-premium--outline" onclick="goToStep('step-fare')">
                                    <i class="las la-arrow-left"></i> Volver
                                </button>
                                <button type="button" class="taxi-btn-premium" id="btn-search" onclick="submitTaxiRequest()">
                                    <i class="las la-search"></i> Solicitar Taxi Ahora
                                </button>
                            </div>
                        </div>

                        <!-- Step 4: Tracking -->
                        <div id="step-tracking" class="taxi-step" style="display:none">
                            <div class="taxi-step__badge">Buscando conductor</div>
                            <div id="tracking-content" style="text-align: center;">
                                <div id="tracking-loading">
                                    <div class="tracking-spinner-premium">
                                        <div class="pulse-ring"></div>
                                        <i class="las la-taxi"></i>
                                    </div>
                                    <p style="margin-top: 24px; color: #475569; font-weight: 500;">Buscando conductores disponibles en la zona...</p>
                                </div>
                                <div id="driver-info" style="display:none;">
                                    <div class="driver-card-premium">
                                        <img id="driver-photo" src="" alt="Conductor">
                                        <div>
                                            <h4 id="driver-name">-</h4>
                                            <small id="driver-vehicle">-</small>
                                            <div class="driver-rating-premium">
                                                <i class="las la-star"></i><span id="driver-rating">4.8</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p id="driver-eta">¡Conductor en camino!</p>
                                </div>
                            </div>
                            <button type="button" class="taxi-btn-premium taxi-btn-premium--danger" onclick="cancelTaxiRequest()" style="margin-top: 16px;">
                                <i class="las la-times"></i> Cancelar Solicitud
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- History Section --}}
    <section class="taxi-info" id="my-rides-section" style="display:none">
        <div class="container">
            <div class="section-header-premium">
                <span>Tu Historial</span>
                <h2>Mis Viajes Recientes</h2>
            </div>
            <div id="my-rides-list" class="rides-grid-premium"></div>
        </div>
    </section>

    {{-- Features Section --}}
    <section class="taxi-features-section">
        <div class="container">
            <div class="section-header-premium text-center">
                <span>Beneficios</span>
                <h2>¿Por qué viajar con Lizto Taxi?</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="premium-feature-card">
                        <div class="feature-icon"><i class="las la-mobile-alt"></i></div>
                        <h3>Pide al Instante</h3>
                        <p>Solicita tu taxi directamente desde tu smartphone o web en segundos.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="premium-feature-card">
                        <div class="feature-icon"><i class="las la-user-shield"></i></div>
                        <h3>Seguridad Absoluta</h3>
                        <p>Filtros rigurosos y verificación detallada de todos los conductores.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="premium-feature-card">
                        <div class="feature-icon"><i class="las la-dollar-sign"></i></div>
                        <h3>Tarifas Transparentes</h3>
                        <p>Precios justos calculados matemáticamente según tu trayecto.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="premium-feature-card">
                        <div class="feature-icon"><i class="las la-clock"></i></div>
                        <h3>Disponibilidad 24/7</h3>
                        <p>Conductores listos para llevarte a tu destino en cualquier hora del día.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Conductor Banner Section --}}
    <section class="taxi-driver-cta-section">
        <div class="container">
            <div class="driver-cta-card">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <h2>¿Quieres ganar dinero conduciendo?</h2>
                        <p>Regístrate en Lizto Conductor. Disfruta de la tasa de comisión más baja del mercado, pagos directos de tus pasajeros y total flexibilidad horaria.</p>
                    </div>
                    <div class="col-lg-5 text-lg-end">
                        <a href="{{ route('pages', 'register') }}" class="driver-cta-btn">
                            <i class="las la-steering-wheel"></i> Registrarme como Conductor
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.taxi-landing {
    padding-top: 80px;
    background: radial-gradient(circle at 5% 15%, rgba(34, 197, 94, 0.07), transparent 45%),
                radial-gradient(circle at 95% 85%, rgba(34, 197, 94, 0.04), transparent 50%),
                #fafcfa;
    min-height: 100vh;
}
.taxi-hero-section {
    padding: 80px 0;
}
.taxi-badge {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 24px;
}
.taxi-main-title {
    font-size: clamp(38px, 5.5vw, 60px);
    font-weight: 900;
    line-height: 1.1;
    color: #0f172a;
    letter-spacing: -1.8px;
    margin-bottom: 20px;
}
.taxi-main-title span {
    background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.taxi-hero-desc {
    font-size: 17px;
    color: #475569;
    line-height: 1.6;
    margin-bottom: 40px;
    max-width: 520px;
}

/* Store Badges */
.taxi-download-group {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.download-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    max-width: 460px;
}
.download-card h6 {
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #64748b;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.download-card h6 i {
    font-size: 16px;
    color: #22c55e;
}
.badge-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.store-badge-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    background: #0f172a;
    color: #fff !important;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.2s;
    flex: 1;
    min-width: 140px;
}
.store-badge-btn:hover {
    background: #1e293b;
    transform: translateY(-1px);
}
.store-badge-btn i {
    font-size: 26px;
}
.store-badge-btn div {
    display: flex;
    flex-direction: column;
    text-align: left;
}
.store-badge-btn small {
    font-size: 9px;
    color: #94a3b8;
    text-transform: uppercase;
    font-weight: 500;
}
.store-badge-btn span {
    font-size: 13px;
    font-weight: 700;
}

/* Card Widget */
.taxi-card-wrapper {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 28px;
    padding: 40px;
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.05);
}
.taxi-step__badge {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 24px;
    display: inline-block;
}
.step-title {
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.step-title i {
    color: #22c55e;
}
.form-group {
    margin-bottom: 18px;
    text-align: left;
}
.input-with-icon {
    position: relative;
    width: 100%;
}
.input-with-icon i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 16px;
}
.pickup-dot {
    color: #22c55e;
}
.destination-dot {
    color: #dc2626;
}
.form-control-premium {
    width: 100%;
    padding: 16px 16px 16px 46px;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    font-size: 15px;
    color: #0f172a;
    background: #fff;
    transition: all 0.2s;
}
.form-control-premium:focus {
    outline: none;
    border-color: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}
.location-btn-premium {
    background: none;
    border: none;
    color: #16a34a;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    cursor: pointer;
    transition: color 0.2s;
}
.location-btn-premium:hover {
    color: #15803d;
}
.taxi-btn-premium {
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
    color: #fff !important;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}
.taxi-btn-premium:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
}
.taxi-btn-premium--outline {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    color: #475569 !important;
}
.taxi-btn-premium--outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    transform: none;
    box-shadow: none;
}
.taxi-btn-premium--danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}
.taxi-btn-premium--danger:hover {
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
}
.action-buttons-row {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}
.action-buttons-row button {
    flex: 1;
}

/* Step 2 summary */
.fare-summary-premium {
    background: rgba(34, 197, 94, 0.05);
    border: 1.5px dashed rgba(34, 197, 94, 0.2);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
}
.fare-row-premium {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
    color: #475569;
}
.fare-total-premium {
    font-size: 19px;
    font-weight: 800;
    color: #15803d;
    border-top: 1.5px solid rgba(34, 197, 94, 0.15);
    padding-top: 14px;
    margin-top: 6px;
}

/* Service Options */
.service-option-premium {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    cursor: pointer;
    background: #fff;
    transition: all 0.2s;
}
.service-option-premium:hover {
    border-color: #86efac;
}
.service-option-premium.selected {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.04);
}
.service-option-premium__icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(34, 197, 94, 0.08);
    color: #15803d;
    display: grid;
    place-items: center;
    font-size: 20px;
}
.service-option-premium__info strong {
    display: block;
    font-size: 14.5px;
    color: #0f172a;
}
.service-option-premium__info small {
    font-size: 12.5px;
    color: #64748b;
}

/* Terms card */
.terms-card-premium {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    font-size: 13.5px;
    color: #475569;
    margin-bottom: 20px;
}
.terms-card-premium ul {
    padding-left: 18px;
    margin: 8px 0 0;
}
.terms-card-premium li {
    margin-bottom: 6px;
}

/* Tracking spinner */
.tracking-spinner-premium {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto;
    background: #efffef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #22c55e;
    font-size: 36px;
}
.pulse-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid #22c55e;
    border-radius: 50%;
    animation: pulse 1.5s cubic-bezier(0.215, 0.610, 0.355, 1) infinite;
}
@keyframes pulse {
    0% { transform: scale(0.95); opacity: 0.8; }
    50% { opacity: 0.4; }
    100% { transform: scale(1.6); opacity: 0; }
}

.driver-card-premium {
    display: flex;
    align-items: center;
    gap: 16px;
    background: rgba(34, 197, 94, 0.05);
    padding: 20px;
    border-radius: 16px;
    border: 1px solid rgba(34, 197, 94, 0.1);
    text-align: left;
    margin: 20px 0;
}
.driver-card-premium img {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    background: #e2e8f0;
    border: 2px solid #fff;
}
.driver-card-premium h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
}
.driver-card-premium small {
    color: #475569;
    font-size: 12.5px;
}
.driver-rating-premium {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 4px;
    color: #f59e0b;
    font-size: 13.5px;
    font-weight: 700;
}

/* Features */
.taxi-features-section {
    padding: 80px 0;
    background: #fff;
}
.section-header-premium {
    margin-bottom: 48px;
}
.section-header-premium span {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #22c55e;
}
.section-header-premium h2 {
    font-size: 32px;
    font-weight: 800;
    letter-spacing: -0.8px;
    margin-top: 6px;
    color: #0f172a;
}
.premium-feature-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 32px;
    height: 100%;
    transition: all 0.2s;
}
.premium-feature-card:hover {
    transform: translateY(-2px);
    border-color: #86efac;
    box-shadow: 0 10px 30px rgba(0,0,0,0.02);
}
.feature-icon {
    font-size: 40px;
    color: #22c55e;
    margin-bottom: 20px;
}
.premium-feature-card h3 {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 10px;
}
.premium-feature-card p {
    font-size: 13.5px;
    color: #475569;
    line-height: 1.5;
    margin: 0;
}

/* Driver CTA */
.taxi-driver-cta-section {
    padding: 40px 0 100px;
}
.driver-cta-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 24px;
    padding: 48px;
    color: #fff;
}
.driver-cta-card h2 {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin-bottom: 12px;
}
.driver-cta-card p {
    font-size: 15px;
    color: #94a3b8;
    line-height: 1.6;
    margin: 0;
}
.driver-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 16px 28px;
    background: #22c55e;
    color: #000 !important;
    font-weight: 700;
    font-size: 14.5px;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
}
.driver-cta-btn:hover {
    background: #4ade80;
    transform: translateY(-1px);
}
</style>
<style>
.taxi-landing{background:#fff;color:#101828}
.taxi-hero-section{position:relative;overflow:hidden;background:linear-gradient(180deg,#f6fbf7 0%,#fff 82%)!important;padding:104px 0 76px!important}
.taxi-hero-section:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 84% 12%,rgba(34,197,94,.2),transparent 30%),linear-gradient(90deg,rgba(22,163,74,.1),transparent 42%);pointer-events:none}
.taxi-hero-section .container{position:relative;z-index:1}
.taxi-badge{border-radius:999px!important;background:#fff!important;border:1px solid rgba(22,163,74,.22)!important;color:#137b3b!important;font-weight:900!important;padding:9px 14px!important}
.taxi-main-title{font-family:Outfit,Inter,sans-serif!important;font-size:clamp(38px,5vw,64px)!important;line-height:1!important;font-weight:900!important;color:#101828!important;letter-spacing:0!important}
.taxi-main-title span{color:#16a34a!important;background:none!important;-webkit-text-fill-color:initial!important}
.taxi-hero-desc{font-size:18px!important;line-height:1.7!important;color:#475467!important;max-width:620px!important}
.download-card,.taxi-card-wrapper,.taxi-feature-card,.taxi-service-card,.my-rides-card{border-radius:8px!important;border:1px solid #e7eaee!important;box-shadow:0 18px 45px rgba(16,24,40,.08)!important}
.store-badge-btn,.taxi-btn-premium,.location-btn-premium{border-radius:8px!important;font-weight:900!important}
.taxi-btn-premium{background:#16a34a!important;box-shadow:0 14px 30px rgba(22,163,74,.2)!important}
.form-control-premium,.service-option-premium{border-radius:8px!important}
.taxi-section-title,.taxi-section-heading h2{font-family:Outfit,Inter,sans-serif!important;color:#101828!important;font-weight:900!important;letter-spacing:0!important}
@media(max-width:767px){.taxi-hero-section{padding:82px 0 56px!important}.taxi-main-title{font-size:38px!important}}
</style>

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script>
const taxiServices = @json($taxiServices->keyBy('id'));

let taxiRequest = {
    serviceId: null,
    pickupLat: null, pickupLng: null,
    destinationLat: null, destinationLng: null,
    distance: 0, duration: 0, fare: 0,
    requestId: null
};

window.addEventListener('load', function() {
    if (window.google && google.maps.places) {
        new google.maps.places.Autocomplete(document.getElementById('pickup-location'), {
            componentRestrictions: {country: 'pe'},
            fields: ['formatted_address', 'geometry']
        }).addListener('place_changed', function() {
            var place = this.getPlace();
            if (place.geometry) {
                taxiRequest.pickupLat = place.geometry.location.lat();
                taxiRequest.pickupLng = place.geometry.location.lng();
            }
        });

        new google.maps.places.Autocomplete(document.getElementById('destination-location'), {
            componentRestrictions: {country: 'pe'},
            fields: ['formatted_address', 'geometry']
        }).addListener('place_changed', function() {
            var place = this.getPlace();
            if (place.geometry) {
                taxiRequest.destinationLat = place.geometry.location.lat();
                taxiRequest.destinationLng = place.geometry.location.lng();
            }
        });
    }

    // Auto-detect location
    setTimeout(function() { loadSessionPickupLocation(); }, 500);

    // Service option click
    document.querySelectorAll('.service-option-premium').forEach(function(opt) {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.service-option-premium').forEach(function(o) { o.classList.remove('selected'); });
            opt.classList.add('selected');
            opt.querySelector('input').checked = true;
            
            // Recalculate fare for this service
            var serviceId = opt.getAttribute('data-id');
            var service = taxiServices[serviceId];
            if (service && taxiRequest.distance > 0) {
                var isIntercity = taxiRequest.distance > 30;
                var baseFare = parseFloat(isIntercity ? (service.intercity_base_fare || 0) : (service.city_base_fare || 0));
                var perKmRate = parseFloat(isIntercity ? (service.intercity_rate_per_km || service.intercity_recommend_fare || 0) : (service.city_rate_per_km || service.city_recommend_fare || 0));
                var minTripFare = parseFloat(isIntercity ? (service.intercity_min_trip_fare || 0) : (service.city_min_trip_fare || 0));
                taxiRequest.fare = Math.max(baseFare + (taxiRequest.distance * perKmRate), minTripFare);
                document.getElementById('fare-total').textContent = 'S/ ' + taxiRequest.fare.toFixed(2);
            }
        });
    });
    // Select first service by default
    var firstService = document.querySelector('.service-option-premium');
    if (firstService) firstService.classList.add('selected');
});

function loadSessionPickupLocation() {
    fetch('/location/get', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data && data.lat && data.lng && data.label && data.label !== "Tu ubicación") {
                taxiRequest.pickupLat = parseFloat(data.lat);
                taxiRequest.pickupLng = parseFloat(data.lng);
                document.getElementById('pickup-location').value = data.label;
            } else {
                useCurrentLocation(silent = true);
            }
        })
        .catch(function() {
            useCurrentLocation(silent = true);
        });
}

function useCurrentLocation(silent) {
    if (!navigator.geolocation) {
        if (!silent) alert('Tu navegador no soporta geolocalización');
        return;
    }
    navigator.geolocation.getCurrentPosition(function(pos) {
        taxiRequest.pickupLat = pos.coords.latitude;
        taxiRequest.pickupLng = pos.coords.longitude;
        if (window.google && google.maps) {
            new google.maps.Geocoder().geocode({
                location: {lat: pos.coords.latitude, lng: pos.coords.longitude}
            }, function(results) {
                if (results && results[0]) {
                    document.getElementById('pickup-location').value = results[0].formatted_address;
                }
            });
        }
    }, function() { if (!silent) alert('No pudimos acceder a tu ubicación'); });
}

function goToStep(stepId) {
    document.querySelectorAll('.taxi-step').forEach(function(s) { s.style.display = 'none'; });
    document.getElementById(stepId).style.display = 'block';
    document.querySelector('.taxi-card-wrapper').scrollIntoView({behavior: 'smooth'});
}

function stepCalculateFare() {
    var pickupInput = document.getElementById('pickup-location').value.trim();
    var destInput = document.getElementById('destination-location').value.trim();

    if (!pickupInput) { alert('Ingresa tu ubicación de recogida'); return; }
    if (!destInput) { alert('Ingresa tu destino'); return; }

    var btn = document.getElementById('btn-calculate');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Calculando...';

    var tasks = [];
    if (!taxiRequest.pickupLat) {
        tasks.push(new Promise(function(resolve) {
            new google.maps.Geocoder().geocode({address: pickupInput, componentRestrictions: {country: 'pe'}}, function(r) {
                if (r && r[0]) { taxiRequest.pickupLat = r[0].geometry.location.lat(); taxiRequest.pickupLng = r[0].geometry.location.lng(); }
                resolve();
            });
        }));
    }
    if (!taxiRequest.destinationLat) {
        tasks.push(new Promise(function(resolve) {
            new google.maps.Geocoder().geocode({address: destInput, componentRestrictions: {country: 'pe'}}, function(r) {
                if (r && r[0]) { taxiRequest.destinationLat = r[0].geometry.location.lat(); taxiRequest.destinationLng = r[0].geometry.location.lng(); }
                resolve();
            });
        }));
    }

    Promise.all(tasks).then(function() {
        var serviceId = document.querySelector('input[name="service_id"]:checked')?.value || Object.keys(taxiServices)[0];
        var service = taxiServices[serviceId];
        if (!service) { alert('Servicio no válido'); btn.disabled = false; btn.innerHTML = '<i class="las la-calculator"></i> Calcular Tarifa'; return; }
        taxiRequest.serviceId = serviceId;

        new google.maps.DistanceMatrixService().getDistanceMatrix({
            origins: [{lat: taxiRequest.pickupLat, lng: taxiRequest.pickupLng}],
            destinations: [{lat: taxiRequest.destinationLat, lng: taxiRequest.destinationLng}],
            travelMode: 'DRIVING'
        }, function(response) {
            var el = response.rows[0].elements[0];
            if (el.status !== 'OK') { alert('No pudimos calcular la ruta. Verifica las direcciones.'); btn.disabled = false; btn.innerHTML = '<i class="las la-calculator"></i> Calcular Tarifa'; return; }

            taxiRequest.distance = el.distance.value / 1000;
            taxiRequest.duration = Math.ceil(el.duration.value / 60);

            var isIntercity = taxiRequest.distance > 30;
            var baseFare = parseFloat(isIntercity ? (service.intercity_base_fare || 0) : (service.city_base_fare || 0));
            var perKmRate = parseFloat(isIntercity ? (service.intercity_rate_per_km || service.intercity_recommend_fare || 0) : (service.city_rate_per_km || service.city_recommend_fare || 0));
            var minTripFare = parseFloat(isIntercity ? (service.intercity_min_trip_fare || 0) : (service.city_min_trip_fare || 0));
            taxiRequest.fare = Math.max(baseFare + (taxiRequest.distance * perKmRate), minTripFare);

            document.getElementById('fare-distance').textContent = taxiRequest.distance.toFixed(2) + ' km';
            document.getElementById('fare-duration').textContent = taxiRequest.duration + ' min';
            document.getElementById('fare-total').textContent = 'S/ ' + taxiRequest.fare.toFixed(2);

            goToStep('step-fare');
            btn.disabled = false;
            btn.innerHTML = '<i class="las la-calculator"></i> Calcular Tarifa';
        });
    });
}

function submitTaxiRequest() {
    const isLoggedIn = @json(auth()->check());
    if (!isLoggedIn) {
        if (typeof showLoginModal === 'function') {
            showLoginModal();
        } else {
            alert('Por favor inicia sesión para continuar.');
        }
        return;
    }

    if (!document.getElementById('agree-terms').checked) {
        alert('Debes aceptar los términos y condiciones');
        return;
    }

    var btn = document.getElementById('btn-search');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Buscando...';

    var serviceId = document.querySelector('input[name="service_id"]:checked')?.value;
    var service = taxiServices[serviceId];

    fetch('{{ route("service.request") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            service_type: 'taxi',
            service_id: parseInt(serviceId),
            name: '{{ addslashes(auth()->user()->fullname ?? "") }}',
            phone: '{{ addslashes(auth()->user()->mobile ?? "") }}',
            pickup: document.getElementById('pickup-location').value,
            destination: document.getElementById('destination-location').value,
            pickup_lat: taxiRequest.pickupLat,
            pickup_lng: taxiRequest.pickupLng,
            destination_lat: taxiRequest.destinationLat,
            destination_lng: taxiRequest.destinationLng,
            distance: taxiRequest.distance,
            duration: taxiRequest.duration,
            notes: 'Tarifa: S/ ' + taxiRequest.fare.toFixed(2) + ' | Distancia: ' + taxiRequest.distance.toFixed(1) + ' km | Servicio: ' + (service?.name || '')
        })
    })
    .then(function(r) {
        if (!r.ok) throw new Error('Error del servidor');
        return r.json();
    })
    .then(function(data) {
        taxiRequest.requestId = data.data?.ride?.ride_id;

        goToStep('step-tracking');

        if (taxiRequest.requestId) {
            document.getElementById('tracking-loading').querySelector('p').textContent = 'Buscando el mejor conductor para ti...';
            pollDriverAssignment(taxiRequest.requestId);
        } else {
            document.getElementById('tracking-loading').querySelector('p').textContent = 'Solicitud recibida. Te notificaremos cuando un conductor acepte.';
        }
    })
    .catch(function(err) {
        console.error('Error:', err);
        alert('Error al enviar la solicitud. Intenta de nuevo.');
        btn.disabled = false;
        btn.innerHTML = '<i class="las la-search"></i> Buscar Taxi';
    });
}

function pollDriverAssignment(rideId) {
    var attempts = 0;
    var maxAttempts = 60;

    var interval = setInterval(function() {
        attempts++;
        fetch('/ride/' + rideId + '/status', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.driver && data.status !== 0) {
                clearInterval(interval);
                showDriverInfo(data.driver);
            }
            if (attempts >= maxAttempts) {
                clearInterval(interval);
                document.getElementById('tracking-loading').querySelector('p').textContent = 'Aún buscando conductor. Te notificaremos cuando uno acepte.';
            }
        })
        .catch(function() {
            if (attempts >= maxAttempts) clearInterval(interval);
        });
    }, 3000);
}

function showDriverInfo(driver) {
    document.getElementById('tracking-loading').style.display = 'none';
    var di = document.getElementById('driver-info');
    di.style.display = 'block';
    document.getElementById('driver-photo').src = driver.image || '';
    document.getElementById('driver-name').textContent = driver.name;
    document.getElementById('driver-vehicle').textContent = driver.dial_code + ' ' + driver.phone + ' · ' + driver.vehicle;
    document.getElementById('driver-rating').textContent = (driver.rating || 4.8).toFixed(1);
    document.getElementById('driver-eta').textContent = '¡Conductor asignado!';
}

function cancelTaxiRequest() {
    if (confirm('¿Cancelar solicitud de taxi?')) {
        goToStep('step-location');
        document.getElementById('agree-terms').checked = false;
    }
}

if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Load ride history
(function(){
    fetch('/ride/user-rides', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var rides = data.rides || [];
            var section = document.getElementById('my-rides-section');
            var list = document.getElementById('my-rides-list');
            if (!section || rides.length === 0) return;
            section.style.display = 'block';
            var statusMap = { 0: ['Pendiente','color:#f59e0b;background:#fef3c7'], 1: ['Aceptado','color:#16a34a;background:#dcfce7'], 2: ['En curso','color:#3b82f6;background:#dbeafe'], 3: ['Finalizado','color:#6b7280;background:#f3f4f6'], 5: ['Cancelado','color:#dc2626;background:#fee2e2'] };
            rides.forEach(function(ride) {
                var s = statusMap[ride.status] || ['Desconocido','color:#6b7280;background:#f3f4f6'];
                var row = document.createElement('div');
                row.style.cssText = 'display:flex;align-items:center;gap:14px;padding:16px;background:#fff;border-radius:14px;border:1px solid #e0eee2';
                row.innerHTML = '<div style="width:44px;height:44px;border-radius:12px;background:#f0fdf4;display:grid;place-items:center;font-size:20px;color:#16a34a;flex-shrink:0"><i class="las la-route"></i></div>'
                    + '<div style="flex:1;min-width:0"><strong style="font-size:14px;color:#1a2e1a;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + (ride.destination || 'Sin destino') + '</strong>'
                    + '<small style="color:#68736c">' + (ride.service || '') + ' &middot; ' + ride.distance.toFixed(1) + ' km &middot; ' + ride.created_at + '</small></div>'
                    + '<div style="text-align:right;flex-shrink:0"><div style="font-size:15px;font-weight:800;color:#16a34a">S/ ' + ride.amount.toFixed(2) + '</div>'
                    + '<span style="display:inline-block;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;' + s[1] + '">' + s[0] + '</span></div>';
                list.appendChild(row);
            });
        }).catch(function(){});
})();
</script>
@endpush
@endsection
