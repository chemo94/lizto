@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-12"><div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h5 class="card-title mb-0">{{ $pageTitle }}</h5>
<form method="GET" class="d-flex gap-2">
<input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar #favor..." value="{{ request('search') }}">
<select name="status" class="form-select form-select-sm" style="width:auto">
<option value="">Todos</option>
@foreach(['searching_courier'=>'Buscando','accepted'=>'Aceptado','on_way_to_pickup'=>'Recogiendo','at_pickup'=>'Recogido','on_way_to_delivery'=>'Entregando','delivered'=>'Entregado','cancelled'=>'Cancelado'] as $k=>$v)
<option value="{{ $k }}" {{ request('status')==$k?'selected':'' }}>{{ $v }}</option>
@endforeach
</select>
<button type="submit" class="btn btn--primary btn-sm"><i class="las la-search"></i></button>
</form>
</div>
<div class="card-body p-0"><div class="table-responsive--md"><table class="table table--light">
<thead><tr><th>#Favor</th><th>Cliente</th><th>Tipo</th><th>Total</th><th>Repartidor</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr></thead>
<tbody>@forelse($favors as $f)
<tr>
<td><strong>{{ $f->order_no }}</strong></td><td>{{ $f->user?->fullname }}</td>
<td><span class="badge badge--{{ $f->type=='buy'?'info':'warning' }}">{{ $f->type=='buy'?'Compra':'Envío' }}</span></td>
<td>S/ {{ number_format($f->total,2) }}</td><td>{{ $f->courier?->fullname ?? 'Sin asignar' }}</td>
<td><span class="badge badge--{{ $f->status=='delivered'?'success':($f->status=='cancelled'?'danger':'primary') }}">{{ $f->status }}</span></td>
<td>{{ $f->created_at?->format('d/m/Y H:i') }}</td>
<td><a href="{{ route('admin.delivery.favor.detail',$f->id) }}" class="btn btn-sm btn-outline--primary"><i class="las la-eye"></i></a></td>
</tr>
@empty
<tr><td colspan="8" class="text-center">No hay favores</td></tr>
@endforelse
</tbody></table></div></div>
<div class="card-footer">{{ $favors->links() }}</div>
</div></div></div>
@endsection
