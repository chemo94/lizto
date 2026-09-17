@extends('admin.layouts.app')

@push('style-lib')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@push('style')
<style>
    /* ═════════════════════════════════════════════════════════════
       Sneat Theme Refined Design Tokens & Contrast Overrides
       ═════════════════════════════════════════════════════════════ */
    :root {
        --sneat-primary: #696cff;
        --sneat-primary-hover: #5f61e6;
        --sneat-primary-soft: #ecebff;
        --sneat-surface: #ffffff;
        --sneat-canvas: #f5f5f9;
        --sneat-text-dark: #2b2c40;
        --sneat-text-body: #435971;
        --sneat-text-muted: #697a8d;
        --sneat-text-subtle: #8592a3;
        --sneat-border: #d9dee3;
        --sneat-border-light: #eceef1;
    }

    /* Hero Card */
    .store-hero-card {
        background: #ffffff !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 10px rgba(67, 89, 113, 0.08) !important;
        border: 1px solid #d9dee3 !important;
        margin-bottom: 1.25rem !important;
        position: relative;
        overflow: hidden;
    }
    .store-hero-cover {
        height: 140px;
        background: linear-gradient(135deg, #696cff 0%, #4338ca 100%);
        background-size: cover;
        background-position: center;
        position: relative;
    }
    .store-hero-cover::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.45) 100%);
    }
    .store-hero-body {
        padding: 0.9rem 1.4rem 1.1rem;
        position: relative;
        background: #ffffff;
    }
    .store-avatar-wrap {
        position: absolute;
        top: -45px;
        left: 1.4rem;
        width: 92px;
        height: 92px;
        border-radius: 12px;
        border: 4px solid #ffffff;
        background: #ffffff;
        box-shadow: 0 4px 14px rgba(67, 89, 113, 0.18);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }
    .store-avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .store-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: #ecebff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #696cff;
    }
    .store-hero-content {
        margin-left: 110px;
        min-height: 48px;
    }
    .store-hero-title {
        color: #2b2c40 !important;
        font-weight: 700 !important;
        font-size: 1.35rem !important;
        margin-bottom: 0.25rem !important;
        letter-spacing: -0.2px;
    }
    .store-hero-meta {
        color: #566a7f !important;
        font-size: 0.85rem !important;
    }
    .store-hero-meta i {
        color: #696cff !important;
        font-size: 1rem;
    }

    /* Badges High Contrast */
    .badge-sneat {
        font-size: 0.76rem !important;
        font-weight: 700 !important;
        padding: 0.32rem 0.65rem !important;
        border-radius: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.3rem !important;
        line-height: 1.2 !important;
    }
    .badge-sneat-primary {
        background-color: #ecebff !important;
        color: #5f61e6 !important;
        border: 1px solid rgba(105, 108, 255, 0.3) !important;
    }
    .badge-sneat-success {
        background-color: #e8fadf !important;
        color: #2e7d32 !important;
        border: 1px solid rgba(46, 125, 50, 0.3) !important;
    }
    .badge-sneat-warning {
        background-color: #fff4e5 !important;
        color: #d85a00 !important;
        border: 1px solid rgba(216, 90, 0, 0.3) !important;
    }
    .badge-sneat-danger {
        background-color: #ffebee !important;
        color: #c62828 !important;
        border: 1px solid rgba(198, 40, 40, 0.3) !important;
    }
    .badge-sneat-info {
        background-color: #e1f5fe !important;
        color: #0277bd !important;
        border: 1px solid rgba(2, 119, 189, 0.3) !important;
    }
    .badge-sneat-neutral {
        background-color: #f5f5f9 !important;
        color: #566a7f !important;
        border: 1px solid #d9dee3 !important;
    }

    /* Custom Buttons to Defeat Any Framework Overrides */
    .btn-sneat-submit {
        background: #696cff !important;
        background-color: #696cff !important;
        border: 1px solid #696cff !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        padding: 0.52rem 1.25rem !important;
        border-radius: 6px !important;
        box-shadow: 0 3px 10px rgba(105, 108, 255, 0.4) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.5rem !important;
        white-space: nowrap !important;
        cursor: pointer !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
    }
    .btn-sneat-submit:hover,
    .btn-sneat-submit:focus,
    .btn-sneat-submit:active {
        background: #5f61e6 !important;
        background-color: #5f61e6 !important;
        border-color: #5f61e6 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(105, 108, 255, 0.55) !important;
        transform: translateY(-1px);
    }
    .btn-sneat-submit i,
    .btn-sneat-submit span {
        color: #ffffff !important;
    }

    .btn-sneat-hero-action {
        background-color: #ffffff !important;
        border: 1px solid #d9dee3 !important;
        color: #566a7f !important;
        font-weight: 600 !important;
        font-size: 0.84rem !important;
        padding: 0.45rem 0.9rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        text-decoration: none !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
    }
    .btn-sneat-hero-action:hover {
        background-color: #f5f5f9 !important;
        border-color: #696cff !important;
        color: #696cff !important;
    }
    .btn-sneat-hero-action:hover i {
        color: #696cff !important;
    }

    .btn-sneat-success {
        background-color: #71dd37 !important;
        border-color: #71dd37 !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        padding: 0.5rem 1.1rem !important;
        border-radius: 6px !important;
        box-shadow: 0 3px 8px rgba(113, 221, 55, 0.4) !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        cursor: pointer !important;
    }
    .btn-sneat-success:hover {
        background-color: #64c730 !important;
        color: #ffffff !important;
    }
    .btn-sneat-success i, .btn-sneat-success span {
        color: #ffffff !important;
    }

    .btn-sneat-search {
        background: #696cff !important;
        border-color: #696cff !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }
    .btn-sneat-search:hover {
        background: #5f61e6 !important;
        color: #ffffff !important;
    }
    .btn-sneat-search i {
        color: #ffffff !important;
    }

    /* ═════════════════════════════════════════════════════════════
       Sticky Navigation & Action Bar on Scroll
       ═════════════════════════════════════════════════════════════ */
    .sticky-actions-bar {
        position: sticky;
        top: 10px;
        z-index: 1020;
        background: #ffffff !important;
        padding: 0.5rem 1rem !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 18px rgba(67, 89, 113, 0.1) !important;
        border: 1px solid #e0e4e8 !important;
        margin-bottom: 1.25rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 0.75rem !important;
        transition: box-shadow 0.2s ease, top 0.2s ease, border-color 0.2s ease !important;
    }
    .sticky-actions-bar.is-scrolled {
        box-shadow: 0 6px 24px rgba(67, 89, 113, 0.18) !important;
        border-color: #caced5 !important;
        top: 8px !important;
    }
    .sticky-tabs-wrapper {
        display: flex;
        align-items: center;
        overflow-x: auto;
        scrollbar-width: none;
        flex-grow: 1;
        min-width: 0;
    }
    .sticky-tabs-wrapper::-webkit-scrollbar {
        display: none;
    }
    .sticky-actions-right {
        flex-shrink: 0;
    }
    .sneat-tabs {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.35rem;
        padding: 0;
        margin: 0;
        border: 0;
    }
    .sneat-tabs .nav-link {
        color: #566a7f !important;
        font-weight: 600 !important;
        font-size: 0.86rem !important;
        border-radius: 6px !important;
        padding: 0.48rem 0.85rem !important;
        border: 0 !important;
        background: transparent !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.45rem !important;
        white-space: nowrap !important;
        transition: all 0.15s ease-in-out !important;
    }
    .sneat-tabs .nav-link:hover {
        color: #696cff !important;
        background: rgba(105, 108, 255, 0.08) !important;
    }
    .sneat-tabs .nav-link.active {
        color: #696cff !important;
        background: #ecebff !important;
        font-weight: 700 !important;
    }
    .sneat-tabs .nav-link.active i {
        color: #696cff !important;
    }
    .sneat-tabs .nav-link:not(.active) i {
        color: #8592a3 !important;
    }

    /* ═════════════════════════════════════════════════════════════
       Sneat Cards, Inputs & Typography
       ═════════════════════════════════════════════════════════════ */
    .sneat-card {
        background: #ffffff !important;
        border-radius: 8px !important;
        border: 1px solid #e0e4e8 !important;
        box-shadow: 0 2px 6px rgba(67, 89, 113, 0.05) !important;
        margin-bottom: 1.25rem !important;
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
        font-size: 1.2rem;
    }
    .sneat-card-body {
        padding: 1.35rem !important;
        background: #ffffff !important;
    }

    /* Form Labels & Controls */
    .form-label {
        font-size: 0.84rem !important;
        font-weight: 600 !important;
        color: #566a7f !important;
        margin-bottom: 0.35rem !important;
    }
    .form-control, .form-select {
        border-radius: 6px !important;
        border: 1px solid #d9dee3 !important;
        color: #435971 !important;
        background-color: #ffffff !important;
        font-size: 0.88rem !important;
        font-weight: 500 !important;
        padding: 0.48rem 0.85rem !important;
    }
    .form-control::placeholder {
        color: #a1acb8 !important;
        font-weight: 400 !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: #696cff !important;
        box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.14) !important;
        background-color: #ffffff !important;
        color: #435971 !important;
    }
    .input-group-text {
        background-color: #f5f5f9 !important;
        border: 1px solid #d9dee3 !important;
        color: #566a7f !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
    }
    .form-section-title {
        font-size: 0.78rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.7px !important;
        font-weight: 700 !important;
        color: #8592a3 !important;
        margin: 1.25rem 0 0.85rem !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .form-section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e7e7e8;
    }

    /* Multi-select styling with rich contrast */
    select[multiple].form-select {
        background-color: #ffffff !important;
        border: 1px solid #d9dee3 !important;
        border-radius: 8px !important;
        padding: 6px !important;
        color: #435971 !important;
        min-height: 125px !important;
    }
    select[multiple].form-select option {
        padding: 7px 12px !important;
        margin-bottom: 2px !important;
        border-radius: 5px !important;
        color: #435971 !important;
        font-size: 0.87rem !important;
    }
    select[multiple].form-select option:hover {
        background-color: #f5f5f9 !important;
    }
    select[multiple].form-select option:checked {
        background: #696cff !important;
        background-color: #696cff !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }

    /* Schedules Section */
    .schedule-day-row {
        background: #ffffff !important;
        border: 1px solid #e7e7e8 !important;
        border-radius: 8px !important;
        padding: 0.6rem 0.9rem !important;
        margin-bottom: 0.6rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 0.6rem !important;
        transition: border-color 0.15s ease !important;
    }
    .schedule-day-row:hover {
        border-color: #c4c8cd !important;
    }
    .schedule-day-badge {
        background: #f5f5f9 !important;
        color: #435971 !important;
        border: 1px solid #d9dee3 !important;
        font-weight: 700 !important;
        font-size: 0.82rem !important;
        padding: 0.35rem 0.65rem !important;
        border-radius: 6px !important;
        display: inline-block !important;
    }
    .btn-add-slot {
        background-color: #ecebff !important;
        border: 1px solid rgba(105, 108, 255, 0.4) !important;
        color: #696cff !important;
        font-weight: 600 !important;
        font-size: 0.78rem !important;
        padding: 0.25rem 0.55rem !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
    }
    .btn-add-slot:hover {
        background-color: #696cff !important;
        color: #ffffff !important;
        border-color: #696cff !important;
    }
    .btn-add-slot:hover i {
        color: #ffffff !important;
    }
    .slot-pill {
        background: #f8f9fa !important;
        border: 1px solid #d9dee3 !important;
        border-radius: 6px !important;
        padding: 0.25rem 0.5rem !important;
    }
    .slot-pill input[type="time"] {
        background: #ffffff !important;
        border: 1px solid #d9dee3 !important;
        color: #435971 !important;
        font-weight: 600 !important;
    }

    /* SUNAT Environment Selector Boxes */
    .sunat-env-box {
        cursor: pointer;
        border: 2px solid #d9dee3 !important;
        border-radius: 10px !important;
        padding: 1.25rem !important;
        background: #ffffff !important;
        transition: all 0.2s ease !important;
    }
    .sunat-env-box:hover {
        border-color: #696cff !important;
    }
    .sunat-env-box.active {
        border-color: #ffab00 !important;
        background: #fff8e1 !important;
    }
    .sunat-env-box.active h6 {
        color: #b77a00 !important;
    }
    .sunat-env-box.active-production {
        border-color: #71dd37 !important;
        background: #e8fadf !important;
    }
    .sunat-env-box.active-production h6 {
        color: #2e7d32 !important;
    }

    /* Map Box */
    #map-preview {
        height: 380px;
        width: 100%;
        border-radius: 8px;
        border: 1px solid #d9dee3;
        z-index: 1;
    }

    .form-check-input:checked {
        background-color: #696cff !important;
        border-color: #696cff !important;
    }
