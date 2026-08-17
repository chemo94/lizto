@extends('Template::layouts.frontend')
@section('content')
<style>
.upanel{padding:100px 0 60px;min-height:100vh;background:#f8fdf8}
.upanel .container{max-width:700px}
.upanel-head{margin-bottom:28px}
.upanel-head h1{font-size:28px;font-weight:800;color:#1a2e1a;margin:0 0 6px}
.upanel-head p{color:#68736c;margin:0;font-size:15px}
.upanel-back{display:inline-flex;align-items:center;gap:6px;color:#16a34a;font-weight:700;font-size:13px;text-decoration:none;margin-bottom:20px}
.upanel-back:hover{color:#15803d}
.upanel-wallet-hero{background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:16px;padding:32px;margin-bottom:24px;text-align:center}
.upanel-wallet-hero i{font-size:42px;margin-bottom:12px;opacity:.8}
.upanel-wallet-hero h2{font-size:14px;font-weight:600;opacity:.8;margin:0 0 6px}
.upanel-wallet-hero strong{font-size:40px;font-weight:800;display:block}
.upanel-card{background:#fff;border:1px solid #e0eee2;border-radius:16px;padding:24px;margin-bottom:24px}
.upanel-card h3{font-size:17px;font-weight:800;color:#1a2e1a;margin:0 0 16px;display:flex;align-items:center;gap:8px}
.upanel-card h3 i{color:#16a34a}
.tx-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f0f5f0}
.tx-row:last-child{border:0}
.tx-left{display:flex;align-items:center;gap:12px}
.tx-icon{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;font-size:18px}
.tx-icon.credit{background:#dcfce7;color:#16a34a}
.tx-icon.debit{background:#fee2e2;color:#dc2626}
.tx-left strong{font-size:14px;color:#1a2e1a;display:block}
.tx-left small{font-size:12px;color:#68736c}
.tx-amount{font-weight:800;font-size:15px}
.tx-amount.positive{color:#16a34a}
.tx-amount.negative{color:#dc2626}
.upanel-empty{text-align:center;padding:40px;color:#68736c}
.upanel-empty i{font-size:48px;color:#d1d5db;margin-bottom:12px;display:block}
.upanel-nav{display:flex;gap:12px;margin-top:20px}
.upanel-nav a{padding:10px 18px;border:1px solid #e0eee2;border-radius:10px;text-decoration:none;color:#374151;font-weight:700;font-size:13px;transition:.2s}
.upanel-nav a:hover{border-color:#16a34a;color:#16a34a}
.tx-trx{font-size:11px;color:#9ca3af;font-family:monospace}
</style>

<div class="upanel">
    <div class="container">
        <a href="{{ route('user.dashboard') }}" class="upanel-back"><i class="las la-arrow-left"></i> Volver al Panel</a>

        <div class="upanel-head">
            <h1>Billetera</h1>
            <p>Tu saldo y historial de transacciones</p>
        </div>

        <!-- Balance Card -->
        <div class="upanel-wallet-hero">
            <i class="las la-wallet"></i>
            <h2>Saldo Disponible</h2>
            <strong>S/ {{ number_format($walletBalance, 2) }}</strong>
        </div>

        <!-- Transactions -->
        <div class="upanel-card">
            <h3><i class="las la-exchange-alt"></i> Historial de Transacciones</h3>

            @forelse($transactions as $tx)
            <div class="tx-row">
                <div class="tx-left">
                    <div class="tx-icon {{ $tx->trx_type == '+' ? 'credit' : 'debit' }}">
                        <i class="las {{ $tx->trx_type == '+' ? 'la-arrow-down' : 'la-arrow-up' }}"></i>
                    </div>
                    <div>
                        <strong>{{ $tx->remark ?? ($tx->details ?? 'Transacción') }}</strong>
                        <small>{{ $tx->created_at->format('d/m/Y H:i') }}</small>
                        @if($tx->trx)
                            <br><span class="tx-trx">TRX: {{ $tx->trx }}</span>
                        @endif
                    </div>
                </div>
                <div class="tx-amount {{ $tx->trx_type == '+' ? 'positive' : 'negative' }}">
                    {{ $tx->trx_type }} S/ {{ number_format(abs($tx->amount), 2) }}
                </div>
            </div>
            @empty
            <div class="upanel-empty">
                <i class="las la-receipt"></i>
                <p>No hay transacciones aún</p>
            </div>
            @endforelse

            @if($transactions->hasPages())
            <div style="text-align:center;margin-top:16px">
                {{ $transactions->links() }}
            </div>
            @endif
        </div>

        <!-- Nav -->
        <div class="upanel-nav">
            <a href="{{ route('user.dashboard') }}"><i class="las la-tachometer-alt"></i> Mi Panel</a>
            <a href="{{ route('user.profile') }}"><i class="las la-user"></i> Mi Perfil</a>
            <a href="{{ route('user.deposit.history') }}"><i class="las la-history"></i> Historial de Pagos</a>
        </div>
    </div>
</div>
@endsection
