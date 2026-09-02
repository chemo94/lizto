@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-map-marked-alt"></i></span> Salón y Mesas
@endsection

@section('topbar-actions')
@if(!session()->has('seller_staff_id'))
<div style="display: flex; gap: 10px;">
    <button class="s-btn s-btn-secondary s-btn-sm" onclick="saveAllPositions()">
        <i class="las la-save"></i> Guardar Posiciones
    </button>
    <a href="{{ route('seller.pos.tables') }}" class="s-btn s-btn-ghost s-btn-sm" style="border: 1px solid var(--s-border);">
        <i class="las la-cog"></i> Gestionar Mesas
    </a>
</div>
@endif
@endsection

@section('seller-content')
<style>
.floor-head{min-height:112px;margin-bottom:16px;padding:20px 22px;border:1px solid var(--s-border);border-radius:14px;background:#fff;box-shadow:var(--s-shadow-sm);display:flex;align-items:center;justify-content:space-between;gap:18px}.floor-head h2{font:800 21px 'Plus Jakarta Sans','Inter',sans-serif;color:#172033;margin:0 0 4px;letter-spacing:-.45px}.floor-head h2 i{color:#f97316}.floor-head p{margin:0;color:#94a3b8;font-size:10px}.floor-legend{display:flex;gap:8px}.floor-legend span{padding:9px 12px;border:1px solid var(--s-border);border-radius:9px;background:#f8fafc;font-size:9px;font-weight:700;color:#64748b}.floor-legend i{margin-right:5px}.floor-area>.s-card,.floor-selector-card{border-radius:14px!important;box-shadow:var(--s-shadow-sm)!important}.area-tab-btn.s-btn-primary{background:#f97316!important;border-color:#f97316!important}@media(max-width:767px){.floor-head{align-items:flex-start;flex-direction:column}.floor-legend{width:100%;overflow:auto}}
</style>
<div class="s-content">
    @php $floorTables = $areas->flatMap->tables; @endphp
    <section class="floor-head">
        <div><h2><i class="las la-border-all"></i> Plano del Salón</h2><p>Visualiza la ocupación y organiza la distribución de mesas por área.</p></div>
        <div class="floor-legend"><span><i class="las la-circle" style="color:#10b981"></i>{{ $floorTables->where('status','free')->count() }} disponibles</span><span><i class="las la-circle" style="color:#f59e0b"></i>{{ $floorTables->where('status','!=','free')->count() }} ocupadas</span><span><i class="las la-layer-group" style="color:#8b5cf6"></i>{{ $areas->count() }} áreas</span></div>
    </section>
    
    <!-- SELECTOR DE AREA -->
    <div class="s-card floor-selector-card" style="margin-bottom: 14px; padding: 12px 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @foreach($areas as $index => $area)
                @php
                    $isFirst = !request('area') ? ($index === 0) : (request('area') == $area->id);
                @endphp
                <button class="s-btn {{ $isFirst ? 's-btn-primary' : 's-btn-ghost' }} s-btn-sm area-tab-btn" data-area-id="{{ $area->id }}" onclick="switchArea({{ $area->id }}, this)">
                    <i class="las la-layer-group"></i> {{ $area->name }}
                    <span class="s-badge {{ $isFirst ? 's-badge-green' : 's-badge-gray' }}" style="font-size: 10px; padding: 2px 6px; margin-left: 6px; border-radius: 12px;">{{ $area->tables->count() }}</span>
                </button>
                @endforeach
            </div>
            
            @if(!session()->has('seller_staff_id'))
            <div style="font-size: 13px; color: var(--s-text-3); display: flex; align-items: center; gap: 6px;">
                <i class="las la-info-circle" style="font-size:16px; color:var(--s-primary);"></i>
                Arrastra las mesas para reubicarlas. Doble clic para agregar mesa.
            </div>
            @else
            <div style="font-size: 13px; color: var(--s-text-3); display: flex; align-items: center; gap: 6px;">
                <i class="las la-info-circle" style="font-size:16px; color:var(--s-primary);"></i>
                Selecciona una mesa para tomar un pedido.
            </div>
            @endif
        </div>
    </div>

    <!-- CANVAS POR AREA -->
    @foreach($areas as $index => $area)
    @php
        $isFirst = !request('area') ? ($index === 0) : (request('area') == $area->id);
        $hasCustomPlan = !empty($area->floorplan_image);
        $planImageUrl = $hasCustomPlan ? getImage('assets/images/areas/' . $area->floorplan_image) : asset('assets/images/area.webp');
    @endphp
    <div class="floor-area" id="area-{{ $area->id }}" style="{{ $isFirst ? '' : 'display:none' }}">
        <div class="s-card" style="padding: 0; overflow: hidden; border: 1px solid var(--s-border);">
            
            <div style="background: var(--s-bg-light); padding: 16px 20px; border-bottom: 1px solid var(--s-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="background: var(--s-accent-light); width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: var(--s-primary);">
                        <i class="las la-layer-group" style="font-size: 18px;"></i>
                    </span>
                    <div>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--s-text);">{{ $area->name }}</h3>
                        <span style="font-size: 11px; color: var(--s-text-3);">{{ $area->tables->count() }} mesas configuradas</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    @if(!session()->has('seller_staff_id'))
                    <form method="POST" action="{{ route('seller.pos.floorplan.upload') }}" enctype="multipart/form-data" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        @csrf
                        <input type="hidden" name="area_id" value="{{ $area->id }}">
                        <label class="s-btn s-btn-ghost s-btn-xs" style="border: 1px solid var(--s-border); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; height: 34px; font-size: 11.5px; border-radius: 8px; background: #fff; font-weight: 700; color: var(--s-primary);">
                            <i class="las la-cloud-upload-alt" style="font-size:18px;"></i>
                            {{ $hasCustomPlan ? 'Cambiar Plano 2D' : 'Subir Plano 2D' }}
                            <input type="file" name="image" accept="image/*" style="display: none;" onchange="this.form.submit()">
                        </label>
                    </form>
                    @endif

                    <div style="display: flex; gap: 4px; background: #fff; padding: 2px; border-radius: 8px; border: 1px solid var(--s-border);">
                        <button type="button" class="s-btn s-btn-ghost s-btn-xs" onclick="zoomCanvas({{ $area->id }}, 0.1)" title="Acercar" style="padding: 4px 8px;"><i class="las la-search-plus" style="font-size:16px;"></i></button>
                        <button type="button" class="s-btn s-btn-ghost s-btn-xs" onclick="zoomCanvas({{ $area->id }}, -0.1)" title="Alejar" style="padding: 4px 8px;"><i class="las la-search-minus" style="font-size:16px;"></i></button>
                        <button type="button" class="s-btn s-btn-ghost s-btn-xs" onclick="resetZoomCanvas({{ $area->id }})" title="Restablecer" style="padding: 4px 8px;"><i class="las la-redo-alt" style="font-size:14px;"></i></button>
                    </div>

                    <div style="display: flex; gap: 12px; font-size: 12px; font-weight: 600;">
                        <span style="display: flex; align-items: center; gap: 6px; color: var(--s-text-2);">
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--s-success);"></span>
                            Disponibles: {{ $area->tables->where('status', 'free')->count() }}
                        </span>
                        <span style="display: flex; align-items: center; gap: 6px; color: var(--s-text-2);">
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--s-warning);"></span>
                            Ocupadas: {{ $area->tables->where('status', '!=', 'free')->count() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- VIEWPORT DEL CANVAS RESPONSIVO -->
            <div class="floor-viewport" id="viewport-{{ $area->id }}" style="position: relative; width: 100%; height: 580px; overflow: auto; background: var(--s-surface-2); touch-action: pan-x pan-y;">
                
                <!-- CANVAS 2D -->
                <div class="floor-canvas" id="canvas-{{ $area->id }}" data-area="{{ $area->id }}"
                     style="position: relative; width: 100%; min-width: 700px; min-height: 580px; height: 100%;
                            @if($hasCustomPlan)
                            background-image: url('{{ $planImageUrl }}');
                            background-size: cover;
                            background-position: center;
                            background-repeat: no-repeat;
                            @else
                            background-image: radial-gradient(var(--s-border) 1px, transparent 0), url('{{ $planImageUrl }}');
                            background-size: 24px 24px, cover;
                            background-position: center center;
                            background-repeat: repeat, no-repeat;
                            @endif
                            background-color: var(--s-surface);
                            transition: transform 0.2s ease-out; transform-origin: 0 0;
                            cursor: {{ session()->has('seller_staff_id') ? 'default' : 'crosshair' }};"
                     @if(!session()->has('seller_staff_id')) ondblclick="addTableToCanvas({{ $area->id }}, event)" @endif>
                    
                    @foreach($area->tables as $table)
                    @php
                        $isBusy = $table->status !== 'free';
                        $isLinked = $table->linked_to_table_id !== null;
                        $hasReservation = isset($reservations) ? $reservations->where('pos_table_id', $table->id)->isNotEmpty() : false;
                    @endphp
                    <div class="floor-table {{ $isBusy ? 'busy' : 'free' }} {{ $isLinked ? 'linked' : '' }}" id="ftable-{{ $table->id }}"
                         data-x="{{ $table->pos_x }}" data-y="{{ $table->pos_y }}" data-id="{{ $table->id }}"
                         style="position: absolute; left: {{ $table->pos_x }}px; top: {{ $table->pos_y }}px;
                                width: 76px; height: 76px; border-radius: 16px;
                                background: var(--s-surface);
                                border: 2.5px solid {{ $isLinked ? 'var(--s-primary)' : ($isBusy ? 'var(--s-warning)' : 'var(--s-border)') }};
                                display: flex; flex-direction: column; align-items: center; justify-content: center;
                                cursor: {{ session()->has('seller_staff_id') ? 'pointer' : 'grab' }}; user-select: none; transition: transform 0.15s, box-shadow 0.15s; z-index: 10;
                                box-shadow: 0 6px 16px rgba(0,0,0,0.08);"
                         @if(!session()->has('seller_staff_id'))
                         ontouchstart="startDragTouch(event, {{ $table->id }}, {{ $area->id }})" onmousedown="startDrag(event, {{ $table->id }}, {{ $area->id }})"
                         onclick="if(!_touchDragged) { event.stopPropagation(); window.location='{{ route('seller.pos.workspace', ['type' => 'dine_in', 'table' => $table->id]) }}'; }"
                         @else
                         onclick="event.stopPropagation(); window.location='{{ route('seller.pos.workspace', ['type' => 'dine_in', 'table' => $table->id]) }}'"
                         @endif>
                        
                        @if($hasReservation)
                        <span style="position: absolute; top: 4px; right: 4px; color: var(--s-primary); font-size: 13px;" title="Tiene reservación hoy">
                            <i class="las la-calendar-check"></i>
                        </span>
                        @endif

                        <span style="color: {{ $isLinked ? 'var(--s-primary)' : ($isBusy ? 'var(--s-warning)' : 'var(--s-text-3)') }}; margin-bottom: 2px;">
                            <i class="las la-utensils" style="font-size: 18px;"></i>
                        </span>
                        <strong style="font-size: 11.5px; font-weight: 800; color: var(--s-text); line-height: 1.1; max-width: 68px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">{{ $table->name }}</strong>
                        
                        <span class="s-badge {{ $isLinked ? 's-badge-blue' : ($isBusy ? 's-badge-yellow' : 's-badge-green') }}" style="font-size: 8.5px; padding: 1px 5px; margin-top: 3px; border-radius: 4px;">
                            {{ $isLinked ? 'Unida' : ($isBusy ? 'Ocupada' : 'Libre') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
    @endforeach

    @if($areas->isEmpty())
    <div class="s-card" style="text-align: center; padding: 60px 20px; color: var(--s-text-3);">
        <i class="las la-store-alt" style="font-size: 48px; color: var(--s-border); display: block; margin-bottom: 12px;"></i>
        <h4 style="color: var(--s-text); font-weight: 600; margin-bottom: 4px;">No hay áreas de salón</h4>
        <p style="font-size: 13px; margin-bottom: 16px;">Para poder ubicar tus mesas en un plano interactivo debes crear al menos un área.</p>
        <a href="{{ route('seller.pos.tables') }}" class="s-btn s-btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="las la-plus-circle"></i> Configurar Áreas y Mesas
        </a>
    </div>
    @endif

</div>

<style>
.floor-viewport {
    -webkit-overflow-scrolling: touch;
}
.floor-table:hover {
    transform: scale(1.06);
    box-shadow: 0 10px 24px rgba(0,0,0,0.15) !important;
    border-color: var(--s-primary) !important;
    z-index: 100 !important;
}
.floor-table:active {
    cursor: grabbing;
    transform: scale(1.02);
}
.floor-table.busy {
    background: linear-gradient(135deg, var(--s-surface) 0%, rgba(245, 158, 11, 0.05) 100%) !important;
}
.floor-table {
    touch-action: none;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
}
@media (max-width: 768px) {
    .floor-viewport {
        height: 480px !important;
    }
    .floor-table {
        width: 68px !important;
        height: 68px !important;
    }
}
</style>

@push('script')
<script>
var dragId = null, dragArea = null, offX = 0, offY = 0;
var _touchDragged = false;
var zoomLevels = {};

function switchArea(id, button) {
    document.querySelectorAll('.floor-area').forEach(function(a) { a.style.display = 'none'; });
    var el = document.getElementById('area-' + id);
    if (el) el.style.display = 'block';
    
    document.querySelectorAll('.area-tab-btn').forEach(function(b) {
        b.classList.remove('s-btn-primary');
        b.classList.add('s-btn-ghost');
        var badge = b.querySelector('.s-badge');
        if (badge) {
            badge.classList.remove('s-badge-green');
            badge.classList.add('s-badge-gray');
        }
    });
    
    if (button) {
        button.classList.add('s-btn-primary');
        button.classList.remove('s-btn-ghost');
        var badge = button.querySelector('.s-badge');
        if (badge) {
            badge.classList.remove('s-badge-gray');
            badge.classList.add('s-badge-green');
        }
    }
}

function zoomCanvas(areaId, delta) {
    var current = zoomLevels[areaId] || 1;
    var next = Math.max(0.6, Math.min(2.0, current + delta));
    zoomLevels[areaId] = next;
    var canvas = document.getElementById('canvas-' + areaId);
    if (canvas) {
        canvas.style.transform = 'scale(' + next + ')';
    }
}

function resetZoomCanvas(areaId) {
    zoomLevels[areaId] = 1;
    var canvas = document.getElementById('canvas-' + areaId);
    if (canvas) {
        canvas.style.transform = 'scale(1)';
    }
}

function startDrag(e, tableId, areaId) {
    e.preventDefault();
    dragId = tableId;
    dragArea = areaId;
    var el = document.getElementById('ftable-' + tableId);
    var scale = zoomLevels[areaId] || 1;
    offX = (e.clientX / scale) - el.offsetLeft;
    offY = (e.clientY / scale) - el.offsetTop;
    el.style.zIndex = 200;
}

function startDragTouch(e, tableId, areaId) {
    _touchDragged = false;
    var touch = e.touches[0];
    dragId = tableId;
    dragArea = areaId;
    var el = document.getElementById('ftable-' + tableId);
    var scale = zoomLevels[areaId] || 1;
    offX = (touch.clientX / scale) - el.offsetLeft;
    offY = (touch.clientY / scale) - el.offsetTop;
    el.style.zIndex = 200;
}

document.addEventListener('mousemove', function(e) {
    if (!dragId) return;
    var el = document.getElementById('ftable-' + dragId);
    if (!el) return;
    var canvas = document.getElementById('canvas-' + dragArea);
    var scale = zoomLevels[dragArea] || 1;
    var rx = (e.clientX / scale) - offX;
    var ry = (e.clientY / scale) - offY;
    var maxX = canvas.clientWidth - 76;
    var maxY = canvas.clientHeight - 76;
    el.style.left = Math.max(0, Math.min(rx, maxX)) + 'px';
    el.style.top = Math.max(0, Math.min(ry, maxY)) + 'px';
});

document.addEventListener('touchmove', function(e) {
    if (!dragId) return;
    _touchDragged = true;
    e.preventDefault();
    var touch = e.touches[0];
    var el = document.getElementById('ftable-' + dragId);
    if (!el) return;
    var canvas = document.getElementById('canvas-' + dragArea);
    var scale = zoomLevels[dragArea] || 1;
    var rx = (touch.clientX / scale) - offX;
    var ry = (touch.clientY / scale) - offY;
    var maxX = canvas.clientWidth - 76;
    var maxY = canvas.clientHeight - 76;
    el.style.left = Math.max(0, Math.min(rx, maxX)) + 'px';
    el.style.top = Math.max(0, Math.min(ry, maxY)) + 'px';
}, { passive: false });

document.addEventListener('mouseup', function() {
    if (!dragId) return;
    var el = document.getElementById('ftable-' + dragId);
    if (el) {
        el.style.zIndex = 10;
        savePosition(dragId, parseInt(el.style.left), parseInt(el.style.top));
    }
    dragId = null;
});

document.addEventListener('touchend', function() {
    if (!dragId) return;
    var el = document.getElementById('ftable-' + dragId);
    if (el) {
        el.style.zIndex = 10;
        savePosition(dragId, parseInt(el.style.left), parseInt(el.style.top));
    }
    dragId = null;
});

function savePosition(id, x, y) {
    fetch('/seller/pos/tables/position', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ id: id, x: x, y: y })
    });
}

function saveAllPositions() {
    var els = document.querySelectorAll('.floor-table');
    els.forEach(function(el) {
        savePosition(parseInt(el.dataset.id), parseInt(el.style.left) || parseInt(el.dataset.x), parseInt(el.style.top) || parseInt(el.dataset.y));
    });
    alert('Posiciones del salón guardadas correctamente.');
}

function addTableToCanvas(areaId, e) {
    var canvas = document.getElementById('canvas-' + areaId);
    var rect = canvas.getBoundingClientRect();
    var scale = zoomLevels[areaId] || 1;
    var x = Math.round(((e.clientX - rect.left) / scale) - 38);
    var y = Math.round(((e.clientY - rect.top) / scale) - 38);
    window.location.href = '{{ route('seller.pos.tables') }}?area=' + areaId + '&new_x=' + x + '&new_y=' + y;
}
</script>
@endpush
@endsection
