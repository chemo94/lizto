@extends('admin.layouts.app')
@section('panel')
<p class="lz-subtitle">Una vista de tu operación, tus tiendas y tus entregas.</p>
<div class="lz-grid">
@foreach ([['today_orders','Pedidos de hoy','las la-receipt','Actividad del día'],['pending_orders','Pedidos pendientes','las la-clock','Pendientes de atención'],['delivered_orders','Pedidos entregados','las la-check-circle','Total acumulado'],['active_stores','Tiendas activas','las la-store','Disponibles en la plataforma']] as [$key,$label,$icon,$hint])
<div class="lz-stat"><div class="lz-stat-top"><span class="lz-stat-icon"><i class="{{ $icon }}"></i></span><strong>{{ $stats[$key] }}</strong></div><p>{{ $label }}</p><small>{{ $hint }}</small></div>
@endforeach
</div>
@php
$states = ['pending'=>['Pendientes','#ffab00'],'confirmed'=>['Confirmados','#696cff'],'preparing'=>['En preparación','#03c3ec'],'ready'=>['Listos','#8592a3'],'on_way'=>['En camino','#9b7bff'],'delivered'=>['Entregados','#71dd37'],'cancelled'=>['Cancelados','#ff3e1d']];
@endphp
<div class="lz-columns">
<section class="card"><div class="card-body"><h5 class="card-title">Resumen de pedidos</h5><p class="text-muted">{{ $stats['total_orders'] }} pedidos registrados · Todos los estados</p>
<div class="lz-progress" aria-label="Distribución de pedidos por estado">
@foreach($states as $state => [$label,$color])<span style="width:{{ $stats['total_orders'] ? (($orderStates[$state] ?? 0) / $stats['total_orders'] * 100) : 0 }}%;background:{{ $color }}" title="{{ $label }}: {{ $orderStates[$state] ?? 0 }}"></span>@endforeach
</div><ul class="lz-list">@foreach($states as $state => [$label,$color])<li><span><i class="las la-circle me-2" style="color:{{ $color }}"></i>{{ $label }}</span><strong>{{ $orderStates[$state] ?? 0 }}</strong></li>@endforeach</ul>
</div></section>
<section class="card"><div class="card-body"><h5 class="card-title">Actividad de pedidos</h5><p class="text-muted">Pedidos creados · Últimos 14 días</p><div id="lz-delivery-activity"></div><p class="text-muted small mb-0">{{ $activity->sum('total') ? 'Volumen diario de pedidos registrados en la plataforma.' : 'Todavía no hay pedidos en este período.' }}</p></div></section>
</div>
<div class="lz-columns">
<section class="card"><div class="card-body"><h5 class="card-title">Rendimiento del negocio</h5><ul class="lz-list">
<li><span>Ingresos de hoy <small class="text-muted">· Entregados</small></span><strong>S/ {{ number_format($stats['today_revenue'],2) }}</strong></li>
<li><span>Ingresos acumulados <small class="text-muted">· Entregados</small></span><strong>S/ {{ number_format($stats['total_revenue'],2) }}</strong></li>
<li><span>Comisiones registradas</span><strong>S/ {{ number_format($stats['total_commission'],2) }}</strong></li>
<li><span>Favores de hoy</span><strong>{{ $stats['today_favors'] }}</strong></li>
<li><span>Reembolsos pendientes</span><strong>{{ $stats['pending_refunds'] }}</strong></li>
</ul></div></section>
<section class="card"><div class="card-body"><h5 class="card-title">Centro de operaciones</h5><p class="text-muted">Accesos a la gestión diaria</p><ul class="lz-list">
@foreach([['delivery.orders','admin.delivery.orders','Pedidos','las la-receipt'],['delivery.favors','admin.delivery.favors','Favores','las la-hand-holding-heart'],['delivery.stores','admin.delivery.stores','Tiendas','las la-store'],['delivery.refunds','admin.delivery.refunds','Reembolsos','las la-undo-alt'],['delivery.wallets','admin.delivery.wallets','Billeteras','las la-wallet'],['delivery.commission','admin.delivery.commission','Configurar comisión','las la-percent']] as [$permission,$route,$label,$icon])
<x-permission_check :permission="$permission"><li><a href="{{ route($route) }}"><i class="{{ $icon }} me-2"></i>{{ $label }}</a><i class="las la-angle-right text-muted"></i></li></x-permission_check>
@endforeach
</ul><p class="text-muted small mt-3 mb-0">Delivery: {{ $commission?->delivery_percent ?? 10 }}% · Favor: {{ $commission?->favor_percent ?? 15 }}% · Mínima: S/ {{ $commission?->min_commission ?? 1 }}</p></div></section>
</div>
<x-permission_check permission="delivery.orders">
<section class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Pedidos recientes</h5><a class="btn btn-outline--primary btn-sm" href="{{ route('admin.delivery.orders') }}">Ver todos <i class="las la-arrow-right"></i></a></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Pedido</th><th>Tienda</th><th>Repartidor</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
@forelse($recentOrders as $order)<tr><td><a href="{{ route('admin.delivery.order.detail',$order->id) }}">{{ $order->order_no }}</a></td><td>{{ $order->store?->name ?? 'Sin tienda' }}</td><td>{{ $order->driver?->fullname ?? 'Sin asignar' }}</td><td>S/ {{ number_format($order->total,2) }}</td><td><span class="badge badge--primary">{{ $states[$order->status][0] ?? $order->status }}</span></td><td>{{ $order->created_at?->format('d/m/Y H:i') }}</td></tr>
@empty<tr><td colspan="6"><div class="lz-empty"><i class="las la-box-open"></i>No hay pedidos todavía.<br><small>Las nuevas operaciones aparecerán aquí.</small></div></td></tr>@endforelse
</tbody></table></div></section>
</x-permission_check>
@endsection
@push('script-lib')<script src="{{ asset('assets/admin/js/apexcharts.min.js') }}"></script>@endpush
@push('script')
<script>
(() => {
    const activity = @json($activity);
    const chart = new ApexCharts(document.querySelector('#lz-delivery-activity'), {
        chart:{type:'bar',height:350,fontFamily:'Public Sans, sans-serif',background:'transparent',toolbar:{show:false}},
        series:[{name:'Pedidos',data:activity.map(day=>day.total)}],colors:['#696cff'],
        plotOptions:{bar:{borderRadius:4,columnWidth:'38%'}},dataLabels:{enabled:false},
        xaxis:{categories:activity.map(day=>day.label)},yaxis:{min:0,forceNiceScale:true,decimalsInFloat:0},
        grid:{borderColor:'#a1acb833',strokeDashArray:5},
        theme:{mode:document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light'},
        tooltip:{theme:document.documentElement.dataset.theme}
    });
    chart.render();
    new MutationObserver(()=>chart.updateOptions({theme:{mode:document.documentElement.dataset.theme},tooltip:{theme:document.documentElement.dataset.theme}})).observe(document.documentElement,{attributes:true,attributeFilter:['data-theme']});
})();
</script>
@endpush
