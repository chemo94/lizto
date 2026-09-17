@extends('admin.layouts.app')

@push('style')
<style>
    /* ═════════════════════════════════════════════════════════════
       Sneat Theme Refined Design Tokens for Delivery Request
       ═════════════════════════════════════════════════════════════ */
    .sneat-header-card {
        background: #ffffff !important;
        border-radius: 12px !important;
        border: 1px solid #d9dee3 !important;
        box-shadow: 0 2px 10px rgba(67, 89, 113, 0.08) !important;
        margin-bottom: 1.25rem !important;
        padding: 1.2rem 1.5rem !important;
    }
    .sneat-header-title {
        color: #2b2c40 !important;
        font-weight: 700 !important;
        font-size: 1.35rem !important;
        margin: 0 !important;
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }
    .sneat-header-subtitle {
        color: #697a8d !important;
        font-size: 0.88rem !important;
        margin-top: 0.25rem;
    }

    /* Sneat Stat Cards */
    .sneat-stat-card {
        background: #ffffff !important;
        border: 1px solid #e0e4e8 !important;
        border-radius: 10px !important;
        padding: 1rem 1.15rem !important;
        box-shadow: 0 2px 6px rgba(67, 89, 113, 0.05) !important;
        display: flex;
        align-items: center;
        gap: 0.9rem;
        margin-bottom: 1.25rem;
        transition: transform 0.15s ease;
    }
    .sneat-stat-card:hover {
        transform: translateY(-2px);
    }
    .sneat-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .sneat-stat-title {
        color: #8592a3 !important;
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 0.1rem !important;
    }
    .sneat-stat-value {
        color: #2b2c40 !important;
        font-size: 1.35rem !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }

    /* Sneat Cards */
    .sneat-card {
        background: #ffffff !important;
        border-radius: 10px !important;
        border: 1px solid #e0e4e8 !important;
        box-shadow: 0 2px 6px rgba(67, 89, 113, 0.05) !important;
        margin-bottom: 1.25rem !important;
        overflow: hidden;
    }
    .sneat-card-header {
        padding: 1rem 1.35rem !important;
        border-bottom: 1px solid #eceef1 !important;
        background: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .sneat-card-header h6 {
        margin: 0;
        font-size: 0.96rem !important;
        font-weight: 700 !important;
        color: #384551 !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .sneat-card-header h6 i {
        color: #696cff !important;
        font-size: 1.15rem;
    }
    .sneat-card-body {
        padding: 1.25rem 1.35rem !important;
    }

    /* Section Subheadings */
    .form-section-title {
        color: #696cff;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 1.1rem 0 0.65rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid #f0f2f5;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .form-section-title:first-child {
        margin-top: 0;
    }

    /* Form Inputs */
    .form-label-sneat {
        color: #566a7f !important;
        font-weight: 600 !important;
        font-size: 0.84rem !important;
        margin-bottom: 0.35rem !important;
        display: block;
    }
    .form-control-sneat, .form-select-sneat {
        border: 1px solid #d9dee3 !important;
        color: #435971 !important;
        border-radius: 6px !important;
        padding: 0.52rem 0.85rem !important;
        font-size: 0.9rem !important;
        background-color: #fff !important;
        width: 100%;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    }
    .form-control-sneat:focus, .form-select-sneat:focus {
        border-color: #696cff !important;
        box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.14) !important;
        outline: none !important;
    }

    /* Buttons */
    .btn-sneat-submit {
        background: #696cff !important;
        background-color: #696cff !important;
        border: 1px solid #696cff !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 0.92rem !important;
        padding: 0.65rem 1.4rem !important;
        border-radius: 6px !important;
        box-shadow: 0 3px 10px rgba(105, 108, 255, 0.4) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        cursor: pointer !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
    }
    .btn-sneat-submit:hover {
        background: #5f61e6 !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(105, 108, 255, 0.55) !important;
    }
    .btn-sneat-submit i, .btn-sneat-submit span { color: #ffffff !important; }

    .btn-sneat-cancel {
        background-color: #ffffff !important;
        border: 1px solid #d9dee3 !important;
        color: #566a7f !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        padding: 0.5rem 1rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.4rem !important;
        text-decoration: none !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
    }
    .btn-sneat-cancel:hover {
        background-color: #f5f5f9 !important;
        border-color: #b4bdc6 !important;
        color: #384551 !important;
    }

    .btn-map-picker {
        background: #ecebff !important;
        border: 1px solid rgba(105, 108, 255, 0.3) !important;
        color: #5f61e6 !important;
        font-weight: 600 !important;
        font-size: 0.78rem !important;
        padding: 0.25rem 0.65rem !important;
        border-radius: 5px !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.3rem !important;
        transition: all 0.15s ease !important;
    }
    .btn-map-picker:hover {
        background: #696cff !important;
        color: #ffffff !important;
    }

    /* Badges */
    .badge-sneat {
        font-size: 0.76rem !important;
        font-weight: 700 !important;
        padding: 0.32rem 0.65rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.3rem !important;
    }
    .badge-sneat-primary { background-color: #ecebff !important; color: #5f61e6 !important; border: 1px solid rgba(105, 108, 255, 0.3) !important; }
    .badge-sneat-success { background-color: #e8fadf !important; color: #2e7d32 !important; border: 1px solid rgba(46, 125, 50, 0.3) !important; }
    .badge-sneat-warning { background-color: #fff4e5 !important; color: #d85a00 !important; border: 1px solid rgba(216, 90, 0, 0.3) !important; }

    /* Tables */
    .sneat-fee-table {
        width: 100%;
        margin-bottom: 0;
    }
    .sneat-fee-table tr td {
        padding: 0.6rem 0;
        border-bottom: 1px dashed #eceef1;
        color: #566a7f;
        font-size: 0.88rem;
    }
    .sneat-fee-table tr:last-child td {
        border-bottom: none;
    }

    /* Modals for Map */
    .map-selector-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(4px);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .map-selector-overlay.active {
        opacity: 1;
        pointer-events: all;
    }
    .map-selector-content {
        background: #fff;
        border-radius: 14px;
        padding: 22px;
        max-width: 650px;
        width: 92%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .pac-container {
        z-index: 9999999 !important;
    }

    /* Pulse animations for inquiry */
    .courier-inquiry-animation {
        width: 82px; height: 82px; margin: 0 auto; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; background: #696cff; position: relative;
        animation: courierInquiryPulse 1.4s ease-in-out infinite;
    }
    .courier-inquiry-animation::before, .courier-inquiry-animation::after {
        content: ''; position: absolute; inset: -9px; border-radius: 50%;
        border: 2px solid rgba(105, 108, 255, .35); animation: courierInquiryRing 1.4s ease-out infinite;
    }
    .courier-inquiry-animation::after { animation-delay: .7s; }
    @keyframes courierInquiryPulse { 50% { transform: scale(.94); } }
    @keyframes courierInquiryRing { from { transform: scale(.75); opacity: 1; } to { transform: scale(1.25); opacity: 0; } }
</style>
@endpush

@section('panel')
<!-- Sneat Header Card -->
<div class="sneat-header-card d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h4 class="sneat-header-title">
            <i class="las la-shipping-fast text--primary"></i>
            Solicitar Envío Express
            <span class="badge-sneat badge-sneat-primary">
                <i class="las la-motorcycle"></i> Despacho Administrativo
            </span>
        </h4>
        <div class="sneat-header-subtitle">
            Crea una solicitud de delivery inmediata con cotización automática por distancia y tiempo.
        </div>
    </div>
    <div>
        <a href="{{ route('admin.delivery.favors') }}" class="btn-sneat-cancel">
            <i class="las la-list"></i> Ver Envíos Solicitados
        </a>
    </div>
</div>

<!-- 4 Sneat Quick KPI Cards -->
<div class="row">
    <div class="col-xl-3 col-sm-6">
        <div class="sneat-stat-card">
            <div class="sneat-stat-icon" style="background: #e8fadf; color: #2e7d32;">
                <i class="las la-motorcycle"></i>
            </div>
            <div>
                <div class="sneat-stat-title">Repartidores Activos</div>
                <div class="sneat-stat-value" id="active-drivers">{{ $activeDrivers }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sneat-stat-card">
            <div class="sneat-stat-icon" style="background: #ecebff; color: #5f61e6;">
                <i class="las la-tag"></i>
            </div>
            <div>
                <div class="sneat-stat-title">Tarifa Mínima</div>
                <div class="sneat-stat-value">S/ {{ number_format(gs('delivery_min_fee') ?? 4, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sneat-stat-card">
            <div class="sneat-stat-icon" style="background: #e1f5fe; color: #0277bd;">
                <i class="las la-route"></i>
            </div>
            <div>
                <div class="sneat-stat-title">Km Base Incluidos</div>
                <div class="sneat-stat-value">{{ gs('delivery_base_km') ?? 3 }} km</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sneat-stat-card">
            <div class="sneat-stat-icon" style="background: #fff4e5; color: #d85a00;">
                <i class="las la-tachometer-alt"></i>
            </div>
            <div>
                <div class="sneat-stat-title">Km Extra</div>
                <div class="sneat-stat-value">S/ {{ number_format(gs('delivery_fee_per_km') ?? 1.5, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Form & Dispatch Flow (7 cols) -->
    <div class="col-lg-7">
        <!-- Main Form Card -->
        <div class="sneat-card" id="request-card">
            <div class="sneat-card-header">
                <h6><i class="las la-paper-plane"></i> Formulario de Despacho</h6>
                <span class="badge-sneat badge-sneat-neutral">Tarapoto & Alrededores</span>
            </div>
            <div class="sneat-card-body">
                <form id="request-form" method="POST" action="{{ route('admin.delivery.request.submit') }}">
                    @csrf

                    <!-- Section 1: Origin -->
                    <div class="form-section-title">
                        <i class="las la-map-marker-alt"></i> 1. Punto de Recogida (Origen)
                    </div>

                    <div class="mb-3">
                        <label class="form-label-sneat">Origen de la Solicitud <span class="text-danger">*</span></label>
                        <select class="form-select-sneat" name="store_id" id="store-select" required>
                            <option value="">-- Selecciona una tienda registrada o recojo libre --</option>
                            <option value="custom" {{ old('request_mode') === 'custom' ? 'selected' : '' }}>
                                📍 Compra o recojo libre (sin tienda registrada)
                            </option>
                            @foreach($stores as $store)
                            <option value="{{ $store->id }}"
                                data-address="{{ $store->address }}"
                                data-lat="{{ $store->latitude }}"
                                data-lng="{{ $store->longitude }}"
                                {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="request_mode" id="request-mode" value="{{ old('request_mode', 'store') }}">
                    </div>

                    <div class="mb-3" id="custom-origin-name-group" style="display:none;">
                        <label class="form-label-sneat">Nombre del lugar de compra o recojo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control-sneat" name="custom_store_name" id="custom-store-name"
                            value="{{ old('custom_store_name') }}" placeholder="Ej: Mercado Central, Farmacia Inkafarma, domicilio remitente..." maxlength="255">
                    </div>

                    <div class="mb-3" id="store-info" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label-sneat mb-0">Dirección exacta de recogida <span class="text-danger">*</span></label>
                            <button type="button" class="btn-map-picker" id="select-pickup-map-btn">
                                <i class="las la-map-marked-alt"></i> Fijar en mapa
                            </button>
                        </div>
                        <input type="text" class="form-control-sneat" name="pickup_address" id="store-address"
                            value="{{ old('pickup_address') }}" placeholder="Busca dirección o referencia con Google Maps..." required autocomplete="off">
                        <small class="text-muted mt-1 d-block" style="font-size:0.75rem;">Coordenadas: <span id="store-coords">--</span></small>
                    </div>

                    <!-- Section 2: Driver -->
                    <div class="form-section-title">
                        <i class="las la-user-astronaut"></i> 2. Asignación de Repartidor
                    </div>

                    <div class="mb-3">
                        <label class="form-label-sneat">Destinatario del Despacho</label>
                        <select class="form-select-sneat" name="driver_id" id="driver-select">
                            <option value="all">📢 Enviar solicitud a TODOS los repartidores disponibles</option>
                            @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                👤 {{ $driver->firstname }} {{ $driver->lastname }} ({{ $driver->username }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section 3: Destination -->
                    <div class="form-section-title">
                        <i class="las la-map-pin"></i> 3. Destino y Destinatario
                    </div>

                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label-sneat">Nombre del Cliente / Destinatario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control-sneat" name="recipient_name"
                                value="{{ old('recipient_name') }}" placeholder="Nombre y apellido" required maxlength="255">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label-sneat">Teléfono de Contacto</label>
                            <input type="text" class="form-control-sneat" name="recipient_phone"
                                value="{{ old('recipient_phone') }}" placeholder="+51 999 888 777">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label-sneat mb-0">Dirección de Destino <span class="text-danger">*</span></label>
                            <button type="button" class="btn-map-picker" id="select-dest-map-btn">
                                <i class="las la-map-marked-alt"></i> Fijar en mapa
                            </button>
                        </div>
                        <input type="text" class="form-control-sneat" name="delivery_address" id="dest-address"
                            value="{{ old('delivery_address') }}" placeholder="Calle, número, urbanización o referencia..." required autocomplete="off">
                    </div>

                    <input type="hidden" name="pickup_lat" id="pickup-lat" value="{{ old('pickup_lat') }}">
                    <input type="hidden" name="pickup_lng" id="pickup-lng" value="{{ old('pickup_lng') }}">
                    <input type="hidden" name="delivery_lat" id="delivery-lat" value="{{ old('delivery_lat') }}">
                    <input type="hidden" name="delivery_lng" id="delivery-lng" value="{{ old('delivery_lng') }}">

                    <!-- Section 4: Details & Additional Charge -->
                    <div class="form-section-title">
                        <i class="las la-box"></i> 4. Contenido del Paquete y Cobros
                    </div>

                    <div class="mb-3">
                        <label class="form-label-sneat">Descripción de la Entrega <span class="text-danger">*</span></label>
                        <textarea class="form-control-sneat" name="description" rows="2"
                            placeholder="Ej: Recoger paquete sellado en recepción y entregar en el departamento 301..." required>{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-sneat">Cargo Adicional / Monto de Compra Estimado (S/)</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#f5f5f9; border-color:#d9dee3; color:#566a7f; font-weight:700;">S/</span>
                            <input type="number" class="form-control-sneat" name="estimated_amount" id="additional-charge"
                                value="{{ old('estimated_amount', '0.00') }}" min="0" max="999999.99" step="0.01" inputmode="decimal">
                        </div>
                        <small class="text-muted" style="font-size:0.75rem;">Opcional. Se sumará al costo del delivery y figurará en el total a cobrar.</small>
                    </div>

                    <button type="submit" class="btn-sneat-submit w-100" id="submit-btn">
                        <i class="las la-paper-plane"></i> Enviar solicitud a repartidores
                    </button>
                </form>
            </div>
        </div>

        <!-- Waiting / Polling State Card -->
        <div class="sneat-card" id="waiting-card" style="display:none;">
            <div class="sneat-card-body text-center py-5">
                <div id="waiting-animation" class="mb-3">
                    <div class="spinner-grow text-primary" style="width: 3.5rem; height: 3.5rem;" role="status">
                        <span class="sr-only">Buscando...</span>
                    </div>
                </div>
                <h5 id="waiting-title" style="color:#2b2c40; font-weight:700;">Buscando repartidor disponible...</h5>
                <p class="text-muted" id="waiting-subtitle" style="font-size:0.88rem;">Solicitud <strong id="waiting-order-no">--</strong></p>
                <div id="waiting-timer" class="mt-3">
                    <span class="badge-sneat badge-sneat-primary" style="font-size:1rem; padding:0.45rem 1rem;">
                        <i class="las la-clock"></i> <span id="elapsed-time">0:00</span>
                    </span>
                </div>
                <div class="mt-4">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="cancel-btn" style="display:none; border-radius:6px;">
                        <i class="las la-times"></i> Cancelar Solicitud
                    </button>
                </div>
            </div>
        </div>

        <!-- Driver Found State Card -->
        <div class="sneat-card" id="driver-card" style="display:none;">
            <div class="sneat-card-body text-center py-4">
                <div id="driver-found-animation" class="mb-3">
                    <div style="width:80px;height:80px;border-radius:50%;background:#e8fadf;display:flex;align-items:center;justify-content:center;margin:0 auto;border:3px solid #2e7d32;">
                        <i class="las la-check-circle" style="font-size:44px;color:#2e7d32;"></i>
                    </div>
                </div>
                <h5 style="color:#2e7d32; font-weight:700;">¡Repartidor Encontrado y Asignado!</h5>
                <p class="text-muted" style="font-size:0.88rem;">Solicitud <strong id="driver-order-no">--</strong></p>

                <div class="mt-3" style="max-width:380px; margin:0 auto;">
                    <div class="d-flex align-items-center p-3 rounded text-left" style="background:#f8fdf6; border:1px solid #c8e6c9;">
                        <div id="driver-avatar" style="width:56px;height:56px;border-radius:50%;background:#e8fadf;display:flex;align-items:center;justify-content:center;margin-right:14px;flex-shrink:0;overflow:hidden;border:2px solid #2e7d32;">
                            <i class="las la-user" style="font-size:24px;color:#2e7d32;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:1rem; color:#2b2c40;" id="driver-name">--</div>
                            <div class="text-muted" style="font-size:0.85rem;" id="driver-phone">--</div>
                            <div class="mt-1" id="driver-distance-badge" style="display:none;">
                                <span class="badge-sneat badge-sneat-success" style="font-size:0.72rem;">
                                    <i class="las la-route"></i> <span id="driver-distance">--</span> km
                                </span>
                                <span class="badge-sneat badge-sneat-primary" style="font-size:0.72rem;">
                                    <i class="las la-clock"></i> ~<span id="driver-time">--</span> min
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="#" class="btn-sneat-submit" id="view-detail-btn" target="_blank">
                        <i class="las la-eye"></i> Ver Detalles del Envío
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Live Pricing & System Rates (5 cols) -->
    <div class="col-lg-5">
        <!-- Live Fee Breakdown Card -->
        <div class="sneat-card">
            <div class="sneat-card-header">
                <h6><i class="las la-calculator"></i> Cotización en Tiempo Real</h6>
                <span class="badge-sneat badge-sneat-success">Cálculo Automático</span>
            </div>
            <div class="sneat-card-body">
                <div id="fee-breakdown">
                    <div class="text-center py-4 text-muted">
                        <i class="las la-map-marked-alt" style="font-size:2.8rem; color:#cdd4dc; display:block; margin-bottom:0.5rem;"></i>
                        <p style="font-size:0.88rem; margin:0;">Selecciona el punto de recogida y la dirección de destino para calcular la tarifa.</p>
                    </div>
                </div>

                <div id="fee-details" style="display:none;">
                    <!-- Short Distance Warning Alert -->
                    <div id="short-distance-warning" style="display:none; margin-bottom:14px; padding:12px; background:#fff8eb; border:1px solid #ffd28c; border-radius:8px; font-size:0.82rem; color:#d85a00;">
                        <div style="font-weight:700; margin-bottom:2px;">
                            <i class="las la-exclamation-triangle"></i> Distancia muy corta (<span id="short-dist-km">--</span> km)
                        </div>
                        <span>Aplica automáticamente la <strong>tarifa corta fija de S/ 4.00</strong>.</span>
                    </div>

                    <table class="sneat-fee-table">
                        <tr>
                            <td>Distancia calculada:</td>
                            <td class="text-end font-weight-bold" style="color:#2b2c40;"><strong id="fee-distance">--</strong> km</td>
                        </tr>
                        <tr>
                            <td>Tarifa base:</td>
                            <td class="text-end font-weight-bold" style="color:#2b2c40;">S/ <span id="fee-base">--</span></td>
                        </tr>
                        <tr>
                            <td>Distancia adicional:</td>
                            <td class="text-end font-weight-bold" style="color:#2b2c40;">S/ <span id="fee-distance-fee">--</span></td>
                        </tr>
                        <tr>
                            <td>Tiempo estimado:</td>
                            <td class="text-end font-weight-bold" style="color:#2b2c40;"><span id="fee-time-min">--</span> min (S/ <span id="fee-time">--</span>)</td>
                        </tr>
                        <tr id="surge-row" style="display:none;">
                            <td style="color:#d85a00;">Demanda actual (surge):</td>
                            <td class="text-end font-weight-bold" style="color:#d85a00;">×<span id="fee-surge">--</span></td>
                        </tr>
                        <tr>
                            <td>Cargo adicional:</td>
                            <td class="text-end font-weight-bold" style="color:#2b2c40;">S/ <span id="fee-additional">0.00</span></td>
                        </tr>
                        <tr style="border-top:2px solid #e7eaf0;">
                            <td style="font-size:1.05rem; font-weight:800; color:#2b2c40; padding-top:0.8rem;">TOTAL ESTIMADO:</td>
                            <td class="text-end" style="padding-top:0.8rem;">
                                <strong class="text-primary" style="font-size:1.45rem;">S/ <span id="fee-total">--</span></strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- System Rates Info Card -->
        <div class="sneat-card">
            <div class="sneat-card-header">
                <h6><i class="las la-info-circle"></i> Tarifario del Sistema</h6>
            </div>
            <div class="sneat-card-body p-0">
                <ul class="list-group list-group-flush" style="font-size:0.88rem;">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Tarifa mínima fija</span>
                        <strong class="text-dark">S/ {{ number_format(gs('delivery_min_fee') ?? 4, 2) }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Kilómetros incluidos en base</span>
                        <strong class="text-dark">{{ gs('delivery_base_km') ?? 3 }} km</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Tarifa por kilómetro extra</span>
                        <strong class="text-dark">S/ {{ number_format(gs('delivery_fee_per_km') ?? 1.5, 2) }}/km</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Tarifa por minuto en ruta</span>
                        <strong class="text-dark">S/ {{ number_format(gs('delivery_time_rate') ?? 0.30, 2) }}/min</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted">Multiplicador de demanda (Surge)</span>
                        <strong class="text-dark">×{{ number_format(gs('delivery_surge') ?? 1.00, 2) }}</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Map Selector Modal (Destino) -->
<div id="map-selector-modal" class="map-selector-overlay">
    <div class="map-selector-content">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0" style="font-weight:700; color:#2b2c40; display:flex; align-items:center; gap:8px;">
                <i class="las la-map-marked-alt text--primary font-22"></i> Seleccionar Ubicación de Destino
            </h5>
            <button type="button" class="btn-close" id="close-map-selector-btn-top" onclick="document.getElementById('map-selector-modal').classList.remove('active')"></button>
        </div>
        <p class="text-muted mb-2" style="font-size: 13px;">Escribe en el buscador o arrastra el marcador para fijar el destino exacto.</p>
        <div class="mb-3">
            <input type="text" id="modal-admin-dest-search" class="form-control-sneat" placeholder="🔍 Buscar dirección o referencia en Tarapoto..." autocomplete="off">
        </div>
        <div id="selector-map" style="width: 100%; height: 380px; border-radius: 10px; margin-bottom: 16px; border: 1px solid #d9dee3; background: #e9ecef;"></div>
        <div class="d-flex justify-content-end" style="gap: 10px;">
            <button type="button" class="btn-sneat-cancel" id="close-map-selector-btn">Cancelar</button>
            <button type="button" class="btn-sneat-submit" id="confirm-map-selector-btn">Confirmar Ubicación</button>
        </div>
    </div>
</div>

<!-- Map Selector Modal (Recogida / Pickup) -->
<div id="pickup-map-modal" class="map-selector-overlay">
    <div class="map-selector-content">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0" style="font-weight:700; color:#2b2c40; display:flex; align-items:center; gap:8px;">
                <i class="las la-store text--success font-22"></i> Seleccionar Dirección de Recogida
            </h5>
            <button type="button" class="btn-close" id="close-pickup-map-btn-top" onclick="document.getElementById('pickup-map-modal').classList.remove('active')"></button>
        </div>
        <p class="text-muted mb-2" style="font-size: 13px;">Escribe en el buscador o arrastra el marcador para fijar el punto de recogida.</p>
        <div class="mb-3">
            <input type="text" id="modal-admin-pickup-search" class="form-control-sneat" placeholder="🔍 Buscar dirección o referencia de recogida..." autocomplete="off">
        </div>
        <div id="pickup-selector-map" style="width: 100%; height: 380px; border-radius: 10px; margin-bottom: 16px; border: 1px solid #d9dee3; background: #e9ecef;"></div>
        <div class="d-flex justify-content-end" style="gap: 10px;">
            <button type="button" class="btn-sneat-cancel" id="close-pickup-map-btn">Cancelar</button>
            <button type="button" class="btn-sneat-submit" id="confirm-pickup-map-btn">Confirmar Recogida</button>
        </div>
    </div>
</div>

@push('breadcrumb-plugins')
<a href="{{ route('admin.delivery.favors') }}" class="btn-sneat-cancel">
    <i class="las la-list"></i> Ver envíos solicitados
</a>
@endpush

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script>
(function() {
    var storeSelect = document.getElementById('store-select');
    var requestMode = document.getElementById('request-mode');
    var customOriginNameGroup = document.getElementById('custom-origin-name-group');
    var customStoreName = document.getElementById('custom-store-name');
    var storeInfo = document.getElementById('store-info');
    var storeAddr = document.getElementById('store-address');
    var storeCoords = document.getElementById('store-coords');
    var destAddr = document.getElementById('dest-address');
    var feeBreakdown = document.getElementById('fee-breakdown');
    var feeDetails = document.getElementById('fee-details');
    var requestForm = document.getElementById('request-form');
    var requestCard = document.getElementById('request-card');
    var waitingCard = document.getElementById('waiting-card');
    var driverCard = document.getElementById('driver-card');
    var submitBtn = document.getElementById('submit-btn');
    var cancelBtn = document.getElementById('cancel-btn');
    var additionalChargeInput = document.getElementById('additional-charge');
    var baseDeliveryFee = 0;

    var pickupLat = parseFloat(document.getElementById('pickup-lat').value) || null;
    var pickupLng = parseFloat(document.getElementById('pickup-lng').value) || null;
    var deliveryLat = parseFloat(document.getElementById('delivery-lat').value) || null;
    var deliveryLng = parseFloat(document.getElementById('delivery-lng').value) || null;
    var pollingInterval = null;
    var pollStartTime = null;
    var maxPollTime = 180000; // 3 minutes
    var timerInterval = null;

    function formatFeeDisplay(val) {
        var num = parseFloat(val) || 0;
        var floor = Math.floor(num);
        var dec = Math.round((num - floor) * 100) / 100;
        if (dec >= 0.46 && dec <= 0.54) {
            return (floor + 0.50).toFixed(2);
        } else if (dec > 0.54) {
            return Math.ceil(num).toString();
        } else {
            return floor.toString();
        }
    }

    function refreshEstimatedTotal() {
        var additional = Math.max(0, parseFloat(additionalChargeInput.value) || 0);
        var total = baseDeliveryFee + additional;
        document.getElementById('fee-additional').textContent = additional > 0 ? formatFeeDisplay(additional) : '0.00';
        if (baseDeliveryFee > 0) {
            document.getElementById('fee-total').textContent = formatFeeDisplay(total);
            submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Enviar solicitud a repartidores · S/ ' + formatFeeDisplay(total);
        }
    }

    additionalChargeInput.addEventListener('input', refreshEstimatedTotal);

    // Store selection
    storeSelect.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var isCustom = opt.value === 'custom';
        var previousMode = requestMode.value;
        requestMode.value = isCustom ? 'custom' : 'store';
        customOriginNameGroup.style.display = isCustom ? 'block' : 'none';
        customStoreName.required = isCustom;

        if (isCustom) {
            storeInfo.style.display = 'block';
            if (previousMode !== 'custom') {
                storeAddr.value = '';
                pickupLat = null;
                pickupLng = null;
                document.getElementById('pickup-lat').value = '';
                document.getElementById('pickup-lng').value = '';
            }
            storeCoords.textContent = pickupLat && pickupLng
                ? pickupLat.toFixed(6) + ', ' + pickupLng.toFixed(6)
                : '--';
        } else if (opt.value) {
            storeInfo.style.display = 'block';
            storeAddr.value = opt.dataset.address || '';
            storeCoords.textContent = (opt.dataset.lat || '--') + ', ' + (opt.dataset.lng || '--');
            pickupLat = parseFloat(opt.dataset.lat) || null;
            pickupLng = parseFloat(opt.dataset.lng) || null;
            document.getElementById('pickup-lat').value = pickupLat || '';
            document.getElementById('pickup-lng').value = pickupLng || '';
        } else {
            storeInfo.style.display = 'none';
            customOriginNameGroup.style.display = 'none';
            customStoreName.required = false;
            pickupLat = null; pickupLng = null;
            document.getElementById('pickup-lat').value = '';
            document.getElementById('pickup-lng').value = '';
            storeCoords.textContent = '--';
        }
        calculateFee();
    });

    // Reset pickup coords when user types manually in pickup address
    storeAddr.addEventListener('input', function() {
        pickupLat = null; pickupLng = null;
        document.getElementById('pickup-lat').value = '';
        document.getElementById('pickup-lng').value = '';
        storeCoords.textContent = '--';
    });

    // Trigger on old value
    if (storeSelect.value) storeSelect.dispatchEvent(new Event('change'));

    // Google Maps autocomplete for destination AND pickup
    function initAutocomplete() {
        if (!window.google || !google.maps || !google.maps.places) {
            setTimeout(initAutocomplete, 300);
            return;
        }

        // Destination autocomplete
        var autocomplete = new google.maps.places.Autocomplete(destAddr, {
            componentRestrictions: { country: 'pe' }
        });
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (place.geometry) {
                deliveryLat = place.geometry.location.lat();
                deliveryLng = place.geometry.location.lng();
                document.getElementById('delivery-lat').value = deliveryLat;
                document.getElementById('delivery-lng').value = deliveryLng;
                calculateFee();
            }
        });

        // Pickup autocomplete
        var pickupAutocomplete = new google.maps.places.Autocomplete(storeAddr, {
            componentRestrictions: { country: 'pe' }
        });
        pickupAutocomplete.addListener('place_changed', function() {
            var place = pickupAutocomplete.getPlace();
            if (place.geometry) {
                pickupLat = place.geometry.location.lat();
                pickupLng = place.geometry.location.lng();
                document.getElementById('pickup-lat').value = pickupLat;
                document.getElementById('pickup-lng').value = pickupLng;
                storeCoords.textContent = pickupLat.toFixed(6) + ', ' + pickupLng.toFixed(6);
                calculateFee();
            }
        });
    }

    // ════════════════════════════════
    //  MAP SELECTOR MODAL — DESTINO
    // ════════════════════════════════
    var mapSelectorModal = document.getElementById('map-selector-modal');
    var selectDestMapBtn = document.getElementById('select-dest-map-btn');
    var closeMapSelectorBtn = document.getElementById('close-map-selector-btn');
    var confirmMapSelectorBtn = document.getElementById('confirm-map-selector-btn');

    var selectorMap = null;
    var selectorMarker = null;
    var tempLat = null;
    var tempLng = null;

    selectDestMapBtn.addEventListener('click', function() {
        mapSelectorModal.classList.add('active');
        initSelectorMap('dest');
    });

    closeMapSelectorBtn.addEventListener('click', function() {
        mapSelectorModal.classList.remove('active');
    });

    confirmMapSelectorBtn.addEventListener('click', function() {
        if (tempLat && tempLng) {
            deliveryLat = tempLat;
            deliveryLng = tempLng;
            document.getElementById('delivery-lat').value = tempLat;
            document.getElementById('delivery-lng').value = tempLng;
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: { lat: tempLat, lng: tempLng } }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    destAddr.value = results[0].formatted_address;
                } else {
                    destAddr.value = tempLat.toFixed(6) + ', ' + tempLng.toFixed(6);
                }
                calculateFee();
                mapSelectorModal.classList.remove('active');
            });
        } else {
            mapSelectorModal.classList.remove('active');
        }
    });

    // ════════════════════════════════
    //  MAP SELECTOR MODAL — RECOGIDA
    // ════════════════════════════════
    var pickupMapModal = document.getElementById('pickup-map-modal');
    var selectPickupMapBtn = document.getElementById('select-pickup-map-btn');
    var closePickupMapBtn = document.getElementById('close-pickup-map-btn');
    var confirmPickupMapBtn = document.getElementById('confirm-pickup-map-btn');

    var pickupSelectorMap = null;
    var pickupSelectorMarker = null;
    var tempPickupLat = null;
    var tempPickupLng = null;

    selectPickupMapBtn.addEventListener('click', function() {
        pickupMapModal.classList.add('active');
        initSelectorMap('pickup');
    });

    closePickupMapBtn.addEventListener('click', function() {
        pickupMapModal.classList.remove('active');
    });

    confirmPickupMapBtn.addEventListener('click', function() {
        if (tempPickupLat && tempPickupLng) {
            pickupLat = tempPickupLat;
            pickupLng = tempPickupLng;
            document.getElementById('pickup-lat').value = tempPickupLat;
            document.getElementById('pickup-lng').value = tempPickupLng;
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: { lat: tempPickupLat, lng: tempPickupLng } }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    storeAddr.value = results[0].formatted_address;
                } else {
                    storeAddr.value = tempPickupLat.toFixed(6) + ', ' + tempPickupLng.toFixed(6);
                }
                storeCoords.textContent = tempPickupLat.toFixed(6) + ', ' + tempPickupLng.toFixed(6);
                calculateFee();
                pickupMapModal.classList.remove('active');
            });
        } else {
            pickupMapModal.classList.remove('active');
        }
    });

    function initSelectorMap(mode) {
        if (!window.google || !google.maps) return;

        if (mode === 'pickup') {
            var centerLat = pickupLat || deliveryLat || -6.4916;
            var centerLng = pickupLng || deliveryLng || -76.3724;
            tempPickupLat = centerLat;
            tempPickupLng = centerLng;

            setTimeout(function() {
                if (!pickupSelectorMap) {
                    pickupSelectorMap = new google.maps.Map(document.getElementById('pickup-selector-map'), {
                        center: { lat: centerLat, lng: centerLng },
                        zoom: 15,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false
                    });
                    pickupSelectorMarker = new google.maps.Marker({
                        position: { lat: centerLat, lng: centerLng },
                        map: pickupSelectorMap,
                        draggable: true,
                        animation: google.maps.Animation.DROP,
                        icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
                    });
                    pickupSelectorMap.addListener('click', function(e) {
                        var ll = e.latLng;
                        pickupSelectorMarker.setPosition(ll);
                        tempPickupLat = ll.lat();
                        tempPickupLng = ll.lng();
                    });
                    pickupSelectorMarker.addListener('dragend', function() {
                        var pos = pickupSelectorMarker.getPosition();
                        tempPickupLat = pos.lat();
                        tempPickupLng = pos.lng();
                    });
                } else {
                    pickupSelectorMap.setCenter({ lat: centerLat, lng: centerLng });
                    pickupSelectorMarker.setPosition({ lat: centerLat, lng: centerLng });
                    google.maps.event.trigger(pickupSelectorMap, 'resize');
                }
            }, 200);
        } else {
            var centerLat = deliveryLat || pickupLat || -6.4916;
            var centerLng = deliveryLng || pickupLng || -76.3724;
            tempLat = centerLat;
            tempLng = centerLng;

            setTimeout(function() {
                if (!selectorMap) {
                    selectorMap = new google.maps.Map(document.getElementById('selector-map'), {
                        center: { lat: centerLat, lng: centerLng },
                        zoom: 15,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false
                    });
                    selectorMarker = new google.maps.Marker({
                        position: { lat: centerLat, lng: centerLng },
                        map: selectorMap,
                        draggable: true,
                        animation: google.maps.Animation.DROP
                    });
                    selectorMap.addListener('click', function(e) {
                        var latLng = e.latLng;
                        selectorMarker.setPosition(latLng);
                        tempLat = latLng.lat();
                        tempLng = latLng.lng();
                    });
                    selectorMarker.addListener('dragend', function() {
                        var position = selectorMarker.getPosition();
                        tempLat = position.lat();
                        tempLng = position.lng();
                    });
                    var adminDestSearch = document.getElementById('modal-admin-dest-search');
                    if (adminDestSearch && !adminDestSearch.dataset.acBound && window.google && google.maps && google.maps.places) {
                        adminDestSearch.dataset.acBound = '1';
                        var acD = new google.maps.places.Autocomplete(adminDestSearch, { componentRestrictions: { country: 'pe' } });
                        acD.addListener('place_changed', function() {
                            var p = acD.getPlace();
                            if (p.geometry) {
                                selectorMap.setCenter(p.geometry.location);
                                selectorMap.setZoom(17);
                                selectorMarker.setPosition(p.geometry.location);
                                tempLat = p.geometry.location.lat();
                                tempLng = p.geometry.location.lng();
                            }
                        });
                    }
                } else {
                    selectorMap.setCenter({ lat: centerLat, lng: centerLng });
                    selectorMarker.setPosition({ lat: centerLat, lng: centerLng });
                    google.maps.event.trigger(selectorMap, 'resize');
                }
            }, 200);
        }
    }

    window.addEventListener('load', initAutocomplete);

    // Manual address change reset
    destAddr.addEventListener('input', function() {
        deliveryLat = null; deliveryLng = null;
        document.getElementById('delivery-lat').value = '';
        document.getElementById('delivery-lng').value = '';
    });

    function calculateFee() {
        if (!pickupLat || !pickupLng || !deliveryLat || !deliveryLng) return;

        fetch('/admin/delivery/request/fee-calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                pickup_lat: pickupLat, pickup_lng: pickupLng,
                delivery_lat: deliveryLat, delivery_lng: deliveryLng
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                feeBreakdown.style.display = 'none';
                feeDetails.style.display = 'block';

                var distKm = parseFloat(data.distance_km) || 0;
                var isShort = distKm > 0 && distKm < 1.0;
                var feeTotal = (isShort || data.is_short_distance) ? 4.0 : (parseFloat(data.delivery_fee) || 0);
                baseDeliveryFee = feeTotal;

                document.getElementById('fee-distance').textContent = data.distance_km || '--';
                document.getElementById('fee-base').textContent = (isShort || data.is_short_distance) ? '4.00 (Corta)' : (data.base_fare || '--');
                document.getElementById('fee-distance-fee').textContent = (isShort || data.is_short_distance) ? '0.00' : (data.distance_fee || '0.00');
                document.getElementById('fee-time-min').textContent = data.time_min || '--';
                document.getElementById('fee-time').textContent = (isShort || data.is_short_distance) ? '0.00' : (data.time_fee || '0.00');
                refreshEstimatedTotal();

                var shortAlert = document.getElementById('short-distance-warning');
                if (shortAlert) {
                    if (isShort || data.is_short_distance) {
                        shortAlert.style.display = 'block';
                        var distEl = document.getElementById('short-dist-km');
                        if (distEl) distEl.textContent = distKm.toFixed(2);
                    } else {
                        shortAlert.style.display = 'none';
                    }
                }

                if (parseFloat(data.surge) > 1) {
                    document.getElementById('surge-row').style.display = '';
                    document.getElementById('fee-surge').textContent = data.surge;
                } else {
                    document.getElementById('surge-row').style.display = 'none';
                }
            }
        });
    }

    // AJAX Form Submit
    requestForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!destAddr.value || !document.getElementById('delivery-lat').value) {
            alert('Selecciona una dirección de destino válida usando la sugerencia de Google Maps o el mapa.');
            return;
        }

        if (!document.getElementById('pickup-lat').value || !document.getElementById('pickup-lng').value) {
            alert('Selecciona una dirección de recogida válida. Usa el autocompletado de Google Maps o el selector de mapa.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="las la-spinner la-spin"></i> Procesando solicitud...';

        var formData = new FormData(requestForm);

        fetch(requestForm.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                requestCard.style.display = 'none';
                waitingCard.style.display = 'block';
                document.getElementById('waiting-order-no').textContent = data.order_no;
                cancelBtn.style.display = 'inline-block';
                startTimer();
                startStatusPolling(data.favor_id);
            } else {
                alert(data.message || 'Ocurrió un error al procesar la solicitud.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Enviar solicitud a repartidores';
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('Error de conexión al servidor.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Enviar solicitud a repartidores';
        });
    });

    // Timer
    function startTimer() {
        pollStartTime = Date.now();
        timerInterval = setInterval(function() {
            var elapsed = Math.floor((Date.now() - pollStartTime) / 1000);
            var min = Math.floor(elapsed / 60);
            var sec = elapsed % 60;
            document.getElementById('elapsed-time').textContent = min + ':' + (sec < 10 ? '0' : '') + sec;
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) clearInterval(timerInterval);
    }

    // Polling
    function startStatusPolling(favorId) {
        if (pollingInterval) clearInterval(pollingInterval);
        pollStartTime = Date.now();

        pollingInterval = setInterval(function() {
            if (Date.now() - pollStartTime > maxPollTime) {
                clearInterval(pollingInterval);
                stopTimer();
                alert('Tiempo de espera agotado. No se encontraron repartidores disponibles.');
                window.location.reload();
                return;
            }

            var url = '{{ route("admin.delivery.request.status.show", ":id") }}'.replace(':id', favorId);
            fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    if (data.favor_status === 'accepted' || data.favor_status === 'on_way_to_pickup' || data.favor_status === 'at_pickup' || data.favor_status === 'on_way_to_delivery') {
                        clearInterval(pollingInterval);
                        stopTimer();
                        showDriverFound(data);
                    } else if (data.favor_status === 'cancelled') {
                        clearInterval(pollingInterval);
                        stopTimer();
                        alert('La solicitud ha sido cancelada.');
                        window.location.reload();
                    } else {
                        showWaitingDispatch(data.dispatch);
                    }
                }
            })
            .catch(function(err) { console.error('Poll error:', err); });
        }, 5000);
    }

    function showWaitingDispatch(dispatch) {
        if (!dispatch || dispatch.mode !== 'admin_targeted' || !dispatch.driver_name) return;

        document.getElementById('waiting-animation').innerHTML =
            '<div class="courier-inquiry-animation"><i class="las la-motorcycle" style="font-size:36px;"></i></div>';
        document.getElementById('waiting-title').innerHTML = 'Esperando respuesta de <strong>' + escapeHtml(dispatch.driver_name) + '</strong>';
        document.getElementById('waiting-subtitle').innerHTML =
            'Solicitud <strong id="waiting-order-no">' + escapeHtml(document.getElementById('waiting-order-no').textContent) + '</strong> · consulta individual por 30 segundos';
    }

    function escapeHtml(value) {
        var element = document.createElement('div');
        element.textContent = value || '';
        return element.innerHTML;
    }

    function showDriverFound(data) {
        waitingCard.style.display = 'none';
        driverCard.style.display = 'block';
        document.getElementById('driver-order-no').textContent = data.favor.order_no;

        if (data.courier) {
            var c = data.courier;
            document.getElementById('driver-name').textContent = c.name || 'Repartidor asignado';
            document.getElementById('driver-phone').textContent = c.phone || 'Sin teléfono';

            if (c.image) {
                document.getElementById('driver-avatar').innerHTML = '<img src="' + c.image + '" style="width:56px;height:56px;object-fit:cover;border-radius:50%;" alt="Foto">';
            } else {
                var initials = (c.name || 'R').split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2).toUpperCase();
                document.getElementById('driver-avatar').innerHTML = '<div style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#2e7d32;font-size:18px;">' + initials + '</div>';
            }

            if (c.distance_km && c.distance_km !== '--') {
                document.getElementById('driver-distance').textContent = c.distance_km;
                document.getElementById('driver-time').textContent = c.time_min || '--';
                document.getElementById('driver-distance-badge').style.display = 'block';
            }
        }

        document.getElementById('view-detail-btn').href = '{{ url("admin/delivery/favors") }}';
    }

    // Cancel button
    cancelBtn.addEventListener('click', function() {
        if (confirm('¿Estás seguro de cancelar esta solicitud?')) {
            clearInterval(pollingInterval);
            stopTimer();
            window.location.reload();
        }
    });
})();
</script>
@endpush
@endsection
