@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-receipt"></i></span> Pedidos
@endsection

@section('topbar-actions')
<span class="s-badge s-badge-gray">POS + Delivery App</span>
@endsection

@section('seller-content')
<style>
    #det-tracking-map { width:100%; height:280px; border-radius:8px; margin-top:8px; border:1px solid var(--s-border); }
    #det-pin-display { padding:10px 14px; background:rgba(139,92,246,0.06); border:1px solid rgba(139,92,246,0.2); border-radius:8px; margin-bottom:12px; display:none; }
    #det-pin-display .pin-code { font-size:22px; font-weight:900; letter-spacing:4px; color:#8b5cf6; }
    #det-eta-display { padding:8px 14px; background:rgba(34,197,94,0.06); border:1px solid rgba(34,197,94,0.2); border-radius:8px; margin-bottom:12px; display:none; font-size:13px; color:var(--s-text-2); }
    #det-route-info { padding:8px 14px; background:var(--s-bg-2); border-radius:8px; margin-bottom:12px; display:none; font-size:12px; color:var(--s-text-3); }
    #detail-modal .s-modal-box { max-width:800px; }
    .orders-hero{min-height:138px;border-radius:14px;padding:24px;margin-bottom:18px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:20px;position:relative;overflow:hidden;background:linear-gradient(90deg,rgba(24,61,145,.95),rgba(62,62,168,.78),rgba(78,50,155,.58)),url('{{ asset('assets/images/banner-cover.png') }}') center/cover no-repeat}.orders-hero:after{content:'';position:absolute;inset:0;background:radial-gradient(circle at 65% 20%,rgba(255,255,255,.12),transparent 35%);pointer-events:none}.orders-hero>div{position:relative;z-index:1}.orders-hero .crumb{font-size:10px;color:rgba(255,255,255,.72);margin-bottom:9px}.orders-hero h2{font:800 24px 'Plus Jakarta Sans','Inter',sans-serif;letter-spacing:-.6px;margin:0 0 4px}.orders-hero p{font-size:11px;color:rgba(255,255,255,.72);margin:0}.orders-hero-actions{display:flex;gap:8px}.orders-hero-actions a,.orders-hero-actions button{height:35px;border:1px solid rgba(255,255,255,.28);border-radius:9px;padding:0 13px;color:#fff;background:rgba(255,255,255,.12);backdrop-filter:blur(8px);font-size:10px;font-weight:700;display:inline-flex;align-items:center;gap:6px;text-decoration:none}.orders-hero-actions .primary{background:#fff;color:#3253a8;border-color:#fff}
    .orders-kpis{display:grid;grid-template-columns:repeat(6,minmax(130px,1fr));gap:12px;margin-bottom:18px}.orders-kpi{background:#fff;border:1px solid var(--s-border);border-radius:14px;min-height:104px;padding:15px;box-shadow:var(--s-shadow-sm)}.orders-kpi-icon{width:37px;height:37px;border-radius:10px;display:grid;place-items:center;color:#fff;font-size:18px;margin-bottom:10px;box-shadow:0 7px 15px rgba(15,23,42,.11)}.orders-kpi b{display:block;font-size:19px;line-height:1;color:#172033;margin-bottom:5px}.orders-kpi small{color:#94a3b8;font-size:9px}.orders-tools{padding:0 20px;border-bottom:1px solid var(--s-border);min-width:0}.orders-tabs{display:flex;gap:4px;max-width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;scroll-behavior:smooth;overscroll-behavior-x:contain}.orders-tab{flex:0 0 auto;border:0;background:transparent;padding:14px 12px 12px;color:#8b98aa;font-size:10px;font-weight:700;white-space:nowrap;border-bottom:2px solid transparent}.orders-tab.active{color:#f97316;border-bottom-color:#f97316}.orders-tabs::-webkit-scrollbar{height:4px}.orders-tabs::-webkit-scrollbar-thumb{background:var(--s-border);border-radius:4px}.orders-filterbar{padding:14px 20px;display:flex;gap:9px;align-items:center}.orders-search{height:38px;flex:1;min-width:220px;border:1px solid var(--s-border);border-radius:9px;padding:0 12px 0 36px;background:#fff;outline:none;font-size:11px}.orders-search-wrap{position:relative;flex:1}.orders-search-wrap i{position:absolute;left:12px;top:11px;color:#94a3b8}.orders-filterbar select{height:38px;border:1px solid var(--s-border);border-radius:9px;background:#fff;padding:0 10px;color:#64748b;font-size:10px}.s-table tbody tr{transition:background .15s}.s-table tbody tr:hover{background:#fffaf6}.s-table th{font-size:9px!important;text-transform:uppercase;letter-spacing:.35px;color:#9aa6b6!important}.s-table td{padding-top:13px!important;padding-bottom:13px!important}
    @media(max-width:1100px){.orders-kpis{grid-template-columns:repeat(3,1fr)}}@media(max-width:767px){.orders-hero{align-items:flex-start;flex-direction:column;min-height:190px;padding:18px}.orders-kpis{grid-template-columns:repeat(2,1fr)}.orders-tools{padding:0 12px}.orders-tab{padding:14px 14px 12px;font-size:11px}.orders-filterbar{align-items:stretch;flex-direction:column}.orders-search-wrap{width:100%}.orders-filterbar select{width:100%}.orders-hero h2{font-size:21px}}
</style>
<div class="s-content">
    @php
        $orderCollection = collect($orders);
        $orderStats = [
            'all' => $orderCollection->count(),
            'pending' => $orderCollection->whereIn('status', ['pending','confirmed'])->count(),
            'preparing' => $orderCollection->where('status', 'preparing')->count(),
            'way' => $orderCollection->whereIn('status', ['ready','on_the_way','on_way','accepted','on_way_to_pickup','at_pickup','on_way_to_delivery'])->count(),
            'delivered' => $orderCollection->where('status', 'delivered')->count(),
            'cancelled' => $orderCollection->where('status', 'cancelled')->count(),
        ];
    @endphp
    <section class="orders-hero">
        <div>
            <div class="crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Pedidos</div>
            <h2><i class="las la-box" style="color:#ffbd59"></i> Gestión de Pedidos</h2>
            <p>Revisa y administra todos los pedidos del POS, la app y los envíos de tu negocio.</p>
        </div>
        <div class="orders-hero-actions">
            <a class="primary" href="{{ route('seller.pos') }}"><i class="las la-plus"></i> Crear pedido</a>
            <button type="button" onclick="window.print()"><i class="las la-print"></i> Imprimir</button>
        </div>
    </section>

    <div class="orders-kpis">
        <div class="orders-kpi"><span class="orders-kpi-icon" style="background:#3b82f6"><i class="las la-layer-group"></i></span><b>{{ $orderStats['all'] }}</b><small>Total pedidos</small></div>
        <div class="orders-kpi"><span class="orders-kpi-icon" style="background:#f59e0b"><i class="las la-hourglass-half"></i></span><b style="color:#d97706">{{ $orderStats['pending'] }}</b><small>Pendientes</small></div>
        <div class="orders-kpi"><span class="orders-kpi-icon" style="background:#8b5cf6"><i class="las la-utensils"></i></span><b style="color:#7c3aed">{{ $orderStats['preparing'] }}</b><small>Preparando</small></div>
        <div class="orders-kpi"><span class="orders-kpi-icon" style="background:#22b8cf"><i class="las la-motorcycle"></i></span><b style="color:#0891b2">{{ $orderStats['way'] }}</b><small>Listos / en camino</small></div>
        <div class="orders-kpi"><span class="orders-kpi-icon" style="background:#10b981"><i class="las la-check-circle"></i></span><b style="color:#059669">{{ $orderStats['delivered'] }}</b><small>Entregados</small></div>
        <div class="orders-kpi"><span class="orders-kpi-icon" style="background:#ef4444"><i class="las la-ban"></i></span><b style="color:#dc2626">{{ $orderStats['cancelled'] }}</b><small>Cancelados</small></div>
    </div>

    <div class="s-card" style="overflow-x:auto;padding:0">
        <div style="padding:20px 24px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <h3 class="s-card-title" style="margin:0"><i class="las la-receipt"></i> Todos los Pedidos</h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <span class="s-badge s-badge-green"><i class="las la-cash-register"></i> POS</span>
                <span class="s-badge s-badge-purple"><i class="las la-mobile"></i> App</span>
            </div>
        </div>
        <div class="orders-tools">
            <div class="orders-tabs">
                <button class="orders-tab active" data-filter="all">Todos <span class="s-badge s-badge-amber">{{ $orderStats['all'] }}</span></button>
                <button class="orders-tab" data-filter="pending">Pendientes <span class="s-badge s-badge-amber">{{ $orderStats['pending'] }}</span></button>
                <button class="orders-tab" data-filter="preparing">Preparando <span class="s-badge s-badge-purple">{{ $orderStats['preparing'] }}</span></button>
                <button class="orders-tab" data-filter="way">Listos / en camino <span class="s-badge s-badge-blue">{{ $orderStats['way'] }}</span></button>
                <button class="orders-tab" data-filter="delivered">Entregados <span class="s-badge s-badge-green">{{ $orderStats['delivered'] }}</span></button>
                <button class="orders-tab" data-filter="cancelled">Cancelados <span class="s-badge s-badge-red">{{ $orderStats['cancelled'] }}</span></button>
            </div>
        </div>
        <div class="orders-filterbar">
            <label class="orders-search-wrap"><i class="las la-search"></i><input class="orders-search" id="ordersSearch" type="search" placeholder="Buscar por pedido, cliente, repartidor..."></label>
            <select id="ordersSource"><option value="all">Todos los orígenes</option><option value="POS">POS</option><option value="App">App</option><option value="Envío">Envío</option></select>
            <select id="ordersType"><option value="all">Todos los tipos</option><option value="delivery">Delivery</option><option value="takeaway">Para llevar</option><option value="dine_in">Mesa</option></select>
        </div>
        <div style="padding:0 20px 20px;overflow-x:auto">
        <table class="s-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Origen</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Repartidor</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr class="order-data-row" data-status="{{ $o['status'] }}" data-source="{{ $o['source'] }}" data-type="{{ $o['type'] }}" style="{{ $o['status'] === 'cancelled' ? 'opacity:.45' : '' }}">
                    <td>
                        <b style="font-size:12px;color:var(--s-text)">{{ $o['order_no'] }}</b>
                    </td>
                    <td>
                        @if($o['source'] === 'POS')
                            <span class="s-badge s-badge-green">
                                <i class="las la-cash-register"></i> POS
                            </span>
                        @elseif($o['source'] === 'App')
                            <span class="s-badge s-badge-purple">
                                <i class="las la-mobile"></i> App
                            </span>
                        @else
                            <span class="s-badge" style="background:#ea580c;color:#fff;">
                                <i class="las la-motorcycle"></i> Envío
                            </span>
                        @endif
                    </td>
                    <td style="color:var(--s-text-2)">{{ $o['customer'] ?: '—' }}</td>
                    <td>
                        @php
                            $typeMap = ['delivery'=>'Delivery','takeaway'=>'Llevar','dine_in'=>'Mesa','daz'=>'DAZ','llama'=>'LLAMA','rappi'=>'RAPPI','pedidosya'=>'PEDIDOSYA','lizto_delivery'=>'Lizto'];
                            $typeIcon = ['delivery'=>'la-motorcycle','takeaway'=>'la-shopping-bag','dine_in'=>'la-utensils','daz'=>'la-bolt','llama'=>'la-fire','rappi'=>'la-biking','pedidosya'=>'la-truck','lizto_delivery'=>'la-motorcycle'];
                            $typeColors = ['daz'=>'#e11d48','llama'=>'#f59e0b','rappi'=>'#8b5cf6','pedidosya'=>'#0891b2','lizto_delivery'=>'#16a34a'];
                        @endphp
                        <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--s-text-2)">
                            @if(isset($typeColors[$o['type']]))
                            <span style="font-weight:900;font-size:9px;padding:2px 6px;border-radius:4px;color:#fff;background:{{ $typeColors[$o['type']] }}">{{ $typeMap[$o['type']] ?? $o['type'] }}</span>
                            @else
                            <i class="las {{ $typeIcon[$o['type']] ?? 'la-question' }}" style="font-size:14px"></i>
                            {{ $typeMap[$o['type']] ?? $o['type'] }}
                            @endif
                            @if($o['table'])<span style="color:var(--s-text-3);font-size:11px">· {{ $o['table'] }}</span>@endif
                        </span>
                    </td>
                    <td>
                        <span class="s-badge s-badge-gray">{{ $o['items'] }} items</span>
                    </td>
                    <td>
                        <b style="color:var(--s-accent-dark)">S/ {{ number_format($o['total'], 2) }}</b>
                    </td>
                    <td style="color:var(--s-text-3);font-size:12px">{{ $o['driver'] ?? '—' }}</td>
                    <td>
                        @php $s = $o['status']; @endphp
                        <span class="s-badge {{ $s==='delivered'?'s-badge-green':($s==='cancelled'?'s-badge-red':(in_array($s, ['preparing','on_the_way','on_way','on_way_to_pickup','at_pickup','on_way_to_delivery'])?'s-badge-blue':'s-badge-amber')) }}">
                            @if(in_array($s, ['on_the_way','on_way','on_way_to_pickup','on_way_to_delivery']))<i class="las la-motorcycle"></i>@endif
                            @php
                                $statusMap = [
                                    'pending' => 'Pendiente',
                                    'confirmed' => 'Confirmado',
                                    'preparing' => 'En Cocina',
                                    'ready' => 'Listo',
                                    'on_the_way' => 'En camino',
                                    'on_way' => 'En camino',
                                    'delivered' => 'Entregado',
                                    'cancelled' => 'Cancelado',
                                    'searching_courier' => 'Buscando Repartidor',
                                    'accepted' => 'Asignado',
                                    'on_way_to_pickup' => 'Camino a tienda',
                                    'at_pickup' => 'En tienda',
                                    'on_way_to_delivery' => 'Camino a entrega',
                                ];
                            @endphp
                            {{ $statusMap[$s] ?? $s }}
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--s-text-3);white-space:nowrap">{{ \Carbon\Carbon::parse($o['created_at'])->format('d/m H:i') }}</td>
                    <td>
                        <div style="display:flex;gap:4px">
                            @if($o['is_pos'] || (!($o['is_favor'] ?? false) && $o['source'] === 'Delivery'))
                            <a class="s-btn s-btn-outline s-btn-xs" href="{{ route('seller.orders.show', [$o['is_pos'] ? 'pos' : 'delivery', $o['id']]) }}"><i class="las la-eye"></i> Detalle</a>
                            @else
                            <button class="s-btn s-btn-outline s-btn-xs" onclick="viewDetails(this)" data-order='@json($o)'><i class="las la-eye"></i> Detalle</button>
                            @endif
                            @if($o['is_pos'] && !in_array($o['status'], ['delivered','cancelled']))
                            <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" onclick="cancelOrder({{ $o['id'] }})">
                                <i class="las la-times-circle"></i> Cancelar
                            </button>
                            @endif
                            @if(isset($o['is_favor']) && $o['is_favor'])
                                @if(!in_array($o['status'], ['delivered','cancelled']))
                                <form method="POST" action="{{ route('seller.delivery.request.cancel', $o['id']) }}" style="display:inline" onsubmit="return confirm('¿Está seguro de cancelar este envío?')">
                                    @csrf
                                    <button type="submit" class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)">
                                        <i class="las la-times-circle"></i> Cancelar
                                    </button>
                                </form>
                                @endif
                            @else
                                @if(!$o['is_pos'])
                                    @if($o['status'] === 'pending')
                                    <form method="POST" action="{{ route('seller.orders.status', $o['id']) }}" style="display:inline">
                                        @csrf
                                        <input type="hidden" name="status" value="confirmed">
                                        <input type="hidden" name="is_delivery" value="1">
                                        <button class="s-btn s-btn-primary s-btn-xs">
                                            <i class="las la-check"></i> Confirmar
                                        </button>
                                    </form>
                                    @elseif($o['status'] === 'confirmed')
                                    <form method="POST" action="{{ route('seller.orders.status', $o['id']) }}" style="display:inline">
                                        @csrf
                                        <input type="hidden" name="status" value="preparing">
                                        <input type="hidden" name="is_delivery" value="1">
                                        <button class="s-btn s-btn-xs" style="background:#3b82f6;color:#fff;">
                                            <i class="las la-utensils"></i> Preparar
                                        </button>
                                    </form>
                                    @elseif($o['status'] === 'preparing')
                                    <form method="POST" action="{{ route('seller.orders.status', $o['id']) }}" style="display:inline">
                                        @csrf
                                        <input type="hidden" name="status" value="ready">
                                        <input type="hidden" name="is_delivery" value="1">
                                        <button class="s-btn s-btn-xs" style="background:#f59e0b;color:#fff;">
                                            <i class="las la-box"></i> Listo
                                        </button>
                                    </form>
                                    @elseif($o['status'] === 'ready')
                                    <form method="POST" action="{{ route('seller.orders.status', $o['id']) }}" style="display:inline">
                                        @csrf
                                        <input type="hidden" name="status" value="on_the_way">
                                        <input type="hidden" name="is_delivery" value="1">
                                        <button class="s-btn s-btn-xs" style="background:#8b5cf6;color:#fff;">
                                            <i class="las la-motorcycle"></i> En Camino
                                        </button>
                                    </form>
                                    @elseif($o['status'] === 'on_the_way')
                                    <form method="POST" action="{{ route('seller.orders.status', $o['id']) }}" style="display:inline">
                                        @csrf
                                        <input type="hidden" name="status" value="delivered">
                                        <input type="hidden" name="is_delivery" value="1">
                                        <button class="s-btn s-btn-success s-btn-xs">
                                            <i class="las la-check-double"></i> Entregado
                                        </button>
                                    </form>
                                    @endif
                                @endif
                                @if(!$o['is_pos'] && !in_array($o['status'], ['delivered','cancelled']))
                                <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" onclick="cancelAppOrder({{ $o['id'] }})">
                                    <i class="las la-times-circle"></i> Cancelar
                                </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">
                        <div class="s-empty">
                            <i class="las la-inbox"></i>
                            <p>Sin pedidos aún. Los nuevos pedidos aparecerán aquí.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- CANCEL MODAL -->
