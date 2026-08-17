@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-users"></i></span> Clientes
@endsection

@section('seller-content')
<div class="s-content">
    <div class="s-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <h3 class="s-card-title" style="margin:0"><i class="las la-users"></i> Historial de Clientes</h3>
            <span class="s-badge s-badge-blue">{{ $customers->count() }} clientes</span>
        </div>
        @if($customers->count())
        <!-- Quick KPIs -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--s-border)">
            <div style="background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:12px;padding:12px 18px;text-align:center;flex:1;min-width:120px">
                <div style="font-size:22px;font-weight:900;color:var(--s-accent-dark)">{{ $customers->count() }}</div>
                <small style="color:var(--s-text-3);font-size:11px;font-weight:600">Clientes únicos</small>
            </div>
            <div style="background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:12px;padding:12px 18px;text-align:center;flex:1;min-width:120px">
                <div style="font-size:22px;font-weight:900;color:var(--s-accent-dark)">{{ $customers->sum('total_orders') }}</div>
                <small style="color:var(--s-text-3);font-size:11px;font-weight:600">Pedidos totales</small>
            </div>
            <div style="background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:12px;padding:12px 18px;text-align:center;flex:1;min-width:140px">
                <div style="font-size:22px;font-weight:900;color:var(--s-accent-dark)">S/ {{ number_format($customers->sum('total_spent'),2) }}</div>
                <small style="color:var(--s-text-3);font-size:11px;font-weight:600">Ingreso acumulado</small>
            </div>
        </div>
        @endif
        <div style="overflow-x:auto">
        <table class="s-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Pedidos</th>
                    <th>Total Gastado</th>
                    <th>Ticket Promedio</th>
                    <th>Último Pedido</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--s-accent-light);display:grid;place-items:center;font-size:14px;font-weight:800;color:var(--s-accent-dark);flex-shrink:0;text-transform:uppercase">
                                {{ substr($c->customer_name ?: 'A', 0, 1) }}
                            </div>
                            <div>
                                <b style="color:var(--s-text)">{{ $c->customer_name ?: 'Anónimo' }}</b>
                                @if($c->total_orders >= 5)
                                <span class="s-badge s-badge-amber" style="font-size:9px;margin-left:6px">⭐ Frecuente</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--s-text-3);font-size:13px">{{ $c->customer_phone ?: '—' }}</td>
                    <td>
                        <span class="s-badge {{ $c->total_orders >= 5 ? 's-badge-amber' : ($c->total_orders >= 2 ? 's-badge-blue' : 's-badge-gray') }}">
                            {{ $c->total_orders }} pedidos
                        </span>
                    </td>
                    <td><b style="color:var(--s-accent-dark)">S/ {{ number_format($c->total_spent, 2) }}</b></td>
                    <td style="color:var(--s-text-2)">
                        S/ {{ $c->total_orders > 0 ? number_format($c->total_spent / $c->total_orders, 2) : '0.00' }}
                    </td>
                    <td style="font-size:12px;color:var(--s-text-3);white-space:nowrap">
                        {{ $c->last_order ? $c->last_order->format('d/m/Y H:i') : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="s-empty">
                            <i class="las la-user-friends"></i>
                            <p>Sin clientes registrados aún</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
