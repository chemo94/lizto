@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-chart-bar"></i></span> Reportes Avanzados
@endsection

@section('topbar-actions')
<a href="{{ route('seller.reports') }}" class="s-btn s-btn-ghost s-btn-sm" style="border:1px solid var(--s-border);">
    <i class="las la-arrow-left"></i> Reportes Básicos
</a>
@endsection

@section('seller-content')
<div class="s-content" style="max-width:1400px;margin:0 auto;">

    <!-- Filtro de fechas -->
    <div class="s-card" style="margin-bottom:24px;">
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;">
                <label style="font-weight:700;font-size:12px;">Desde</label>
                <input type="date" name="from" value="{{ $dateFrom }}" class="s-input" style="width:160px;">
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <label style="font-weight:700;font-size:12px;">Hasta</label>
                <input type="date" name="to" value="{{ $dateTo }}" class="s-input" style="width:160px;">
            </div>
            <button type="submit" class="s-btn s-btn-primary s-btn-sm"><i class="las la-filter"></i> Filtrar</button>
        </form>
    </div>

    <!-- Top Products -->
    <div class="s-card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;display:flex;align-items:center;gap:8px;">
            <i class="las la-star" style="color:var(--s-warning);"></i> Top 20 Productos Más Vendidos
        </h3>
        <div class="s-table-wrapper">
            <table class="s-table"><thead><tr><th>#</th><th>Producto</th><th style="text-align:center">Cantidad</th><th style="text-align:right">Total</th></tr></thead><tbody>
                @foreach($byProduct as $i => $p)
                <tr><td>{{ $i+1 }}</td><td style="font-weight:700">{{ $p->product_name }}</td><td style="text-align:center;font-weight:600">{{ $p->qty }}</td><td style="text-align:right;font-weight:900;color:var(--s-accent-dark)">S/ {{ number_format($p->total,2) }}</td></tr>
                @endforeach
            </tbody></table>
        </div>
    </div>

    <!-- By Category + By Hour -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
        <div class="s-card">
            <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;"><i class="las la-tags" style="color:var(--s-info);"></i> Ventas por Categoría</h3>
            <table class="s-table"><thead><tr><th>Categoría</th><th style="text-align:right">Ventas</th></tr></thead><tbody>
                @foreach($byCategory as $c)
                <tr><td style="font-weight:700">{{ $c->cat_name }}</td><td style="text-align:right;font-weight:900;color:var(--s-accent-dark)">S/ {{ number_format($c->total,2) }}</td></tr>
                @endforeach
            </tbody></table>
        </div>
        <div class="s-card">
            <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;"><i class="las la-clock" style="color:var(--s-purple);"></i> Ventas por Hora</h3>
            <table class="s-table"><thead><tr><th>Hora</th><th style="text-align:center">Pedidos</th><th style="text-align:right">Total</th></tr></thead><tbody>
                @foreach($byHour as $h)
                <tr><td style="font-weight:700">{{ str_pad($h->hora,2,'0',STR_PAD_LEFT) }}:00</td><td style="text-align:center;font-weight:600">{{ $h->c }}</td><td style="text-align:right;font-weight:900;color:var(--s-accent-dark)">S/ {{ number_format($h->s,2) }}</td></tr>
                @endforeach
            </tbody></table>
        </div>
    </div>

    <!-- Ventas por Plataforma -->
    <div class="s-card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;"><i class="las la-globe" style="color:var(--s-primary);"></i> Ventas por Plataforma</h3>
        <table class="s-table"><thead><tr><th>Plataforma</th><th style="text-align:center">Pedidos</th><th style="text-align:right">Total</th></tr></thead><tbody>
            @php $platLabels = ['dine_in'=>'Mesa','takeaway'=>'Recojo','delivery'=>'Delivery Lizto','daz'=>'DAZ','llama'=>'LLAMA','rappi'=>'RAPPI','pedidosya'=>'PEDIDOSYA','lizto_delivery'=>'App Lizto']; @endphp
            @foreach($byPlatform as $p)
            <tr>
                <td><span style="font-weight:900;font-size:11px;padding:2px 8px;border-radius:4px;color:#fff;background:{{ ['daz'=>'#e11d48','llama'=>'#f59e0b','rappi'=>'#8b5cf6','pedidosya'=>'#0891b2','lizto_delivery'=>'#16a34a','delivery'=>'var(--s-text-3)','dine_in'=>'var(--s-text-3)','takeaway'=>'var(--s-text-3)'][$p->order_type] ?? 'var(--s-text-3)' }}">{{ $platLabels[$p->order_type] ?? $p->order_type }}</span></td>
                <td style="text-align:center;font-weight:600">{{ $p->count }}</td><td style="text-align:right;font-weight:900;color:var(--s-accent-dark)">S/ {{ number_format($p->total,2) }}</td>
            </tr>
            @endforeach
        </tbody></table>
    </div>

    <!-- Gastos -->
    <div class="s-card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;"><i class="las la-file-invoice-dollar" style="color:var(--s-danger);"></i> Gastos por Categoría</h3>
        <table class="s-table"><thead><tr><th>Categoría</th><th style="text-align:right">Total</th></tr></thead><tbody>
            @foreach($expenses as $e)
            <tr><td style="font-weight:700;text-transform:uppercase">{{ $e->category }}</td><td style="text-align:right;font-weight:900;color:var(--s-danger)">S/ {{ number_format($e->total,2) }}</td></tr>
            @endforeach
        </tbody></table>
    </div>

    <!-- Mermas (Waste from voided prepared items) -->
    <div class="s-card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;"><i class="las la-trash-alt" style="color:var(--s-warning);"></i> Mermas por Anulación (Preparados)</h3>
        <p style="font-size:12px;color:var(--s-text-3);margin-bottom:12px;">
            Productos tipo <b>preparado</b> anulados cuyos ingredientes no se devuelven al inventario.
            Son pérdidas asumidas. Usa <b>Ajuste Manual</b> en Inventario para cuadrar diferencias.
        </p>
        <table class="s-table"><thead><tr><th>Producto</th><th style="text-align:center">Cant. Anulada</th><th style="text-align:right">Costo Estimado</th></tr></thead><tbody>
            @forelse($wasteByProduct as $w)
            <tr><td style="font-weight:700">{{ $w->product_name }}</td><td style="text-align:center;font-weight:600;color:var(--s-danger)">{{ $w->qty }}</td><td style="text-align:right;font-weight:900;color:var(--s-warning-text)">S/ {{ number_format($w->cost,2) }}</td></tr>
            @empty
            <tr><td colspan="3" style="text-align:center;color:var(--s-text-3);padding:24px;"><i class="las la-check-circle" style="color:var(--s-accent)"></i> Sin mermas en este período</td></tr>
            @endforelse
        </tbody></table>
    </div>

    <!-- Arqueos (Cash Sessions) -->
    <div class="s-card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;"><i class="las la-cash-register" style="color:var(--s-accent-dark);"></i> Arqueos de Caja</h3>
        <table class="s-table"><thead><tr><th>Sesión</th><th>Apertura</th><th>Cierre</th><th>S. Inicial</th><th>Ventas</th><th>Gastos</th><th>S. Final</th><th>Dif</th><th></th></tr></thead><tbody>
            @foreach($cashSessions as $cs)
            @php $csDiff = ($cs->closing_balance??0) - $cs->opening_balance - $cs->total_sales - $cs->total_cash_in + $cs->total_expenses + $cs->total_cash_out; @endphp
            <tr><td style="font-weight:700">#{{ $cs->id }}</td>
                <td style="font-size:11px">{{ $cs->opened_at?->format('d/m H:i') }}</td>
                <td style="font-size:11px">{{ $cs->closed_at?->format('H:i') ?: 'Abierta' }}</td>
                <td>S/ {{ number_format($cs->opening_balance,2) }}</td>
                <td style="color:var(--s-accent-dark)">S/ {{ number_format($cs->total_sales,2) }}</td>
                <td style="color:var(--s-danger)">S/ {{ number_format($cs->total_expenses,2) }}</td>
                <td>S/ {{ number_format($cs->closing_balance??0,2) }}</td>
                <td style="font-weight:900;color:{{ $csDiff<0?'var(--s-danger)':'var(--s-accent-dark)' }}">S/ {{ number_format($csDiff,2) }}</td>
                <td><a href="{{ route('seller.cash.arqueo', $cs->id) }}" target="_blank" class="s-btn s-btn-ghost s-btn-xs"><i class="las la-print"></i></a></td>
            @endforeach
        </tbody></table>
    </div>

    <!-- SUNAT Exports -->
    <div class="s-card" style="margin-bottom:24px;">
        <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;"><i class="las la-file-export" style="color:var(--s-info);"></i> Exportaciones SUNAT (SIRE / Libros PLE)</h3>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:20px;">
            <div style="border:1px solid var(--s-border);border-radius:14px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <span style="background:var(--s-info-bg);padding:8px;border-radius:10px;color:var(--s-info);"><i class="las la-file-invoice" style="font-size:20px;"></i></span>
                        <div><b style="font-size:14px;">RVIE</b><br><small style="color:var(--s-text-3)">Ventas e Ingresos</small></div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('seller.reports.export.rvie', ['from'=>$dateFrom,'to'=>$dateTo,'format'=>'excel']) }}" class="s-btn s-btn-outline s-btn-sm" style="gap:6px;width:100%;justify-content:center;"><i class="las la-file-excel" style="color:#16a34a;"></i> Excel</a>
                    <a href="{{ route('seller.reports.export.rvie', ['from'=>$dateFrom,'to'=>$dateTo,'format'=>'txt']) }}" class="s-btn s-btn-outline s-btn-sm" style="gap:6px;width:100%;justify-content:center;"><i class="las la-file-alt" style="color:var(--s-purple);"></i> TXT (SIRE)</a>
                </div>
            </div>

            <div style="border:1px solid var(--s-border);border-radius:14px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <span style="background:var(--s-warning-bg);padding:8px;border-radius:10px;color:var(--s-warning);"><i class="las la-shopping-cart" style="font-size:20px;"></i></span>
                        <div><b style="font-size:14px;">RCE</b><br><small style="color:var(--s-text-3)">Registro de Compras Electrónico</small></div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('seller.reports.export.rce', ['from'=>$dateFrom,'to'=>$dateTo,'format'=>'excel']) }}" class="s-btn s-btn-outline s-btn-sm" style="gap:6px;width:100%;justify-content:center;"><i class="las la-file-excel" style="color:#16a34a;"></i> Excel</a>
                    <a href="{{ route('seller.reports.export.rce', ['from'=>$dateFrom,'to'=>$dateTo,'format'=>'txt']) }}" class="s-btn s-btn-outline s-btn-sm" style="gap:6px;width:100%;justify-content:center;"><i class="las la-file-alt" style="color:var(--s-purple);"></i> TXT (SIRE)</a>
                </div>
            </div>

            <div style="border:1px solid var(--s-border);border-radius:14px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <span style="background:var(--s-success-bg);padding:8px;border-radius:10px;color:var(--s-success);"><i class="las la-book" style="font-size:20px;"></i></span>
                        <div><b style="font-size:14px;">Libro Diario</b><br><small style="color:var(--s-text-3)">PLE SUNAT 5.1</small></div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('seller.reports.export.diario', ['from'=>$dateFrom,'to'=>$dateTo,'format'=>'excel']) }}" class="s-btn s-btn-outline s-btn-sm" style="gap:6px;width:100%;justify-content:center;"><i class="las la-file-excel" style="color:#16a34a;"></i> Excel</a>
                    <a href="{{ route('seller.reports.export.diario', ['from'=>$dateFrom,'to'=>$dateTo,'format'=>'txt']) }}" class="s-btn s-btn-outline s-btn-sm" style="gap:6px;width:100%;justify-content:center;"><i class="las la-file-alt" style="color:var(--s-purple);"></i> TXT (PLE)</a>
                </div>
            </div>

            <div style="border:1px solid var(--s-border);border-radius:14px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                        <span style="background:var(--s-purple-bg);padding:8px;border-radius:10px;color:var(--s-purple);"><i class="las la-calculator" style="font-size:20px;"></i></span>
                        <div><b style="font-size:14px;">Libro Mayor</b><br><small style="color:var(--s-text-3)">PLE SUNAT 6.1</small></div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('seller.reports.export.mayor', ['from'=>$dateFrom,'to'=>$dateTo,'format'=>'excel']) }}" class="s-btn s-btn-outline s-btn-sm" style="gap:6px;width:100%;justify-content:center;"><i class="las la-file-excel" style="color:#16a34a;"></i> Excel</a>
                    <a href="{{ route('seller.reports.export.mayor', ['from'=>$dateFrom,'to'=>$dateTo,'format'=>'txt']) }}" class="s-btn s-btn-outline s-btn-sm" style="gap:6px;width:100%;justify-content:center;"><i class="las la-file-alt" style="color:var(--s-purple);"></i> TXT (PLE)</a>
                </div>
            </div>
        </div>
    </div>

    <!-- SUNAT Invoice List -->
    <div class="s-card">
        <h3 style="margin-bottom:16px;font-weight:800;font-size:16px;"><i class="las la-receipt" style="color:var(--s-accent-dark);"></i> Comprobantes SUNAT</h3>
        <table class="s-table"><thead><tr><th>Comprobante</th><th>Cliente</th><th style="text-align:right">Total</th><th style="text-align:center">Estado</th><th>Fecha</th></tr></thead><tbody>
            @foreach($sunatInvoices as $inv)
            <tr><td style="font-weight:900">{{ $inv->serie }}-{{ str_pad($inv->correlativo,8,'0',STR_PAD_LEFT) }}</td><td>{{ $inv->cliente_nombre }}</td><td style="text-align:right;font-weight:900;color:var(--s-accent-dark)">S/ {{ number_format($inv->total,2) }}</td><td style="text-align:center"><span class="s-badge" style="background:{{ $inv->statusColor() }};color:#fff;">{{ $inv->statusLabel() }}</span></td><td style="font-size:11px;">{{ $inv->fecha_emision?->format('d/m/Y') }}</td></tr>
            @endforeach
        </tbody></table>
    </div>

</div>
@endsection