<div id="cancel-modal" class="s-modal">
    <div class="s-modal-bg" onclick="this.parentElement.classList.remove('open')"></div>
    <div class="s-modal-box" style="max-width:420px">
        <div class="s-modal-head">
            <h3 class="s-modal-title" style="display:flex;align-items:center;gap:8px">
                <span style="width:34px;height:34px;border-radius:10px;background:var(--s-danger-bg);display:grid;place-items:center;color:var(--s-danger)">
                    <i class="las la-exclamation-triangle"></i>
                </span>
                Cancelar Pedido
            </h3>
            <button class="s-modal-close" onclick="this.closest('.s-modal').classList.remove('open')">✕</button>
        </div>
        <div class="s-modal-body">
            <p style="color:var(--s-text-2);font-size:13px;margin:0 0 16px">¿Por qué se cancela este pedido? Selecciona un motivo.</p>
            <form method="POST" action="" id="cancel-form">
                @csrf
                <div class="s-input-group" style="margin-bottom:16px">
                    <label class="s-input-label">Motivo de cancelación</label>
                    <select class="s-input" name="reason" required>
                        <option value="">Seleccionar motivo...</option>
                        <option>Cliente canceló</option>
                        <option>Error en pedido</option>
                        <option>Falta de insumos</option>
                        <option>No se pudo entregar</option>
                        <option>Otro</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px">
                    <button type="button" class="s-btn s-btn-outline" style="flex:1;justify-content:center" onclick="document.getElementById('cancel-modal').classList.remove('open')">
                        <i class="las la-arrow-left"></i> Volver
                    </button>
                    <button type="submit" class="s-btn s-btn-danger" style="flex:1;justify-content:center">
                        <i class="las la-times"></i> Cancelar Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DETAIL MODAL -->
