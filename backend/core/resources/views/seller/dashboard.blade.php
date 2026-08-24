@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-chart-pie"></i></span> Dashboard
@endsection

@section('topbar-actions')
<span class="s-badge s-badge-green" style="font-size:11px;padding:6px 12px">
    <i class="las la-circle" style="font-size:8px"></i> En línea
</span>
@endsection

@push('style')
<style>
.seller-hero{min-height:132px;margin-bottom:18px;padding:22px 24px;border-radius:14px;position:relative;overflow:hidden;display:flex;align-items:flex-end;justify-content:space-between;gap:20px;background:linear-gradient(90deg,rgba(14,22,38,.96),rgba(22,30,46,.76),rgba(19,30,31,.38)),url('{{ asset('assets/images/banner-cover.png') }}') center/cover no-repeat;color:#fff}
.seller-hero:after{content:'';position:absolute;inset:0;background:linear-gradient(120deg,rgba(249,115,22,.12),transparent 45%);pointer-events:none}
.seller-hero-copy,.seller-hero-filter{position:relative;z-index:1}.seller-hero-kicker{display:flex;align-items:center;gap:6px;font-size:10px;color:rgba(255,255,255,.72);margin-bottom:9px}.seller-hero h2{font:800 23px 'Plus Jakarta Sans','Inter',sans-serif;letter-spacing:-.6px;margin:0 0 3px}.seller-hero p{margin:0;color:rgba(255,255,255,.7);font-size:11px}.seller-hero-filter{display:flex;align-items:center;gap:7px;flex-wrap:wrap;justify-content:flex-end}.seller-hero-filter select,.seller-hero-filter input{height:34px;border:1px solid rgba(255,255,255,.22)!important;background:rgba(15,23,42,.56)!important;color:#fff!important;border-radius:9px!important;padding:0 10px!important;font-size:10px!important;backdrop-filter:blur(8px)}.seller-hero-filter option{color:#172033;background:#fff}.seller-hero-filter button{height:34px;padding:0 13px!important;background:#fff!important;color:#334155!important;border-radius:9px!important;font-weight:700;box-shadow:0 5px 14px rgba(0,0,0,.12)}
.s-stat{min-height:112px!important;padding:16px!important;border:1px solid var(--s-border)!important;border-left:1px solid var(--s-border)!important;border-radius:14px!important;background:#fff!important;box-shadow:var(--s-shadow-sm)!important;align-items:flex-start!important;position:relative;overflow:hidden}.s-stat:after{content:'';position:absolute;width:72px;height:72px;border-radius:50%;right:-30px;top:-30px;background:rgba(249,115,22,.035)}.s-stat-icon{width:40px!important;height:40px!important;border-radius:11px!important;box-shadow:0 7px 16px rgba(15,23,42,.09)}.s-stat strong{font-size:20px!important;letter-spacing:-.5px}.s-stat small{font-size:10px!important;color:#8b98aa!important}.s-grid-4{gap:12px!important}
.dashboard-mini-stats>div{box-shadow:var(--s-shadow-sm);min-height:72px}.dashboard-mini-stats>div:hover{transform:translateY(-1px)}
.seller-welcome{display:flex;align-items:center;gap:10px}.seller-welcome-avatar{width:38px;height:38px;border-radius:11px;border:2px solid rgba(255,255,255,.75);overflow:hidden;background:#fff;display:grid;place-items:center;color:#f97316;font-size:20px;box-shadow:0 6px 18px rgba(0,0,0,.2)}.seller-welcome-avatar img{width:100%;height:100%;object-fit:cover}
.dashboard-primary .s-stat{display:block!important}.dashboard-primary .s-stat-icon{margin-bottom:12px}.dashboard-primary .s-stat strong{display:block;margin-bottom:2px}.dashboard-primary .s-stat small{display:block}.dashboard-primary .s-stat:nth-child(1) .s-stat-icon{background:linear-gradient(135deg,#34d399,#10b981)!important;color:#fff}.dashboard-primary .s-stat:nth-child(2) .s-stat-icon{background:linear-gradient(135deg,#60a5fa,#2563eb)!important;color:#fff}.dashboard-primary .s-stat:nth-child(3) .s-stat-icon{background:linear-gradient(135deg,#a78bfa,#7c3aed)!important;color:#fff}.dashboard-primary .s-stat:nth-child(4) .s-stat-icon{background:linear-gradient(135deg,#fbbf24,#f97316)!important;color:#fff}
.dashboard-secondary{display:grid!important;grid-template-columns:repeat(4,1fr)!important;gap:10px!important;margin-bottom:18px!important}.dashboard-secondary .s-stat{min-height:62px!important;padding:10px 13px!important;display:flex!important;align-items:center!important;gap:10px}.dashboard-secondary .s-stat-icon{width:30px!important;height:30px!important;border-radius:8px!important;margin:0!important;font-size:15px!important;box-shadow:none}.dashboard-secondary .s-stat strong{font-size:15px!important}.dashboard-secondary .s-stat small{font-size:8px!important}
.dashboard-main-chart{min-height:330px}.dashboard-main-chart canvas{height:245px!important}.s-content>.s-card,.s-content>.s-grid-2>.s-card{border:1px solid #e8edf3!important;box-shadow:0 3px 12px rgba(15,23,42,.045)!important}.s-table thead th{background:#fbfcfe!important;color:#94a3b8!important;font-size:9px!important;text-transform:uppercase!important;letter-spacing:.4px}.s-table tbody td{padding-top:12px!important;padding-bottom:12px!important}.s-card-title i{color:#f97316}.dashboard-mini-stats{grid-template-columns:repeat(5,1fr)!important}.dashboard-mini-stats>div{background:#fff!important}
.s-content>.s-grid-2{min-width:0;max-width:100%}.s-content>.s-grid-2>.s-card{min-width:0;max-width:100%}.s-content>.s-grid-2>.s-card>div[style*="overflow-x:auto"]{display:block;width:100%;max-width:100%;overflow-x:auto!important;-webkit-overflow-scrolling:touch}
@media(max-width:767px){.seller-hero{min-height:180px;align-items:flex-start;flex-direction:column;padding:18px}.seller-hero-filter{justify-content:flex-start}.seller-hero h2{font-size:20px}.s-stat{min-height:100px!important}}
@media(max-width:991px){.dashboard-secondary{grid-template-columns:repeat(2,1fr)!important}.dashboard-mini-stats{grid-template-columns:repeat(2,1fr)!important}}
@media(max-width:560px){.dashboard-secondary{grid-template-columns:1fr!important}.dashboard-mini-stats{grid-template-columns:1fr!important}}
</style>
@endpush

@section('seller-content')
<div class="s-content">

    <!-- ═══ HERO + DATE RANGE FILTER ═══ -->
    <section class="seller-hero">
        <div class="seller-hero-copy">
            <div class="seller-hero-kicker"><i class="las la-home"></i><span>Inicio</span><i class="las la-angle-right"></i><span>Dashboard</span></div>
            <div class="seller-welcome">
                <div class="seller-welcome-avatar">
                    @if($store && $store->image)
                        <img src="{{ getImage('assets/images/store/' . $store->image) }}" alt="{{ $store->name }}">
                    @else
                        <i class="las la-store"></i>
                    @endif
                </div>
                <div>
                    <h2>Buenos días, {{ Str::before($seller->name ?? 'Administrador', ' ') }} <span aria-hidden="true">👋</span></h2>
                    <p>Esto es lo que está pasando hoy en {{ $store->name ?? 'tu negocio' }}.</p>
                </div>
            </div>
        </div>
        <form class="seller-hero-filter" method="GET" action="{{ route('seller.dashboard') }}">
            <div style="display:flex;gap:7px;align-items:center">
                <select name="period" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--s-border);border-radius:6px;font-size:12px;background:var(--s-surface);color:var(--s-text)">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hoy</option>
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Esta Semana</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Este Mes</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Personalizado</option>
                </select>
            </div>
            <div id="customDates" style="display:{{ $period === 'custom' ? 'flex' : 'none' }};gap:8px;align-items:center">
                <input type="date" name="date_from" value="{{ $dateFrom }}" style="padding:6px 10px;border:1px solid var(--s-border);border-radius:6px;font-size:12px">
                <span style="font-size:12px;color:var(--s-text-3)">a</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" style="padding:6px 10px;border:1px solid var(--s-border);border-radius:6px;font-size:12px">
                <button type="submit" style="padding:6px 14px;background:#22c55e;color:#fff;border:none;border-radius:6px;font-size:12px;cursor:pointer">Filtrar</button>
            </div>
        </form>
    </section>

    <!-- ═══ ROW 1: KPI PRINCIPALES ═══ -->
    <div class="s-grid-4 dashboard-primary" style="margin-bottom:16px">
        <div class="s-stat" style="border-left:4px solid #22c55e">
            <div class="s-stat-icon green"><i class="las la-dollar-sign"></i></div>
            <div>
                <strong>S/ {{ number_format($stats['total_sales_today'], 2) }}</strong>
                <small>Ingresos de hoy</small>
            </div>
        </div>
        <div class="s-stat" style="border-left:4px solid #8b5cf6">
            <div class="s-stat-icon purple"><i class="las la-chart-line"></i></div>
            <div>
                <strong>S/ {{ number_format($stats['total_sales_month'], 2) }}</strong>
                <small>Ingresos del mes</small>
            </div>
        </div>
        <div class="s-stat" style="border-left:4px solid #3b82f6">
            <div class="s-stat-icon blue"><i class="las la-shopping-bag"></i></div>
            <div>
                <strong>{{ $stats['pos_today_count'] + $stats['del_today_count'] }}</strong>
                <small>Pedidos de hoy</small>
            </div>
        </div>
        <div class="s-stat" style="border-left:4px solid #f59e0b">
            <div class="s-stat-icon amber"><i class="las la-utensils"></i></div>
            <div>
                <strong>{{ $stats['kitchen_pending'] }}</strong>
                <small>Pedidos en cocina</small>
            </div>
        </div>
    </div>

    <!-- ═══ ROW 2: KPI SECUNDARIOS ═══ -->
    <div class="s-grid-4 dashboard-secondary" style="margin-bottom:20px">
        <div class="s-stat" style="border-left:4px solid #22c55e">
            <div class="s-stat-icon green"><i class="las la-receipt"></i></div>
            <div>
                <strong>S/ {{ number_format($stats['avg_ticket_today'], 2) }}</strong>
                <small>Ticket Prom. Hoy</small>
            </div>
        </div>
        <div class="s-stat" style="border-left:4px solid #8b5cf6">
            <div class="s-stat-icon purple"><i class="las la-motorcycle"></i></div>
            <div>
                <strong>{{ $stats['del_pct'] }}%</strong>
                <small>Delivery ({{ $stats['del_month_count'] }} ped.)</small>
            </div>
        </div>
        <div class="s-stat" style="border-left:4px solid {{ $stats['sales_growth_pct'] >= 0 ? '#22c55e' : '#ef4444' }}">
            <div class="s-stat-icon {{ $stats['sales_growth_pct'] >= 0 ? 'green' : 'red' }}"><i class="las la-{{ $stats['sales_growth_pct'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i></div>
            <div>
                <strong>{{ $stats['sales_growth_pct'] >= 0 ? '+' : '' }}{{ $stats['sales_growth_pct'] }}%</strong>
                <small>Crecimiento vs Mes Ant.</small>
            </div>
        </div>
        <div class="s-stat" style="border-left:4px solid #f59e0b">
            <div class="s-stat-icon amber"><i class="las la-check-circle"></i></div>
            <div>
                <strong>{{ $stats['completed_today'] }}</strong>
                <small>Completados Hoy</small>
            </div>
        </div>
    </div>

    <!-- ═══ ROW 3: KPI EXTRA ═══ -->
    <div class="dashboard-mini-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:24px">
        <div style="background:var(--s-surface);border:1px solid var(--s-border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px">
            <div style="width:40px;height:40px;border-radius:10px;background:#dbeafe;display:grid;place-items:center;flex-shrink:0"><i class="las la-calendar-check" style="color:#2563eb;font-size:20px"></i></div>
            <div><b style="font-size:18px;color:var(--s-text);display:block">{{ $stats['pos_month_count'] + $stats['del_month_count'] }}</b><small style="font-size:11px;color:var(--s-text-3)">Pedidos del Mes</small></div>
        </div>
        <div style="background:var(--s-surface);border:1px solid var(--s-border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px">
            <div style="width:40px;height:40px;border-radius:10px;background:#dcfce7;display:grid;place-items:center;flex-shrink:0"><i class="las la-store" style="color:#16a34a;font-size:20px"></i></div>
            <div><b style="font-size:18px;color:var(--s-text);display:block">{{ $stats['pos_pct'] }}%</b><small style="font-size:11px;color:var(--s-text-3)">POS ({{ $stats['pos_month_count'] }} ped.)</small></div>
        </div>
        <div style="background:var(--s-surface);border:1px solid var(--s-border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px">
            <div style="width:40px;height:40px;border-radius:10px;background:#fef3c7;display:grid;place-items:center;flex-shrink:0"><i class="las la-sack-dollar" style="color:#d97706;font-size:20px"></i></div>
            <div><b style="font-size:18px;color:var(--s-text);display:block">S/ {{ number_format($stats['receivables_amount'], 2) }}</b><small style="font-size:11px;color:var(--s-text-3)">Por Cobrar a Lizto</small></div>
        </div>
        @if($store && $store->isRestaurant())
        <div style="background:var(--s-surface);border:1px solid var(--s-border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px">
            <div style="width:40px;height:40px;border-radius:10px;background:#e0e7ff;display:grid;place-items:center;flex-shrink:0"><i class="las la-chair" style="color:#4f46e5;font-size:20px"></i></div>
            <div><b style="font-size:18px;color:var(--s-text);display:block">{{ $stats['active_tables'] }}</b><small style="font-size:11px;color:var(--s-text-3)">Mesas Ocupadas</small></div>
        </div>
        @endif
        <div style="background:var(--s-surface);border:1px solid var(--s-border);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px">
            <div style="width:40px;height:40px;border-radius:10px;background:#f3e8ff;display:grid;place-items:center;flex-shrink:0"><i class="las la-chart-bar" style="color:#9333ea;font-size:20px"></i></div>
            <div><b style="font-size:18px;color:var(--s-text);display:block">S/ {{ number_format($stats['avg_ticket_month'], 2) }}</b><small style="font-size:11px;color:var(--s-text-3)">Ticket Prom. Mes</small></div>
        </div>
    </div>

    <!-- ═══ CHART: VENTAS 30 DÍAS (Chart.js) ═══ -->
    <div class="s-card dashboard-main-chart" style="margin-bottom:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:8px">
            <div><h3 class="s-card-title" style="margin:0 0 3px"><i class="las la-chart-line"></i> Analítica de ingresos</h3><small style="color:#94a3b8;font-size:9px">Ventas POS y delivery de los últimos 30 días</small></div>
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                <span class="s-badge s-badge-green">S/ {{ number_format($chartDays->sum('total'), 2) }} total</span>
            </div>
        </div>
        <canvas id="salesChart" height="100" style="width:100%"></canvas>
    </div>

    <!-- ═══ CHARTS EN PARALELO: HORA + DÍA SEMANA ═══ -->
    <div class="s-grid-2" style="margin-bottom:20px">
        <!-- Pedidos por Hora Hoy -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-clock"></i> Pedidos por Hora (Hoy)</h3>
            @php $maxHour = $hourlyChart->max('total') ?: 1; @endphp
            <div style="display:flex;align-items:flex-end;gap:4px;height:140px;padding:8px 0 4px">
                @foreach($hourlyChart as $h)
                @php $hh = $maxHour > 0 ? max(2, ($h['total']/$maxHour)*100) : 2; @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end" title="{{ $h['hour'] }} — {{ $h['total'] }} pedidos (POS: {{ $h['pos'] }}, Del: {{ $h['del'] }})">
                    @if($h['total'] > 0)
                    <div style="width:100%;max-width:18px;display:flex;flex-direction:column;justify-content:flex-end;height:{{ $hh }}%;border-radius:4px 4px 0 0;overflow:hidden">
                        @if($h['del'] > 0)
                        <div style="background:#8b5cf6;height:{{ ($h['del']/$h['total'])*100 }}%"></div>
                        @endif
                        @if($h['pos'] > 0)
                        <div style="background:#22c55e;height:{{ ($h['pos']/$h['total'])*100 }}%"></div>
                        @endif
                    </div>
                    @else
                    <div style="background:#e4e9ef;width:100%;max-width:18px;height:2px;border-radius:2px 2px 0 0"></div>
                    @endif
                    <small style="font-size:8px;color:var(--s-text-3);margin-top:3px;white-space:nowrap">{{ substr($h['hour'],0,2) }}</small>
                </div>
                @endforeach
            </div>
            <div style="display:flex;gap:12px;font-size:10px;font-weight:600;margin-top:6px;padding-top:6px;border-top:1px solid var(--s-border)">
                <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#22c55e;display:inline-block"></span> POS</span>
                <span style="display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:2px;background:#8b5cf6;display:inline-block"></span> Delivery</span>
            </div>
        </div>

        <!-- Pedidos por Día de Semana -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-calendar-week"></i> Pedidos por Día (Mes)</h3>
            @php $maxDay = $ordersByDay->max('count') ?: 1; @endphp
            <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px">
                @php
                $dayNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
                $days = collect($dayNames)->map(fn($n, $i) => ['name' => $n, 'count' => $ordersByDay->firstWhere('day_num', $i+1)['count'] ?? 0, 'num' => $i+1]);
                @endphp
                @foreach($days as $d)
                @php $w = $maxDay > 0 ? max(2, ($d['count']/$maxDay)*100) : 2; @endphp
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="width:24px;font-size:10px;font-weight:700;color:var(--s-text-3);text-align:right;flex-shrink:0">{{ $d['name'] }}</span>
                    <div style="flex:1;height:18px;background:var(--s-surface-2);border-radius:6px;overflow:hidden;position:relative">
                        <div style="height:100%;width:{{ $w }}%;background:linear-gradient(90deg,#22c55e,#16a34a);border-radius:6px;transition:width .3s"></div>
                    </div>
                    <span style="width:30px;font-size:11px;font-weight:800;color:var(--s-text);text-align:right;flex-shrink:0">{{ $d['count'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- ═══ HEATMAP DELIVERY ═══ -->
    <div class="s-card" style="margin-bottom:20px">
        <h3 class="s-card-title"><i class="las la-map-marked-alt"></i> Mapa de Calor — Zonas Delivery (Últimos 60 días)</h3>
        @if($heatmapPoints->count() > 0)
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap">
            <span class="s-badge s-badge-purple">{{ $heatmapPoints->count() }} zonas con pedidos</span>
            <span style="font-size:11px;color:var(--s-text-3)">Los puntos más oscuros indican mayor concentración de pedidos</span>
        </div>
        @else
        <div style="margin-bottom:12px">
            <span class="s-badge s-badge-gray">Sin pedidos delivery aún</span>
            <span style="font-size:11px;color:var(--s-text-3);margin-left:8px">Apenas se registren pedidos con dirección, las zonas aparecerán aquí</span>
        </div>
        @endif
        <div style="position: relative;">
            <div id="heatmap-map" style="width:100%;height:380px;border-radius:12px;border:1.5px solid var(--s-border);overflow:hidden"></div>
            @if(!$store->hasPremiumPackage())
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 12px; padding: 20px; text-align: center; z-index: 10;">
                <div style="background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 400px;">
                    <div style="font-size: 40px; margin-bottom: 12px;">📍</div>
                    <h4 style="font-weight: 700; margin-bottom: 8px; color: #1e293b; font-size: 16px;">Mapa de Calor Premium</h4>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 16px; line-height: 1.5;">Si deseas ver dónde es tu público que más pide, consigue el plan Premium.</p>
                    <a href="{{ route('seller.pricing') }}" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); border: none; padding: 8px 16px; border-radius: 8px; color: white; font-weight: 600; text-decoration: none; display: inline-block; font-size: 12px;">Adquirir Plan Premium</a>
                </div>
            </div>
            @endif
        </div>
        @if(gs('google_maps_api'))
        @push('script')
        <script>
        function initHeatmap() {
            if (!window.google || !google.maps) return;
            @php $storeLat = $store?->latitude ?? -12.0464; $storeLng = $store?->longitude ?? -77.0428; @endphp
            var map = new google.maps.Map(document.getElementById('heatmap-map'), {
                center: {lat: {{ $storeLat }}, lng: {{ $storeLng }}},
                zoom: 13,
                styles: [
                    {featureType: 'poi', stylers: [{visibility: 'off'}]},
                    {featureType: 'transit', stylers: [{visibility: 'off'}]}
                ]
            });

            var storeMarker = new google.maps.Marker({
                position: {lat: {{ $storeLat }}, lng: {{ $storeLng }}},
                map: map,
                title: 'Tu Tienda',
                icon: {url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"><circle cx="14" cy="14" r="12" fill="#ef4444" stroke="#fff" stroke-width="2"/><text x="14" y="18" text-anchor="middle" fill="#fff" font-size="14" font-weight="bold">T</text></svg>')},
                zIndex: 1000
            });

            var points = {!! $store->hasPremiumPackage() ? $heatmapPoints->toJson() : '[]' !!};
            if (!points.length) return;

            var pointWeights = points.map(function(p) { return p.weight; });
            var maxWeight = Math.max.apply(null, pointWeights);

            // Individual markers with size based on weight
            points.forEach(function(p) {
                var radius = Math.max(8, Math.min(40, (p.weight / maxWeight) * 40));
                var opacity = 0.3 + (p.weight / maxWeight) * 0.5;
                new google.maps.Circle({
                    map: map,
                    center: {lat: p.lat, lng: p.lng},
                    radius: radius * 100,
                    fillColor: '#8b5cf6',
                    fillOpacity: opacity,
                    strokeColor: '#7c3aed',
                    strokeOpacity: 0.6,
                    strokeWeight: 1
                });
                var infoLabel = new google.maps.Marker({
                    position: {lat: p.lat, lng: p.lng},
                    map: map,
                    label: {text: '' + p.weight, color: '#fff', fontSize: '10px', fontWeight: 'bold'},
                    icon: {url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><circle cx="0" cy="0" r="0"/></svg>'), size: null},
                    zIndex: 500,
                    title: p.weight + ' pedidos en esta zona'
                });
            });
        }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places&callback=initHeatmap" async defer></script>
        @endpush
        @else
        <div style="height:200px;display:flex;align-items:center;justify-content:center;color:var(--s-text-3);font-size:13px;border:1px dashed var(--s-border);border-radius:12px">
            <i class="las la-map" style="font-size:32px;margin-right:8px;opacity:0.5"></i> Configura Google Maps API para ver el mapa
        </div>
        @endif
    </div>

    <!-- ═══ TOP PRODUCTOS + STATUS BREAKDOWN ═══ -->
    <div class="s-grid-2" style="margin-bottom:20px">
        <!-- Top 10 Productos -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-fire"></i> Top 10 Productos Más Vendidos (Mes)</h3>
            <div style="overflow-x:auto">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Cant.</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $i => $tp)
                        <tr>
                            <td>
                                <span style="width:22px;height:22px;border-radius:50%;background:{{ $i < 3 ? 'var(--s-accent)' : 'var(--s-surface-2)' }};color:{{ $i < 3 ? '#fff' : 'var(--s-text-3)' }};display:grid;place-items:center;font-size:10px;font-weight:800;flex-shrink:0">
                                    {{ $i + 1 }}
                                </span>
                            </td>
                            <td>
                                <b style="font-size:12px;color:var(--s-text)">{{ $tp->product_name ?: 'Sin nombre' }}</b>
                            </td>
                            <td><span class="s-badge s-badge-green" style="font-size:10px">{{ $tp->total_qty }}</span></td>
                            <td><b style="font-size:12px;color:var(--s-accent-dark)">S/ {{ number_format($tp->total_revenue, 2) }}</b></td>
                        </tr>
                        @empty
                        <tr><td colspan="4"><div class="s-empty"><i class="las la-box"></i><p>Sin ventas este mes</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Estado de Pedidos (Mes) -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las fa-stream"></i> Estado de Pedidos (Mes)</h3>
            @php
                $statusColors = [
                    'confirmed' => '#22c55e', 'preparing' => '#f59e0b', 'ready' => '#3b82f6',
                    'delivered' => '#16a34a', 'cancelled' => '#ef4444', 'pending' => '#f97316',
                    'on_the_way' => '#8b5cf6', 'paid' => '#22c55e',
                ];
                $statusLabels = [
                    'confirmed' => 'Confirmado', 'preparing' => 'En Cocina', 'ready' => 'Listo',
                    'delivered' => 'Entregado', 'cancelled' => 'Cancelado', 'pending' => 'Pendiente',
                    'on_the_way' => 'En Camino', 'paid' => 'Pagado',
                ];
                $statusTotalOrders = $statusBreakdown->sum('count') ?: 1;
            @endphp
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px">
                @forelse($statusBreakdown as $sb)
                @php $pct = round(($sb->count / $statusTotalOrders) * 100, 1); @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                        <span style="font-size:12px;font-weight:600;color:var(--s-text);display:flex;align-items:center;gap:6px">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $statusColors[$sb->status] ?? '#6b7280' }};display:inline-block"></span>
                            {{ $statusLabels[$sb->status] ?? $sb->status }}
                        </span>
                        <span style="font-size:12px;font-weight:800;color:var(--s-text)">{{ $sb->count }} <span style="font-size:10px;color:var(--s-text-3);font-weight:600">({{ $pct }}%)</span></span>
                    </div>
                    <div style="height:8px;background:var(--s-surface-2);border-radius:6px;overflow:hidden">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $statusColors[$sb->status] ?? '#6b7280' }};border-radius:6px;transition:width .3s"></div>
                    </div>
                </div>
                @empty
                <div class="s-empty"><i class="las fa-inbox"></i><p>Sin pedidos este mes</p></div>
                @endforelse
            </div>

            @if($statusBreakdown->count())
            <!-- Mini donut chart -->
            <div style="display:flex;align-items:center;gap:16px;margin-top:20px;padding-top:14px;border-top:1px solid var(--s-border)">
                @php
                    $donutTotal = $statusBreakdown->sum('count');
                    $donutOffset = 0;
                @endphp
                <svg viewBox="0 0 36 36" style="width:72px;height:72px;flex-shrink:0">
                    @foreach($statusBreakdown as $sb)
                    @php $pctDash = ($sb->count / $donutTotal) * 100; @endphp
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="{{ $statusColors[$sb->status] ?? '#6b7280' }}" stroke-width="3.5" stroke-dasharray="{{ $pctDash }} {{ 100 - $pctDash }}" stroke-dashoffset="{{ -$donutOffset }}" style="transition:stroke-dashoffset .3s"/>
                    @php $donutOffset += $pctDash; @endphp
                    @endforeach
                    <text x="18" y="18" text-anchor="middle" dominant-baseline="central" style="font-size:5px;font-weight:900;fill:var(--s-text)">{{ $donutTotal }}</text>
                </svg>
                <div style="display:flex;flex-wrap:wrap;gap:4px 12px">
                    @foreach($statusBreakdown as $sb)
                    <span style="display:flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:var(--s-text-2)">
                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $statusColors[$sb->status] ?? '#6b7280' }}"></span>
                        {{ $statusLabels[$sb->status] ?? $sb->status }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- ═══ TABLAS: RECIENTES + CLIENTES ═══ -->
    <div class="s-grid-2">
        <!-- Recent Orders -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-clock"></i> Pedidos Recientes</h3>
            <div style="overflow-x:auto">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Origen</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $o)
                        <tr>
                            <td><b style="font-size:12px">{{ $o['order_no'] }}</b></td>
                            <td>
                                <span class="s-badge {{ $o['source']==='POS' ? 's-badge-green' : 's-badge-purple' }}">
                                    {{ $o['source'] }}
                                </span>
                            </td>
                            <td style="color:var(--s-text-2)">{{ $o['customer'] ?: '—' }}</td>
                            <td><b>S/ {{ number_format($o['total'],2) }}</b></td>
                            <td>
                                @php $s=$o['status']; @endphp
                                <span class="s-badge {{ $s==='delivered'?'s-badge-green':($s==='cancelled'?'s-badge-red':($s==='preparing'||$s==='on_the_way'?'s-badge-blue':'s-badge-amber')) }}">
                                    {{ $s }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5"><div class="s-empty"><i class="las la-inbox"></i><p>Sin pedidos aún</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-star"></i> Top Clientes</h3>
            <div style="overflow-x:auto">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Pedidos</th>
                            <th>Total</th>
                            <th>Último</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $c)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:30px;height:30px;border-radius:50%;background:var(--s-accent-light);display:grid;place-items:center;font-size:12px;font-weight:800;color:var(--s-accent-dark);flex-shrink:0">
                                        {{ strtoupper(substr($c->customer_name ?: 'A', 0, 1)) }}
                                    </div>
                                    <b>{{ $c->customer_name ?: 'Anónimo' }}</b>
                                </div>
                            </td>
                            <td><span class="s-badge s-badge-blue">{{ $c->total_orders }}</span></td>
                            <td><b>S/ {{ number_format($c->total_spent,2) }}</b></td>
                            <td style="font-size:11px;color:var(--s-text-3)">{{ $c->last_order?->format('d/m') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4"><div class="s-empty"><i class="las la-users"></i><p>Sin clientes</p></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle custom dates
    const periodSelect = document.querySelector('select[name="period"]');
    const customDates = document.getElementById('customDates');
    if (periodSelect && customDates) {
        periodSelect.addEventListener('change', function() {
            customDates.style.display = this.value === 'custom' ? 'flex' : 'none';
        });
    }

    // Sales Chart (Chart.js)
    const ctx = document.getElementById('salesChart');
    if (ctx) {
        const labels = {!! json_encode($chartDays->pluck('date')->map(fn($d) => substr($d, 5))->values()) !!};
        const posData = {!! json_encode($chartDays->pluck('pos')->values()) !!};
        const delData = {!! json_encode($chartDays->pluck('del')->values()) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'POS',
                        data: posData,
                        backgroundColor: 'rgba(249, 115, 22, 0.08)',
                        borderColor: '#f97316',
                        borderWidth: 2,
                        pointRadius: 0,
                        tension: .38,
                        fill: true,
                    },
                    {
                        label: 'Delivery',
                        data: delData,
                        backgroundColor: 'rgba(59, 130, 246, 0.04)',
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        pointRadius: 0,
                        tension: .38,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, font: { size: 10 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': S/ ' + ctx.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: false, grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                    y: { stacked: false, beginAtZero: true, border: { display:false }, grid:{color:'#eef2f6'}, ticks: { callback: v => 'S/ ' + v, font: { size: 9 } } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
