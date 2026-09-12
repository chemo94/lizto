@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-calculator"></i></span> Reporte de Comisiones de Personal
@endsection

@section('topbar-actions')
<form class="staff-report-topbar-filter" method="GET" action="" style="display: flex; gap: 8px; align-items: center;">
    <div style="display: flex; align-items: center; background: var(--s-surface-2); border: 1.5px solid var(--s-border); border-radius: var(--s-radius); padding: 2px 10px; height: 38px;">
        <span style="font-size: 11px; font-weight: 700; color: var(--s-text-3); text-transform: uppercase; margin-right: 8px;">Desde:</span>
        <input type="date" name="from" value="{{ $dateFrom }}" style="background:transparent; border:none; outline:none; color:var(--s-text); font-size:12px; font-weight:600;">
    </div>
    <div style="display: flex; align-items: center; background: var(--s-surface-2); border: 1.5px solid var(--s-border); border-radius: var(--s-radius); padding: 2px 10px; height: 38px;">
        <span style="font-size: 11px; font-weight: 700; color: var(--s-text-3); text-transform: uppercase; margin-right: 8px;">Hasta:</span>
        <input type="date" name="to" value="{{ $dateTo }}" style="background:transparent; border:none; outline:none; color:var(--s-text); font-size:12px; font-weight:600;">
    </div>
    <button type="submit" class="s-btn s-btn-primary s-btn-sm" style="height: 38px; border-radius: var(--s-radius);">
        <i class="las la-filter"></i> Filtrar
    </button>
</form>
@endsection