<div id="detail-modal" class="s-modal">
    <div class="s-modal-bg" onclick="this.parentElement.classList.remove('open')"></div>
    <div class="s-modal-box" style="max-width:550px">
        <div class="s-modal-head">
            <h3 class="s-modal-title" style="display:flex;align-items:center;gap:8px">
                <span style="width:34px;height:34px;border-radius:10px;background:var(--s-accent-bg);display:grid;place-items:center;color:var(--s-accent)">
                    <i class="las la-receipt"></i>
                </span>
                Detalle del Pedido
            </h3>
            <button class="s-modal-close" onclick="this.closest('.s-modal').classList.remove('open')">✕</button>
        </div>
        <div class="s-modal-body" style="max-height: 80vh; overflow-y: auto;">
            <div id="detail-loading" style="text-align:center;padding:20px;display:none;">
                <i class="las la-spinner la-spin" style="font-size:32px;color:var(--s-accent)"></i>
                <p style="margin-top:10px;color:var(--s-text-3)">Cargando detalles...</p>
            </div>
            
            <div id="detail-content">
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--s-border);padding-bottom:12px;margin-bottom:16px;">
                    <div>
                        <h4 id="det-order-no" style="margin:0;font-size:16px;font-weight:900;color:var(--s-text)">#0000</h4>
                        <small id="det-source" class="s-badge s-badge-gray" style="margin-top:4px;display:inline-block;">POS</small>
                    </div>
                    <div style="text-align:right">
                        <div id="det-status" class="s-badge s-badge-amber">Pendiente</div>
                        <div id="det-date" style="font-size:11px;color:var(--s-text-3);margin-top:4px;">00/00 00:00</div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <h5 style="margin:0 0 6px;font-size:12px;text-transform:uppercase;color:var(--s-text-3);letter-spacing:0.5px">Cliente</h5>
                    <p id="det-customer" style="margin:0;color:var(--s-text);font-weight:600;">—</p>
                </div>

                <!-- ITEMS LIST (POS / APP) -->
                <div id="det-items-section" style="margin-bottom:16px;">
                    <h5 style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:var(--s-text-3);letter-spacing:0.5px">Productos</h5>
                    <table class="s-table" style="font-size:13px;">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th style="text-align:center">Cant.</th>
                                <th style="text-align:right">Precio</th>
                                <th style="text-align:right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="det-items-tbody">
                            <!-- Items populated here -->
                        </tbody>
                    </table>
                </div>

                <!-- FAVOR DETAILS -->
                <div id="det-favor-section" style="margin-bottom:16px;display:none;">
                    <h5 style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:var(--s-text-3);letter-spacing:0.5px">Detalles del Envío</h5>
                    <div style="background:var(--s-bg-2);padding:12px;border-radius:8px;font-size:13px;color:var(--s-text-2);">
                        <p style="margin:0 0 8px;"><strong>Descripción:</strong> <span id="det-favor-desc">—</span></p>
                        <p style="margin:0 0 8px;"><strong>Punto de Recojo:</strong> <span id="det-favor-pickup">—</span></p>
                        <p style="margin:0;"><strong>Punto de Entrega:</strong> <span id="det-favor-delivery">—</span></p>
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;background:var(--s-bg-2);padding:12px 16px;border-radius:8px;margin-bottom:16px;">
                    <span style="font-weight:700;color:var(--s-text-2)">Total del Pedido:</span>
                    <span id="det-total" style="font-size:18px;font-weight:900;color:var(--s-accent-dark)">S/ 0.00</span>
                </div>

                <!-- DRIVER INFO -->
                <div style="margin-bottom:16px;border-top:1px solid var(--s-border);padding-top:16px;">
                    <h5 style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:var(--s-text-3);letter-spacing:0.5px">Repartidor Asignado</h5>
                    <div id="det-driver-box" style="display:flex;align-items:center;gap:12px;background:var(--s-bg-2);padding:12px;border-radius:8px;">
                        <span style="width:40px;height:40px;border-radius:50%;background:var(--s-accent-bg);color:var(--s-accent);display:grid;place-items:center;font-size:20px;">
                            <i class="las la-motorcycle"></i>
                        </span>
                        <div>
                            <p id="det-driver-name" style="margin:0;font-weight:600;color:var(--s-text)">Sin asignar</p>
                            <small id="det-driver-contact" style="color:var(--s-text-3);font-size:12px;">—</small>
                        </div>
                    </div>
                </div>

                <!-- PIN DISPLAY -->
                <div id="det-pin-display">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--s-text-2);">
                        <i class="las la-lock" style="font-size:16px;color:#8b5cf6;"></i>
                        PIN de entrega: <span class="pin-code" id="det-pin-code">----</span>
                    </div>
                    <div style="font-size:11px;color:var(--s-text-3);margin-top:4px;">Envía este PIN al cliente. El repartidor lo solicitará al entregar.</div>
                </div>

                <!-- ETA DISPLAY -->
                <div id="det-eta-display">
                    <i class="las la-clock" style="color:#22c55e;"></i>
                    Tiempo estimado: <strong id="det-eta-text" style="color:#22c55e;">--</strong>
                </div>

                <!-- ROUTE INFO -->
                <div id="det-route-info">
                    <i class="las la-route" style="color:var(--s-accent);"></i>
                    Ruta: <span id="det-route-desc">--</span>
                    <strong id="det-route-eta" style="float:right;color:var(--s-accent);"></strong>
                </div>

                <!-- TRACKING MAP -->
                <div id="det-map-section" style="display:none;">
                    <h5 style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:var(--s-text-3);letter-spacing:0.5px">
                        <i class="las la-map-marked-alt" style="color:var(--s-accent);"></i> Ubicación del Repartidor
                    </h5>
                    <div id="det-tracking-map"></div>
                </div>

                <!-- DELIVERY PHOTO -->
                <div id="det-proof-section" style="margin-bottom:16px;border-top:1px solid var(--s-border);padding-top:16px;display:none;">
                    <h5 style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:var(--s-text-3);letter-spacing:0.5px">Foto de Entrega (Comprobante)</h5>
                    <div style="text-align:center;">
                        <a href="#" id="det-proof-link" target="_blank">
                            <img id="det-proof-img" src="" alt="Foto de entrega" style="width:100%;max-height:220px;object-fit:contain;border-radius:8px;border:1px solid var(--s-border);background:#f3f4f6;margin-bottom:6px;">
                        </a>
                        <p id="det-proof-note" style="margin:0;font-size:12px;color:var(--s-text-3);font-style:italic;"></p>
                    </div>
                </div>
                <div id="det-proof-empty" style="margin-bottom:16px;border-top:1px solid var(--s-border);padding-top:16px;display:none;">
                    <h5 style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:var(--s-text-3);letter-spacing:0.5px">Foto de Entrega (Comprobante)</h5>
                    <div style="background:var(--s-bg-2);border:1px dashed var(--s-border);padding:12px;border-radius:8px;color:var(--s-text-3);font-size:13px;">
                        <i class="las la-camera"></i> Aún no hay foto de entrega cargada por el repartidor.
                    </div>
                </div>

                <!-- QUICK ACTIONS IN MODAL -->
                <div id="det-actions-section" style="margin-top:20px;border-top:1px solid var(--s-border);padding-top:16px;display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="s-btn s-btn-ghost" id="modal-cancel-btn" style="display:none;color:var(--s-danger)" onclick="cancelOrderFromDetail()">
                        <i class="las la-times-circle"></i> Cancelar
                    </button>
                    <form method="POST" action="" id="modal-status-form" style="display:none;">
                        @csrf
                        <input type="hidden" name="status" id="modal-status-value">
                        <input type="hidden" name="is_delivery" value="1">
                        <button type="submit" class="s-btn s-btn-primary" id="modal-status-btn">Confirmar</button>
                    </form>
                    <form method="POST" action="" id="modal-favor-status-form" style="display:none;">
                        @csrf
                        <input type="hidden" name="status" id="modal-favor-status-value">
                        <button type="submit" class="s-btn s-btn-primary" id="modal-favor-status-btn">Avanzar Estado</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
