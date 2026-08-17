@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-12"><div class="card">
<div class="card-header"><h5>{{ $pageTitle }}</h5></div>
<div class="card-body p-0"><div class="table-responsive--md"><table class="table table--light">
<thead><tr><th>ID</th><th>Cliente</th><th>Pedido/Favor</th><th>Monto</th><th>Motivo</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr></thead>
<tbody>@foreach($refunds as $r)
<tr><td>{{ $r->id }}</td><td>{{ $r->user?->fullname }}</td>
<td>{{ $r->order?->order_no ?? $r->favor?->order_no ?? 'N/A' }}</td>
<td>S/ {{ number_format($r->amount,2) }}</td><td>{{ \Str::limit($r->reason,60) }}</td>
<td><span class="badge badge--{{ $r->status=='approved'?'success':($r->status=='rejected'?'danger':'warning') }}">{{ $r->status }}</span></td>
<td>{{ $r->created_at?->format('d/m/Y') }}</td>
<td>
<form method="POST" action="{{ route('admin.delivery.refund.action',$r->id) }}" class="d-inline">
@csrf
<input type="hidden" name="action" value="approve">
<input type="text" name="admin_remark" class="form-control form-control-sm d-inline" placeholder="Observación" style="width:120px">
<button type="submit" class="btn btn-sm btn--success">Aprobar</button>
</form>
<form method="POST" action="{{ route('admin.delivery.refund.action',$r->id) }}" class="d-inline">@csrf
<input type="hidden" name="action" value="reject">
<button type="submit" class="btn btn-sm btn--danger">Rechazar</button>
</form>
</td></tr>
@endforeach</tbody></table></div></div>
<div class="card-footer">{{ $refunds->links() }}</div>
</div></div></div>
@endsection
