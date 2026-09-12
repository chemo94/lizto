@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-utensils"></i></span> Pantalla de Cocina / Comandas
@endsection

@section('topbar-actions')
<div class="kitchen-topbar-wrapper">
    <div style="display:flex;gap:4px;background:var(--s-bg-light);border-radius:8px;padding:3px;border:1px solid var(--s-border);">
        <button onclick="filterStation('all')" class="s-btn s-btn-sm station-btn station-active" data-station="all">
            <i class="las la-layer-group"></i> Todos
        </button>
        <button onclick="filterStation('kitchen')" class="s-btn s-btn-sm station-btn" data-station="kitchen">
            <i class="las la-utensils"></i> Cocina
        </button>
        <button onclick="filterStation('bar')" class="s-btn s-btn-sm station-btn" data-station="bar">
            <i class="las la-cocktail"></i> Barra
        </button>
    </div>
    <button id="enable-sound-btn" onclick="enableKitchenSound()" class="s-btn s-btn-outline s-btn-sm" style="font-weight: 800; color: #f59e0b; border-color: #f59e0b; background: rgba(245, 158, 11, 0.05); height: 36px; border-radius: 8px; display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 0 12px; font-size: 13px;">
        <i class="las la-volume-mute" style="font-size: 18px;"></i> Sonido
    </button>
    <span id="kitchen-clock" style="font-weight: 700; color: var(--s-text-secondary); background: var(--s-bg-light); padding: 6px 12px; border-radius: 8px; border: 1px solid var(--s-border);"></span>
    <span class="s-badge s-badge-yellow" id="pending-count" style="font-size:12px; padding: 6px 12px; font-weight: 700;">
        {{ $orders->sum(fn($o) => $o->items->where('status','pending')->count()) }} Pendientes
    </span>
</div>
@endsection