</style>
@endpush

@section('panel')
@php
    $isEdit = isset($store) && $store->id;
    $coverUrl = ($isEdit && $store->cover_image) ? getImage('assets/images/store_cover/' . $store->cover_image) : null;
    $logoUrl = ($isEdit && $store->image) ? getImage('assets/images/store/' . $store->image) : null;
    $currentPlanId = $activeSubscription?->package_id;
    $companyRuc = old('document_number', $sellerCompany->document_number ?? $store->seller?->document_number ?? '');
    $companyName = old('business_name', $sellerCompany->business_name ?? $store->name ?? '');
    $companyTrade = old('trade_name', $sellerCompany->trade_name ?? $store->name ?? '');
    $companyAddress = old('fiscal_address', $sellerCompany->address ?? $store->address ?? '');
    $companyUbigeo = old('ubigeo', $sellerCompany->ubigeo ?? '');
    $sunatUser = old('sunat_sol_user', $sellerCompany->sunat_sol_user ?? '');
    $sunatPass = old('sunat_sol_pass', $sellerCompany->sunat_sol_pass ?? '');
    $sunatEnv = old('sunat_env', $sellerCompany->sunat_env ?? 'beta');
    $certPass = old('sunat_cert_pass', $sellerCompany->sunat_cert_pass ?? '');
    $taxType = old('default_tax_type', $sellerCompany->default_tax_type ?? 'gravado');
@endphp

