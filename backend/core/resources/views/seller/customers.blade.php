@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-users"></i></span> Clientes
@endsection

@section('seller-content')
<style>
.customers-head{min-height:142px;margin-bottom:16px;padding:24px 26px;border-radius:14px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:20px;background:linear-gradient(90deg,rgba(82,32,151,.96),rgba(126,64,183,.83),rgba(79,24,119,.68)),url('{{ asset('assets/images/banner-cover.png') }}') center/cover no-repeat}.customers-head .crumb{font-size:9px;color:rgba(255,255,255,.7);margin-bottom:8px}.customers-head h2{font:800 24px 'Plus Jakarta Sans','Inter',sans-serif;margin:0 0 4px}.customers-head p{font-size:10px;color:rgba(255,255,255,.75);margin:0}.customers-head-actions{display:flex;gap:8px}.customers-head-actions button{height:36px;border-radius:9px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.12);color:#fff;padding:0 13px;font-size:10px;font-weight:700}.customer-kpis{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:16px}.customer-kpi{background:#fff;border:1px solid var(--s-border);border-radius:14px;padding:14px;box-shadow:var(--s-shadow-sm);min-height:104px}.customer-kpi i{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;color:#fff;font-size:18px;margin-bottom:9px;box-shadow:0 6px 14px rgba(15,23,42,.1)}.customer-kpi b{display:block;font-size:19px;color:#172033}.customer-kpi small{font-size:9px;color:#94a3b8}.customer-analytics{display:grid;grid-template-columns:minmax(0,2.2fr) minmax(260px,.8fr);gap:14px;margin-bottom:16px}.customer-chart-card,.customer-top-card{border:1px solid var(--s-border);box-shadow:var(--s-shadow-sm)!important}.customer-chart-wrap{height:235px}.customer-top-list{display:flex;flex-direction:column;gap:8px;margin-top:4px}.customer-top-item{display:grid;grid-template-columns:20px 34px 1fr auto;gap:9px;align-items:center;padding:7px;border-radius:9px}.customer-top-item:first-child{background:#fffbeb}.customer-top-rank{font-size:11px;font-weight:800;color:#94a3b8;text-align:center}.customer-top-avatar{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#f3e8ff;color:#7c3aed;font-weight:800}.customer-top-copy b{display:block;font-size:10px;color:#273244}.customer-top-copy small{font-size:8px;color:#94a3b8}.customer-top-spent{font-size:10px;font-weight:800;color:#10b981}.customers-table-card{padding:0!important;overflow:hidden}.customers-toolbar{padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:10px;border-bottom:1px solid var(--s-border)}.customers-search{height:37px;min-width:260px;border:1px solid var(--s-border);border-radius:9px;padding:0 12px;font-size:10px;outline:0}.customers-table-card .s-table thead th{background:#fbfcfe;font-size:9px;color:#94a3b8;text-transform:uppercase}.customers-table-card .s-table tbody td{padding-top:12px;padding-bottom:12px}@media(max-width:1100px){.customer-kpis{grid-template-columns:repeat(3,1fr)}}@media(max-width:767px){.customers-head{align-items:flex-start;flex-direction:column}.customer-kpis{grid-template-columns:repeat(2,1fr)}.customer-analytics{grid-template-columns:1fr}.customers-toolbar{align-items:stretch;flex-direction:column}.customers-search{width:100%;min-width:0}}@media(max-width:480px){.customer-kpis{grid-template-columns:1fr}}
</style>
<div class="s-content">
    @php $frequentCustomers = $customers->where('total_orders','>=',5)->count(); $avgCustomerTicket = $customers->sum('total_orders') > 0 ? $customers->sum('total_spent') / $customers->sum('total_orders') : 0; @endphp
    <section class="customers-head"><div><div class="crumb"><i class="las la-home"></i> Seller &nbsp;/&nbsp; Clientes</div><h2><i class="las la-user-friends"></i> Gestión de clientes</h2><p>Conoce, segmenta y fortalece la relación con tu base de clientes.</p></div><div class="customers-head-actions"><button type="button" onclick="window.print()"><i class="las la-file-export"></i> Exportar</button></div></section>
    <div class="customer-kpis">
        <div class="customer-kpi"><i class="las la-users" style="background:#8b5cf6"></i><b>{{ $customers->count() }}</b><small>Total clientes</small></div>
        <div class="customer-kpi"><i class="las la-user-check" style="background:#10b981"></i><b style="color:#10b981">{{ $customerStats['active'] }}</b><small>Activos (90 días)</small></div>
        <div class="customer-kpi"><i class="las la-user-plus" style="background:#3b82f6"></i><b style="color:#3b82f6">{{ $customerStats['new_today'] }}</b><small>Nuevos hoy</small></div>
        <div class="customer-kpi"><i class="las la-crown" style="background:#f59e0b"></i><b style="color:#f59e0b">{{ $customerStats['vip'] }}</b><small>Clientes frecuentes</small></div>
        <div class="customer-kpi"><i class="las la-user-times" style="background:#ef4444"></i><b style="color:#ef4444">{{ $customerStats['churned'] }}</b><small>Inactivos +90 días</small></div>
        <div class="customer-kpi"><i class="las la-dollar-sign" style="background:#f97316"></i><b style="color:#f97316">S/ {{ number_format($avgCustomerTicket,2) }}</b><small>Valor medio pedido</small></div>
    </div>
    <div class="customer-analytics">
        <div class="s-card customer-chart-card"><h3 class="s-card-title"><i class="las la-chart-line" style="color:#8b5cf6"></i> Crecimiento de clientes (12 meses)</h3><div class="customer-chart-wrap"><canvas id="customerGrowthChart"></canvas></div></div>
        <div class="s-card customer-top-card"><h3 class="s-card-title"><i class="las la-trophy" style="color:#f59e0b"></i> Top clientes</h3><div class="customer-top-list">@forelse($customers->sortByDesc('total_spent')->take(5)->values() as $index => $top)<div class="customer-top-item"><span class="customer-top-rank">{{ $index + 1 }}</span><span class="customer-top-avatar">{{ strtoupper(substr($top->customer_name ?: 'A',0,1)) }}</span><span class="customer-top-copy"><b>{{ $top->customer_name ?: 'Anónimo' }}</b><small>{{ $top->total_orders }} pedidos</small></span><span class="customer-top-spent">S/ {{ number_format($top->total_spent,0) }}</span></div>@empty<div class="s-empty"><p>Sin clientes todavía</p></div>@endforelse</div></div>
    </div>
    <div class="s-card customers-table-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <h3 class="s-card-title" style="margin:0"><i class="las la-users"></i> Historial de Clientes</h3>
            <span class="s-badge s-badge-blue">{{ $customers->count() }} clientes</span>
        </div>
        @if(false && $customers->count())
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
        <div class="customers-toolbar"><div><b style="font-size:12px;color:#172033">Todos los clientes</b><span class="s-badge s-badge-purple" style="margin-left:6px">{{ $customers->count() }}</span></div><input class="customers-search" id="customersSearch" type="search" placeholder="Buscar cliente o teléfono..."></div>
        <div style="overflow-x:auto;padding:0 18px 18px">
        <table class="s-table">
            <thead>
                <tr class="customer-data-row">
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
                <tr class="customer-data-row">
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
@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chart = document.getElementById('customerGrowthChart');
    if (chart && window.Chart) {
        new Chart(chart, {
            type: 'line',
            data: {
                labels: @json($customerGrowth->pluck('label')->values()),
                datasets: [
                    {label:'Nuevos',data:@json($customerGrowth->pluck('new')->values()),borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,.08)',borderWidth:2,pointRadius:0,tension:.38,fill:true},
                    {label:'Recurrentes',data:@json($customerGrowth->pluck('returning')->values()),borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.07)',borderWidth:2,pointRadius:0,tension:.38,fill:true}
                ]
            },
            options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:7,font:{size:9}}}},scales:{x:{grid:{display:false},ticks:{font:{size:9}}},y:{beginAtZero:true,border:{display:false},grid:{color:'#eef2f6'},ticks:{precision:0,font:{size:9}}}}}
        });
    }
    var search = document.getElementById('customersSearch');
    if (!search) return;
    search.addEventListener('input', function() {
        var query = search.value.trim().toLowerCase();
        document.querySelectorAll('.customer-data-row').forEach(function(row) {
            row.style.display = !query || row.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
    });
});
</script>
@endpush
@endsection
