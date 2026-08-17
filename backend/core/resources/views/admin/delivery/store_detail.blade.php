@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-12"><div class="card">
<div class="card-header d-flex justify-content-between"><h5>{{ $store->name }}</h5>
<div><a href="{{ route('admin.delivery.store.edit',$store->id) }}" class="btn btn-sm btn-outline--warning"><i class="las la-edit"></i> Editar</a>
<a href="{{ route('admin.delivery.product.create',$store->id) }}" class="btn btn-sm btn--primary"><i class="las la-plus"></i> Agregar Producto</a></div>
</div>
<div class="card-body">
<p><strong>Vendedor:</strong> {{ $store->seller?->name }} | <strong>Subcategoría:</strong> {{ $store->subCategories->pluck('name')->implode(', ') }}</p>
<p><strong>Dirección:</strong> {{ $store->address }} | <strong>Delivery:</strong> S/ {{ number_format($store->delivery_fee,2) }}</p>
<p><strong>Horario:</strong> {{ $store->opening_time }} - {{ $store->closing_time }} | <strong>Tiempo prep:</strong> {{ $store->preparation_time }} min</p>

<!-- Store Categories Management -->
<h5 class="mt-4">Categorías de la Tienda</h5>
<form method="POST" action="{{ route('admin.delivery.store.category.save', $store->id) }}" class="row g-2 mb-3 align-items-end">
    @csrf
    <div class="col-md-4">
        <label class="form-label">Nueva Categoría</label>
        <input type="text" name="name" class="form-control" placeholder="Ej: Bebidas, Postres, Combos..." required>
    </div>
    <div class="col-md-2">
        <label class="form-label">Orden</label>
        <input type="number" name="sort_order" class="form-control" value="0" min="0">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn--primary w-100"><i class="las la-plus"></i> Agregar</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>#</th><th>Categoría</th><th>Productos</th><th>Orden</th><th>Activo</th><th>Acción</th></tr></thead>
        <tbody>
            @forelse($store->categories as $cat)
            <tr>
                <td>{{ $cat->id }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.delivery.store.category.update', $cat->id) }}" class="d-flex gap-1">
                        @csrf
                        <input type="text" name="name" value="{{ $cat->name }}" class="form-control form-control-sm" style="width:140px">
                        <input type="number" name="sort_order" value="{{ $cat->sort_order }}" class="form-control form-control-sm" style="width:60px">
                        <button type="submit" class="btn btn-sm btn-outline--primary"><i class="las la-save"></i></button>
                    </form>
                </td>
                <td>{{ $cat->products->count() }}</td>
                <td>{{ $cat->sort_order }}</td>
                <td>
                    <a href="{{ route('admin.delivery.store.category.toggle', $cat->id) }}" class="btn btn-sm {{ $cat->status ? 'btn--success' : 'btn--danger' }}">
                        {{ $cat->status ? 'Sí' : 'No' }}
                    </a>
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.delivery.store.category.delete', $cat->id) }}" onsubmit="return confirm('¿Eliminar esta categoría? Los productos quedarán sin categoría.')" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-outline--danger"><i class="las la-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">Sin categorías. Crea una para organizar los productos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h5 class="mt-4">Productos por Categoría</h5>
@forelse($store->categories as $cat)
<div class="card mb-3"><div class="card-header"><h6>{{ $cat->name }}</h6></div>
<div class="card-body p-0"><table class="table table-sm"><thead><tr><th>Producto</th><th>Precio</th><th>Desc</th><th>Variaciones</th><th>Addons</th><th>Acción</th></tr></thead>
<tbody>@foreach($cat->products as $p)
<tr><td>{{ $p->name }}</td><td>S/ {{ number_format($p->price,2) }}</td><td>{{ $p->discount_price?number_format($p->discount_price,2):'-' }}</td>
<td>{{ $p->variations?->count() ?? 0 }}</td><td>{{ $p->addons?->count() ?? 0 }}</td>
<td>
<a href="{{ route('admin.delivery.product.edit',$p->id) }}" class="btn btn-sm btn-outline--warning"><i class="las la-edit"></i></a>
<form method="POST" action="{{ route('admin.delivery.product.delete',$p->id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar producto?')">@csrf
<button class="btn btn-sm btn-outline--danger"><i class="las la-trash"></i></button></form>
</td></tr>
@endforeach</tbody></table></div></div>
@empty
<div class="text-center text-muted py-4">Crea categorías y luego agrega productos desde "Agregar Producto".</div>
@endforelse
</div></div></div></div>
@endsection
