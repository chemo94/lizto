@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-chair"></i></span> Mesas y atención
@endsection

@section('topbar-actions')
<a href="{{ route('seller.pos.kitchen') }}" class="s-btn s-btn-primary s-btn-sm"><i class="las la-utensils"></i> Cocina</a>
@endsection

@section('seller-content')
@php
    $groups = [];
    foreach ($tables as $table) {
        if ($table->linked_to_table_id) {
            $parentId = $table->linked_to_table_id;
            if (!isset($groups[$parentId])) {
                $groups[$parentId] = ['primary' => $tables->firstWhere('id', $parentId), 'children' => collect()];
            }
            $groups[$parentId]['children']->push($table);
        }
    }
    $standaloneTables = $tables->filter(fn($table) => !$table->linked_to_table_id && !isset($groups[$table->id]));
@endphp

<div class="s-content pos-launcher">
    <div class="launcher-types" aria-label="Tipo de atención">
        <button type="button" class="launcher-type active" data-type="dine_in"><i class="las la-utensils"></i><span>Mesa</span></button>
        <a class="launcher-type" href="{{ route('seller.pos.workspace', ['type' => 'takeaway', 'counter' => 1]) }}"><i class="las la-shopping-bag"></i><span>Para llevar</span></a>
        <a class="launcher-type" href="{{ route('seller.pos.workspace', ['type' => 'delivery']) }}"><i class="las la-motorcycle"></i><span>Delivery (LIZTO)</span></a>
        <button type="button" class="launcher-type courtesy" data-type="courtesy"><i class="las la-gift"></i><span>Cortesía</span></button>
        <a class="launcher-type" href="{{ route('seller.pos.workspace', ['type' => 'daz']) }}"><b class="channel daz">DAZ DAZ</b></a>
        <a class="launcher-type" href="{{ route('seller.pos.workspace', ['type' => 'llama']) }}"><b class="channel llama">LLAMA FOOD</b></a>
    </div>

    <div class="launcher-heading">
        <div>
            <h2>Selecciona una mesa</h2>
            <p>Las mesas ocupadas pueden arrastrarse hacia una mesa libre para trasladar su comanda.</p>
        </div>
        <span class="drag-help"><i class="las la-hand-rock"></i> Arrastra para cambiar de mesa</span>
    </div>

    <div class="zone-tabs">
        <button type="button" class="zone-tab active" data-zone="all">Todas</button>
        @foreach($areas as $area)
        <button type="button" class="zone-tab" data-zone="area-{{ $area->id }}">{{ $area->name }} <small>{{ $tables->where('pos_area_id', $area->id)->count() }}</small></button>
        @endforeach
        @if($tables->whereNull('pos_area_id')->count())
        <button type="button" class="zone-tab" data-zone="sin-area">Sin área <small>{{ $tables->whereNull('pos_area_id')->count() }}</small></button>
        @endif
    </div>

    <div class="table-grid" id="table-grid">
        <a class="table-card counter" data-zone="all" href="{{ route('seller.pos.workspace', ['type' => 'dine_in', 'counter' => 1]) }}">
            <i class="las la-plus-circle"></i><strong>Mostrador / Barra</strong><span>ABRIR POS</span>
        </a>

        @foreach($standaloneTables as $table)
            @php
                $busy = (bool) $table->active_order || $table->status !== 'free';
                $order = $table->active_order;
                $elapsed = $order?->created_at?->diffForHumans(null, true, true, 1);
            @endphp
            <a class="table-card {{ $busy ? 'busy' : 'free' }}"
               href="{{ route('seller.pos.workspace', ['type' => 'dine_in', 'table' => $table->id]) }}"
               data-id="{{ $table->id }}" data-name="{{ $table->name }}"
               data-zone="{{ $table->pos_area_id ? 'area-'.$table->pos_area_id : 'sin-area' }}"
               data-busy="{{ $busy ? '1' : '0' }}" draggable="{{ $busy && $table->active_order ? 'true' : 'false' }}">
                <i class="las la-chair"></i><strong>{{ $table->name }}</strong>
                <span>{{ $busy ? 'OCUPADA' : 'DISPONIBLE' }}</span>
                <small class="table-capacity"><i class="las la-user-friends"></i> {{ $table->capacity ?? 4 }} personas</small>
                @if($order)
                    <div class="table-summary"><b>S/ {{ number_format($order->total, 2) }}</b><em>{{ $elapsed ?: 'recién abierta' }}</em></div>
                    <div class="table-tooltip" role="tooltip">
                        <b>{{ $table->name }} · Mesa ocupada</b>
                        <div><span>Comanda</span><strong>#{{ $order->order_no }}</strong></div>
                        <div><span>Consumo actual</span><strong>S/ {{ number_format($order->total, 2) }}</strong></div>
                        <div><span>Tiempo</span><strong>{{ $elapsed ?: 'recién abierta' }}</strong></div>
                        <div><span>Capacidad</span><strong>{{ $table->capacity ?? 4 }} personas</strong></div>
                        @if($order->staff)<div><span>Responsable</span><strong>{{ $order->staff->name }}</strong></div>@endif
                        @if($order->customer_name)<div><span>Cliente</span><strong>{{ $order->customer_name }}</strong></div>@endif
                        @if($order->notes)<p><i class="las la-sticky-note"></i> {{ $order->notes }}</p>@endif
                    </div>
                @elseif($busy)
                    <div class="table-tooltip" role="tooltip">
                        <b>{{ $table->name }} · Mesa ocupada</b>
                        <div><span>Capacidad</span><strong>{{ $table->capacity ?? 4 }} personas</strong></div>
                        <p><i class="las la-info-circle"></i> No hay una comanda activa vinculada.</p>
                    </div>
                @endif
            </a>
        @endforeach

        @foreach($groups as $group)
            @continue(!$group['primary'])
            @php
                $primary = $group['primary'];
                $busy = (bool) $primary->active_order || $primary->status !== 'free';
                $order = $primary->active_order;
                $elapsed = $order?->created_at?->diffForHumans(null, true, true, 1);
                $names = collect([$primary])->merge($group['children'])->pluck('name')->implode(' + ');
                $capacity = collect([$primary])->merge($group['children'])->sum(fn($item) => $item->capacity ?? 4);
            @endphp
            <a class="table-card grouped {{ $busy ? 'busy' : 'free' }}"
               href="{{ route('seller.pos.workspace', ['type' => 'dine_in', 'table' => $primary->id]) }}"
               data-id="{{ $primary->id }}" data-name="{{ $names }}"
               data-zone="{{ $primary->pos_area_id ? 'area-'.$primary->pos_area_id : 'sin-area' }}"
               data-busy="{{ $busy ? '1' : '0' }}" draggable="{{ $busy && $primary->active_order ? 'true' : 'false' }}">
                <i class="las la-object-group"></i><strong>{{ $names }}</strong>
                <span>{{ $busy ? 'OCUPADA · AGRUPADA' : 'DISPONIBLE' }}</span>
                <small class="table-capacity"><i class="las la-user-friends"></i> {{ $capacity }} personas</small>
                @if($order)
                    <div class="table-summary"><b>S/ {{ number_format($order->total, 2) }}</b><em>{{ $elapsed ?: 'recién abierta' }}</em></div>
                    <div class="table-tooltip" role="tooltip">
                        <b>{{ $names }} · Mesas agrupadas</b>
                        <div><span>Comanda</span><strong>#{{ $order->order_no }}</strong></div>
                        <div><span>Consumo actual</span><strong>S/ {{ number_format($order->total, 2) }}</strong></div>
                        <div><span>Tiempo</span><strong>{{ $elapsed ?: 'recién abierta' }}</strong></div>
                        <div><span>Capacidad total</span><strong>{{ $capacity }} personas</strong></div>
                        @if($order->staff)<div><span>Responsable</span><strong>{{ $order->staff->name }}</strong></div>@endif
                        @if($order->customer_name)<div><span>Cliente</span><strong>{{ $order->customer_name }}</strong></div>@endif
                        @if($order->notes)<p><i class="las la-sticky-note"></i> {{ $order->notes }}</p>@endif
                    </div>
                @elseif($busy)
                    <div class="table-tooltip" role="tooltip">
                        <b>{{ $names }} · Mesas agrupadas</b>
                        <div><span>Capacidad total</span><strong>{{ $capacity }} personas</strong></div>
                        <p><i class="las la-info-circle"></i> No hay una comanda activa vinculada.</p>
                    </div>
                @endif
            </a>
        @endforeach
    </div>
