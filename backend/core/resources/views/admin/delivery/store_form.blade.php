@extends('admin.layouts.app')
@section('panel')
@if(gs('google_maps_api'))
<script>
    function initMapAutocomplete() {
        var input = document.getElementById('address-search');
        if (!input || typeof google === 'undefined' || !google.maps) return;
        var autocomplete = new google.maps.places.Autocomplete(input, { types: ['address'] });
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (!place.geometry) return;
            document.getElementById('latitude').value = place.geometry.location.lat();
            document.getElementById('longitude').value = place.geometry.location.lng();
            document.getElementById('address').value = place.formatted_address;
        });
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places&callback=initMapAutocomplete" async defer></script>
@endif
<div class="row"><div class="col-12"><div class="card">
<div class="card-header"><h5>{{ $pageTitle }}</h5></div>
<div class="card-body">
<form method="POST" action="{{ route('admin.delivery.store.save', $store->id ?? null) }}" enctype="multipart/form-data" novalidate>
@csrf
<div class="row">
<div class="col-md-12 mb-3">
    <label class="d-block">Asignar Vendedor *</label>
    <div class="form-check form-check-inline">
        <input class="form-check-input seller-option-radio" type="radio" name="seller_option" id="existingSeller" value="existing" checked>
        <label class="form-check-label" for="existingSeller">Vendedor Existente</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input seller-option-radio" type="radio" name="seller_option" id="newSeller" value="new">
        <label class="form-check-label" for="newSeller">Crear Nuevo Vendedor (Cuenta Seller)</label>
    </div>
</div>

<div class="col-md-6 mb-3" id="existing-seller-group">
    <label>Vendedor *</label>
    <select name="seller_id" class="form-select">
        <option value="">Seleccione un vendedor...</option>
        @foreach($sellers as $s)
            <option value="{{ $s->id }}" {{ ($store->seller_id??'')==$s->id?'selected':'' }}>{{ $s->name }} ({{ $s->email }})</option>
        @endforeach
    </select>
</div>

<div class="col-md-12 p-0" id="new-seller-group" style="display: none;">
    <div class="row px-3">
        <div class="col-md-4 mb-3">
            <label>Nombre del Vendedor *</label>
            <input type="text" name="seller_name" class="form-control" placeholder="Nombre completo">
        </div>
        <div class="col-md-4 mb-3">
            <label>Email del Vendedor *</label>
            <input type="text" name="seller_email" class="form-control" placeholder="correo@ejemplo.com">
        </div>
        <div class="col-md-4 mb-3">
            <label>Contraseña *</label>
            <input type="password" name="seller_password" class="form-control" placeholder="Mínimo 6 caracteres">
        </div>
    </div>
</div>