@section('seller-content')
<style>
.kitchen-head{min-height:118px;margin-bottom:16px;padding:20px 22px;border-radius:14px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:18px;background:linear-gradient(100deg,#2d1d45,#68439a 55%,#9670c2);box-shadow:var(--s-shadow-sm)}.kitchen-head .crumb{font-size:9px;color:rgba(255,255,255,.68);margin-bottom:7px}.kitchen-head h2{font:800 22px 'Plus Jakarta Sans','Inter',sans-serif;margin:0 0 3px;letter-spacing:-.45px}.kitchen-head p{font-size:10px;color:rgba(255,255,255,.7);margin:0}.kitchen-summary{display:flex;gap:8px}.kitchen-summary div{min-width:84px;padding:10px 12px;text-align:center;border:1px solid rgba(255,255,255,.18);border-radius:10px;background:rgba(255,255,255,.1);backdrop-filter:blur(8px)}.kitchen-summary b{display:block;font-size:17px}.kitchen-summary small{font-size:8px;color:rgba(255,255,255,.72)}#kitchen-grid{gap:14px!important}.task-card{border-radius:14px!important;box-shadow:var(--s-shadow-sm)!important}.task-card:hover{transform:translateY(-2px);box-shadow:var(--s-shadow)!important}.task-card--preparing{border-top-color:#f97316!important}.station-btn.station-active{background:#f97316!important;color:#fff!important}.kitchen-topbar-wrapper{display:flex;gap:8px;align-items:center}@media(max-width:767px){.kitchen-head{align-items:flex-start;flex-direction:column}.kitchen-summary{width:100%;overflow:auto}.kitchen-summary div{flex:1}.kitchen-topbar-wrapper{overflow:auto;max-width:72vw}}
</style>
<div class="s-content">
    @php
    $statusColors = ['confirmed' => 'var(--s-warning)', 'preparing' => 'var(--s-primary)', 'ready' => 'var(--s-success)'];
    $statusBadges = ['confirmed' => 's-badge-yellow', 'preparing' => 's-badge-blue', 'ready' => 's-badge-green'];
    $statusLabels = ['confirmed' => 'Nueva', 'preparing' => 'Preparando', 'ready' => 'Listo'];
    $typeIcons = ['delivery'=>'motorcycle','takeaway'=>'shopping-bag','dine_in'=>'utensils','daz'=>'bolt','llama'=>'fire','rappi'=>'biking','pedidosya'=>'truck','lizto_delivery'=>'motorcycle','app_delivery'=>'mobile-alt'];
    $typeLabels = ['dine_in'=>'Para Servir','takeaway'=>'Para Llevar','delivery'=>'Delivery','daz'=>'DAZ','llama'=>'LLAMA','rappi'=>'RAPPI','pedidosya'=>'PEDIDOSYA','lizto_delivery'=>'LIZTO','app_delivery'=>'App Delivery'];
    $kitchenPending = $orders->where('status', 'confirmed')->count();
    $kitchenPreparing = $orders->where('status', 'preparing')->count();
    $kitchenReady = $orders->where('status', 'ready')->count();
    @endphp

    <section class="kitchen-head">
        <div><div class="crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Operaciones &nbsp;/&nbsp; Cocina</div><h2><i class="las la-fire"></i> Monitor de Cocina</h2><p>Prioriza comandas, controla tiempos y despacha pedidos en tiempo real.</p></div>
        <div class="kitchen-summary"><div><b>{{ $orders->count() }}</b><small>Comandas</small></div><div><b>{{ $kitchenPending }}</b><small>Nuevas</small></div><div><b>{{ $kitchenPreparing }}</b><small>Preparando</small></div><div><b>{{ $kitchenReady }}</b><small>Listas</small></div></div>
    </section>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 24px;" id="kitchen-grid">
        @forelse($orders as $order)
        @php
        $totalItems = $order->items->count();
        $doneItems = $order->items->where('status', 'done')->count();
        $pct = $totalItems > 0 ? round(($doneItems / $totalItems) * 100) : 0;
        @endphp
        @php $orderSource = $order->source ?? 'pos'; @endphp
        @php
            $orderStation = 'kitchen';
            foreach ($order->items as $oi) {
                $prod = $oi->product;
                if ($prod && method_exists($prod, 'isBarProduct') && $prod->isBarProduct()) {
                    $orderStation = 'bar';
                    break;
                }
                if ($prod && $prod->category && stripos($prod->category->name ?? '', 'bar') !== false) {
                    $orderStation = 'bar';
                    break;
                }
            }
        @endphp
        <div class="task-card task-card--{{ $order->status }}" id="order-{{ $order->id }}" data-order="{{ $order->id }}" data-source="{{ $orderSource }}" data-station="{{ $orderStation }}"
             style="background: var(--s-bg-card); border: 1px solid var(--s-border); border-top: 4px solid {{ $statusColors[$order->status] }}; border-radius: 16px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s;">
            
            <!-- Header -->
            <div style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--s-border); background: var(--s-bg-light);">
                <div>
                    <div style="font-size: 18px; font-weight: 850; color: var(--s-text-primary); letter-spacing: -0.3px;">
                        Pedido #{{ $order->order_no }}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; font-size: 11px; font-weight: 600; color: var(--s-text-muted);">
                        <span><i class="las la-{{ $typeIcons[$order->order_type] }}"></i> {{ $typeLabels[$order->order_type] }}</span>
                        @if($order->table)<span><i class="las la-circle-notch"></i> {{ $order->table->name }}</span>@endif
                        @if($order->customer_name)<span><i class="las la-user"></i> {{ $order->customer_name }}</span>@endif
                    </div>
                </div>
                
                <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                    <div class="kitchen-timer" data-created="{{ $order->created_at->timestamp }}" 
                         style="font-size:16px;font-weight:900;font-variant-numeric:tabular-nums;min-width:50px;text-align:right;"
                         data-status="{{ $order->status }}">
                        0:00
                    </div>
                    <span class="s-badge {{ $statusBadges[$order->status] }}" style="font-size: 10px; font-weight: 700; padding: 2px 6px;">
                        {{ $statusLabels[$order->status] }}
                    </span>
                </div>
            </div>

            <!-- Progress Bar -->
            <div style="padding: 10px 20px 0;">
                <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: var(--s-text-secondary); margin-bottom: 4px;">
                    <span>Progreso del plato</span>
                    <span>{{ $doneItems }}/{{ $totalItems }}</span>
                </div>
                <div style="height: 6px; background: var(--s-bg-light); border-radius: 3px; overflow: hidden; border: 1px solid var(--s-border);">
                    <div style="height: 100%; width: {{ $pct }}%; background: {{ $pct == 100 ? 'var(--s-success)' : 'var(--s-primary)' }}; transition: width 0.3s ease;"></div>
                </div>
            </div>

            <!-- Kitchen Notes -->
            @if($order->kitchen_notes)
            <div style="margin: 12px 20px 0; background: var(--s-primary-light); color: var(--s-primary); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: flex; gap: 6px; align-items: flex-start;">
                <span>💡</span> <span>{{ $order->kitchen_notes }}</span>
            </div>
            @endif

            <!-- Task Items -->
            <div style="padding: 12px 20px; flex: 1; display: flex; flex-direction: column; gap: 8px;">
                @foreach($order->items as $item)
                @php $isDone = $item->status === 'done'; @endphp
                <div class="task-item" style="display: flex; align-items: flex-start; gap: 12px; padding: 8px 0; border-bottom: 1px dashed var(--s-border); opacity: {{ $isDone ? '0.5' : '1' }};" data-item="{{ $item->id }}" data-order="{{ $order->id }}">
                    <button class="task-check {{ $isDone ? 'checked' : '' }}" onclick="toggleItem({{ $order->id }}, {{ $item->id }}, this, '{{ $orderSource }}')" 
                            style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid {{ $isDone ? 'var(--s-success)' : 'var(--s-border)' }}; background: {{ $isDone ? 'var(--s-success)' : 'transparent' }}; display: flex; align-items: center; justify-content: center; cursor: pointer; color: {{ $isDone ? '#fff' : 'transparent' }}; transition: all 0.2s;" title="Marcar plato">
                        <i class="las la-check" style="font-size: 14px; font-weight: 800;"></i>
                    </button>
                    
                    <div style="flex: 1;">
                        <div style="font-size: 14px; font-weight: 700; color: var(--s-text-primary); text-decoration: {{ $isDone ? 'line-through' : 'none' }};">
                            <span style="color: var(--s-primary); margin-right: 4px;">{{ $item->quantity }}x</span> {{ $item->product_name }}
                            @if(isset($item->is_takeaway) && $item->is_takeaway)
                            <span style="font-size: 10px; background: #f97316; color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: 800; margin-left: 6px; display: inline-flex; align-items: center; gap: 3px;">
                                <i class="las la-shopping-bag"></i> LLEVAR
                            </span>
                            @endif
                        </div>
                        @if($item->notes)
                        <div style="font-size: 11px; color: var(--s-warning); font-weight: 600; margin-top: 2px;">
                            Nota: {{ $item->notes }}
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Actions -->
            <div style="padding: 12px 20px; background: var(--s-bg-light); border-top: 1px solid var(--s-border); display: flex; gap: 8px;">
                @if($order->status === 'confirmed')
                <button class="s-btn s-btn-primary s-btn-sm" style="flex: 1; justify-content: center;" onclick="updateStatus({{ $order->id }}, 'preparing', '{{ $orderSource }}')">
                    <i class="las la-fire" style="font-size: 16px;"></i> Iniciar Preparación
                </button>
                @elseif($order->status === 'preparing')
                <button class="s-btn s-btn-success s-btn-sm" style="flex: 1; justify-content: center;" onclick="updateStatus({{ $order->id }}, 'ready', '{{ $orderSource }}')">
                    <i class="las la-check-circle" style="font-size: 16px;"></i> Terminado / Listo
                </button>
                @endif
                @if($order->status === 'ready' && $orderSource === 'pos')
                <button class="s-btn s-btn-ghost s-btn-sm" style="flex: 1; justify-content: center; background: var(--s-text-primary); color: #fff;" onclick="updateStatus({{ $order->id }}, 'delivered', '{{ $orderSource }}')">
                    <i class="las la-check-double" style="font-size: 16px;"></i> Entregado a Mesa
                </button>
                @endif
            </div>
        </div>
        @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px; color: var(--s-text-muted);">
            <i class="las la-check-circle" style="font-size: 64px; color: var(--s-success); display: block; margin-bottom: 16px;"></i>
            <h3 style="color: var(--s-text-primary); font-weight: 700; margin-bottom: 6px;">¡Cocina al día!</h3>
            <p style="font-size: 14px;">No hay comandas pendientes de preparación en este momento.</p>
        </div>
        @endforelse
    </div>
</div>

@push('script')
<script>
var shownOrders = {};
document.querySelectorAll('.task-card').forEach(function(c){
    var id = c.getAttribute('data-order');
    var src = c.getAttribute('data-source') || 'pos';
    if(id) shownOrders[src + '-' + id] = true;
});

function updateTime() {
    var now = new Date();
    document.getElementById('kitchen-clock').textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
}
updateTime();
setInterval(updateTime, 1000);

// Auto-refresco cocina
setInterval(refreshKitchen, 6000);

function refreshKitchen() {
    fetch('{{ route('seller.pos.orders.active') }}')
    .then(function(r) { return r.json(); })
    .then(function(orders) {
        var grid = document.getElementById('kitchen-grid');
        var newCount = 0, totalPending = 0;
        
        var statusColors = {confirmed: 'var(--s-warning)', preparing: 'var(--s-primary)', ready: 'var(--s-success)'};
        var statusBadges = {confirmed: 's-badge-yellow', preparing: 's-badge-blue', ready: 's-badge-green'};
        var statusLabels = {confirmed: 'Nueva', preparing: 'Preparando', ready: 'Listo'};
        var typeIcons = {delivery: 'motorcycle', takeaway: 'shopping-bag', dine_in: 'utensils', daz: 'bolt', llama: 'fire', rappi: 'biking', pedidosya: 'truck', lizto_delivery: 'motorcycle', app_delivery: 'mobile-alt'};
        var typeLabels = {dine_in: 'Para Servir', takeaway: 'Para Llevar', delivery: 'Delivery', daz: 'DAZ', llama: 'LLAMA', rappi: 'RAPPI', pedidosya: 'PEDIDOSYA', lizto_delivery: 'LIZTO', app_delivery: 'App Delivery'};

        if (!orders.length) {
            grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px; color: var(--s-text-muted);">' +
                '<i class="las la-check-circle" style="font-size: 64px; color: var(--s-success); display: block; margin-bottom: 16px;"></i>' +
                '<h3 style="color: var(--s-text-primary); font-weight: 700; margin-bottom: 6px;">¡Cocina al día!</h3>' +
                '<p style="font-size: 14px;">No hay comandas pendientes de preparación en este momento.</p>' +
                '</div>';
            shownOrders = {};
            updatePendingCount(0);
            return;
        }

        var html = '';
        orders.forEach(function(o) {
            var orderKey = (o.source || 'pos') + '-' + o.id;
            var isNew = !shownOrders[orderKey];
            if (isNew) {
                newCount++;
                shownOrders[orderKey] = true;
            }
            var done = o.items.filter(function(i){ return i.status === 'done'; }).length;
            var total = o.items.length;
            var pct = total > 0 ? Math.round(done / total * 100) : 0;
            totalPending += (total - done);

            html += '<div class="task-card task-card--' + o.status + '" id="order-' + o.id + '" data-order="' + o.id + '" data-source="' + (o.source || 'pos') + '" data-station="' + (o.station || 'kitchen') + '" style="background: var(--s-bg-card); border: 1px solid var(--s-border); border-top: 4px solid ' + statusColors[o.status] + '; border-radius: 16px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s;">' +
                '<div style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--s-border); background: var(--s-bg-light);">' +
                    '<div>' +
                        '<div style="font-size: 18px; font-weight: 850; color: var(--s-text-primary); letter-spacing: -0.3px;">Pedido #' + o.order_no + '</div>' +
                        '<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; font-size: 11px; font-weight: 600; color: var(--s-text-muted);">' +
                            '<span><i class="las la-' + typeIcons[o.order_type] + '"></i> ' + typeLabels[o.order_type] + '</span>' +
                            (o.table ? '<span><i class="las la-circle-notch"></i> ' + o.table + '</span>' : '') +
                            (o.customer_name ? '<span><i class="las la-user"></i> ' + o.customer_name + '</span>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">' +
                        '<span style="font-size: 11px; color: var(--s-text-muted); font-weight: 600;">' + o.created_at + '</span>' +
                        '<span class="s-badge ' + statusBadges[o.status] + '" style="font-size: 10px; font-weight: 700; padding: 2px 6px;">' + statusLabels[o.status] + '</span>' +
                    '</div>' +
                '</div>' +
                '<div style="padding: 10px 20px 0;">' +
                    '<div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: var(--s-text-secondary); margin-bottom: 4px;">' +
                        '<span>Progreso del plato</span>' +
                        '<span>' + done + '/' + total + '</span>' +
                    '</div>' +
                    '<div style="height: 6px; background: var(--s-bg-light); border-radius: 3px; overflow: hidden; border: 1px solid var(--s-border);">' +
                        '<div style="height: 100%; width: ' + pct + '%; background: ' + (pct === 100 ? 'var(--s-success)' : 'var(--s-primary)') + '; transition: width 0.3s ease;"></div>' +
                    '</div>' +
                '</div>' +
                (o.kitchen_notes ? '<div style="margin: 12px 20px 0; background: var(--s-primary-light); color: var(--s-primary); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: flex; gap: 6px; align-items: flex-start;"><span>💡</span> <span>' + o.kitchen_notes + '</span></div>' : '') +
                '<div style="padding: 12px 20px; flex: 1; display: flex; flex-direction: column; gap: 8px;">' +
                    o.items.map(function(i) {
                        var isDone = (i.status === 'done');
                        return '<div class="task-item" style="display: flex; align-items: flex-start; gap: 12px; padding: 8px 0; border-bottom: 1px dashed var(--s-border); opacity: ' + (isDone ? '0.5' : '1') + ';" data-item="' + i.id + '" data-order="' + o.id + '">' +
                            '<button class="task-check ' + (isDone ? 'checked' : '') + '" onclick="toggleItem(' + o.id + ',' + i.id + ',this,\'' + (o.source || 'pos') + '\')" style="width: 24px; height: 24px; border-radius: 50%; border: 2px solid ' + (isDone ? 'var(--s-success)' : 'var(--s-border)') + '; background: ' + (isDone ? 'var(--s-success)' : 'transparent') + '; display: flex; align-items: center; justify-content: center; cursor: pointer; color: ' + (isDone ? '#fff' : 'transparent') + '; transition: all 0.2s;"><i class="las la-check" style="font-size: 14px; font-weight: 800;"></i></button>' +
                            '<div style="flex: 1;">' +
                                '<div style="font-size: 14px; font-weight: 700; color: var(--s-text-primary); text-decoration: ' + (isDone ? 'line-through' : 'none') + ';"><span style="color: var(--s-primary); margin-right: 4px;">' + i.qty + 'x</span> ' + i.name + (i.is_takeaway ? ' <span style="font-size: 10px; background: #f97316; color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: 800; margin-left: 6px; display: inline-flex; align-items: center; gap: 3px;"><i class="las la-shopping-bag"></i> LLEVAR</span>' : '') + '</div>' +
                                (i.notes ? '<div style="font-size: 11px; color: var(--s-warning); font-weight: 600; margin-top: 2px;">Nota: ' + i.notes + '</div>' : '') +
                            '</div>' +
                        '</div>';
                    }).join('') +
                '</div>' +
                '<div style="padding: 12px 20px; background: var(--s-bg-light); border-top: 1px solid var(--s-border); display: flex; gap: 8px;">' +
                    (o.status === 'confirmed' ? '<button class="s-btn s-btn-primary s-btn-sm" style="flex: 1; justify-content: center;" onclick="updateStatus(' + o.id + ',\'preparing\',\'' + (o.source || 'pos') + '\')"><i class="las la-fire" style="font-size:16px;"></i> Iniciar Preparación</button>' : '') +
                    (o.status === 'preparing' ? '<button class="s-btn s-btn-success s-btn-sm" style="flex: 1; justify-content: center;" onclick="updateStatus(' + o.id + ',\'ready\',\'' + (o.source || 'pos') + '\')"><i class="las la-check-circle" style="font-size:16px;"></i> Terminado / Listo</button>' : '') +
                    (o.status === 'ready' && (o.source || 'pos') === 'pos' ? '<button class="s-btn s-btn-ghost s-btn-sm" style="flex: 1; justify-content: center; background: var(--s-text-primary); color: #fff;" onclick="updateStatus(' + o.id + ',\'delivered\',\'' + (o.source || 'pos') + '\')"><i class="las la-check-double" style="font-size:16px;"></i> Entregado a Mesa</button>' : '') +
                '</div></div>';
        });

        grid.innerHTML = html;
        updatePendingCount(totalPending);

        // Volver a aplicar el filtro activo de estación
        filterStation(currentStation);

        if (newCount > 0) {
            playNotificationSound();
        }
    });
}

var soundEnabled = false;
var audioCtx = null;

function updateSoundButtonState() {
    var btn = document.getElementById('enable-sound-btn');
    if (!btn) return;
    
    var isEnabled = localStorage.getItem('kitchen_sound_enabled') === 'true';
    var notifPerm = ('Notification' in window) ? Notification.permission : 'granted';
    
    if (isEnabled && notifPerm === 'granted') {
        soundEnabled = true;
        btn.innerHTML = '<i class="las la-volume-up" style="font-size: 18px;"></i> Sonido y Alertas: ACTIVADO';
        btn.style.color = 'var(--s-success)';
        btn.style.borderColor = 'var(--s-success)';
        btn.style.background = 'rgba(22, 163, 74, 0.05)';
    } else {
        soundEnabled = false;
        btn.innerHTML = '<i class="las la-volume-mute" style="font-size: 18px;"></i> Sonido y Alertas: DESACTIVADO';
        btn.style.color = '#f59e0b';
        btn.style.borderColor = '#f59e0b';
        btn.style.background = 'rgba(245, 158, 11, 0.05)';
    }
}

function enableKitchenSound(forceEnable) {
    var isEnabled = localStorage.getItem('kitchen_sound_enabled') === 'true';
    
    if (isEnabled && !forceEnable) {
        // Toggle/Desactivar
        soundEnabled = false;
        localStorage.setItem('kitchen_sound_enabled', 'false');
        updateSoundButtonState();
        return;
    }

    try {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        
        // Play brief quiet test beep to unlock browser audio
        var osc = audioCtx.createOscillator();
        var gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        gain.gain.setValueAtTime(0.05, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.1);

        soundEnabled = true;
        localStorage.setItem('kitchen_sound_enabled', 'true');
        
        // Solicitar permisos de notificación si no están concedidos
        if ('Notification' in window) {
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        if (typeof window.grantNotificationPermission === 'function') {
                            window.grantNotificationPermission();
                        }
                    }
                    updateSoundButtonState();
                });
            } else if (Notification.permission === 'denied') {
                alert('Las notificaciones están bloqueadas en tu navegador.\n\nPor favor, haz clic en el icono del candado o configuración junto a la URL en la barra de direcciones de tu navegador y cámbialo a "Permitir" para poder recibir alertas en tiempo real.');
            }
        }
        
        updateSoundButtonState();

        // Test speech (solo si es activado explícitamente, no en auto-enable silencioso)
        if (!forceEnable && 'speechSynthesis' in window) {
            var msg = new SpeechSynthesisUtterance("Sonido de cocina activado");
            msg.lang = 'es-ES';
            msg.rate = 1.0;
            window.speechSynthesis.speak(msg);
        }
    } catch (e) {
        console.error(e);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateSoundButtonState();
    
    if (localStorage.getItem('kitchen_sound_enabled') === 'true') {
        var autoEnable = function() {
            enableKitchenSound(true);
            document.removeEventListener('click', autoEnable);
            document.removeEventListener('keydown', autoEnable);
        };
        document.addEventListener('click', autoEnable);
        document.addEventListener('keydown', autoEnable);
    }
});

