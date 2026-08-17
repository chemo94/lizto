@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-12"><div class="card">
<div class="card-header d-flex justify-content-between"><h5>{{ $pageTitle }}</h5>
<a href="{{ route('admin.delivery.store.create') }}" class="btn btn--primary btn-sm"><i class="las la-plus"></i> Nueva Tienda</a>
</div>
<div class="card-body p-0"><div class="table-responsive--md"><table class="table table--light">
<thead><tr><th>ID</th><th>Tienda</th><th>Tipo</th><th>Vendedor</th><th>Subcategoría</th><th>Delivery</th><th>Abierto</th><th>Estado</th><th>Acción</th></tr></thead>
<tbody>@foreach($stores as $s)
<tr><td>{{ $s->id }}</td><td><a href="{{ route('admin.delivery.store.detail',$s->id) }}">{{ $s->name }}</a></td><td><span class="badge badge--info">{{ \App\Models\Store::types()[$s->store_type] ?? $s->store_type }}</span></td><td>{{ $s->seller?->name }}</td><td>{{ $s->subCategories->pluck('name')->implode(', ') ?: 'N/A' }}</td>
<td>S/ {{ number_format($s->delivery_fee,2) }}</td>
<td><span class="badge badge--{{ $s->is_open?'success':'danger' }}">{{ $s->is_open?'Sí':'No' }}</span></td>
<td><span class="badge badge--{{ $s->status?'success':'danger' }}">{{ $s->status?'Activo':'Inactivo' }}</span></td>
<td>
<a href="{{ route('admin.delivery.store.detail',$s->id) }}" class="btn btn-sm btn-outline--primary"><i class="las la-eye"></i></a>
<a href="{{ route('admin.delivery.store.edit',$s->id) }}" class="btn btn-sm btn-outline--warning"><i class="las la-edit"></i></a>
</td>
</tr>
@endforeach</tbody></table></div></div>
<div class="card-footer">{{ $stores->links() }}</div>
</div></div></div>
@endsection