@if(gs('google_maps_api'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places,directions" defer></script>
@endif
<script>
var currentDetailOrder = null;
var activeOrderFilter = 'all';

function orderMatchesGroup(status, group) {
    if (group === 'all') return true;
    if (group === 'pending') return ['pending', 'confirmed'].includes(status);
    if (group === 'way') return ['ready', 'on_the_way', 'on_way', 'accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery'].includes(status);
    return status === group;
}

function filterSellerOrders() {
    var query = (document.getElementById('ordersSearch')?.value || '').trim().toLowerCase();
    var source = document.getElementById('ordersSource')?.value || 'all';
    var type = document.getElementById('ordersType')?.value || 'all';
    document.querySelectorAll('.order-data-row').forEach(function(row) {
        var matches = orderMatchesGroup(row.dataset.status, activeOrderFilter)
            && (source === 'all' || row.dataset.source === source)
            && (type === 'all' || row.dataset.type === type)
            && (!query || row.textContent.toLowerCase().includes(query));
        row.style.display = matches ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.orders-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.orders-tab').forEach(function(item) { item.classList.remove('active'); });
            tab.classList.add('active');
            activeOrderFilter = tab.dataset.filter || 'all';
            filterSellerOrders();
        });
    });
    document.getElementById('ordersSearch')?.addEventListener('input', filterSellerOrders);
    document.getElementById('ordersSource')?.addEventListener('change', filterSellerOrders);
    document.getElementById('ordersType')?.addEventListener('change', filterSellerOrders);
});
var orderMap = null;
var orderCourierMarker = null;
var orderPickupMarker = null;
var orderDeliveryMarker = null;
var orderDirectionsRenderer = null;
var orderDirectionsService = null;
var orderPusherChannel = null;
var orderCourierLat = null;
var orderCourierLng = null;