function playNotificationSound() {
    try {
        if (!soundEnabled) return;

        // Resume AudioContext if suspended (browser behavior)
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }

        // Speech notification
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            var msg = new SpeechSynthesisUtterance("Nuevo pedido");
            msg.lang = 'es-ES';
            msg.rate = 1.0;
            window.speechSynthesis.speak(msg);
        }

        // Synth audio context notification
        if (audioCtx) {
            var playBeep = function(time, frequency, duration) {
                var osc = audioCtx.createOscillator();
                var gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = frequency;
                gain.gain.setValueAtTime(0.6, time);
                gain.gain.exponentialRampToValueAtTime(0.01, time + duration);
                osc.start(time);
                osc.stop(time + duration);
            };
            
            var now = audioCtx.currentTime;
            playBeep(now, 880, 0.15);     // High A note beep
            playBeep(now + 0.2, 880, 0.25); // Another high A note beep
            playBeep(now + 0.4, 1100, 0.3);  // Triple alert beep
        }
    } catch (e) {
        console.error("Audio error: ", e);
    }
}

function updatePendingCount(n) {
    var b = document.getElementById('pending-count');
    if (!b) return;
    b.textContent = n + ' Pendientes';
    b.className = n > 0 ? 's-badge s-badge-yellow' : 's-badge s-badge-green';
}