<!-- Store Hero Overview Card -->
<div class="store-hero-card">
    <div class="store-hero-cover" @if($coverUrl) style="background-image: url('{{ $coverUrl }}');" @endif></div>
    <div class="store-hero-body">
        <div class="store-avatar-wrap">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" id="preview-logo-img" alt="Logo de tienda">
            @else
                <div class="store-avatar-placeholder" id="preview-logo-placeholder">
                    <i class="las la-store fs-1"></i>
                </div>
                <img src="" id="preview-logo-img" alt="Logo de tienda" style="display:none; width:100%; height:100%; object-fit:cover;">
            @endif
        </div>
        <div class="store-hero-content d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="store-hero-title mb-0">{{ $store->name ?? 'Nueva Tienda' }}</h4>
                    @if($isEdit)
                        <span class="badge-sneat badge-sneat-primary">ID #{{ $store->id }}</span>

                        @if($store->is_open)
                            <span class="badge-sneat badge-sneat-success"><i class="las la-door-open"></i> Abierta</span>
                        @else
                            <span class="badge-sneat badge-sneat-warning"><i class="las la-door-closed"></i> Cerrada</span>
                        @endif

                        @if($store->status)
                            <span class="badge-sneat badge-sneat-success"><i class="las la-check-circle"></i> Activa</span>
                        @else
                            <span class="badge-sneat badge-sneat-danger"><i class="las la-ban"></i> Inactiva</span>
                        @endif

                        <span class="badge-sneat badge-sneat-info text-capitalize">{{ \App\Models\Store::types()[$store->store_type ?? 'restaurant'] ?? $store->store_type }}</span>

                        @if($sellerCompany && $sellerCompany->sunat_env === 'production')
                            <span class="badge-sneat badge-sneat-success"><i class="las la-shield-alt"></i> SUNAT: Producción</span>
                        @elseif($sellerCompany && $sellerCompany->sunat_env === 'beta')
                            <span class="badge-sneat badge-sneat-warning"><i class="las la-flask"></i> SUNAT: Pruebas (Beta)</span>
                        @else
                            <span class="badge-sneat badge-sneat-neutral"><i class="las la-cog"></i> SUNAT: No configurado</span>
                        @endif
                    @endif
                </div>
                <div class="store-hero-meta d-flex align-items-center gap-3 flex-wrap">
                    @if($isEdit && $store->seller)
                        <span><i class="las la-user-tie"></i> <strong>Vendedor:</strong> {{ $store->seller->name }} ({{ $store->seller->email }})</span>
                    @endif
                    @if($isEdit && $store->address)
                        <span><i class="las la-map-marker-alt"></i> {{ Str::limit($store->address, 60) }}</span>
                    @endif
                    @if($isEdit && $registeredDevicesCount > 0)
                        <span><i class="las la-mobile-alt text-success"></i> <strong>{{ $registeredDevicesCount }}</strong> dispositivo(s) conectado(s)</span>
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($isEdit)
                    <button type="button" class="btn-sneat-hero-action" data-bs-toggle="modal" data-bs-target="#notificationModal">
                        <i class="las la-paper-plane text-primary"></i> <span>Notificar a Tienda</span>
                    </button>
                    <a href="{{ route('delivery.store', $store->id) }}" target="_blank" class="btn-sneat-hero-action" title="Ver catálogo en marketplace">
                        <i class="las la-external-link-alt text-secondary"></i> <span>Ver en Web</span>
                    </a>
                @endif
                <a href="{{ route('admin.delivery.stores') }}" class="btn-sneat-hero-action">
                    <i class="las la-arrow-left text-dark"></i> <span>Volver</span>
                </a>
                <button type="button" class="btn-sneat-submit" onclick="document.getElementById('store-main-form').submit()">
                    <i class="las la-save"></i> <span>Guardar Cambios</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Form -->