function cleanupOrderTracking() {
    if (orderPusherChannel) { try { orderPusherChannel.unsubscribe(); } catch(e){} orderPusherChannel = null; }
    orderMap = null; orderCourierMarker = null; orderPickupMarker = null; orderDeliveryMarker = null;
    orderDirectionsRenderer = null; orderDirectionsService = null; orderCourierLat = null; orderCourierLng = null;
    document.getElementById('det-tracking-map').innerHTML = '';
    document.getElementById('det-map-section').style.display = 'none';
    document.getElementById('det-pin-display').style.display = 'none';
    document.getElementById('det-eta-display').style.display = 'none';
    document.getElementById('det-route-info').style.display = 'none';
}

function loadOrderTracking(order) {
    cleanupOrderTracking();
    if (order.is_pos || order.status === 'delivered' || order.status === 'cancelled') return;

    var url;
    if (order.is_favor) {
        url = '{{ route("seller.delivery.request.status.show", ":id") }}'.replace(':id', order.id);
    } else if (order.source === 'App' && order.driver) {
        url = '{{ route("seller.delivery.order.status.show", ":id") }}'.replace(':id', order.id);
    } else {
        return;
    }

    fetch(url, { headers: { 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status !== 'success') return;

        var fav = data.favor || data.order;
        var courier = data.courier;
        if (!fav || !courier || !courier.current_lat || !courier.current_lng) return;

        orderCourierLat = courier.current_lat;
        orderCourierLng = courier.current_lng;

        document.getElementById('det-map-section').style.display = 'block';

        if (fav.pin_code) {
            document.getElementById('det-pin-code').textContent = fav.pin_code;
            document.getElementById('det-pin-display').style.display = 'block';
        }

        if (fav.estimated_minutes) {
            document.getElementById('det-eta-text').textContent = '~' + fav.estimated_minutes + ' min';
            document.getElementById('det-eta-display').style.display = 'block';
        }

        var pickupLat = parseFloat(fav.pickup_lat);
        var pickupLng = parseFloat(fav.pickup_lng);
        var deliveryLat = parseFloat(fav.delivery_lat);
        var deliveryLng = parseFloat(fav.delivery_lng);

        if (!pickupLat || !pickupLng || !deliveryLat || !deliveryLng) return;

        if (!window.google || !google.maps) {
            setTimeout(function() { initOrderMap(fav, courier); }, 500);
            return;
        }
        initOrderMap(fav, courier);
        connectOrderPusher(order);
    })
    .catch(function(e) { console.error('Tracking error:', e); });
}

