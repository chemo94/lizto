@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-6"><div class="card"><div class="card-header"><h5>Billetera #{{ $wallet->id }}</h5></div>
<div class="card-body">
<p><strong>Titular:</strong> {{ $wallet->holder?->fullname ?? $wallet->holder?->name }} ({{ class_basename($wallet->holder_type) }})</p>
<p><strong>Balance:</strong> S/ {{ number_format($wallet->balance,2) }} | <strong>Bloqueado:</strong> S/ {{ number_format($wallet->blocked_balance,2) }}</p>
<a href="{{ route('admin.delivery.wallet.transactions',$wallet->id) }}" class="btn btn--info">Ver Transacciones</a>
</div></div></div></div>
@endsection