<div class="col-md-4 mb-3"><label>Nombre *</label><input type="text" name="name" class="form-control" value="{{ old('name',$store->name??'') }}" required></div>
<div class="col-md-2 mb-3"><label>Tipo de Tienda</label>
    <select name="store_type" class="form-select">
        @foreach(\App\Models\Store::types() as $k => $v)
        <option value="{{ $k }}" {{ ($store->store_type??'restaurant')==$k?'selected':'' }}>{{ $v }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6 mb-3"><label>Categorías Principales</label>
    <select name="general_category_ids[]" class="form-select" multiple size="5" onchange="filterSubCategories()">
        @foreach($generalCategories as $gc)
            @php $selected = isset($store) && $store->generalCategories->contains($gc->id); @endphp
            <option value="{{ $gc->id }}" data-subs="{{ $gc->allSubCategories->pluck('id')->implode(',') }}" {{ $selected?'selected':'' }}>{{ $gc->name }}</option>
        @endforeach
    </select>
    <small class="text-muted">Ctrl+Click para múltiples</small>
</div>
<div class="col-md-6 mb-3"><label>Sub-categorías</label>
    <select name="sub_category_ids[]" class="form-select" multiple size="5">
        @foreach($subCategories as $sc)
            @php $selected = isset($store) && $store->subCategories->contains($sc->id); @endphp
            <option value="{{ $sc->id }}" data-parent="{{ $sc->general_category_id }}" {{ $selected?'selected':'' }}>{{ $sc->generalCategory?->name }} > {{ $sc->name }}</option>
        @endforeach
    </select>
    <small class="text-muted">Ctrl+Click para múltiples. Se filtran según Categorías Principales.</small>
</div>
<div class="col-md-6 mb-3"><label>Pedido Mínimo (S/)</label><input type="number" step="0.01" name="min_order_amount" class="form-control" value="{{ old('min_order_amount',$store->min_order_amount??'') }}"></div>
<div class="col-md-3 mb-3"><label>Tiempo Prep (min)</label><input type="number" name="preparation_time" class="form-control" value="{{ old('preparation_time',$store->preparation_time??'') }}"></div>
<div class="col-md-3 mb-3"><label>Apertura General</label><input type="time" name="opening_time" class="form-control" value="{{ old('opening_time',$store->opening_time??'08:00') }}" placeholder="08:00"></div>
<div class="col-md-3 mb-3"><label>Cierre General</label><input type="time" name="closing_time" class="form-control" value="{{ old('closing_time',$store->closing_time??'22:00') }}" placeholder="22:00"></div>
<div class="col-md-12 mb-3">
    <label class="d-block mb-2">Horarios por Día (opcional, reemplaza Apertura/Cierre general)</label>
    @php
        $days = \App\Models\StoreSchedule::days();
        $hasSchedules = isset($store) && $store->schedules->isNotEmpty();
    @endphp
    <div class="row" id="schedules-container">
        @foreach($days as $dayKey => $dayName)
        <div class="col-md-12 mb-3 border-bottom pb-2">
            <div class="d-flex align-items-center gap-2">
                <strong style="width:80px">{{ $dayName }}</strong>
                <span class="text-muted small">Horarios (click para agregar)</span>
                <button type="button" class="btn btn-sm btn-outline--primary add-slot" data-day="{{ $dayKey }}">
                    <i class="las la-plus"></i>
                </button>
            </div>
            <div class="slots mt-1" id="slots-day-{{ $dayKey }}">
                @if($hasSchedules)
                    @foreach($store->schedules->where('day', $dayKey) as $schedule)
                    <div class="d-flex align-items-center gap-2 mb-1 slot-row">
                        <input type="time" name="schedules[{{ $dayKey }}][{{ $loop->index }}][open]" value="{{ $schedule->open_time }}" class="form-control form-control-sm" style="width:130px">
                        <span>a</span>
                        <input type="time" name="schedules[{{ $dayKey }}][{{ $loop->index }}][close]" value="{{ $schedule->close_time }}" class="form-control form-control-sm" style="width:130px">
                        <button type="button" class="btn btn-sm btn-outline--danger remove-slot"><i class="las la-trash"></i></button>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endforeach
    </div>
    <small class="text-muted">Si no agregas horarios por día, se usará Apertura/Cierre general.</small>
</div>
<div class="col-md-12 mb-3"><label>Buscar Dirección en Google Maps</label><input type="text" id="address-search" class="form-control" placeholder="Escribe la dirección de la tienda..."></div>
<div class="col-md-6 mb-3"><label>Latitud</label><input type="text" id="latitude" name="latitude" class="form-control" value="{{ old('latitude',$store->latitude??'') }}"></div>
<div class="col-md-6 mb-3"><label>Longitud</label><input type="text" id="longitude" name="longitude" class="form-control" value="{{ old('longitude',$store->longitude??'') }}"></div>
<div class="col-md-12 mb-3"><label>Dirección</label><input type="text" id="address" name="address" class="form-control" value="{{ old('address',$store->address??'') }}"></div>
<div class="col-md-12 mb-3"><label>Descripción</label><textarea name="description" class="form-control" rows="2">{{ old('description',$store->description??'') }}</textarea></div>

<div class="col-md-12 mb-2"><hr><h6><i class="las la-qrcode"></i> QR para cobros en la entrega</h6><small class="text-muted">Pega la cadena de texto del QR; no subas una imagen. El repartidor generará el código al cobrar.</small></div>
<div class="col-md-6 mb-3">
    <label>Cadena QR de Yape</label>
    <textarea name="yape_qr_string" class="form-control" rows="3" maxlength="4096" placeholder="000201010212...">{{ old('yape_qr_string',$store->yape_qr_string??'') }}</textarea>
</div>
<div class="col-md-6 mb-3">
    <label>Cadena QR de Plin</label>
    <textarea name="plin_qr_string" class="form-control" rows="3" maxlength="4096" placeholder="Pega aquí la cadena del QR de Plin">{{ old('plin_qr_string',$store->plin_qr_string??'') }}</textarea>
</div>

<div class="col-md-6 mb-3">
    <label>Imagen Logo (400x400 px)</label>
    <input type="file" name="image" class="form-control" accept="image/*">
    @if(isset($store) && $store->image)
        <div class="mt-2"><img src="{{ getImage('assets/images/store'.'/'.$store->image) }}" height="60"></div>
    @endif
</div>
<div class="col-md-6 mb-3">
    <label>Imagen Portada (1200x400 px)</label>
    <input type="file" name="cover_image" class="form-control" accept="image/*">
    @if(isset($store) && $store->cover_image)
        <div class="mt-2"><img src="{{ getImage('assets/images/store_cover'.'/'.$store->cover_image) }}" height="60"></div>
    @endif
</div>

<div class="col-md-6 mb-3"><div class="form-check"><input type="checkbox" name="is_open" class="form-check-input" {{ ($store->is_open??1)?'checked':'' }}><label class="form-check-label">Abierto</label></div></div>
<div class="col-md-6 mb-3"><div class="form-check"><input type="checkbox" name="status" class="form-check-input" {{ ($store->status??1)?'checked':'' }}><label class="form-check-label">Activo</label></div></div>

<div class="col-md-12 mb-3"><hr><label class="mb-2 fw-bold">⭐ Asignar Plan Empresarial</label>
@php $currentPlan = isset($store) ? $store->storePackages()->where('status','active')->first()?->package_id : null; @endphp
    <select name="business_package_id" class="form-select">
        <option value="">Sin plan</option>
        @foreach(\App\Models\BusinessPackage::active()->orderBy('sort_order')->get() as $pkg)
        <option value="{{ $pkg->id }}" {{ $currentPlan == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }} — S/ {{ number_format($pkg->price,2) }} / {{ $pkg->duration_days }} días</option>
        @endforeach
    </select>
    <small class="text-muted">Selecciona un plan para activarlo en esta tienda. Si ya tiene uno activo, será reemplazado.</small>
</div>
</div>
<button type="submit" class="btn btn--primary">Guardar Tienda</button>
<a href="{{ route('admin.delivery.stores') }}" class="btn btn-dark">Cancelar</a>
</form>
@if(isset($store))
<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0">Renovar suscripción</h5></div>
    <div class="card-body">
        <p class="text-muted small">Activa el plan seleccionado y registra el pago recibido. El plan activo anterior quedará vencido.</p>
        <form method="POST" action="{{ route('admin.delivery.store.subscription.renew', $store->id) }}">
            @csrf
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label>Plan</label>
                    <select name="package_id" class="form-select" required>
                        @foreach(\App\Models\BusinessPackage::active()->orderBy('sort_order')->get() as $pkg)
                        <option value="{{ $pkg->id }}" {{ $currentPlan == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }} — S/ {{ number_format($pkg->price, 2) }} / {{ $pkg->duration_days }} días</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Método de pago</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Efectivo</option><option value="yape">Yape</option><option value="transfer">Transferencia</option><option value="plin">Plin</option><option value="pos">POS</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label>Operación / referencia</label><input name="payment_reference" class="form-control" maxlength="100" placeholder="Opcional"></div>
                <div class="col-md-12 mb-3"><label>Notas</label><input name="notes" class="form-control" maxlength="500" placeholder="Opcional"></div>
            </div>
            <button type="submit" class="btn btn--success" onclick="return confirm('¿Registrar el pago y renovar la suscripción de esta tienda?')"><i class="las la-sync"></i> Registrar renovación</button>
        </form>
    </div>
</div>
@endif
</div></div></div></div>
@endsection

@push('script')
<script>
    (function($) {
        "use strict";
        function toggleSellerFields() {
            var val = $('input[name="seller_option"]:checked').val();
            if (val === 'new') {
                $('#new-seller-group').slideDown();
                $('#existing-seller-group').slideUp();
                $('select[name="seller_id"]').prop('required', false);
                $('input[name="seller_name"], input[name="seller_email"], input[name="seller_password"]').prop('required', true);
            } else {
                $('#new-seller-group').slideUp();
                $('#existing-seller-group').slideDown();
                $('select[name="seller_id"]').prop('required', true);
                $('input[name="seller_name"], input[name="seller_email"], input[name="seller_password"]').prop('required', false).val('');
            }
        }
        toggleSellerFields();
        $('input[name="seller_option"]').on('change', toggleSellerFields);

        // Schedule slots
        $('.add-slot').on('click', function(){
            var day = $(this).data('day');
            var container = $('#slots-day-' + day);
            var idx = container.find('.slot-row').length;
            var html = '<div class="d-flex align-items-center gap-2 mb-1 slot-row">' +
                '<input type="time" name="schedules['+day+']['+idx+'][open]" class="form-control form-control-sm" style="width:130px" value="08:00">' +
                '<span>a</span>' +
                '<input type="time" name="schedules['+day+']['+idx+'][close]" class="form-control form-control-sm" style="width:130px" value="22:00">' +
                '<button type="button" class="btn btn-sm btn-outline--danger remove-slot"><i class="las la-trash"></i></button>' +
                '</div>';
            container.append(html);
        });

        $('#schedules-container').on('click', '.remove-slot', function(){
            $(this).closest('.slot-row').remove();
        });

        // Filter sub-categories based on selected general categories
        window.filterSubCategories = function() {
            var selectedIds = $('select[name="general_category_ids[]"]').val() || [];
            var allSubs = {};
            $('select[name="general_category_ids[]"] option').each(function() {
                var subs = ($(this).data('subs') || '').toString().split(',');
                allSubs[$(this).val()] = subs;
            });
            var allowed = [];
            selectedIds.forEach(function(id) { allowed = allowed.concat(allSubs[id] || []); });
            $('select[name="sub_category_ids[]"] option').each(function() {
                var parent = $(this).data('parent')?.toString();
                if (allowed.length === 0 || allowed.includes(parent)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        };
        filterSubCategories();
    })(jQuery);
</script>
@endpush