function initOrderMap(fav, courier) {
    var mapEl = document.getElementById('det-tracking-map');
    if (!mapEl || !window.google) return;

    orderDirectionsService = new google.maps.DirectionsService();

    orderMap = new google.maps.Map(mapEl, {
        center: { lat: courier.current_lat, lng: courier.current_lng },
        zoom: 14,
        mapTypeControl: false,
        streetViewControl: false,
    });

    orderPickupMarker = new google.maps.Marker({
        position: { lat: parseFloat(fav.pickup_lat), lng: parseFloat(fav.pickup_lng) },
        map: orderMap,
        label: { text: 'R', color: '#fff', fontWeight: 'bold', fontSize: '12px' },
        icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8, fillColor: '#22c55e', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 }
    });

    orderDeliveryMarker = new google.maps.Marker({
        position: { lat: parseFloat(fav.delivery_lat), lng: parseFloat(fav.delivery_lng) },
        map: orderMap,
        label: { text: 'E', color: '#fff', fontWeight: 'bold', fontSize: '12px' },
        icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8, fillColor: '#ef4444', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 }
    });

    addOrderCourierMarker(courier.current_lat, courier.current_lng);

    var bounds = new google.maps.LatLngBounds();
    bounds.extend({ lat: parseFloat(fav.pickup_lat), lng: parseFloat(fav.pickup_lng) });
    bounds.extend({ lat: parseFloat(fav.delivery_lat), lng: parseFloat(fav.delivery_lng) });
    bounds.extend({ lat: courier.current_lat, lng: courier.current_lng });
    orderMap.fitBounds(bounds);
    if (orderMap.getZoom() > 16) orderMap.setZoom(16);

    drawOrderRoute(fav);
}

function addOrderCourierMarker(lat, lng) {
    if (!orderMap) return;
    if (orderCourierMarker) {
        orderCourierMarker.setPosition({ lat: lat, lng: lng });
    } else {
        orderCourierMarker = new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: orderMap,
            title: 'Repartidor',
            icon: {
                url: '{{ asset("assets/images/delivery_man_marker.png") }}',
                scaledSize: new google.maps.Size(42, 42),
                anchor: new google.maps.Point(21, 21)
            }
        });
    }
}

function drawOrderRoute(fav) {
    if (!orderDirectionsService || !orderMap) return;
    if (orderDirectionsRenderer) { orderDirectionsRenderer.setMap(null); }

    var origin = { lat: parseFloat(fav.pickup_lat), lng: parseFloat(fav.pickup_lng) };
    var destination = { lat: parseFloat(fav.delivery_lat), lng: parseFloat(fav.delivery_lng) };

    orderDirectionsService.route({ origin: origin, destination: destination, travelMode: google.maps.TravelMode.DRIVING }, function(result, status) {
        if (status === 'OK') {
            orderDirectionsRenderer = new google.maps.DirectionsRenderer({
                map: orderMap, directions: result, suppressMarkers: true,
                polylineOptions: { strokeColor: '#22c55e', strokeWeight: 3, strokeOpacity: 0.8 }
            });
            if (result.routes[0] && result.routes[0].legs[0]) {
                var leg = result.routes[0].legs[0];
                document.getElementById('det-route-desc').textContent = leg.distance.text;
                document.getElementById('det-route-eta').textContent = leg.duration.text;
                document.getElementById('det-route-info').style.display = 'block';
            }
        }
    });
}