<form id="store-main-form" method="POST" action="{{ route('admin.delivery.store.save', $store->id ?? null) }}" enctype="multipart/form-data" novalidate>
    @csrf

    <!-- Sticky Navigation & Action Bar -->
    <div class="sticky-actions-bar">
        <div class="sticky-tabs-wrapper">
            <ul class="nav nav-pills sneat-tabs" id="storeEditTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-general-btn" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab">
                        <i class="las la-store fs-5"></i> <span>1. Información & Operación</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-location-btn" data-bs-toggle="pill" data-bs-target="#tab-location" type="button" role="tab">
                        <i class="las la-map-marked-alt fs-5"></i> <span>2. Ubicación & GPS (App)</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-sunat-btn" data-bs-toggle="pill" data-bs-target="#tab-sunat" type="button" role="tab">
                        <i class="las la-file-invoice-dollar fs-5"></i> <span>3. Facturación SUNAT & Fiscal</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-subscription-btn" data-bs-toggle="pill" data-bs-target="#tab-subscription" type="button" role="tab">
                        <i class="las la-crown fs-5"></i> <span>4. Plan & Cobros QR</span>
                    </button>
                </li>
                @if($isEdit)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-notif-btn" data-bs-toggle="pill" data-bs-target="#tab-notif" type="button" role="tab">
                        <i class="las la-bell fs-5"></i> <span>5. Enviar Notificación</span>
                    </button>
                </li>
                @endif
            </ul>
        </div>

        <div class="sticky-actions-right">
            <button type="submit" class="btn-sneat-submit">
                <i class="las la-save"></i> <span>Guardar Todo</span>
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="storeEditTabContent">

        <!-- ══════════════════════════════════════════════════════
             TAB 1: INFORMACIÓN GENERAL & OPERACIÓN
        ══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <div class="row">
                <!-- Vendedor Asignado -->
                <div class="col-12">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-user-shield"></i> Cuenta de Vendedor Asociada (Seller)</h6>
                        </div>
                        <div class="sneat-card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input seller-option-radio" type="radio" name="seller_option" id="existingSeller" value="existing" checked>
                                            <label class="form-check-label fw-semibold" for="existingSeller">Asignar a Vendedor Existente</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input seller-option-radio" type="radio" name="seller_option" id="newSeller" value="new">
                                            <label class="form-check-label fw-semibold" for="newSeller">Crear Nueva Cuenta Seller</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-7 mb-2" id="existing-seller-group">
                                    <label class="form-label">Seleccionar Vendedor *</label>
                                    <select name="seller_id" class="form-select select2-seller" style="width:100%">
                                        <option value="">-- Elige el vendedor dueño de la tienda --</option>
                                        @foreach($sellers as $s)
                                            <option value="{{ $s->id }}" {{ ($store->seller_id ?? '') == $s->id ? 'selected' : '' }}>
                                                {{ $s->name }} · {{ $s->email }} @if($s->document_number) · RUC: {{ $s->document_number }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">El vendedor podrá iniciar sesión en la Web Seller y en la App Seller para gestionar productos, pedidos y reportes.</small>
                                </div>

                                <div class="col-md-12" id="new-seller-group" style="display: none;">
                                    <div class="p-3 rounded bg-light border">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Nombre Completo del Vendedor *</label>
                                                <input type="text" name="seller_name" class="form-control" placeholder="Ej. Juan Pérez Ramos">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Email de Acceso *</label>
                                                <input type="email" name="seller_email" class="form-control" placeholder="vendedor@mitienda.pe">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Contraseña Inicial *</label>
                                                <input type="password" name="seller_password" class="form-control" placeholder="Mínimo 6 caracteres">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos de la Tienda -->
                <div class="col-lg-8">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-store-alt"></i> Perfil y Configuración Operativa</h6>
                        </div>
                        <div class="sneat-card-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Nombre de la Tienda *</label>
                                    <input type="text" name="name" class="form-control form-control-lg fw-bold" value="{{ old('name', $store->name ?? '') }}" placeholder="Ej. Pollería El Madero" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tipo de Tienda *</label>
                                    <select name="store_type" class="form-select">
                                        @foreach(\App\Models\Store::types() as $k => $v)
                                        <option value="{{ $k }}" {{ ($store->store_type ?? 'restaurant') == $k ? 'selected' : '' }}>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Modalidad del Negocio</label>
                                    <select name="service_mode" class="form-select">
                                        <option value="restaurant" {{ ($store->service_mode ?? 'restaurant') == 'restaurant' ? 'selected' : '' }}>Restaurante Completo (Mesas, Mozos, Comandas, POS y Delivery)</option>
                                        <option value="delivery_only" {{ ($store->service_mode ?? '') == 'delivery_only' ? 'selected' : '' }}>Dark Kitchen / Tienda Express (Solo Delivery & Para Llevar)</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Pedido Mínimo (S/)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" step="0.5" min="0" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $store->min_order_amount ?? 0) }}">
                                    </div>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Tiempo de Prep.</label>
                                    <div class="input-group">
                                        <input type="number" min="1" name="preparation_time" class="form-control" value="{{ old('preparation_time', $store->preparation_time ?? 20) }}">
                                        <span class="input-group-text">min</span>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Descripción o Reseña Comercial</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="Especialidades, platos bandera o información útil para los clientes">{{ old('description', $store->description ?? '') }}</textarea>
                                </div>

                                <!-- Categorización -->
                                <div class="col-12"><div class="form-section-title"><i class="las la-tags"></i> Clasificación en el Marketplace</div></div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Categorías Generales</label>
                                    <select name="general_category_ids[]" id="general-cats-select" class="form-select" multiple size="4" onchange="filterSubCategories()">
                                        @foreach($generalCategories as $gc)
                                            @php $selected = isset($store) && $store->generalCategories->contains($gc->id); @endphp
                                            <option value="{{ $gc->id }}" data-subs="{{ $gc->allSubCategories->pluck('id')->implode(',') }}" {{ $selected ? 'selected' : '' }}>
                                                {{ $gc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Mantén presionada tecla Ctrl (o Cmd) para elegir varias.</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Subcategorías Específicas</label>
                                    <select name="sub_category_ids[]" id="sub-cats-select" class="form-select" multiple size="4">
                                        @foreach($subCategories as $sc)
                                            @php $selected = isset($store) && $store->subCategories->contains($sc->id); @endphp
                                            <option value="{{ $sc->id }}" data-parent="{{ $sc->general_category_id }}" {{ $selected ? 'selected' : '' }}>
                                                {{ $sc->generalCategory?->name }} ➔ {{ $sc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Se filtran dinámicamente según las categorías seleccionadas.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Horarios -->
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-clock"></i> Horarios de Atención al Público</h6>
                            <span class="badge-sneat badge-sneat-info">Configura días y turnos</span>
                        </div>
                        <div class="sneat-card-body">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Apertura General Referencial</label>
                                    <input type="time" name="opening_time" class="form-control" value="{{ old('opening_time', $store->opening_time ?? '08:00') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Cierre General Referencial</label>
                                    <input type="time" name="closing_time" class="form-control" value="{{ old('closing_time', $store->closing_time ?? '23:00') }}">
                                </div>
                            </div>

                            <div class="form-section-title"><i class="las la-calendar-alt"></i> Horarios Personalizados por Día</div>

                            @php
                                $days = \App\Models\StoreSchedule::days();
                                $hasSchedules = isset($store) && $store->schedules->isNotEmpty();
                            @endphp

                            <div id="schedules-container">
                                @foreach($days as $dayKey => $dayName)
                                    @php $daySlots = $hasSchedules ? $store->schedules->where('day', $dayKey) : collect(); @endphp
                                    <div class="schedule-day-row">
                                        <div class="d-flex align-items-center gap-2" style="min-width: 140px;">
                                            <span class="schedule-day-badge">{{ $dayName }}</span>
                                            <button type="button" class="btn-add-slot add-slot" data-day="{{ $dayKey }}" title="Agregar turno a {{ $dayName }}">
                                                <i class="las la-plus"></i> Turno
                                            </button>
                                        </div>

                                        <div class="slots flex-grow-1 d-flex flex-wrap align-items-center gap-2" id="slots-day-{{ $dayKey }}">
                                            @if($daySlots->isNotEmpty())
                                                @foreach($daySlots as $schedule)
                                                <div class="d-inline-flex align-items-center gap-1 slot-row slot-pill">
                                                    <input type="time" name="schedules[{{ $dayKey }}][{{ $loop->index }}][open]" value="{{ $schedule->open_time }}" class="form-control form-control-sm" style="width:115px">
                                                    <span class="text-muted small">a</span>
                                                    <input type="time" name="schedules[{{ $dayKey }}][{{ $loop->index }}][close]" value="{{ $schedule->close_time }}" class="form-control form-control-sm" style="width:115px">
                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-slot" title="Quitar"><i class="las la-times fs-6"></i></button>
                                                </div>
                                                @endforeach
                                            @else
                                                <span class="text-muted small fst-italic no-slots-label">Usa horario general</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lateral: Estados y Fotos -->
                <div class="col-lg-4">
                    <!-- Estados -->
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-toggle-on"></i> Disponibilidad</h6>
                        </div>
                        <div class="sneat-card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_open" id="switchIsOpen" {{ ($store->is_open ?? 1) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="switchIsOpen">
                                    Tienda Abierta
                                    <small class="d-block text-muted fw-normal">Permite que los clientes agreguen productos al carrito y ordenen.</small>
                                </label>
                            </div>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="status" id="switchStatus" {{ ($store->status ?? 1) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="switchStatus">
                                    Tienda Activa en App
                                    <small class="d-block text-muted fw-normal">Si se desactiva, quedará oculta de los listados y búsquedas.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Logo & Portada -->
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-image"></i> Multimedia de Marca</h6>
                        </div>
                        <div class="sneat-card-body">
                            <!-- Logo -->
                            <div class="mb-4">
                                <label class="form-label">Logo de la Tienda (400x400 px)</label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    @if($logoUrl)
                                        <img src="{{ $logoUrl }}" id="preview-logo-thumb" class="rounded border" width="65" height="65" style="object-fit: cover;">
                                    @else
                                        <div id="preview-logo-thumb-placeholder" class="rounded border d-flex align-items-center justify-content-center bg-light text-primary fs-3" style="width:65px; height:65px;">
                                            <i class="las la-store"></i>
                                        </div>
                                        <img src="" id="preview-logo-thumb" class="rounded border" width="65" height="65" style="display:none; object-fit: cover;">
                                    @endif
                                    <div class="flex-grow-1">
                                        <input type="file" name="image" class="form-control form-control-sm" accept="image/*" onchange="handleLogoPreview(this)">
                                        <small class="text-muted">PNG o JPG cuadrado</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Portada -->
                            <div>
                                <label class="form-label">Portada / Banner (1200x400 px)</label>
                                @if($coverUrl)
                                    <div class="mb-2 rounded overflow-hidden border" style="max-height: 95px;">
                                        <img src="{{ $coverUrl }}" id="preview-cover-thumb" class="w-100" style="object-fit: cover;">
                                    </div>
                                @endif
                                <input type="file" name="cover_image" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this, 'preview-cover-thumb')">
                                <small class="text-muted">Aparece en la cabecera de la tienda en el app móvil</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════
             TAB 2: UBICACIÓN & COORDENADAS (APP)
        ══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab-location" role="tabpanel">
            <div class="row">
                <div class="col-lg-5">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-map-pin"></i> Dirección Visible en la App</h6>
                        </div>
                        <div class="sneat-card-body">
                            <div class="mb-3">
                                <label class="form-label">Buscador de Dirección (Google / Nominatim)</label>
                                <div class="input-group">
                                    <input type="text" id="address-search" class="form-control" placeholder="Escribe calle, avenida o negocio...">
                                    <button class="btn btn-sneat-search" type="button" id="btn-search-address" title="Buscar en mapa">
                                        <i class="las la-search"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Escribe la dirección y presiona Buscar o selecciona sugerencia.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Dirección que verán los Clientes *</label>
                                <textarea name="address" id="address" class="form-control" rows="2" placeholder="Ej. Jr. San Martín 450, Tarapoto, San Martín">{{ old('address', $store->address ?? '') }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Latitud GPS</label>
                                    <input type="text" name="latitude" id="latitude" class="form-control fw-bold font-monospace" value="{{ old('latitude', $store->latitude ?? '') }}" placeholder="-6.4850000">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Longitud GPS</label>
                                    <input type="text" name="longitude" id="longitude" class="form-control fw-bold font-monospace" value="{{ old('longitude', $store->longitude ?? '') }}" placeholder="-76.3650000">
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2 mb-3">
                                <button type="button" class="btn btn-sneat-hero-action flex-fill" id="btn-current-location">
                                    <i class="las la-crosshairs text-primary"></i> <span>Detectar mi ubicación</span>
                                </button>
                                <button type="button" class="btn btn-sneat-hero-action" id="btn-center-tarapoto" title="Centrar en Tarapoto">
                                    <i class="las la-city text-primary"></i> <span>Tarapoto</span>
                                </button>
                            </div>

                            <div class="alert alert-light border d-flex align-items-start gap-2 mb-0 py-2 px-3 small" style="background:#ecebff !important; border-color:#d5d5fa !important;" role="alert">
                                <i class="las la-info-circle text-primary fs-5 mt-1 flex-shrink-0"></i>
                                <div style="color: #435971;">
                                    <strong>Importancia del Pin GPS:</strong>
                                    Estas coordenadas definen el cálculo de delivery, los motorizados cercanos que recibirán el pedido y la distancia en KM mostrada al cliente.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-compass"></i> Pin en el Mapa (Arrastra el marcador al local exacto)</h6>
                            <span class="badge-sneat badge-sneat-primary" id="map-status-badge">Interactivo</span>
                        </div>
                        <div class="sneat-card-body p-2">
                            <div id="map-preview"></div>
                            <small class="text-muted d-block mt-2 px-2">
                                <i class="las la-hand-pointer text-primary"></i> Haz clic o arrastra el marcador rojo para afinar las coordenadas con precisión métrica.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════
             TAB 3: FACTURACIÓN SUNAT & DATOS FISCALES
        ══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab-sunat" role="tabpanel">
            <div class="row">
                <!-- Entorno SUNAT Selector -->
                <div class="col-12 mb-3">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-server"></i> Entorno de Conexión SUNAT</h6>
                            <span class="badge-sneat {{ $sunatEnv === 'production' ? 'badge-sneat-success' : 'badge-sneat-warning' }}" id="env-badge-label">
                                {{ $sunatEnv === 'production' ? 'PRODUCCIÓN ACTIVA' : 'MODO HOMOLOGACIÓN / PRUEBAS' }}
                            </span>
                        </div>
                        <div class="sneat-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="sunat-env-box {{ $sunatEnv === 'beta' ? 'active' : '' }}" onclick="selectSunatEnv('beta')">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="las la-vial text-warning fs-3"></i>
                                                <h6 class="mb-0 fw-bold">Modo Beta / Demo (Pruebas)</h6>
                                            </div>
                                            <input type="radio" name="sunat_env" id="env_beta" value="beta" class="form-check-input" {{ $sunatEnv === 'beta' ? 'checked' : '' }}>
                                        </div>
                                        <p class="text-muted small mb-0">
                                            Emite facturas y boletas de prueba al Web Service Beta de SUNAT. Los comprobantes <strong>NO tienen validez legal</strong> ni generan obligaciones tributarias. Ideal para capacitar a la tienda.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="sunat-env-box {{ $sunatEnv === 'production' ? 'active-production' : '' }}" onclick="selectSunatEnv('production')">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="las la-check-double text-success fs-3"></i>
                                                <h6 class="mb-0 fw-bold">Modo Producción (Oficial SUNAT)</h6>
                                            </div>
                                            <input type="radio" name="sunat_env" id="env_production" value="production" class="form-check-input" {{ $sunatEnv === 'production' ? 'checked' : '' }}>
                                        </div>
                                        <p class="text-muted small mb-0">
                                            Conexión directa con los servidores de SUNAT. Comprobantes electrónicos reales con firma digital, CDR generado y validez tributaria ante la administración tributaria peruana.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos Tributarios de la Empresa -->
                <div class="col-lg-6">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-id-card"></i> RUC y Domicilio Fiscal</h6>
                        </div>
                        <div class="sneat-card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">RUC (11 dígitos) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="las la-hashtag"></i></span>
                                        <input type="text" name="document_number" id="ruc_field" class="form-control fw-bold font-monospace" maxlength="11" value="{{ $companyRuc }}" placeholder="20XXXXXXXXX">
                                    </div>
                                    <small class="text-muted d-block mt-1" id="ruc_type_label">RUC 20 (Empresas) o RUC 10 (Personas naturales)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Código Ubigeo SUNAT (6 dígitos)</label>
                                    <input type="text" name="ubigeo" class="form-control font-monospace" maxlength="6" value="{{ $companyUbigeo }}" placeholder="Ej. 220901">
                                    <small class="text-muted">Ej: 220901 para Tarapoto</small>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Razón Social Oficial *</label>
                                    <input type="text" name="business_name" class="form-control fw-bold" value="{{ $companyName }}" placeholder="Ej. GASTRONOMIA SELVA S.A.C.">
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Nombre Comercial</label>
                                    <input type="text" name="trade_name" class="form-control" value="{{ $companyTrade }}" placeholder="Ej. La Brasa de Tarapoto">
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Dirección Fiscal Registrada en SUNAT</label>
                                    <textarea name="fiscal_address" class="form-control" rows="2" placeholder="Domicilio fiscal según ficha RUC">{{ $companyAddress }}</textarea>
                                </div>

                                <div class="col-12 mb-2">
                                    <label class="form-label">Tipo de Afectación Tributaria Predeterminada</label>
                                    <select name="default_tax_type" class="form-select">
                                        <option value="gravado" {{ $taxType === 'gravado' ? 'selected' : '' }}>Gravado - Operación Onerosa (Afecto a IGV 18%)</option>
                                        <option value="exonerado" {{ $taxType === 'exonerado' ? 'selected' : '' }}>Exonerado - Operación Onerosa (Amazonía / Selva Ley 27037)</option>
                                        <option value="inafecto" {{ $taxType === 'inafecto' ? 'selected' : '' }}>Inafecto - Operación Onerosa</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">En San Martín y zonas amazónicas peruanas suele aplicarse Exonerado Ley de la Amazonía.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credenciales SOL y Certificado Digital -->
                <div class="col-lg-6">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-key"></i> Credenciales SOL (Usuario Secundario)</h6>
                        </div>
                        <div class="sneat-card-body">
                            <div class="mb-3">
                                <label class="form-label">Usuario SOL *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="las la-user"></i></span>
                                    <input type="text" name="sunat_sol_user" class="form-control font-monospace fw-bold" value="{{ $sunatUser }}" placeholder="Ej. MODDATOS o USERFACTURA">
                                </div>
                                <small class="text-muted">En Beta puedes usar <code>MODDATOS</code></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Clave SOL *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="las la-lock"></i></span>
                                    <input type="password" name="sunat_sol_pass" id="sunat_sol_pass" class="form-control" value="{{ $sunatPass }}" placeholder="Contraseña de usuario SOL">
                                    <button type="button" class="btn btn-sneat-hero-action" onclick="togglePasswordVisibility('sunat_sol_pass')">
                                        <i class="las la-eye" id="icon-sunat_sol_pass"></i>
                                    </button>
                                </div>
                                <small class="text-muted">En Beta puedes usar <code>moddatos</code></small>
                            </div>
                        </div>
                    </div>

                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-certificate"></i> Certificado Digital (.pfx / .p12)</h6>
                            @if($sellerCompany && $sellerCompany->sunat_cert_path)
                                <span class="badge-sneat badge-sneat-success"><i class="las la-check"></i> Certificado Cargado</span>
                            @else
                                <span class="badge-sneat badge-sneat-warning"><i class="las la-exclamation-triangle"></i> Pendiente</span>
                            @endif
                        </div>
                        <div class="sneat-card-body">
                            @if($sellerCompany && $sellerCompany->sunat_cert_path)
                                <div class="p-2 mb-3 rounded border d-flex align-items-center justify-content-between" style="background:#f5fdf3; border-color:#c8e6c9 !important;">
                                    <div class="d-flex align-items-center gap-2 text-truncate">
                                        <i class="las la-file-signature text-success fs-3"></i>
                                        <div>
                                            <strong class="d-block text-dark small text-truncate">{{ basename($sellerCompany->sunat_cert_path) }}</strong>
                                            <span class="text-muted" style="font-size: 11px;">Almacenado de forma segura en servidor</span>
                                        </div>
                                    </div>
                                    <span class="badge bg-success text-white">Activo</span>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">{{ ($sellerCompany && $sellerCompany->sunat_cert_path) ? 'Reemplazar Certificado (.pfx o .p12)' : 'Subir Certificado Digital (.pfx o .p12)' }}</label>
                                <input type="file" name="sunat_cert" class="form-control form-control-sm" accept=".pfx,.p12">
                                <small class="text-muted">Otorgado por entidad certificadora (Llamas.pe, Avansi, RENIEC, etc.). Máx 5MB.</small>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Contraseña del Certificado Digital</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="las la-shield-alt"></i></span>
                                    <input type="password" name="sunat_cert_pass" id="sunat_cert_pass" class="form-control" value="{{ $certPass }}" placeholder="Clave privada del archivo PFX">
                                    <button type="button" class="btn btn-sneat-hero-action" onclick="togglePasswordVisibility('sunat_cert_pass')">
                                        <i class="las la-eye" id="icon-sunat_cert_pass"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════
             TAB 4: SUSCRIPCIÓN & PAGOS QR
        ══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab-subscription" role="tabpanel">
            <div class="row">
                <!-- Suscripción / Plan Empresarial -->
                <div class="col-lg-6">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-crown"></i> Plan Empresarial y Membresía</h6>
                            @if($activeSubscription)
                                <span class="badge-sneat badge-sneat-success">Vigente hasta {{ $activeSubscription->expires_at ? showDateTime($activeSubscription->expires_at, 'd/m/Y') : 'Ilimitado' }}</span>
                            @else
                                <span class="badge-sneat badge-sneat-warning">Sin plan activo</span>
                            @endif
                        </div>
                        <div class="sneat-card-body">
                            @if($activeSubscription)
                                <div class="p-3 mb-3 rounded border" style="background:#f5f5f9; border-color:#e0e4e8 !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold" style="color:#696cff;">{{ $activeSubscription->package?->name ?? 'Plan asignado' }}</h6>
                                            <small class="text-muted">Inicio: {{ showDateTime($activeSubscription->starts_at, 'd/m/Y') }}</small>
                                        </div>
                                        <span class="fs-5 fw-bold text-dark">S/ {{ number_format($activeSubscription->package?->price ?? 0, 2) }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Asignar / Cambiar Plan</label>
                                <select name="business_package_id" class="form-select">
                                    <option value="">-- Sin plan asignado --</option>
                                    @foreach(\App\Models\BusinessPackage::active()->orderBy('sort_order')->get() as $pkg)
                                        <option value="{{ $pkg->id }}" {{ $currentPlanId == $pkg->id ? 'selected' : '' }}>
                                            {{ $pkg->name }} — S/ {{ number_format($pkg->price, 2) }} / {{ $pkg->duration_days }} días
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Si seleccionas un nuevo plan, se activará al guardar.</small>
                            </div>
                        </div>
                    </div>

                    @if($isEdit)
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-receipt"></i> Registro Rápido de Renovación y Pago</h6>
                        </div>
                        <div class="sneat-card-body">
                            <p class="text-muted small">Registra pagos en efectivo, transferencia o POS recibidos por la administración para extender la suscripción de esta tienda.</p>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn-sneat-hero-action" data-bs-toggle="collapse" data-bs-target="#renewalBox">
                                    <i class="las la-plus-circle text-success"></i> <span>Abrir formulario de renovación</span>
                                </button>
                            </div>

                            <div class="collapse mt-3" id="renewalBox">
                                <div class="p-3 rounded border" style="background:#f5f5f9; border-color:#e0e4e8 !important;">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small">Plan a renovar</label>
                                            <select id="renew_package_id" class="form-select form-select-sm">
                                                @foreach(\App\Models\BusinessPackage::active()->orderBy('sort_order')->get() as $pkg)
                                                <option value="{{ $pkg->id }}" {{ $currentPlanId == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }} (S/ {{ number_format($pkg->price,2) }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Método</label>
                                            <select id="renew_payment_method" class="form-select form-select-sm">
                                                <option value="cash">Efectivo</option>
                                                <option value="yape">Yape</option>
                                                <option value="plin">Plin</option>
                                                <option value="transfer">Transferencia</option>
                                                <option value="pos">POS</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Nro Operación</label>
                                            <input type="text" id="renew_reference" class="form-control form-control-sm" placeholder="Opcional">
                                        </div>
                                        <div class="col-12 mt-2">
                                            <button type="button" class="btn-sneat-success w-100 justify-content-center" onclick="submitRenewalForm()">
                                                <i class="las la-check"></i> <span>Registrar Renovación</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Códigos QR Cobros Delivery -->
                <div class="col-lg-6">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-qrcode"></i> Billeteras Móviles (Yape & Plin)</h6>
                        </div>
                        <div class="sneat-card-body">
                            <p class="text-muted small mb-3">
                                Pega aquí la <strong>cadena de texto alfanumérica del código QR</strong> oficial de la tienda. El motorizado generará dinámicamente el QR con el monto exacto del pedido al momento de la entrega en puerta.
                            </p>

                            <div class="mb-3">
                                <label class="form-label"><i class="las la-mobile-alt" style="color:#732282;"></i> Cadena QR de Yape</label>
                                <textarea name="yape_qr_string" class="form-control font-monospace small" rows="3" maxlength="4096" placeholder="00020101021229370014pe.com.yape...">{{ old('yape_qr_string', $store->yape_qr_string ?? '') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="las la-mobile-alt text-info"></i> Cadena QR de Plin</label>
                                <textarea name="plin_qr_string" class="form-control font-monospace small" rows="3" maxlength="4096" placeholder="Pega aquí la cadena de texto del QR de Plin...">{{ old('plin_qr_string', $store->plin_qr_string ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isEdit)
        <!-- ══════════════════════════════════════════════════════
             TAB 5: ENVIAR NOTIFICACIÓN A LA TIENDA
        ══════════════════════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab-notif" role="tabpanel">
            <div class="row">
                <div class="col-lg-7">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-paper-plane"></i> Redactar Notificación Push a {{ $store->name }}</h6>
                        </div>
                        <div class="sneat-card-body">
                            <p class="text-muted small mb-3">
                                El mensaje llegará como notificación emergente instantánea (Push Notification) a la App Seller y al panel web del vendedor asignado a esta tienda.
                            </p>

                            <div class="mb-3">
                                <label class="form-label">Tipo de Aviso</label>
                                <select id="tab_notif_type" class="form-select">
                                    <option value="general">Información General</option>
                                    <option value="warning">Aviso Importante / Advertencia</option>
                                    <option value="order">Actualización de Pedidos o Catálogo</option>
                                    <option value="promo">Comisión o Promoción Especial</option>
                                    <option value="system">Mantenimiento de Sistema</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Título de la Notificación *</label>
                                <input type="text" id="tab_notif_title" class="form-control" maxlength="120" placeholder="Ej. Recordatorio de actualización de precios">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mensaje o Contenido *</label>
                                <textarea id="tab_notif_message" class="form-control" rows="4" maxlength="1000" placeholder="Escribe el mensaje claro y directo para el administrador de la tienda..."></textarea>
                            </div>

                            <button type="button" class="btn-sneat-submit" onclick="sendStorePushNotification('tab_notif')">
                                <i class="las la-paper-plane"></i> <span>Enviar Notificación Inmediata</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="sneat-card">
                        <div class="sneat-card-header">
                            <h6><i class="las la-signal"></i> Dispositivos Conectados</h6>
                            <span class="badge-sneat {{ $registeredDevicesCount > 0 ? 'badge-sneat-success' : 'badge-sneat-neutral' }}">
                                {{ $registeredDevicesCount }} Activo(s)
                            </span>
                        </div>
                        <div class="sneat-card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 rounded text-primary fs-2" style="background:#ecebff;">
                                    <i class="las la-broadcast-tower"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">{{ $registeredDevicesCount > 0 ? 'Tokens FCM Activos' : 'Sin dispositivos' }}</h6>
                                    <span class="text-muted small">
                                        @if($registeredDevicesCount > 0)
                                            El vendedor tiene sesiones activas registradas en Firebase Cloud Messaging para recibir alertas en tiempo real.
                                        @else
                                            El vendedor aún no ha iniciado sesión en la app móvil Seller recientemente.
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="alert alert-light border small text-muted mb-0" style="background:#f5f5f9 !important;">
                                <strong>Consejo UX:</strong> Las notificaciones push directas son útiles para coordinar aperturas de tienda, avisar sobre pedidos especiales pendientes o requerimientos de documentación SUNAT.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div> <!-- /tab-content -->
</form>

<!-- Modal: Enviar Notificación Push (Accesible desde cabecera) -->
@if($isEdit)
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow:hidden;">
            <div class="modal-header" style="background:#ffffff; border-bottom: 1px solid #eceef1;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="notificationModalLabel">
                    <i class="las la-paper-plane text-primary"></i> Notificar a {{ $store->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.delivery.store.notification', $store->id) }}">
                @csrf
                <div class="modal-body p-4" style="background:#ffffff;">
                    <div class="mb-3">
                        <label class="form-label">Tipo de Mensaje</label>
                        <select name="type" class="form-select">
                            <option value="general">Información General</option>
                            <option value="warning">Aviso Importante / Advertencia</option>
                            <option value="order">Operación de Pedidos</option>
                            <option value="promo">Comisiones y Promociones</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Título *</label>
                        <input type="text" name="title" class="form-control" maxlength="120" placeholder="Ej. Aviso de la Administración Lizto" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mensaje *</label>
                        <textarea name="message" class="form-control" rows="3" maxlength="1000" placeholder="Escribe el mensaje..." required></textarea>
                    </div>

                    <div class="small text-muted p-2 rounded" style="background:#f5f5f9;">
                        <i class="las la-info-circle text-primary"></i> Se emitirá push notification a <strong>{{ $registeredDevicesCount }}</strong> dispositivo(s) móvil(es).
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8f9fa; border-top: 1px solid #eceef1;">
                    <button type="button" class="btn-sneat-hero-action" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-sneat-submit">
                        <i class="las la-paper-plane"></i> <span>Enviar Ahora</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulario Oculto para Renovación -->
<form id="renew-subscription-form" method="POST" action="{{ route('admin.delivery.store.subscription.renew', $store->id) }}" style="display:none;">
    @csrf
    <input type="hidden" name="package_id" id="hidden_renew_package_id">
    <input type="hidden" name="payment_method" id="hidden_renew_payment_method">
    <input type="hidden" name="payment_reference" id="hidden_renew_reference">
</form>
@endif

@endsection

@push('script-lib')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@if(gs('google_maps_api'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places&callback=initGooglePlaces" async defer></script>
@endif
@endpush

@push('script')
<script>
(function($) {
    "use strict";

    // ── Sticky Bar Scroll Shadow & Docking ──
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 160) {
            $('.sticky-actions-bar').addClass('is-scrolled');
        } else {
            $('.sticky-actions-bar').removeClass('is-scrolled');
        }
    });

    // ── 1. Toggle Vendedor Nuevo / Existente ──
    function toggleSellerFields() {
        var val = $('input[name="seller_option"]:checked').val();
        if (val === 'new') {
            $('#new-seller-group').slideDown(200);
            $('#existing-seller-group').slideUp(200);
            $('select[name="seller_id"]').prop('required', false);
            $('input[name="seller_name"], input[name="seller_email"], input[name="seller_password"]').prop('required', true);
        } else {
            $('#new-seller-group').slideUp(200);
            $('#existing-seller-group').slideDown(200);
            $('select[name="seller_id"]').prop('required', true);
            $('input[name="seller_name"], input[name="seller_email"], input[name="seller_password"]').prop('required', false);
        }
    }
    toggleSellerFields();
    $('input[name="seller_option"]').on('change', toggleSellerFields);

    // ── 2. Horarios Dinámicos ──
    $('.add-slot').on('click', function(e) {
        e.preventDefault();
        var day = $(this).data('day');
        var container = $('#slots-day-' + day);
        container.find('.no-slots-label').remove();
        var idx = container.find('.slot-row').length;
        var html = '<div class="d-inline-flex align-items-center gap-1 slot-row slot-pill">' +
            '<input type="time" name="schedules['+day+']['+idx+'][open]" class="form-control form-control-sm" style="width:115px" value="08:00">' +
            '<span class="text-muted small">a</span>' +
            '<input type="time" name="schedules['+day+']['+idx+'][close]" class="form-control form-control-sm" style="width:115px" value="23:00">' +
            '<button type="button" class="btn btn-sm btn-link text-danger p-0 remove-slot" title="Quitar"><i class="las la-times fs-6"></i></button>' +
            '</div>';
        container.append(html);
    });

    $('#schedules-container').on('click', '.remove-slot', function(e) {
        e.preventDefault();
        var container = $(this).closest('.slots');
        $(this).closest('.slot-row').remove();
        if (container.find('.slot-row').length === 0) {
            container.html('<span class="text-muted small fst-italic no-slots-label">Usa horario general</span>');
        }
    });

    // ── 3. Filtro de Subcategorías ──
    window.filterSubCategories = function() {
        var selectedIds = $('#general-cats-select').val() || [];
        var allSubs = {};
        $('#general-cats-select option').each(function() {
            var subs = ($(this).data('subs') || '').toString().split(',');
            allSubs[$(this).val()] = subs;
        });
        var allowed = [];
        selectedIds.forEach(function(id) {
            allowed = allowed.concat(allSubs[id] || []);
        });

        $('#sub-cats-select option').each(function() {
            var parent = $(this).data('parent')?.toString();
            if (allowed.length === 0 || allowed.includes(parent)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    };
    filterSubCategories();

    // ── 4. RUC Label Dinámico ──
    $('#ruc_field').on('input', function() {
        var val = $(this).val().trim();
        if (val.startsWith('20')) {
            $('#ruc_type_label').html('<span class="badge-sneat badge-sneat-primary">Persona Jurídica (Empresa S.A.C., S.R.L.)</span>');
        } else if (val.startsWith('10')) {
            $('#ruc_type_label').html('<span class="badge-sneat badge-sneat-info">Persona Natural con Negocio</span>');
        } else if (val.length === 11) {
            $('#ruc_type_label').html('<span class="badge-sneat badge-sneat-neutral">RUC Especial (' + val.substring(0,2) + ')</span>');
        } else {
            $('#ruc_type_label').text('RUC 20 (Empresas) o RUC 10 (Personas naturales)');
        }
    }).trigger('input');

    // ── 5. Selector de Entorno SUNAT ──
    window.selectSunatEnv = function(env) {
        if (env === 'beta') {
            $('#env_beta').prop('checked', true);
            $('.sunat-env-box:has(#env_beta)').addClass('active');
            $('.sunat-env-box:has(#env_production)').removeClass('active active-production');
            $('#env-badge-label').attr('class', 'badge-sneat badge-sneat-warning').text('MODO HOMOLOGACIÓN / PRUEBAS');
        } else {
            $('#env_production').prop('checked', true);
            $('.sunat-env-box:has(#env_production)').addClass('active-production');
            $('.sunat-env-box:has(#env_beta)').removeClass('active');
            $('#env-badge-label').attr('class', 'badge-sneat badge-sneat-success').text('PRODUCCIÓN OFICIAL SUNAT');
        }
    };

    // ── 6. Toggle Visibilidad Contraseñas ──
    window.togglePasswordVisibility = function(id) {
        var input = document.getElementById(id);
        var icon = document.getElementById('icon-' + id);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            if (icon) { icon.classList.remove('la-eye'); icon.classList.add('la-eye-slash'); }
        } else {
            input.type = 'password';
            if (icon) { icon.classList.remove('la-eye-slash'); icon.classList.add('la-eye'); }
        }
    };

    // ── 7. Previsualizador de Logo & Portada ──
    window.handleLogoPreview = function(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-logo-placeholder').hide();
                $('#preview-logo-thumb-placeholder').hide();
                $('#preview-logo-img').attr('src', e.target.result).show();
                $('#preview-logo-thumb').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(input.files[0]);
        }
    };

    window.previewImage = function(input, thumbId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                if (thumbId) $('#' + thumbId).attr('src', e.target.result);
            };
            reader.readAsDataURL(input.files[0]);
        }
    };

    // ── 8. Renovación Rápida de Suscripción ──
    window.submitRenewalForm = function() {
        var pkgId = $('#renew_package_id').val();
        var method = $('#renew_payment_method').val();
        var ref = $('#renew_reference').val();

        if (!pkgId) {
            alert('Por favor selecciona un plan para renovar');
            return;
        }

        if (confirm('¿Confirmas el registro del pago y renovación del plan para esta tienda?')) {
            $('#hidden_renew_package_id').val(pkgId);
            $('#hidden_renew_payment_method').val(method);
            $('#hidden_renew_reference').val(ref);
            document.getElementById('renew-subscription-form').submit();
        }
    };

    // ── 9. Enviar Notificación Push desde Tab ──
    window.sendStorePushNotification = function(prefix) {
        var title = $('#' + prefix + '_title').val().trim();
        var message = $('#' + prefix + '_message').val().trim();
        var type = $('#' + prefix + '_type').val();

        if (!title || !message) {
            alert('Por favor ingresa un título y mensaje para la notificación');
            return;
        }

        var form = $('<form>', {
            method: 'POST',
            action: "{{ isset($store) ? route('admin.delivery.store.notification', $store->id) : '#' }}"
        });
        form.append($('<input>', { type: 'hidden', name: '_token', value: "{{ csrf_token() }}" }));
        form.append($('<input>', { type: 'hidden', name: 'title', value: title }));
        form.append($('<input>', { type: 'hidden', name: 'message', value: message }));
        form.append($('<input>', { type: 'hidden', name: 'type', value: type }));
        $('body').append(form);
        form.submit();
    };

    // ── 10. Mapa Interactivo Leaflet & Geocodificación ──
    var initialLat = parseFloat($('#latitude').val()) || -6.4852;
    var initialLng = parseFloat($('#longitude').val()) || -76.3656;
    var map, marker;

    function initLeafletMap() {
        var mapContainer = document.getElementById('map-preview');
        if (!mapContainer || typeof L === 'undefined') return;

        map = L.map('map-preview').setView([initialLat, initialLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap colaboradores'
        }).addTo(map);

        var customIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        marker = L.marker([initialLat, initialLng], {
            draggable: true,
            icon: customIcon
        }).addTo(map);

        marker.bindPopup('<b>{{ $store->name ?? "Ubicación de Tienda" }}</b><br>Arrastra para afinar').openPopup();

        marker.on('dragend', function(e) {
            var position = marker.getLatLng();
            updateCoordinates(position.lat, position.lng);
            reverseGeocode(position.lat, position.lng);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateCoordinates(e.latlng.lat, e.latlng.lng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });
    }

    function updateCoordinates(lat, lng) {
        $('#latitude').val(lat.toFixed(7));
        $('#longitude').val(lng.toFixed(7));
    }

    function reverseGeocode(lat, lng) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.display_name) {
                    if (!$('#address').val()) {
                        $('#address').val(data.display_name);
                    }
                    marker.setPopupContent('<b>' + ($('#name').val() || 'Tienda') + '</b><br><small>' + data.display_name + '</small>').openPopup();
                }
            })
            .catch(function() {});
    }

    $('#btn-search-address').on('click', function() {
        var query = $('#address-search').val().trim();
        if (!query) return;

        var searchUrl = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query + ', Perú');
        fetch(searchUrl)
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.length > 0) {
                    var lat = parseFloat(data[0].lat);
                    var lon = parseFloat(data[0].lon);
                    map.setView([lat, lon], 16);
                    marker.setLatLng([lat, lon]);
                    updateCoordinates(lat, lon);
                    if (!$('#address').val() || $('#address').val().length < 5) {
                        $('#address').val(data[0].display_name);
                    }
                } else {
                    alert('No se encontraron coordenadas para esa dirección. Puedes buscar otra o hacer clic en el mapa.');
                }
            })
            .catch(function() {
                alert('No fue posible geocodificar en este momento.');
            });
    });

    $('#address-search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#btn-search-address').click();
        }
    });

    $('#btn-current-location').on('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
                updateCoordinates(lat, lng);
                reverseGeocode(lat, lng);
            }, function() {
                alert('No se pudo obtener la geolocalización de tu navegador.');
            });
        } else {
            alert('Geolocalización no soportada por el navegador.');
        }
    });

    $('#btn-center-tarapoto').on('click', function() {
        var tLat = -6.4852;
        var tLng = -76.3656;
        map.setView([tLat, tLng], 15);
        marker.setLatLng([tLat, tLng]);
        updateCoordinates(tLat, tLng);
    });

    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        if (e.target.id === 'tab-location-btn' && map) {
            setTimeout(function() { map.invalidateSize(); }, 200);
        }
    });

    setTimeout(initLeafletMap, 300);

    window.initGooglePlaces = function() {
        var input = document.getElementById('address-search');
        if (!input || typeof google === 'undefined' || !google.maps || !google.maps.places) return;
        var autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['geocode', 'establishment'],
            componentRestrictions: { country: 'pe' }
        });
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry) return;
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();
            updateCoordinates(lat, lng);
            $('#address').val(place.formatted_address || place.name);
            if (map && marker) {
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
            }
        });
    };

})(jQuery);
</script>
@endpush