</div>

<div class="transfer-toast" id="transfer-toast" role="status"></div>
@endsection

@push('style')
<style>
.pos-launcher{max-width:100%;padding-top:12px}.launcher-types{display:grid;grid-template-columns:repeat(6,minmax(130px,1fr));gap:10px;padding:10px;background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:18px;overflow-x:auto}.launcher-type{min-height:72px;border:1px solid transparent;border-radius:14px;background:transparent;color:var(--s-text-2);display:flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;font-weight:800;cursor:pointer;white-space:nowrap}.launcher-type i{font-size:22px}.launcher-type:hover,.launcher-type.active{background:var(--s-primary);color:#fff;box-shadow:0 8px 18px rgba(249,115,22,.2)}.launcher-type.courtesy{color:#9333ea}.channel{font-size:11px;padding:6px 9px;border-radius:6px;color:#fff}.channel.daz{background:#e11d48}.channel.llama{background:#f59e0b}.launcher-heading{display:flex;justify-content:space-between;align-items:end;gap:16px;margin:28px 0 16px}.launcher-heading h2{font-size:20px;margin:0 0 4px;color:var(--s-text)}.launcher-heading p{margin:0;color:var(--s-text-3);font-size:13px}.drag-help{font-size:12px;font-weight:700;color:var(--s-text-3);white-space:nowrap}.zone-tabs{display:flex;gap:8px;overflow-x:auto;padding:2px 0 14px}.zone-tab{border:1px solid var(--s-border);background:var(--s-surface);color:var(--s-text-2);padding:10px 16px;border-radius:999px;font-weight:800;white-space:nowrap;cursor:pointer}.zone-tab.active{background:var(--s-primary);border-color:var(--s-primary);color:#fff}.zone-tab small{margin-left:5px}.table-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px}.table-card{position:relative;min-height:156px;border:2px solid transparent;border-radius:20px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;text-decoration:none;box-shadow:var(--s-shadow-sm);transition:transform .16s,box-shadow .16s,border-color .16s;user-select:none}.table-card>i{font-size:31px}.table-card>strong{font-size:17px;text-align:center}.table-card>span{font-size:11px;font-weight:900;padding:5px 10px;border-radius:999px}.table-card small{font-size:12px;font-weight:700;max-width:90%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.table-capacity{display:flex;align-items:center;gap:4px;opacity:.9}.table-capacity i{font-size:14px}.table-summary{display:flex;align-items:center;gap:8px;font-size:12px}.table-summary b{font-size:13px}.table-summary em{font-style:normal;opacity:.85}.table-tooltip{position:absolute;z-index:30;left:50%;bottom:calc(100% + 12px);width:245px;padding:14px;border-radius:14px;background:#0f172a;color:#fff;box-shadow:0 18px 40px rgba(15,23,42,.3);opacity:0;visibility:hidden;pointer-events:none;transform:translate(-50%,8px);transition:.16s;text-align:left}.table-tooltip:after{content:"";position:absolute;top:100%;left:50%;border:7px solid transparent;border-top-color:#0f172a;transform:translateX(-50%)}.table-tooltip>b{display:block;font-size:13px;margin-bottom:9px}.table-tooltip div{display:flex;justify-content:space-between;gap:12px;padding:5px 0;border-top:1px solid rgba(255,255,255,.1)}.table-tooltip div span,.table-tooltip div strong{padding:0;background:none;font-size:11px;text-align:right}.table-tooltip div span{font-weight:500;color:#cbd5e1;text-align:left}.table-tooltip p{margin:8px 0 0;padding-top:8px;border-top:1px solid rgba(255,255,255,.1);font-size:11px;color:#e2e8f0}.table-card:hover .table-tooltip,.table-card:focus-visible .table-tooltip{opacity:1;visibility:visible;transform:translate(-50%,0)}.table-card.free{background:#22c55e;color:#fff}.table-card.free>span{background:#078b3c}.table-card.busy{background:#ef4444;color:#fff;cursor:grab}.table-card.busy>span{background:#b91c1c}.table-card.counter{background:var(--s-surface);border-color:var(--s-primary);color:var(--s-primary)}.table-card.counter>span{background:var(--s-primary-soft);color:var(--s-primary)}.table-card:hover{transform:translateY(-3px);box-shadow:var(--s-shadow-md)}.table-card.dragging{opacity:.45;transform:scale(.96)}.table-card.drop-target{border-color:#0f172a;box-shadow:0 0 0 5px rgba(15,23,42,.15);transform:scale(1.04)}.table-card.transfering{pointer-events:none;animation:transferPulse .8s infinite alternate}.transfer-toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%) translateY(25px);background:#0f172a;color:#fff;padding:12px 18px;border-radius:12px;font-weight:700;z-index:9999;opacity:0;pointer-events:none;transition:.2s}.transfer-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}@keyframes transferPulse{to{opacity:.55}}
@media(max-width:900px){.launcher-types{display:flex}.launcher-type{min-width:150px;padding:0 14px}.launcher-heading{align-items:start;flex-direction:column}.drag-help{display:none}.table-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.table-card{min-height:125px}}@media(max-width:390px){.table-grid{grid-template-columns:1fr}}
</style>
@endpush

@push('script')
<script>
(function () {
    var selectedType = 'dine_in';
    var dragged = null;
    var didDrag = false;
    var grid = document.getElementById('table-grid');
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.launcher-type[data-type]').forEach(function(button) {
        button.addEventListener('click', function() {
            selectedType = button.dataset.type;
            document.querySelectorAll('.launcher-type[data-type]').forEach(function(item) { item.classList.remove('active'); });
            button.classList.add('active');
            grid.querySelectorAll('.table-card[href]').forEach(function(card) {
                var url = new URL(card.href, window.location.origin);
                url.searchParams.set('type', selectedType);
                card.href = url.toString();
            });
        });
    });

    grid.querySelectorAll('.table-card[data-id]').forEach(function(card) {
        card.addEventListener('click', function(event) { if (didDrag) { event.preventDefault(); didDrag = false; } });
        card.addEventListener('dragstart', function(event) {
            if (card.dataset.busy !== '1' || card.getAttribute('draggable') !== 'true') { event.preventDefault(); return; }
            dragged = card; didDrag = true; card.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', card.dataset.id);
        });
        card.addEventListener('dragend', function() {
            card.classList.remove('dragging');
            grid.querySelectorAll('.drop-target').forEach(function(item) { item.classList.remove('drop-target'); });
            setTimeout(function(){ didDrag = false; }, 50); dragged = null;
        });
        card.addEventListener('dragover', function(event) {
            if (dragged && card.dataset.busy === '0' && card !== dragged) { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; card.classList.add('drop-target'); }
        });
        card.addEventListener('dragleave', function() { card.classList.remove('drop-target'); });
        card.addEventListener('drop', function(event) {
            event.preventDefault(); card.classList.remove('drop-target');
            if (!dragged || card.dataset.busy !== '0') return;
            transferTable(dragged, card);
        });
    });

    // HTML5 drag no se activa de forma consistente en móviles. Una pulsación
    // sostenida sobre una mesa ocupada habilita el mismo traslado táctil.
    var touchSource = null, touchTarget = null, holdTimer = null, startX = 0, startY = 0;
    grid.addEventListener('pointerdown', function(event) {
        if (event.pointerType === 'mouse') return;
        var card = event.target.closest('.table-card[data-id]');
        if (!card || card.dataset.busy !== '1' || card.getAttribute('draggable') !== 'true') return;
        startX = event.clientX; startY = event.clientY;
        holdTimer = setTimeout(function() {
            touchSource = card; didDrag = true; card.classList.add('dragging');
            if (navigator.vibrate) navigator.vibrate(35);
        }, 280);
    });
    grid.addEventListener('pointermove', function(event) {
        if (holdTimer && Math.hypot(event.clientX - startX, event.clientY - startY) > 12) { clearTimeout(holdTimer); holdTimer = null; }
        if (!touchSource) return;
        event.preventDefault();
        var candidate = document.elementFromPoint(event.clientX, event.clientY)?.closest('.table-card[data-id]');
        if (touchTarget && touchTarget !== candidate) touchTarget.classList.remove('drop-target');
        touchTarget = candidate && candidate.dataset.busy === '0' ? candidate : null;
        if (touchTarget) touchTarget.classList.add('drop-target');
    }, {passive:false});
    function finishTouchTransfer() {
        if (holdTimer) clearTimeout(holdTimer); holdTimer = null;
        if (touchSource) touchSource.classList.remove('dragging');
        if (touchTarget) touchTarget.classList.remove('drop-target');
        if (touchSource && touchTarget) transferTable(touchSource, touchTarget);
        touchSource = null; touchTarget = null;
        setTimeout(function(){ didDrag = false; }, 80);
    }
    grid.addEventListener('pointerup', finishTouchTransfer);
    grid.addEventListener('pointercancel', finishTouchTransfer);

    document.querySelectorAll('.zone-tab').forEach(function(button) {
        button.addEventListener('click', function() {
            document.querySelectorAll('.zone-tab').forEach(function(item){ item.classList.remove('active'); });
            button.classList.add('active');
            grid.querySelectorAll('.table-card[data-zone]').forEach(function(card) { card.hidden = button.dataset.zone !== 'all' && card.dataset.zone !== button.dataset.zone; });
        });
    });

    function transferTable(from, to) {
        if (!window.confirm('¿Trasladar la comanda de ' + from.dataset.name + ' a ' + to.dataset.name + '?')) return;
        from.classList.add('transfering'); to.classList.add('transfering');
        fetch('{{ route('seller.pos.table.transfer') }}', {
            method: 'POST', headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
            body: JSON.stringify({from_table_id: from.dataset.id, to_table_id: to.dataset.id})
        }).then(function(response) { return response.json().then(function(data){ return {ok:response.ok,data:data}; }); })
          .then(function(result) {
              if (!result.ok || !result.data.success) throw new Error(result.data.message || 'No se pudo trasladar la comanda.');
              showToast(result.data.message); setTimeout(function(){ window.location.reload(); }, 650);
          }).catch(function(error) { from.classList.remove('transfering'); to.classList.remove('transfering'); alert(error.message); });
    }

    function showToast(message) { var toast=document.getElementById('transfer-toast'); toast.textContent=message; toast.classList.add('show'); }
})();
</script>
@endpush