function connectOrderPusher(order) {
    var pusherKey = '{{ $pusherConfig["key"] ?? "" }}';
    if (!pusherKey || !window.Pusher) return;

    var pusher = new Pusher(pusherKey, {
        wsHost: '{{ $pusherConfig["host"] ?? "" }}',
        wsPort: {{ $pusherConfig["port"] ?? 6001 }},
        wssPort: {{ $pusherConfig["port"] ?? 6001 }},
        forceTLS: {{ ($pusherConfig["scheme"] ?? 'http') === 'https' ? 'true' : 'false' }},
        enabledTransports: ['ws', 'wss'],
        authorizer: function(channel) {
            return {
                authorize: function(socketId, callback) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route("seller.broadcasting.auth") }}', true);
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) callback(null, JSON.parse(xhr.responseText));
                            else callback(new Error('Auth failed'), null);
                        }
                    };
                    xhr.send(JSON.stringify({ socket_id: socketId, channel_name: channel.name }));
                }
            };
        }
    });

    var channelName = order.is_favor ? 'private-favor.' + order.id : 'private-tracking.' + order.id;
    orderPusherChannel = pusher.subscribe(channelName);

    orderPusherChannel.bind('location_update', function(data) {
        if (data.latitude && data.longitude) {
            orderCourierLat = data.latitude;
            orderCourierLng = data.longitude;
            addOrderCourierMarker(data.latitude, data.longitude);
        }
    });

    orderPusherChannel.bind('favor_status_updated', function(data) {
        if (data.status) {
            var statusMap = {
                'pending': 'Pendiente', 'confirmed': 'Confirmado', 'preparing': 'En Cocina', 'ready': 'Listo',
                'on_the_way': 'En camino', 'on_way': 'En camino', 'delivered': 'Entregado', 'cancelled': 'Cancelado',
                'searching_courier': 'Buscando Repartidor', 'accepted': 'Asignado', 'on_way_to_pickup': 'Camino a tienda',
                'at_pickup': 'En tienda', 'on_way_to_delivery': 'Camino a entrega'
            };
            document.getElementById('det-status').innerText = statusMap[data.status] || data.status;
        }
    });
}

function cancelOrder(id) {
    var form = document.getElementById('cancel-form');
    form.action = '{{ route("seller.orders.cancel", ":id") }}'.replace(':id', id);
    
    var existingIsDelivery = form.querySelector('input[name="is_delivery"]');
    if (existingIsDelivery) existingIsDelivery.remove();
    var existingStatus = form.querySelector('input[name="status"]');
    if (existingStatus) existingStatus.remove();

    document.getElementById('cancel-modal').classList.add('open');
}

function cancelAppOrder(id) {
    var form = document.getElementById('cancel-form');
    form.action = '{{ route("seller.orders.status", ":id") }}'.replace(':id', id);
    
    var existingIsDelivery = form.querySelector('input[name="is_delivery"]');
    if (existingIsDelivery) existingIsDelivery.remove();
    var existingStatus = form.querySelector('input[name="status"]');
    if (existingStatus) existingStatus.remove();

    var isDeliveryInput = document.createElement('input');
    isDeliveryInput.type = 'hidden';
    isDeliveryInput.name = 'is_delivery';
    isDeliveryInput.value = '1';
    form.appendChild(isDeliveryInput);

    var statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = 'cancelled';
    form.appendChild(statusInput);

    document.getElementById('cancel-modal').classList.add('open');
}

function cancelOrderFromDetail() {
    if (!currentDetailOrder) return;

    document.getElementById('detail-modal').classList.remove('open');

    if (currentDetailOrder.is_favor) {
        var form = document.getElementById('cancel-form');
        form.action = '{{ route("seller.delivery.request.cancel", ":id") }}'.replace(':id', currentDetailOrder.id);

        var existingIsDelivery = form.querySelector('input[name="is_delivery"]');
        if (existingIsDelivery) existingIsDelivery.remove();
        var existingStatus = form.querySelector('input[name="status"]');
        if (existingStatus) existingStatus.remove();

        document.getElementById('cancel-modal').classList.add('open');
        return;
    }

    if (currentDetailOrder.is_pos) {
        cancelOrder(currentDetailOrder.id);
        return;
    }

    cancelAppOrder(currentDetailOrder.id);
}

