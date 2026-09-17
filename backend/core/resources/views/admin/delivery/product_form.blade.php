@extends('admin.layouts.app')

@push('style')
<style>
    /* ═════════════════════════════════════════════════════════════
       Sneat Theme Design Tokens for Product Form
       ═════════════════════════════════════════════════════════════ */
    .sneat-header-card {
        background: #ffffff !important;
        border-radius: 12px !important;
        border: 1px solid #d9dee3 !important;
        box-shadow: 0 2px 10px rgba(67, 89, 113, 0.08) !important;
        margin-bottom: 1.25rem !important;
        padding: 1.1rem 1.4rem !important;
    }
    .sneat-header-title {
        color: #2b2c40 !important;
        font-weight: 700 !important;
        font-size: 1.35rem !important;
        margin: 0 !important;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .sneat-header-subtitle {
        color: #697a8d !important;
        font-size: 0.88rem !important;
        margin-top: 0.25rem;
    }

    /* Cards */
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

    /* Form Controls */
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
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
        background-color: #fff !important;
        width: 100%;
    }
    .form-control-sneat:focus, .form-select-sneat:focus {
        border-color: #696cff !important;
        box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.14) !important;
        outline: none !important;
    }

    /* Switch Style */
    .sneat-switch-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: #f8f9fa;
        border: 1px solid #e7eaf0;
        border-radius: 8px;
        margin-bottom: 0.85rem;
        transition: background 0.15s ease;
    }
    .sneat-switch-wrapper:hover {
        background: #f1f3f7;
    }
    .sneat-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
        margin-bottom: 0;
    }
    .sneat-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .sneat-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cdd4dc;
        transition: .3s;
        border-radius: 24px;
    }
    .sneat-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }
    .sneat-switch input:checked + .sneat-slider {
        background-color: #696cff;
    }
    .sneat-switch input:checked + .sneat-slider:before {
        transform: translateX(20px);
    }

    /* Dynamic Tables / Repeaters */
    .repeater-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 0.5rem;
    }
    .repeater-row {
        background: #f9fafb;
        border: 1px solid #e5e9f0;
        border-radius: 8px;
        padding: 0.65rem 0.85rem;
        margin-bottom: 0.65rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s ease;
    }
    .repeater-row:hover {
        background: #ffffff;
        border-color: #d0d7e2;
        box-shadow: 0 2px 8px rgba(67, 89, 113, 0.06);
    }
    .btn-remove-row {
        width: 36px;
        height: 36px;
        border-radius: 6px !important;
        background: #ffebee !important;
        border: 1px solid rgba(198, 40, 40, 0.25) !important;
        color: #c62828 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
        padding: 0;
        flex-shrink: 0;
    }
    .btn-remove-row:hover {
        background: #c62828 !important;
        color: #ffffff !important;
    }

    /* Buttons */
    .btn-sneat-submit {
        background: #696cff !important;
        background-color: #696cff !important;
        border: 1px solid #696cff !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        padding: 0.6rem 1.4rem !important;
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
        font-size: 0.9rem !important;
        padding: 0.6rem 1.25rem !important;
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

    .btn-sneat-outline-primary {
        background: transparent !important;
        border: 1px dashed #696cff !important;
        color: #696cff !important;
        font-weight: 600 !important;
        font-size: 0.84rem !important;
        padding: 0.45rem 0.95rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
    }
    .btn-sneat-outline-primary:hover {
        background: #ecebff !important;
        border-color: #5f61e6 !important;
        color: #5f61e6 !important;
    }

    /* Badges */
    .badge-sneat {
        font-size: 0.78rem !important;
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

    /* Image Preview */
    .image-preview-box {
        width: 100%;
        height: 200px;
        border-radius: 8px;
        border: 2px dashed #d9dee3;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        transition: border-color 0.2s ease;
    }
    .image-preview-box:hover {
        border-color: #696cff;
        background: #f5f6fe;
    }
    .image-preview-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .image-preview-placeholder {
        text-align: center;
        color: #8592a3;
    }
    .image-preview-placeholder i {
        font-size: 2.5rem;
        color: #696cff;
        margin-bottom: 0.4rem;
        display: block;
    }
</style>
@endpush

@section('panel')
@php
    $isEdit = isset($product) && $product->id;
    $selectedCategoryId = old('store_category_id', $product->store_category_id ?? request()->query('category_id'));
@endphp

<!-- Sneat Header Card -->
<div class="sneat-header-card d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h4 class="sneat-header-title">
            <i class="las la-utensils text--primary"></i>
            {{ $isEdit ? 'Editar Producto' : 'Nuevo Producto' }}
            <span class="badge-sneat badge-sneat-primary">
                <i class="las la-store"></i> {{ $store->name }}
            </span>
        </h4>
        <div class="sneat-header-subtitle">
            {{ $isEdit ? 'Modifica precios, fotos, presentaciones y adicionales del producto.' : 'Completa la información para agregar un nuevo ítem al catálogo de la tienda.' }}
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.delivery.store.detail', $store->id) }}" class="btn-sneat-cancel">
            <i class="las la-arrow-left"></i> Volver a la Tienda
        </a>
        <button type="button" class="btn-sneat-submit" onclick="document.getElementById('productForm').submit();">
            <i class="las la-check-circle"></i> {{ $isEdit ? 'Guardar Cambios' : 'Crear Producto' }}
        </button>
    </div>
</div>

<form id="productForm" method="POST" action="{{ route('admin.delivery.product.save', $product->id ?? null) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="store_id" value="{{ $store->id }}">

    <div class="row">
        <!-- Main Column (8 cols) -->
        <div class="col-lg-8">
            <!-- Basic Info Card -->
            <div class="sneat-card">
                <div class="sneat-card-header">
                    <h6><i class="las la-info-circle"></i> Información General</h6>
                    @if($isEdit)
                        <span class="badge-sneat badge-sneat-neutral">ID #{{ $product->id }}</span>
                    @endif
                </div>
                <div class="sneat-card-body">
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label-sneat">Nombre del Producto <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control-sneat"
                                   value="{{ old('name', $product->name ?? '') }}"
                                   placeholder="Ej: Hamburguesa Clásica con Queso" required maxlength="150">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label-sneat">Categoría <span class="text-danger">*</span></label>
                            <select name="store_category_id" class="form-select-sneat" required>
                                <option value="">-- Selecciona Categoría --</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ $selectedCategoryId == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label-sneat">Descripción</label>
                            <textarea name="description" class="form-control-sneat" rows="3"
                                      placeholder="Describe los ingredientes, porciones o detalles clave del producto...">{{ old('description', $product->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Card -->
            <div class="sneat-card">
                <div class="sneat-card-header">
                    <h6><i class="las la-tag"></i> Precios y Posición</h6>
                    <div id="discountPreviewBadge" style="display: none;">
                        <span class="badge-sneat badge-sneat-success">
                            <i class="las la-percentage"></i> <span id="discountPercentageText">0% OFF</span>
                        </span>
                    </div>
                </div>
                <div class="sneat-card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label-sneat">Precio Regular (S/) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f5f5f9; border-color:#d9dee3; color:#566a7f; font-weight:700;">S/</span>
                                <input type="number" step="0.01" min="0" name="price" id="inputPrice" class="form-control-sneat"
                                       value="{{ old('price', $product->price ?? '') }}" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-sneat">Precio de Oferta (S/)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f5f5f9; border-color:#d9dee3; color:#566a7f; font-weight:700;">S/</span>
                                <input type="number" step="0.01" min="0" name="discount_price" id="inputDiscountPrice" class="form-control-sneat"
                                       value="{{ old('discount_price', $product->discount_price ?? '') }}" placeholder="0.00">
                            </div>
                            <small class="text-muted" style="font-size:0.75rem;">Opcional. Si se define, el precio regular se tachará.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-sneat">Orden de Visualización</label>
                            <input type="number" name="sort_order" class="form-control-sneat"
                                   value="{{ old('sort_order', $product->sort_order ?? 0) }}" placeholder="0">
                            <small class="text-muted" style="font-size:0.75rem;">Menor número aparece primero.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variations Card -->
            <div class="sneat-card">
                <div class="sneat-card-header">
                    <h6><i class="las la-layer-group"></i> Presentaciones y Variaciones</h6>
                    <button type="button" class="btn-sneat-outline-primary" onclick="addRepeaterRow('variationsContainer', 'variation')">
                        <i class="las la-plus-circle"></i> Agregar Variación
                    </button>
                </div>
                <div class="sneat-card-body">
                    <p class="text-muted mb-3" style="font-size: 0.83rem;">
                        Permite a tus clientes elegir entre diferentes tamaños o versiones (ej: <em>Personal, Mediana, Familiar, 500ml</em>).
                    </p>
                    <div id="variationsContainer">
                        @php $variations = $product->variations ?? []; @endphp
                        @foreach($variations as $v)
                            <div class="repeater-row variation-row">
                                <input type="hidden" name="variation_id[]" value="{{ $v->id }}">
                                <div style="flex: 1;">
                                    <input type="text" name="variation_name[]" class="form-control-sneat"
                                           placeholder="Nombre de la presentación (ej: Familiar)" value="{{ $v->name }}">
                                </div>
                                <div style="width: 140px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text" style="background:#f5f5f9; border-color:#d9dee3;">S/</span>
                                        <input type="number" step="0.01" min="0" name="variation_price[]" class="form-control-sneat"
                                               placeholder="Precio" value="{{ $v->price }}">
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Eliminar variación">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    @if(count($variations) == 0)
                        <div id="emptyVariationsNotice" class="text-center py-3 text-muted" style="font-size:0.85rem; border:1px dashed #e0e4e8; border-radius:8px;">
                            <i class="las la-layer-group" style="font-size:1.6rem; color:#b0b7c3; display:block; margin-bottom:0.25rem;"></i>
                            No hay variaciones configuradas. Haz clic en <strong>+ Agregar Variación</strong> si este producto tiene presentaciones.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Addons Card -->
            <div class="sneat-card">
                <div class="sneat-card-header">
                    <h6><i class="las la-plus-square"></i> Complementos y Adicionales</h6>
                    <button type="button" class="btn-sneat-outline-primary" onclick="addRepeaterRow('addonsContainer', 'addon')">
                        <i class="las la-plus-circle"></i> Agregar Adicional
                    </button>
                </div>
                <div class="sneat-card-body">
                    <p class="text-muted mb-3" style="font-size: 0.83rem;">
                        Opciones extra que el cliente puede sumar al pedido (ej: <em>Queso extra, Tocino, Salsa especial, Bebida</em>).
                    </p>
                    <div id="addonsContainer">
                        @php $addons = $product->addons ?? []; @endphp
                        @foreach($addons as $a)
                            <div class="repeater-row addon-row">
                                <input type="hidden" name="addon_id[]" value="{{ $a->id }}">
                                <div style="flex: 1;">
                                    <input type="text" name="addon_name[]" class="form-control-sneat"
                                           placeholder="Nombre del adicional (ej: Queso extra)" value="{{ $a->name }}">
                                </div>
                                <div style="width: 140px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text" style="background:#f5f5f9; border-color:#d9dee3;">S/</span>
                                        <input type="number" step="0.01" min="0" name="addon_price[]" class="form-control-sneat"
                                               placeholder="Precio extra" value="{{ $a->price }}">
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Eliminar adicional">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    @if(count($addons) == 0)
                        <div id="emptyAddonsNotice" class="text-center py-3 text-muted" style="font-size:0.85rem; border:1px dashed #e0e4e8; border-radius:8px;">
                            <i class="las la-plus-square" style="font-size:1.6rem; color:#b0b7c3; display:block; margin-bottom:0.25rem;"></i>
                            No hay adicionales configurados. Haz clic en <strong>+ Agregar Adicional</strong> para ofrecer complementos.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Column (4 cols) -->
        <div class="col-lg-4">
            <!-- Visibility & Status Card -->
            <div class="sneat-card">
                <div class="sneat-card-header">
                    <h6><i class="las la-eye"></i> Estado y Visibilidad</h6>
                </div>
                <div class="sneat-card-body">
                    <div class="sneat-switch-wrapper">
                        <div>
                            <div style="font-weight: 700; color: #2b2c40; font-size: 0.88rem;">Producto Activo</div>
                            <div style="color: #8592a3; font-size: 0.76rem;">Visible en el catálogo de la tienda</div>
                        </div>
                        <label class="sneat-switch">
                            <input type="checkbox" name="status" value="1" {{ old('status', $product->status ?? 1) ? 'checked' : '' }}>
                            <span class="sneat-slider"></span>
                        </label>
                    </div>

                    <div class="sneat-switch-wrapper">
                        <div>
                            <div style="font-weight: 700; color: #2b2c40; font-size: 0.88rem;">⭐ Destacar Producto</div>
                            <div style="color: #8592a3; font-size: 0.76rem;">Aparecerá en banners y recomendados</div>
                        </div>
                        <label class="sneat-switch">
                            <input type="checkbox" name="is_promoted" value="1" {{ old('is_promoted', $product->is_promoted ?? 0) ? 'checked' : '' }}>
                            <span class="sneat-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Product Image Card -->
            <div class="sneat-card">
                <div class="sneat-card-header">
                    <h6><i class="las la-image"></i> Fotografía del Producto</h6>
                </div>
                <div class="sneat-card-body">
                    <div class="image-preview-box" id="imageDropZone" onclick="document.getElementById('imageFileInput').click();">
                        @if($isEdit && !empty($product->image))
                            <img id="imagePreviewImg" src="{{ getImage(getFilePath('product') . '/' . $product->image) }}" alt="{{ $product->name }}">
                        @else
                            <img id="imagePreviewImg" src="" alt="Vista previa" style="display:none;">
                            <div class="image-preview-placeholder" id="imagePlaceholder">
                                <i class="las la-cloud-upload-alt"></i>
                                <div style="font-weight:600; font-size:0.86rem; color:#566a7f;">Subir imagen</div>
                                <div style="font-size:0.75rem; color:#a1acb8;">PNG, JPG o WEBP (máx. 2MB)</div>
                            </div>
                        @endif
                    </div>
                    <input type="file" name="image" id="imageFileInput" class="d-none" accept="image/*">
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <button type="button" class="btn-sneat-cancel btn-sm" onclick="document.getElementById('imageFileInput').click();" style="padding:0.35rem 0.75rem; font-size:0.8rem;">
                            <i class="las la-folder-open"></i> Seleccionar archivo
                        </button>
                        <span class="text-muted" style="font-size:0.75rem;" id="fileNameDisplay">Sin archivo nuevo</span>
                    </div>
                </div>
            </div>

            <!-- Sticky Actions Card -->
            <div class="sneat-card">
                <div class="sneat-card-body text-center">
                    <button type="submit" class="btn-sneat-submit w-100 mb-2">
                        <i class="las la-save" style="font-size:1.15rem;"></i>
                        {{ $isEdit ? 'Guardar Cambios del Producto' : 'Crear y Publicar Producto' }}
                    </button>
                    <a href="{{ route('admin.delivery.store.detail', $store->id) }}" class="btn-sneat-cancel w-100">
                        <i class="las la-times"></i> Cancelar y Volver
                    </a>

                    @if($isEdit)
                        <hr style="border-color:#eceef1; margin:1rem 0;">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100" style="border-radius:6px;" onclick="confirmDeleteProduct()">
                            <i class="las la-trash"></i> Eliminar este Producto
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

@if($isEdit)
<form id="deleteProductForm" method="POST" action="{{ route('admin.delivery.product.delete', $product->id) }}" class="d-none">
    @csrf
</form>
@endif

@push('script')
<script>
    // ═══════════════════════════════════════════════════
    // Sneat Discount Calculator Preview
    // ═══════════════════════════════════════════════════
    const inputPrice = document.getElementById('inputPrice');
    const inputDiscountPrice = document.getElementById('inputDiscountPrice');
    const discountPreviewBadge = document.getElementById('discountPreviewBadge');
    const discountPercentageText = document.getElementById('discountPercentageText');

    function updateDiscountBadge() {
        const price = parseFloat(inputPrice.value) || 0;
        const discPrice = parseFloat(inputDiscountPrice.value) || 0;

        if (price > 0 && discPrice > 0 && discPrice < price) {
            const savings = ((price - discPrice) / price) * 100;
            discountPercentageText.textContent = Math.round(savings) + '% AHORRO';
            discountPreviewBadge.style.display = 'block';
        } else {
            discountPreviewBadge.style.display = 'none';
        }
    }

    if (inputPrice && inputDiscountPrice) {
        inputPrice.addEventListener('input', updateDiscountBadge);
        inputDiscountPrice.addEventListener('input', updateDiscountBadge);
        updateDiscountBadge();
    }

    // ═══════════════════════════════════════════════════
    // Image Upload & Live Preview
    // ═══════════════════════════════════════════════════
    const imageFileInput = document.getElementById('imageFileInput');
    const imagePreviewImg = document.getElementById('imagePreviewImg');
    const imagePlaceholder = document.getElementById('imagePlaceholder');
    const fileNameDisplay = document.getElementById('fileNameDisplay');

    if (imageFileInput) {
        imageFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function(evt) {
                    imagePreviewImg.src = evt.target.result;
                    imagePreviewImg.style.display = 'block';
                    if (imagePlaceholder) imagePlaceholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ═══════════════════════════════════════════════════
    // Dynamic Repeater Rows (Variations & Addons)
    // ═══════════════════════════════════════════════════
    function addRepeaterRow(containerId, type) {
        const container = document.getElementById(containerId);
        const emptyNotice = document.getElementById(type === 'variation' ? 'emptyVariationsNotice' : 'emptyAddonsNotice');
        if (emptyNotice) emptyNotice.style.display = 'none';

        const row = document.createElement('div');
        row.className = `repeater-row ${type}-row`;

        const placeholderName = type === 'variation' ? 'Nombre (ej: Familiar, Grande)' : 'Nombre (ej: Queso extra, Bebida)';
        const placeholderPrice = type === 'variation' ? 'Precio' : 'Precio extra';

        row.innerHTML = `
            <input type="hidden" name="${type}_id[]" value="">
            <div style="flex: 1;">
                <input type="text" name="${type}_name[]" class="form-control-sneat" placeholder="${placeholderName}" required>
            </div>
            <div style="width: 140px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="background:#f5f5f9; border-color:#d9dee3;">S/</span>
                    <input type="number" step="0.01" min="0" name="${type}_price[]" class="form-control-sneat" placeholder="${placeholderPrice}" required>
                </div>
            </div>
            <button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Eliminar fila">
                <i class="las la-trash"></i>
            </button>
        `;
        container.appendChild(row);
    }

    function removeRow(button) {
        const row = button.closest('.repeater-row');
        const container = row.parentElement;
        row.remove();

        const isVariation = container.id === 'variationsContainer';
        const remaining = container.querySelectorAll('.repeater-row').length;
        if (remaining === 0) {
            const emptyNotice = document.getElementById(isVariation ? 'emptyVariationsNotice' : 'emptyAddonsNotice');
            if (emptyNotice) emptyNotice.style.display = 'block';
        }
    }

    function confirmDeleteProduct() {
        if (confirm('¿Estás completamente seguro de eliminar este producto? Esta acción no se puede deshacer.')) {
            document.getElementById('deleteProductForm').submit();
        }
    }
</script>
@endpush
@endsection