@section('seller-content')
<div class="s-content seller-responsive-page">
    <section class="module-hero commissions">
        <div>
            <div class="module-crumb"><i class="las la-home"></i> Seller / RR.HH / Comisiones</div>
            <h2>Rendimiento y comisiones</h2>
            <p>Compara ventas atribuidas, pedidos atendidos y comisiones generadas.</p>
        </div>
        <div class="module-hero-stats">
            <div><b>S/ {{ number_format($totalSalesAll, 0) }}</b><small>Ventas</small></div>
            <div><b>S/ {{ number_format($totalCommissionsAll, 0) }}</b><small>Comisiones</small></div>
            <div><b>{{ $totalOrdersAll }}</b><small>Pedidos</small></div>
        </div>
    </section>
    
    <!-- KPI CARDS -->
    <div class="hr-workspace" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 14px;">
        <div class="s-card seller-work-card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
            <div style="width: 52px; height: 52px; background: var(--s-accent-light); color: var(--s-accent-dark); border-radius: 50%; display: grid; place-items: center; font-size: 28px;">
                <i class="las la-hand-holding-usd"></i>
            </div>
            <div>
                <span style="font-size: 11px; color: var(--s-text-3); font-weight: 700; text-transform: uppercase;">Total Ventas Atribuidas</span>
                <h4 style="font-size: 22px; font-weight: 900; color: var(--s-text-primary); margin: 4px 0 0;">S/ {{ number_format($totalSalesAll, 2) }}</h4>
            </div>
        </div>

        <div class="s-card seller-work-card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
            <div style="width: 52px; height: 52px; background: var(--s-primary-light); color: var(--s-primary); border-radius: 50%; display: grid; place-items: center; font-size: 28px;">
                <i class="las la-calculator"></i>
            </div>
            <div>
                <span style="font-size: 11px; color: var(--s-text-3); font-weight: 700; text-transform: uppercase;">Total Comisiones</span>
                <h4 style="font-size: 22px; font-weight: 900; color: var(--s-text-primary); margin: 4px 0 0;">S/ {{ number_format($totalCommissionsAll, 2) }}</h4>
            </div>
        </div>

        <div class="s-card seller-work-card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
            <div style="width: 52px; height: 52px; background: var(--s-warning-bg); color: var(--s-warning-text); border-radius: 50%; display: grid; place-items: center; font-size: 28px;">
                <i class="las la-receipt"></i>
            </div>
            <div>
                <span style="font-size: 11px; color: var(--s-text-3); font-weight: 700; text-transform: uppercase;">Órdenes Atendidas</span>
                <h4 style="font-size: 22px; font-weight: 900; color: var(--s-text-primary); margin: 4px 0 0;">{{ $totalOrdersAll }} pedidos</h4>
            </div>
        </div>
    </div>

    <!-- COMPARATIVO POR PERSONAL -->
    <div class="s-card seller-work-card" style="margin-bottom: 14px;">
        <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-users" style="color: var(--s-primary); font-size: 20px;"></i> Rendimiento y Comisiones por Mesero
        </h3>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1.5px solid var(--s-border); color: var(--s-text-muted); font-weight: 700;">
                        <th style="padding: 10px 14px;">Mesero / Personal</th>
                        <th style="padding: 10px 14px; text-align: center;">Tasa Comisión</th>
                        <th style="padding: 10px 14px; text-align: center;">Nº Pedidos</th>
                        <th style="padding: 10px 14px; text-align: right;">Total Vendido (S/)</th>
                        <th style="padding: 10px 14px; text-align: right;">Comisión Calculada (S/)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $rep)
                    <tr style="border-bottom: 1px solid var(--s-border); transition: background 0.15s;" onmouseover="this.style.background='var(--s-bg-light)'" onmouseout="this.style.background=''">
                        <td style="padding: 12px 14px; font-weight: 700; color: var(--s-text-primary);">
                            {{ $rep['member']->name }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; color: var(--s-text-secondary);">
                            {{ number_format($rep['member']->commission_rate, 2) }}%
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-weight: 700; color: var(--s-text-primary);">
                            {{ $rep['orders_count'] }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 700; color: var(--s-text-primary);">
                            S/ {{ number_format($rep['total_sales'], 2) }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 850; color: var(--s-accent-dark);">
                            S/ {{ number_format($rep['commission'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px 10px; color: var(--s-text-muted);">
                            No hay registros de ventas para el personal en este rango de fechas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- DETALLE DE COMANDAS -->
    <div class="s-card">
        <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-receipt" style="color: var(--s-primary); font-size: 20px;"></i> Historial Detallado de Comandas con Comisión
        </h3>

        <div style="overflow-x: auto; margin-bottom: 14px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1.5px solid var(--s-border); color: var(--s-text-muted); font-weight: 700;">
                        <th style="padding: 10px 14px;">Fecha / Hora</th>
                        <th style="padding: 10px 14px;">Pedido</th>
                        <th style="padding: 10px 14px;">Mesero</th>
                        <th style="padding: 10px 14px;">Mesa/Cliente</th>
                        <th style="padding: 10px 14px; text-align: right;">Total Pedido</th>
                        <th style="padding: 10px 14px; text-align: right;">Comisión</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detailedOrders as $order)
                    <tr style="border-bottom: 1px solid var(--s-border);">
                        <td style="padding: 12px 14px; color: var(--s-text-secondary);">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td style="padding: 12px 14px; font-weight: 700; color: var(--s-text-primary);">
                            #{{ $order->order_no }}
                        </td>
                        <td style="padding: 12px 14px; font-weight: 600; color: var(--s-text-primary);">
                            {{ $order->staff?->name ?: 'No asignado' }}
                        </td>
                        <td style="padding: 12px 14px; color: var(--s-text-secondary);">
                            {{ $order->table?->name ?: $order->customer_name ?: 'Venta Directa' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 700; color: var(--s-text-primary);">
                            S/ {{ number_format($order->total, 2) }}
                        </td>
                        <td style="padding: 12px 14px; text-align: right; font-weight: 700; color: var(--s-accent-dark);">
                            @if($order->staff)
                            S/ {{ number_format(($order->total * $order->staff->commission_rate) / 100, 2) }}
                            <small style="font-size: 9px; color: var(--s-text-muted); display: block; font-weight: 500;">({{ number_format($order->staff->commission_rate, 1) }}%)</small>
                            @else
                            S/ 0.00
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px 10px; color: var(--s-text-muted);">
                            No hay comandas registradas en este período.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div>
            {{ $detailedOrders->links() }}
        </div>
    </div>

</div>
@endsection