function toggleItem(orderId, itemId, btn, source) {
    source = source || 'pos';
    var isChecked = btn.classList.toggle('checked');
    var itemRow = btn.closest('.task-item');
    if (isChecked) {
        btn.style.background = 'var(--s-success)';
        btn.style.borderColor = 'var(--s-success)';
        btn.style.color = '#fff';
        itemRow.style.opacity = '0.5';
    } else {
        btn.style.background = 'transparent';
        btn.style.borderColor = 'var(--s-border)';
        btn.style.color = 'transparent';
        itemRow.style.opacity = '1';
    }
    
    var isDone = isChecked;
    fetch('/seller/pos/kitchen/' + orderId + '/status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            status: isDone ? 'preparing' : 'confirmed',
            item_id: itemId,
            item_status: isDone ? 'done' : 'pending',
            source: source
        })
    }).then(function() { 
        refreshKitchen(); 
    });
}

function updateStatus(orderId, status, source) {
    source = source || 'pos';
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = '/seller/pos/kitchen/' + orderId + '/status';
    f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="status" value="' + status + '"><input type="hidden" name="source" value="' + source + '">';
    document.body.appendChild(f);
    f.submit();
}

// ── Timer ────────────────────────────────────────────────────────────────
var currentStation = 'all';

function filterStation(station) {
    currentStation = station;
    document.querySelectorAll('.station-btn').forEach(function(btn) {
        btn.classList.toggle('station-active', btn.dataset.station === station);
    });
    document.querySelectorAll('.task-card').forEach(function(card) {
        if (station === 'all') {
            card.style.display = '';
        } else {
            card.style.display = card.dataset.station === station ? '' : 'none';
        }
    });
}

