@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-8"><div class="card">
<div class="card-header"><h5>{{ $pageTitle }} - {{ $store->name }}</h5></div>
<div class="card-body">
<form method="POST" action="{{ route('admin.delivery.product.save', $product->id ?? null) }}" enctype="multipart/form-data">@csrf
<input type="hidden" name="store_id" value="{{ $store->id }}">
<div class="row">
<div class="col-md-6 mb-3"><label>Nombre *</label><input type="text" name="name" class="form-control" value="{{ old('name',$product->name??'') }}" required></div>
<div class="col-md-6 mb-3"><label>Categoría *</label><select name="store_category_id" class="form-select" required>
@foreach($categories as $c)<option value="{{ $c->id }}" {{ ($product->store_category_id??'')==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
</select></div>
<div class="col-md-4 mb-3"><label>Precio (S/) *</label><input type="number" step="0.01" name="price" class="form-control" value="{{ old('price',$product->price??'') }}" required></div>
<div class="col-md-4 mb-3"><label>Precio Descuento (S/)</label><input type="number" step="0.01" name="discount_price" class="form-control" value="{{ old('discount_price',$product->discount_price??'') }}"></div>
<div class="col-md-4 mb-3"><label>Orden</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order',$product->sort_order??0) }}"></div>
<div class="col-md-12 mb-3"><label>Descripción</label><textarea name="description" class="form-control" rows="2">{{ old('description',$product->description??'') }}</textarea></div>
<div class="col-md-6 mb-3"><label>Imagen</label><input type="file" name="image" class="form-control" accept="image/*"></div>
<div class="col-md-6 mb-3"><div class="form-check mt-4"><input type="checkbox" name="status" class="form-check-input" {{ ($product->status??1)?'checked':'' }}><label class="form-check-label">Activo</label></div></div>
<div class="col-md-6 mb-3"><div class="form-check mt-4"><input type="checkbox" name="is_promoted" class="form-check-input" {{ ($product->is_promoted??false)?'checked':'' }}><label class="form-check-label">⭐ Destacar en Marketplace</label></div></div>
</div>

<h5 class="mt-4">Variaciones</h5>
<div id="variationsContainer">
    @php $variations = $product->variations ?? []; @endphp
    @foreach($variations as $i => $v)
    <div class="row variation-row mb-2"><input type="hidden" name="variation_id[]" value="{{ $v->id }}">
        <div class="col-5"><input type="text" name="variation_name[]" class="form-control form-control-sm" placeholder="Nombre (ej: Grande)" value="{{ $v->name }}"></div>
        <div class="col-5"><input type="number" step="0.01" name="variation_price[]" class="form-control form-control-sm" placeholder="Precio" value="{{ $v->price }}"></div>
        <div class="col-2"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></div>
    </div>
    @endforeach
    @if(count($variations) == 0)
    <div class="row variation-row mb-2"><input type="hidden" name="variation_id[]" value="">
        <div class="col-5"><input type="text" name="variation_name[]" class="form-control form-control-sm" placeholder="Nombre (ej: Grande)"></div>
        <div class="col-5"><input type="number" step="0.01" name="variation_price[]" class="form-control form-control-sm" placeholder="Precio"></div>
        <div class="col-2"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></div>
    </div>
    @endif
</div>
<button type="button" class="btn btn-sm btn-outline--primary mb-3" onclick="addRow('variationsContainer','variation')">+ Agregar Variación</button>

<h5 class="mt-3">Adicionales</h5>
<div id="addonsContainer">
    @php $addons = $product->addons ?? []; @endphp
    @foreach($addons as $i => $a)
    <div class="row addon-row mb-2"><input type="hidden" name="addon_id[]" value="{{ $a->id }}">
        <div class="col-5"><input type="text" name="addon_name[]" class="form-control form-control-sm" placeholder="Nombre (ej: Queso extra)" value="{{ $a->name }}"></div>
        <div class="col-5"><input type="number" step="0.01" name="addon_price[]" class="form-control form-control-sm" placeholder="Precio" value="{{ $a->price }}"></div>
        <div class="col-2"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></div>
    </div>
    @endforeach
    @if(count($addons) == 0)
    <div class="row addon-row mb-2"><input type="hidden" name="addon_id[]" value="">
        <div class="col-5"><input type="text" name="addon_name[]" class="form-control form-control-sm" placeholder="Nombre (ej: Queso extra)"></div>
        <div class="col-5"><input type="number" step="0.01" name="addon_price[]" class="form-control form-control-sm" placeholder="Precio"></div>
        <div class="col-2"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></div>
    </div>
    @endif
</div>
<button type="button" class="btn btn-sm btn-outline--primary mb-3" onclick="addRow('addonsContainer','addon')">+ Agregar Adicional</button>

<div class="mt-4"><button type="submit" class="btn btn--primary">Guardar Producto</button>
<a href="{{ route('admin.delivery.store.detail',$store->id) }}" class="btn btn-dark">Cancelar</a></div>
</form>
</div></div></div></div>

<script>
function addRow(containerId,type){
    const c=document.getElementById(containerId);
    const row=document.createElement('div');
    row.className='row '+type+'-row mb-2';
    row.innerHTML=`<input type="hidden" name="${type}_id[]" value="">
        <div class="col-5"><input type="text" name="${type}_name[]" class="form-control form-control-sm" placeholder="Nombre"></div>
        <div class="col-5"><input type="number" step="0.01" name="${type}_price[]" class="form-control form-control-sm" placeholder="Precio"></div>
        <div class="col-2"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.row').remove()">X</button></div>`;
    c.appendChild(row);
}
document.querySelectorAll('.remove-row').forEach(b=>b.onclick=function(){this.closest('.row').remove()});
</script>
@endsection
