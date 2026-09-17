@extends('admin.layouts.app')

@push('style')
<style>
    /* ═════════════════════════════════════════════════════════════
       Sneat Theme Refined Design Tokens for Store Detail
       ═════════════════════════════════════════════════════════════ */
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
    .badge-sneat-primary { background-color: #ecebff !important; color: #5f61e6 !important; border: 1px solid rgba(105, 108, 255, 0.3) !important; }
    .badge-sneat-success { background-color: #e8fadf !important; color: #2e7d32 !important; border: 1px solid rgba(46, 125, 50, 0.3) !important; }
    .badge-sneat-warning { background-color: #fff4e5 !important; color: #d85a00 !important; border: 1px solid rgba(216, 90, 0, 0.3) !important; }
    .badge-sneat-danger  { background-color: #ffebee !important; color: #c62828 !important; border: 1px solid rgba(198, 40, 40, 0.3) !important; }
    .badge-sneat-info    { background-color: #e1f5fe !important; color: #0277bd !important; border: 1px solid rgba(2, 119, 189, 0.3) !important; }
    .badge-sneat-neutral { background-color: #f5f5f9 !important; color: #566a7f !important; border: 1px solid #d9dee3 !important; }

    /* Custom Sneat Buttons */
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
    .btn-sneat-submit i, .btn-sneat-submit span { color: #ffffff !important; }

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
    .btn-sneat-hero-action:hover i { color: #696cff !important; }

    /* Sneat Stat Cards */
    .sneat-stat-card {
        background: #ffffff !important;
        border: 1px solid #e0e4e8 !important;
        border-radius: 8px !important;
        padding: 1.15rem 1.25rem !important;
        box-shadow: 0 2px 6px rgba(67, 89, 113, 0.05) !important;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.15s ease;
    }
    .sneat-stat-card:hover {
        transform: translateY(-2px);
    }
    .sneat-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }
    .sneat-stat-title {
        color: #8592a3 !important;
        font-size: 0.82rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 0.15rem !important;
    }
    .sneat-stat-value {
        color: #2b2c40 !important;
        font-size: 1.45rem !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        margin: 0 !important;
    }

    /* Sneat Cards & Tables */
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

    .table-sneat {
        margin-bottom: 0;
    }
    .table-sneat thead th {
        background-color: #f5f5f9 !important;
        color: #566a7f !important;
        font-weight: 700 !important;
        font-size: 0.78rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.6px !important;
        border-bottom: 1px solid #e0e4e8 !important;
        padding: 0.75rem 1rem !important;
    }
    .table-sneat tbody td {
        padding: 0.8rem 1rem !important;
        font-size: 0.88rem !important;
        color: #435971 !important;
        border-bottom: 1px solid #eceef1 !important;
        vertical-align: middle !important;
    }
    .table-sneat tbody tr:hover {
        background-color: #fafafc !important;
    }

    /* Product card item */
    .product-list-item {
        border-bottom: 1px solid #eceef1;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        transition: background-color 0.15s ease;
    }
    .product-list-item:hover {
        background-color: #fafafc;
    }
    .product-list-item:last-child {
        border-bottom: 0;
    }

    /* Nav Pills */
    .nav-pills-sneat {
        display: flex;
        gap: 0.35rem;
        border: 0;
        margin-bottom: 1.25rem;
    }
    .nav-pills-sneat .nav-link {
        color: #566a7f !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        border-radius: 6px !important;
        padding: 0.55rem 1rem !important;
        background: #ffffff !important;
        border: 1px solid #e0e4e8 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.45rem !important;
        transition: all 0.15s ease !important;
    }
    .nav-pills-sneat .nav-link:hover {
        color: #696cff !important;
        border-color: #696cff !important;
        background: #f5f5f9 !important;
    }
    .nav-pills-sneat .nav-link.active {
        color: #ffffff !important;
        background: #696cff !important;
        border-color: #696cff !important;
        box-shadow: 0 3px 8px rgba(105, 108, 255, 0.35) !important;
    }
    .nav-pills-sneat .nav-link.active i {
        color: #ffffff !important;
    }
</style>
@endpush

@section('panel')
@php
    $coverUrl = $store->cover_image ? getImage('assets/images/store_cover/' . $store->cover_image) : null;
    $logoUrl = $store->image ? getImage('assets/images/store/' . $store->image) : null;
@endphp

<!-- Store Hero Overview Card -->
<div class="store-hero-card">
    <div class="store-hero-cover" @if($coverUrl) style="background-image: url('{{ $coverUrl }}');" @endif></div>
    <div class="store-hero-body">
        <div class="store-avatar-wrap">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo de {{ $store->name }}">
            @else
                <div class="store-avatar-placeholder">
                    <i class="las la-store fs-1"></i>
                </div>
            @endif
        </div>
        <div class="store-hero-content d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="store-hero-title mb-0">{{ $store->name }}</h4>
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
                </div>

                <div class="store-hero-meta d-flex align-items-center gap-3 flex-wrap">
                    @if($store->seller)
                        <span><i class="las la-user-tie"></i> <strong>Vendedor:</strong> {{ $store->seller->name }} ({{ $store->seller->email }})</span>
                    @endif
                    @if($sellerCompany && $sellerCompany->document_number)
                        <span><i class="las la-id-card"></i> <strong>RUC:</strong> {{ $sellerCompany->document_number }}</span>
                    @endif
                    @if($store->address)
                        <span><i class="las la-map-marker-alt"></i> {{ Str::limit($store->address, 65) }}</span>
                    @endif
                    @if($registeredDevicesCount > 0)
                        <span><i class="las la-mobile-alt text-success"></i> <strong>{{ $registeredDevicesCount }}</strong> dispositivo(s) conectado(s)</span>
                    @endif
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn-sneat-hero-action" data-bs-toggle="modal" data-bs-target="#notificationModal">
                    <i class="las la-paper-plane text-primary"></i> <span>Notificar a Tienda</span>
                </button>
                <a href="{{ route('delivery.store', $store->id) }}" target="_blank" class="btn-sneat-hero-action" title="Ver catálogo en marketplace">
                    <i class="las la-external-link-alt text-secondary"></i> <span>Ver en Web</span>
                </a>
                <a href="{{ route('admin.delivery.product.create', $store->id) }}" class="btn-sneat-hero-action text-primary">
                    <i class="las la-plus-circle text-primary"></i> <span>+ Producto</span>
                </a>
                <a href="{{ route('admin.delivery.store.edit', $store->id) }}" class="btn-sneat-submit">
                    <i class="las la-edit"></i> <span>Editar Tienda</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="sneat-stat-card">
            <div class="sneat-stat-icon" style="background:#ecebff; color:#696cff;">
                <i class="las la-hamburger"></i>
            </div>
            <div>
                <div class="sneat-stat-title">Productos en Carta</div>
                <h5 class="sneat-stat-value">{{ $totalProducts }}</h5>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sneat-stat-card">
            <div class="sneat-stat-icon" style="background:#e1f5fe; color:#0288d1;">
                <i class="las la-layer-group"></i>
            </div>
            <div>
                <div class="sneat-stat-title">Categorías Propias</div>
                <h5 class="sneat-stat-value">{{ $totalCategories }}</h5>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sneat-stat-card">
            <div class="sneat-stat-icon" style="background:#fff4e5; color:#f57c00;">
                <i class="las la-motorcycle"></i>
            </div>
            <div>
                <div class="sneat-stat-title">Pedidos Delivery</div>
                <h5 class="sneat-stat-value">{{ $totalOrders }}</h5>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="sneat-stat-card">
            <div class="sneat-stat-icon" style="background:#e8fadf; color:#2e7d32;">
                <i class="las la-wallet"></i>
            </div>
            <div>
                <div class="sneat-stat-title">Ventas Totales</div>
                <h5 class="sneat-stat-value">S/ {{ number_format($totalSales, 2) }}</h5>
            </div>
        </div>
    </div>
</div>

<!-- Nav Pills Tabs -->
<ul class="nav nav-pills-sneat" id="storeDetailTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-menu-btn" data-bs-toggle="pill" data-bs-target="#tab-menu" type="button" role="tab">
            <i class="las la-utensils fs-5"></i> <span>1. Carta y Productos ({{ $totalProducts }})</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-fiscal-btn" data-bs-toggle="pill" data-bs-target="#tab-fiscal" type="button" role="tab">
            <i class="las la-file-invoice-dollar fs-5"></i> <span>2. Ficha Técnica & SUNAT</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-orders-btn" data-bs-toggle="pill" data-bs-target="#tab-orders" type="button" role="tab">
            <i class="las la-receipt fs-5"></i> <span>3. Últimos Pedidos ({{ $recentOrders->count() }})</span>
        </button>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="storeDetailTabContent">

    <!-- ══════════════════════════════════════════════════════
         TAB 1: CARTA Y PRODUCTOS
    ══════════════════════════════════════════════════════ -->
    <div class="tab-pane fade show active" id="tab-menu" role="tabpanel">
        <div class="row">
            <!-- Columna Izquierda: Gestor de Categorías de la Tienda -->
            <div class="col-lg-5">
                <!-- Crear Categoría -->
                <div class="sneat-card">
                    <div class="sneat-card-header">
                        <h6><i class="las la-folder-plus"></i> Nueva Categoría de Carta</h6>
                    </div>
                    <div class="sneat-card-body">
                        <form method="POST" action="{{ route('admin.delivery.store.category.save', $store->id) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nombre de Categoría *</label>
                                <input type="text" name="name" class="form-control" placeholder="Ej: Hamburguesas, Bebidas, Combos..." required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Orden de Visualización</label>
                                    <input type="number" name="sort_order" class="form-control" value="0" min="0">
                                </div>
                                <div class="col-6 d-flex align-items-end">
                                    <button type="submit" class="btn-sneat-submit w-100 justify-content-center">
                                        <i class="las la-plus"></i> <span>Crear Categoría</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Categorías -->
                <div class="sneat-card">
                    <div class="sneat-card-header">
                        <h6><i class="las la-list"></i> Categorías Registradas ({{ $store->categories->count() }})</h6>
                    </div>
                    <div class="sneat-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sneat">
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th class="text-center">Prod.</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($store->categories as $cat)
                                    <tr>
                                        <td>
                                            <form method="POST" action="{{ route('admin.delivery.store.category.update', $cat->id) }}" class="d-flex align-items-center gap-1">
                                                @csrf
                                                <input type="text" name="name" value="{{ $cat->name }}" class="form-control form-control-sm" style="min-width: 120px;" required>
                                                <input type="number" name="sort_order" value="{{ $cat->sort_order }}" class="form-control form-control-sm" style="width: 50px;" title="Orden">
                                                <button type="submit" class="btn btn-sm btn-link text-primary p-0" title="Guardar cambios"><i class="las la-check fs-5"></i></button>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge-sneat badge-sneat-primary">{{ $cat->products->count() }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.delivery.store.category.toggle', $cat->id) }}" class="badge-sneat {{ $cat->status ? 'badge-sneat-success' : 'badge-sneat-danger' }}" style="text-decoration:none;" title="Clic para alternar">
                                                {{ $cat->status ? 'Activo' : 'Oculto' }}
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.delivery.store.category.delete', $cat->id) }}" onsubmit="return confirm('¿Eliminar esta categoría? Los productos quedarán sin categoría.')" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Eliminar"><i class="las la-trash fs-5"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="las la-folder-open fs-2 d-block mb-1"></i>
                                            Sin categorías creadas aún.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Productos por Categoría -->
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="las la-boxes text-primary"></i> Catálogo de Productos</h6>
                    <a href="{{ route('admin.delivery.product.create', $store->id) }}" class="btn-sneat-submit">
                        <i class="las la-plus"></i> <span>+ Nuevo Producto</span>
                    </a>
                </div>

                @forelse($store->categories as $cat)
                <div class="sneat-card mb-3">
                    <div class="sneat-card-header bg-light">
                        <h6>
                            <span class="badge-sneat badge-sneat-primary me-1">#{{ $cat->sort_order }}</span>
                            {{ $cat->name }}
                            <small class="text-muted fw-normal ms-2">({{ $cat->products->count() }} productos)</small>
                        </h6>
                        <a href="{{ route('admin.delivery.product.create', ['storeId' => $store->id, 'category_id' => $cat->id]) }}" class="btn-sneat-hero-action py-1 px-2 text-primary" style="font-size: 0.78rem;">
                            <i class="las la-plus"></i> Agregar aquí
                        </a>
                    </div>
                    <div class="sneat-card-body p-0">
                        @if($cat->products->isNotEmpty())
                            @foreach($cat->products as $p)
                            <div class="product-list-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded overflow-hidden border flex-shrink-0" style="width: 48px; height: 48px; background:#f5f5f9;">
                                        @if($p->image)
                                            <img src="{{ getImage('assets/images/product/' . $p->image) }}" alt="{{ $p->name }}" style="width:100%; height:100%; object-fit:cover;">
                                        @else
                                            <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                                <i class="las la-box fs-4"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.92rem;">{{ $p->name }}</h6>
                                        <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                            @if($p->variations && $p->variations->count() > 0)
                                                <span><i class="las la-sliders-h text-primary"></i> {{ $p->variations->count() }} var.</span>
                                            @endif
                                            @if($p->addons && $p->addons->count() > 0)
                                                <span><i class="las la-plus-circle text-info"></i> {{ $p->addons->count() }} addons</span>
                                            @endif
                                            @if($p->status)
                                                <span class="text-success"><i class="las la-check"></i> Activo</span>
                                            @else
                                                <span class="text-danger"><i class="las la-ban"></i> Oculto</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-end">
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">S/ {{ number_format($p->price, 2) }}</div>
                                        @if($p->discount_price && $p->discount_price > 0)
                                            <small class="text-danger"><del>S/ {{ number_format($p->discount_price, 2) }}</del></small>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center gap-1">
                                        <a href="{{ route('admin.delivery.product.edit', $p->id) }}" class="btn-sneat-hero-action py-1 px-2" title="Editar producto">
                                            <i class="las la-pen text-primary"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.delivery.product.delete', $p->id) }}" onsubmit="return confirm('¿Eliminar producto {{ $p->name }}?')" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-sneat-hero-action py-1 px-2" title="Eliminar producto">
                                                <i class="las la-trash text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-3 text-muted small">
                                Sin productos asignados a esta categoría.
                            </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="sneat-card text-center py-5">
                    <i class="las la-utensils fs-1 text-primary mb-2"></i>
                    <h5 class="fw-bold text-dark">No hay categorías ni productos aún</h5>
                    <p class="text-muted small mb-3">Crea la primera categoría en el panel izquierdo para comenzar a organizar la carta.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB 2: FICHA TÉCNICA & DATOS SUNAT
    ══════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="tab-fiscal" role="tabpanel">
        <div class="row">
            <!-- Facturación SUNAT -->
            <div class="col-lg-6">
                <div class="sneat-card">
                    <div class="sneat-card-header">
                        <h6><i class="las la-file-invoice-dollar"></i> Datos Fiscales y Facturación Electrónica</h6>
                        <a href="{{ route('admin.delivery.store.edit', $store->id) }}#tab-sunat" class="btn-sneat-hero-action py-1 px-2">
                            <i class="las la-pen text-primary"></i> Configurar
                        </a>
                    </div>
                    <div class="sneat-card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;"><strong>RUC:</strong></td>
                                <td class="fw-bold font-monospace text-dark">{{ $sellerCompany->document_number ?? 'No registrado' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Razón Social:</strong></td>
                                <td class="fw-bold text-dark">{{ $sellerCompany->business_name ?? 'No registrada' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Nombre Comercial:</strong></td>
                                <td>{{ $sellerCompany->trade_name ?? $store->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Dirección Fiscal:</strong></td>
                                <td>{{ $sellerCompany->address ?? 'No especificada' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Ubigeo:</strong></td>
                                <td class="font-monospace">{{ $sellerCompany->ubigeo ?? 'No registrado' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Afectación Tributaria:</strong></td>
                                <td>
                                    <span class="badge-sneat badge-sneat-primary text-uppercase">{{ $sellerCompany->default_tax_type ?? 'gravado' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Entorno SUNAT:</strong></td>
                                <td>
                                    @if($sellerCompany && $sellerCompany->sunat_env === 'production')
                                        <span class="badge-sneat badge-sneat-success"><i class="las la-shield-alt"></i> Producción Oficial</span>
                                    @else
                                        <span class="badge-sneat badge-sneat-warning"><i class="las la-vial"></i> Modo Beta / Pruebas</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Usuario SOL:</strong></td>
                                <td class="font-monospace">{{ $sellerCompany->sunat_sol_user ?? 'Sin configurar' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Certificado Digital:</strong></td>
                                <td>
                                    @if($sellerCompany && $sellerCompany->sunat_cert_path)
                                        <span class="badge-sneat badge-sneat-success"><i class="las la-check"></i> Instalado (.pfx)</span>
                                    @else
                                        <span class="badge-sneat badge-sneat-danger"><i class="las la-times"></i> No instalado</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Plan y Membresía -->
                <div class="sneat-card">
                    <div class="sneat-card-header">
                        <h6><i class="las la-crown"></i> Plan Comercial y Suscripción</h6>
                        <a href="{{ route('admin.delivery.store.edit', $store->id) }}#tab-subscription" class="btn-sneat-hero-action py-1 px-2">
                            <i class="las la-sync text-primary"></i> Renovar
                        </a>
                    </div>
                    <div class="sneat-card-body">
                        @if($activeSubscription)
                            <div class="p-3 mb-3 rounded border" style="background:#f5f5f9;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold" style="color:#696cff;">{{ $activeSubscription->package?->name ?? 'Plan Asignado' }}</h6>
                                        <small class="text-muted">Vence: {{ $activeSubscription->expires_at ? showDateTime($activeSubscription->expires_at, 'd/m/Y') : 'Ilimitado' }}</small>
                                    </div>
                                    <span class="fs-5 fw-bold text-dark">S/ {{ number_format($activeSubscription->package?->price ?? 0, 2) }}</span>
                                </div>
                            </div>
                        @else
                            <p class="text-muted small mb-0">Esta tienda no cuenta con un plan empresarial activo en este momento.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Horarios y Ubicación -->
            <div class="col-lg-6">
                <div class="sneat-card">
                    <div class="sneat-card-header">
                        <h6><i class="las la-map-marked-alt"></i> Ubicación y Coordenadas de App</h6>
                        <a href="{{ route('admin.delivery.store.edit', $store->id) }}#tab-location" class="btn-sneat-hero-action py-1 px-2">
                            <i class="las la-map-pin text-primary"></i> Ajustar en Mapa
                        </a>
                    </div>
                    <div class="sneat-card-body">
                        <table class="table table-sm table-borderless mb-3">
                            <tr>
                                <td class="text-muted" style="width: 35%;"><strong>Dirección App:</strong></td>
                                <td class="fw-bold text-dark">{{ $store->address ?? 'No registrada' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Latitud GPS:</strong></td>
                                <td class="font-monospace fw-bold">{{ $store->latitude ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Longitud GPS:</strong></td>
                                <td class="font-monospace fw-bold">{{ $store->longitude ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Tiempo Prep:</strong></td>
                                <td>{{ $store->preparation_time ?? 15 }} minutos promedio</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><strong>Pedido Mínimo:</strong></td>
                                <td>S/ {{ number_format($store->min_order_amount ?? 0, 2) }}</td>
                            </tr>
                        </table>

                        @if($store->latitude && $store->longitude)
                            <a href="https://www.google.com/maps?q={{ $store->latitude }},{{ $store->longitude }}" target="_blank" class="btn-sneat-hero-action w-100 justify-content-center">
                                <i class="las la-external-link-alt text-primary"></i> Abrir coordenadas en Google Maps
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Horarios de Atención -->
                <div class="sneat-card">
                    <div class="sneat-card-header">
                        <h6><i class="las la-clock"></i> Horarios de Atención Registrados</h6>
                    </div>
                    <div class="sneat-card-body">
                        <p class="mb-2"><strong>Apertura / Cierre General:</strong> {{ $store->opening_time ?? '08:00' }} — {{ $store->closing_time ?? '23:00' }}</p>
                        
                        @if($store->schedules && $store->schedules->isNotEmpty())
                            <div class="mt-3">
                                <h6 class="small fw-bold text-muted text-uppercase mb-2">Turnos por Día</h6>
                                @foreach(\App\Models\StoreSchedule::days() as $dKey => $dName)
                                    @php $slots = $store->schedules->where('day', $dKey); @endphp
                                    @if($slots->isNotEmpty())
                                        <div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                                            <span class="fw-semibold text-dark small">{{ $dName }}</span>
                                            <div>
                                                @foreach($slots as $s)
                                                    <span class="badge-sneat badge-sneat-neutral font-monospace">{{ $s->open_time }} - {{ $s->close_time }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         TAB 3: ÚLTIMOS PEDIDOS DELIVERY
    ══════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="tab-orders" role="tabpanel">
        <div class="sneat-card">
            <div class="sneat-card-header">
                <h6><i class="las la-motorcycle"></i> Pedidos Recientes Despachados por {{ $store->name }}</h6>
                <a href="{{ route('admin.delivery.orders') }}?search={{ $store->name }}" class="btn-sneat-hero-action py-1 px-2">
                    <i class="las la-list text-primary"></i> Ver todos los pedidos
                </a>
            </div>
            <div class="sneat-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sneat">
                        <thead>
                            <tr>
                                <th># Pedido</th>
                                <th>Cliente</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Repartidor</th>
                                <th>Fecha</th>
                                <th class="text-end">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                            <tr>
                                <td>
                                    <strong class="font-monospace text-primary">#{{ $order->order_no ?? $order->id }}</strong>
                                </td>
                                <td>{{ $order->user?->fullname ?? $order->user?->username ?? 'Cliente' }}</td>
                                <td class="fw-bold text-dark">S/ {{ number_format($order->total, 2) }}</td>
                                <td>
                                    @if(in_array($order->status, ['delivered', 'completed']))
                                        <span class="badge-sneat badge-sneat-success">{{ ucfirst($order->status) }}</span>
                                    @elseif($order->status === 'cancelled')
                                        <span class="badge-sneat badge-sneat-danger">Cancelado</span>
                                    @else
                                        <span class="badge-sneat badge-sneat-warning">{{ ucfirst($order->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $order->driver?->name ?? 'Sin asignar' }}</td>
                                <td class="text-muted small">{{ showDateTime($order->created_at, 'd/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.delivery.order.detail', $order->id) }}" class="btn-sneat-hero-action py-1 px-2" title="Ver pedido">
                                        <i class="las la-eye text-primary"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="las la-receipt fs-2 d-block mb-1"></i>
                                    No se registran pedidos delivery para esta tienda aún.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Enviar Notificación Push (Accesible desde cabecera) -->
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

@endsection