function updateTimers() {
    var now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('.kitchen-timer').forEach(function(el) {
        var created = parseInt(el.dataset.created);
        var status = el.dataset.status;
        if (status === 'delivered' || status === 'cancelled') return;
        var diff = now - created;
        if (diff < 0) diff = 0;
        var mins = Math.floor(diff / 60);
        var secs = diff % 60;
        el.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
        // Color coding
        if (diff > 1200) { // >20 min
            el.style.color = '#ef4444';
        } else if (diff > 600) { // >10 min
            el.style.color = '#f59e0b';
        } else {
            el.style.color = 'var(--s-text-primary)';
        }
    });
}

setInterval(updateTimers, 1000);
updateTimers();
</script>
<style>
.kitchen-topbar-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}
.station-btn { transition: all .2s !important; }
.station-active { background: var(--s-accent) !important; color: #fff !important; border-color: var(--s-accent) !important; }

@media (max-width: 991px) {
    .s-topbar {
        height: auto !important;
        min-height: 64px;
        padding-top: 10px;
        padding-bottom: 10px;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px;
    }
    .s-topbar > div:first-child {
        justify-content: space-between;
        width: 100%;
    }
    .s-topbar-right {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 8px;
    }
    .kitchen-topbar-wrapper {
        width: 100%;
        flex-wrap: wrap;
        gap: 8px !important;
    }
    #kitchen-clock {
        display: none !important;
    }
}
@media (max-width: 576px) {
    .s-topbar-title {
        font-size: 13px !important;
    }
    .station-btn {
        padding: 6px 10px !important;
        font-size: 11px !important;
    }
    #enable-sound-btn {
        padding: 6px 10px !important;
        font-size: 11px !important;
    }
    #pending-count {
        padding: 6px 10px !important;
        font-size: 11px !important;
    }
}
</style>
@endpush
@endsection
