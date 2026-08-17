@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-12"><div class="card">
<div class="card-header"><h5>Transacciones - Billetera #{{ $wallet->id }} ({{ $wallet->holder?->fullname ?? $wallet->holder?->name }})</h5></div>
<div class="card-body p-0"><div class="table-responsive--md"><table class="table table--light">
<thead><tr><th>TRX</th><th>Monto</th><th>Tipo</th><th>Balance Post</th><th>Concepto</th><th>Detalle</th><th>Fecha</th></tr></thead>
<tbody>@foreach($transactions as $t)
<tr><td>{{ $t->trx }}</td>
<td class="{{ $t->trx_type=='+'?'text--success':'text--danger' }}">{{ $t->trx_type }} S/ {{ number_format($t->amount,2) }}</td>
<td>{{ $t->remark }}</td><td>S/ {{ number_format($t->post_balance,2) }}</td>
<td>{{ $t->remark }}</td><td>{{ $t->details }}</td><td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
</tr>
@endforeach</tbody></table></div></div>
<div class="card-footer">{{ $transactions->links() }}</div>
</div></div></div>
@endsection
