@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-file-alt"></i></span> Reportes
@endsection

@section('topbar-actions')
<a href="{{ route('seller.reports.advanced') }}" class="s-btn s-btn-primary s-btn-sm" style="gap:6px;">
    <i class="las la-chart-bar"></i> Reportes Avanzados
</a>
@endsection

@section('seller-content')
<div class="s-content">
    <section class="module-hero reports"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Analítica &nbsp;/&nbsp; Reportes</div><h2><i class="las la-chart-line"></i> Reportes del Negocio</h2><p>Analiza ventas, pedidos, canales y desempeño dentro del periodo seleccionado.</p></div><div class="module-hero-stats"><div><b>S/ {{ number_format($report['total_sales'],0) }}</b><small>Ventas</small></div><div><b>{{ $report['total_orders'] }}</b><small>Pedidos</small></div><div><b>S/ {{ $report['total_orders'] ? number_format($report['total_sales']/$report['total_orders'],0) : 0 }}</b><small>Ticket promedio</small></div></div></section>

    <!-- FILTER BAR -->
    <div class="s-card seller-work-card" style="margin-bottom:14px">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div class="s-input-group">
                <label class="s-input-label">Desde</label>
                <input class="s-input" type="date" name="from" value="{{ $dateFrom }}" style="width:170px">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Hasta</label>
                <input class="s-input" type="date" name="to" value="{{ $dateTo }}" style="width:170px">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Cajero</label>
                <select class="s-input" name="seller_filter" style="width:170px">
                    <option value="">Todos</option>
                    @foreach($sellers as $s)
                    <option value="{{ $s->id }}" {{ request('seller_filter') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="s-btn s-btn-primary" style="margin-bottom:1px">
                <i class="las la-search"></i> Filtrar
            </button>
            <a href="{{ route('seller.reports') }}" class="s-btn s-btn-outline" style="margin-bottom:1px">
                <i class="las la-sync"></i> Limpiar
            </a>
            <div style="margin-left:auto;display:flex;gap:8px;margin-bottom:1px">
                <a href="{{ route('seller.reports.export.excel', request()->query()) }}" class="s-btn s-btn-outline" style="font-size:11px;padding:6px 12px">
                    <i class="las la-file-excel"></i> Ventas generales Excel
                </a>
                <a href="{{ route('seller.reports.export.pdf', request()->query()) }}" class="s-btn s-btn-outline" style="font-size:11px;padding:6px 12px">
                    <i class="las la-file-pdf"></i> Ventas generales PDF
                </a>
            </div>
        </form>
    </div>

    <!-- KPIs -->
    <div class="s-grid-4" style="margin-bottom:24px">
        <div class="s-stat">
            <div class="s-stat-icon green"><i class="las la-dollar-sign"></i></div>
            <div>
                <strong>S/ {{ number_format($report['total_sales'], 2) }}</strong>
                <small>Ventas Totales</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon green"><i class="las la-cash-register"></i></div>
            <div>
                <strong>S/ {{ number_format($report['pos_sales'], 2) }}</strong>
                <small>Ventas POS</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon blue"><i class="las la-motorcycle"></i></div>
            <div>
                <strong>S/ {{ number_format($report['del_sales'], 2) }}</strong>
                <small>Ventas Delivery</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon purple"><i class="las la-shopping-cart"></i></div>
            <div>
                <strong>{{ $report['total_orders'] }}</strong>
                <small>Total Pedidos</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon amber"><i class="las la-shopping-bag"></i></div>
            <div>
                <strong>{{ $report['pos_count'] }}</strong>
                <small>Pedidos POS</small>
            </div>
        </div>
        <div class="s-stat">
            <div class="s-stat-icon blue"><i class="las la-truck"></i></div>
            <div>
                <strong>{{ $report['del_count'] }}</strong>
                <small>Pedidos Delivery</small>
            </div>
        </div>
    </div>

    <!-- BY TYPE -->
    @if($report['by_type']->count())
    <div class="s-card" style="margin-bottom:24px">
        <h3 class="s-card-title"><i class="las la-chart-pie"></i> Distribución por Tipo de Orden</h3>
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            @foreach($report['by_type'] as $t)
            @php
                $total = $report['total_orders'] ?: 1;
                $pct = round(($t->c / $total) * 100);
            @endphp
            <div style="flex:1;min-width:140px;background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:14px;padding:18px;text-align:center">
                <div style="font-size:28px;font-weight:900;color:var(--s-accent-dark)">{{ $t->c }}</div>
                <div style="font-size:12px;font-weight:700;color:var(--s-text-2);margin:4px 0">{{ $t->order_type }}</div>
                <div style="font-size:13px;font-weight:800;color:var(--s-text)">S/ {{ number_format($t->s,2) }}</div>
                <div style="margin-top:8px;background:var(--s-border);border-radius:30px;height:5px">
                    <div style="width:{{ $pct }}%;background:linear-gradient(90deg,#22c55e,#16a34a);height:5px;border-radius:30px;transition:width .5s"></div>
                </div>
                <small style="color:var(--s-text-3);font-size:10px">{{ $pct }}%</small>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- ORDERS TABLE -->
    <div class="s-card seller-work-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <h3 class="s-card-title" style="margin:0"><i class="las la-list"></i> Detalle de Pedidos POS</h3>
            <span class="s-badge s-badge-gray">{{ $orders->total() }} registros</span>
        </div>
        <div style="overflow-x:auto">
        <table class="s-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Mesa</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr>
                    <td><b style="font-size:12px">{{ $o->order_no }}</b></td>
                    <td style="color:var(--s-text-2)">{{ $o->customer_name ?: '—' }}</td>
                    <td>
                        <span class="s-badge s-badge-gray" style="font-size:10px">{{ $o->order_type }}</span>
                    </td>
                    <td style="color:var(--s-text-3)">{{ $o->table?->name ?: '—' }}</td>
                    <td><span class="s-badge s-badge-gray">{{ $o->items->count() }}</span></td>
                    <td><b style="color:var(--s-accent-dark)">S/ {{ number_format($o->total,2) }}</b></td>
                    <td>
                        <span class="s-badge {{ $o->status==='delivered'?'s-badge-green':($o->status==='cancelled'?'s-badge-red':'s-badge-amber') }}">
                            {{ $o->status }}
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--s-text-3);white-space:nowrap">{{ $o->created_at->format('d/m H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="s-empty">
                            <i class="las la-chart-bar"></i>
                            <p>Sin pedidos en este rango de fechas</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($orders->hasPages())
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--s-border)">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