function viewDetails(btn) {
    var order = JSON.parse(btn.getAttribute('data-order'));
    currentDetailOrder = order;
    
    document.getElementById('det-order-no').innerText = order.order_no;
    
    // Set source badge
    var srcBadge = document.getElementById('det-source');
    srcBadge.innerText = order.source;
    srcBadge.className = 's-badge ' + (order.source === 'POS' ? 's-badge-green' : (order.source === 'App' ? 's-badge-purple' : 's-badge-orange'));
    if (order.source === 'Envío') srcBadge.style.background = '#ea580c';
    else srcBadge.style.background = '';

    // Set status badge
    var statusMap = {
        'pending': 'Pendiente', 'confirmed': 'Confirmado', 'preparing': 'En Cocina', 'ready': 'Listo',
        'on_the_way': 'En camino', 'on_way': 'En camino', 'delivered': 'Entregado', 'cancelled': 'Cancelado',
        'searching_courier': 'Buscando Repartidor', 'accepted': 'Asignado', 'on_way_to_pickup': 'Camino a tienda',
        'at_pickup': 'En tienda', 'on_way_to_delivery': 'Camino a entrega'
    };
    var statBadge = document.getElementById('det-status');
    statBadge.innerText = statusMap[order.status] || order.status;
    var s = order.status;
    statBadge.className = 's-badge ' + (s === 'delivered' ? 's-badge-green' : (s === 'cancelled' ? 's-badge-red' : (['preparing','on_the_way','on_way','on_way_to_pickup','at_pickup','on_way_to_delivery'].includes(s) ? 's-badge-blue' : 's-badge-amber')));

    // Date and customer
    var dateObj = new Date(order.created_at);
    document.getElementById('det-date').innerText = dateObj.toLocaleDateString() + ' ' + dateObj.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    document.getElementById('det-customer').innerText = order.customer || '—';
    document.getElementById('det-total').innerText = 'S/ ' + parseFloat(order.total).toFixed(2);

    // Toggle items vs favor sections
    var itemsSec = document.getElementById('det-items-section');
    var favorSec = document.getElementById('det-favor-section');
    var itemsTbody = document.getElementById('det-items-tbody');

    if (order.is_favor) {
        itemsSec.style.display = 'none';
        favorSec.style.display = 'block';
        document.getElementById('det-favor-desc').innerText = order.description || '—';
        document.getElementById('det-favor-pickup').innerText = order.pickup_address || '—';
        document.getElementById('det-favor-delivery').innerText = order.delivery_address || '—';
    } else {
        itemsSec.style.display = 'block';
        favorSec.style.display = 'none';
        itemsTbody.innerHTML = '';
        if (order.items_list && order.items_list.length) {
            order.items_list.forEach(function(item) {
                var row = document.createElement('tr');
                var priceVal = parseFloat(item.price) || 0;
                var qtyVal = parseInt(item.qty) || 0;
                var subtotalVal = qtyVal * priceVal;
                row.innerHTML = '<td>' + item.name + '</td>' +
                                '<td style="text-align:center">' + qtyVal + '</td>' +
                                '<td style="text-align:right">S/ ' + priceVal.toFixed(2) + '</td>' +
                                '<td style="text-align:right">S/ ' + subtotalVal.toFixed(2) + '</td>';
                itemsTbody.appendChild(row);
            });
        } else {
            itemsTbody.innerHTML = '<tr><td colspan="4" style="text-align:center">No hay productos</td></tr>';
        }
    }

    // Driver Info
    var drBox = document.getElementById('det-driver-box');
    var drName = document.getElementById('det-driver-name');
    var drContact = document.getElementById('det-driver-contact');
    if (order.driver) {
        drName.innerText = order.driver;
        drContact.innerText = 'Telf: ' + (order.driver_phone || '—') + ' | ' + (order.driver_email || '—');
    } else {
        drName.innerText = 'Sin asignar';
        drContact.innerText = 'Esperando aceptación de un repartidor';
    }

    // Delivery Proof Section
    var proofSec = document.getElementById('det-proof-section');
    var proofEmpty = document.getElementById('det-proof-empty');
    if (order.delivery_proof) {
        proofSec.style.display = 'block';
        proofEmpty.style.display = 'none';
        document.getElementById('det-proof-img').src = order.delivery_proof;
        document.getElementById('det-proof-link').href = order.delivery_proof;
        document.getElementById('det-proof-note').innerText = order.delivery_proof_note ? '"' + order.delivery_proof_note + '"' : '';
    } else {
        proofSec.style.display = 'none';
        proofEmpty.style.display = order.is_pos ? 'none' : 'block';
    }

    // Actions section
    var statusForm = document.getElementById('modal-status-form');
    var favorStatusForm = document.getElementById('modal-favor-status-form');
    var cancelBtn = document.getElementById('modal-cancel-btn');
    statusForm.style.display = 'none';
    favorStatusForm.style.display = 'none';
    cancelBtn.style.display = ['delivered', 'cancelled'].includes(order.status) ? 'none' : 'inline-flex';

    if (order.is_pos && !['delivered', 'cancelled'].includes(order.status)) {
        var posTransitions = {
            'pending': { status: 'preparing', label: 'Preparar pedido', color: '#3b82f6' },
            'preparing': { status: 'ready', label: 'Pedido listo', color: '#f59e0b' },
            'ready': { status: 'delivered', label: 'Marcar entregado', color: 'var(--s-success)' }
        };
        var posTransition = posTransitions[order.status];
        if (posTransition) {
            statusForm.action = '{{ route("seller.orders.status", ":id") }}'.replace(':id', order.id);
            document.getElementById('modal-status-value').value = posTransition.status;
            var posBtn = document.getElementById('modal-status-btn');
            posBtn.innerText = posTransition.label;
            posBtn.style.background = posTransition.color;
            posBtn.style.borderColor = posTransition.color;
            statusForm.querySelector('input[name="is_delivery"]').disabled = true;
            statusForm.style.display = 'inline-block';
        }
    } else if (!order.is_pos && !['delivered', 'cancelled'].includes(order.status)) {
        statusForm.querySelector('input[name="is_delivery"]').disabled = false;
        if (order.is_favor) {
            var favorTransitions = {
                'searching_courier': { status: 'accepted', label: 'Asignar Repartidor' },
                'accepted': { status: 'on_way_to_pickup', label: 'Iniciar ruta al pickup' },
                'on_way_to_pickup': { status: 'at_pickup', label: 'Llegado a tienda' },
                'at_pickup': { status: 'on_way_to_delivery', label: 'Despachar Envío' },
                'on_way_to_delivery': { status: 'delivered', label: 'Confirmar Entrega' }
            };
            var transition = favorTransitions[order.status];
            if (transition) {
                favorStatusForm.action = '{{ route("seller.delivery.request.status", ":id") }}'.replace(':id', order.id);
                document.getElementById('modal-favor-status-value').value = transition.status;
                var btn = document.getElementById('modal-favor-status-btn');
                btn.innerText = transition.label;
                favorStatusForm.style.display = 'inline-block';
            }
        } else {
            var appTransitions = {
                'pending': { status: 'confirmed', label: 'Confirmar Pedido', color: 'var(--s-primary)' },
                'confirmed': { status: 'preparing', label: 'Preparar en cocina', color: '#3b82f6' },
                'preparing': { status: 'ready', label: 'Pedido Listo', color: '#f59e0b' },
                'ready': { status: 'on_the_way', label: 'Despachar (En Camino)', color: '#8b5cf6' },
                'on_the_way': { status: 'delivered', label: 'Entregado', color: 'var(--s-success)' }
            };
            var transition = appTransitions[order.status];
            if (transition) {
                statusForm.action = '{{ route("seller.orders.status", ":id") }}'.replace(':id', order.id);
                document.getElementById('modal-status-value').value = transition.status;
                var btn = document.getElementById('modal-status-btn');
                btn.innerText = transition.label;
                btn.style.background = transition.color;
                btn.style.borderColor = transition.color;
                statusForm.style.display = 'inline-block';
            }
        }
    } else {
        statusForm.querySelector('input[name="is_delivery"]').disabled = false;
    }

    // Load tracking for delivery/favor orders
    loadOrderTracking(order);

    document.getElementById('detail-modal').classList.add('open');
}
</script>
@endpush
@endsection
