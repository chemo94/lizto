@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-th"></i></span> Gestión de Mesas y Áreas
@endsection

@section('topbar-actions')
<a href="{{ route('seller.pos.floorplan') }}" class="s-btn s-btn-outline s-btn-sm" style="border-radius:10px;">
    <i class="las la-map-marked-alt"></i> Ver Salón
</a>
@endsection

@section('seller-content')
<style>
.ops-head{min-height:120px;margin-bottom:16px;padding:20px 22px;border-radius:14px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:18px;background:linear-gradient(100deg,#173d55,#327b89 56%,#7ab9af);box-shadow:var(--s-shadow-sm)}.ops-head .crumb{font-size:9px;color:rgba(255,255,255,.68);margin-bottom:7px}.ops-head h2{font:800 22px 'Plus Jakarta Sans','Inter',sans-serif;margin:0 0 3px;letter-spacing:-.45px}.ops-head p{margin:0;font-size:10px;color:rgba(255,255,255,.7)}.ops-summary{display:flex;gap:8px}.ops-summary div{min-width:86px;padding:10px 12px;text-align:center;border:1px solid rgba(255,255,255,.2);border-radius:10px;background:rgba(255,255,255,.1);backdrop-filter:blur(8px)}.ops-summary b{display:block;font-size:17px}.ops-summary small{font-size:8px;color:rgba(255,255,255,.7)}.tables-workspace>.s-card{border-radius:14px;box-shadow:var(--s-shadow-sm)}.tables-workspace .s-btn-primary{background:#f97316;border-color:#f97316}.tables-workspace .s-input:focus{border-color:#f97316!important;box-shadow:0 0 0 3px rgba(249,115,22,.1)!important}@media(max-width:767px){.ops-head{align-items:flex-start;flex-direction:column}.ops-summary{width:100%;overflow:auto}.ops-summary div{flex:1}.tables-workspace{grid-template-columns:1fr!important}}
</style>
<div class="s-content">
    @php $areasList = \App\Models\PosArea::where('seller_id', $seller->id)->orderBy('sort_order')->get(); @endphp
    @php
        $allTables = $tables->count();
        $noAreaTables = $tables->whereNull('pos_area_id');
        $freeTablesCount = $tables->where('status', 'free')->count();
        $busyTablesCount = $allTables - $freeTablesCount;
    @endphp

    <section class="ops-head">
        <div><div class="crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Salón &nbsp;/&nbsp; Mesas</div><h2><i class="las la-chair"></i> Gestión de Mesas</h2><p>Organiza las áreas y la capacidad de atención de tu establecimiento.</p></div>
        <div class="ops-summary"><div><b>{{ $allTables }}</b><small>Total mesas</small></div><div><b>{{ $freeTablesCount }}</b><small>Disponibles</small></div><div><b>{{ $busyTablesCount }}</b><small>Ocupadas</small></div><div><b>{{ $areasList->count() }}</b><small>Áreas</small></div></div>
    </section>

    <div class="s-grid-2 tables-workspace" style="grid-template-columns: 1fr 1.5fr; gap: 14px; align-items: start;">
        
        <!-- AREAS -->
        <div class="s-card">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-layer-group" style="color: var(--s-primary); font-size: 20px;"></i> Áreas del Establecimiento
            </h3>
            
            <form method="POST" action="{{ route('seller.pos.areas.store') }}" style="display: flex; gap: 8px; margin-bottom: 20px;">
                @csrf
                <input class="s-input" name="name" placeholder="Nueva área (ej: Terraza, Vip, Barra)" required>
                <button type="submit" class="s-btn s-btn-primary" style="padding: 0 16px; height: 42px;">
                    <i class="las la-plus" style="font-size: 18px;"></i>
                </button>
            </form>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse($areasList as $area)
                <div style="display: flex; align-items: center; justify-content: space-between; background: var(--s-bg-light); border: 1px solid var(--s-border); padding: 12px 16px; border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; color: var(--s-text-primary);">
                        <i class="las la-layer-group" style="color: var(--s-primary); font-size: 18px;"></i>
                        <span>{{ $area->name }}</span>
                        <span class="s-badge s-badge-gray" style="font-size: 10px; padding: 2px 6px; border-radius: 10px;">{{ $area->tables->count() }}</span>
                    </div>
                    <form method="POST" action="{{ route('seller.pos.areas.delete') }}" onsubmit="return confirm('¿Eliminar área {{ $area->name }}?')" style="display: inline;">
                        @csrf
                        <input type="hidden" name="id" value="{{ $area->id }}">
                        <button type="submit" class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-danger);">
                            <i class="las la-trash" style="font-size: 16px;"></i>
                        </button>
                    </form>
                </div>
                @empty
                <div style="text-align: center; padding: 30px 10px; color: var(--s-text-muted); font-size: 13px;">
                    <i class="las la-layer-group" style="font-size: 36px; display: block; margin-bottom: 8px; color: var(--s-border);"></i>
                    Crea tu primera área para organizar las mesas.
                </div>
                @endforelse
            </div>
        </div>

        <!-- TABLES -->
        <div class="s-card">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-utensils" style="color: var(--s-primary); font-size: 20px;"></i> Mesas
            </h3>

            <form method="POST" action="{{ route('seller.pos.tables.save') }}" style="display: grid; grid-template-columns: 2fr 2fr 1.2fr auto; gap: 12px; align-items: end; margin-bottom: 24px; background: var(--s-bg-light); padding: 16px; border-radius: 12px; border: 1px solid var(--s-border);">
                @csrf
                <div>
                    <label class="s-label">Nombre Mesa</label>
                    <input class="s-input" name="name" placeholder="Ej: Mesa 12" required>
                </div>
                
                <div>
                    <label class="s-label">Área</label>
                    <select class="s-input" name="pos_area_id">
                        <option value="">Sin área específica</option>
                        @foreach($areasList as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="s-label">Capacidad</label>
                    <input class="s-input" type="number" name="capacity" value="4" min="1" required>
                </div>
                
                <button type="submit" class="s-btn s-btn-primary" style="height: 42px; padding: 0 20px;">
                    <i class="las la-plus-circle" style="font-size: 18px;"></i>
                </button>
            </form>

            <!-- ZONE TABS -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--s-border);">
                <button class="s-btn s-btn-primary s-btn-sm zone-filter active" data-zone="all" onclick="filterZone('all', this)" style="border-radius: 20px; font-weight: 700;">
                    Todas <span class="s-badge s-badge-green" style="font-size: 10px; padding: 2px 6px; margin-left: 4px; border-radius: 10px;">{{ $allTables }}</span>
                </button>
                @foreach($areasList as $area)
                @php $count = $tables->where('pos_area_id', $area->id)->count(); @endphp
                <button class="s-btn s-btn-ghost s-btn-sm zone-filter" data-zone="area-{{ $area->id }}" onclick="filterZone('area-{{ $area->id }}', this)" style="border-radius: 20px; font-weight: 600;">
                    {{ $area->name }} <span class="s-badge s-badge-gray" style="font-size: 10px; padding: 2px 6px; margin-left: 4px; border-radius: 10px;">{{ $count }}</span>
                </button>
                @endforeach
                @if($noAreaTables->count())
                <button class="s-btn s-btn-ghost s-btn-sm zone-filter" data-zone="sin-area" onclick="filterZone('sin-area', this)" style="border-radius: 20px; font-weight: 600; border-style: dashed;">
                    Sin área <span class="s-badge s-badge-gray" style="font-size: 10px; padding: 2px 6px; margin-left: 4px; border-radius: 10px;">{{ $noAreaTables->count() }}</span>
                </button>
                @endif
            </div>

            <!-- TABLES LIST -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                @foreach($areasList as $area)
                @php $areaTables = $tables->where('pos_area_id', $area->id); @endphp
                @if($areaTables->count())
                <div class="zone-group" data-zone="area-{{ $area->id }}">
                    <div style="font-size: 11px; font-weight: 800; color: var(--s-text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--s-border); padding-bottom: 6px; margin-bottom: 12px; display: flex; justify-content: space-between;">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <i class="las la-layer-group" style="color: var(--s-primary); font-size: 14px;"></i>
                            {{ $area->name }}
                        </span>
                        <span>{{ $areaTables->count() }} Mesas</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px;">
                        @foreach($areaTables as $table)
                        <div style="background: var(--s-bg-card); border: 1px solid var(--s-border); padding: 12px; border-radius: 12px; display: flex; flex-direction: column; justify-content: space-between; gap: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: box-shadow 0.2s;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div style="font-weight: 700; color: var(--s-text-primary); font-size: 13px;">{{ $table->name }}</div>
                                    <div style="font-size: 11px; color: var(--s-text-secondary); margin-top: 2px;">Capacidad: {{ $table->capacity }} pers.</div>
                                </div>
                                
                                <form method="POST" action="{{ route('seller.pos.tables.delete', $table->id) }}" onsubmit="return confirm('¿Eliminar esta mesa?')" style="display:inline">
                                    @csrf
                                    <button class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-danger); padding: 2px;">
                                        <i class="las la-times" style="font-size: 14px;"></i>
                                    </button>
                                </form>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--s-border); padding-top: 8px;">
                                <form method="POST" action="{{ route('seller.pos.tables.toggle', $table->id) }}" style="display:inline">
                                    @csrf
                                    <button style="background:none; border:none; padding:0; cursor:pointer;">
                                        @if($table->status === 'free')
                                        <span class="s-badge s-badge-green" style="font-size: 9px;">Libre</span>
                                        @else
                                        <span class="s-badge s-badge-yellow" style="font-size: 9px;">Ocupada</span>
                                        @endif
                                    </button>
                                </form>
                                <span style="font-size: 10px; color: var(--s-text-muted);">
                                    x:{{ $table->pos_x }} y:{{ $table->pos_y }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach

                @if($noAreaTables->count())
                <div class="zone-group" data-zone="sin-area">
                    <div style="font-size: 11px; font-weight: 800; color: var(--s-text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px dashed var(--s-border); padding-bottom: 6px; margin-bottom: 12px; display: flex; justify-content: space-between;">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <i class="las la-exclamation-triangle" style="color: var(--s-warning, #f59e0b); font-size: 14px;"></i>
                            Sin Área Asignada
                        </span>
                        <span>{{ $noAreaTables->count() }} Mesas</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px;">
                        @foreach($noAreaTables as $table)
                        <div style="background: var(--s-bg-card); border: 1px dashed var(--s-border); padding: 12px; border-radius: 12px; display: flex; flex-direction: column; justify-content: space-between; gap: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div style="font-weight: 700; color: var(--s-text-primary); font-size: 13px;">{{ $table->name }}</div>
                                    <div style="font-size: 11px; color: var(--s-text-secondary); margin-top: 2px;">Capacidad: {{ $table->capacity }} pers.</div>
                                </div>
                                
                                <form method="POST" action="{{ route('seller.pos.tables.delete', $table->id) }}" onsubmit="return confirm('¿Eliminar esta mesa?')" style="display:inline">
                                    @csrf
                                    <button class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-danger); padding: 2px;">
                                        <i class="las la-times" style="font-size: 14px;"></i>
                                    </button>
                                </form>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--s-border); padding-top: 8px;">
                                <form method="POST" action="{{ route('seller.pos.tables.toggle', $table->id) }}" style="display:inline">
                                    @csrf
                                    <button style="background:none; border:none; padding:0; cursor:pointer;">
                                        @if($table->status === 'free')
                                        <span class="s-badge s-badge-green" style="font-size: 9px;">Libre</span>
                                        @else
                                        <span class="s-badge s-badge-yellow" style="font-size: 9px;">Ocupada</span>
                                        @endif
                                    </button>
                                </form>
                                <span style="font-size: 10px; color: var(--s-text-muted);">
                                    Sin Posición
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div id="zone-empty" style="display: none; text-align: center; padding: 40px 20px; color: var(--s-text-muted);">
                    <i class="las la-table" style="font-size: 40px; display: block; margin-bottom: 10px; color: var(--s-border);"></i>
                    No hay mesas en esta zona
                </div>
            </div>
        </div>

    </div>
</div>

@push('script')
<script>
function filterZone(zone, btn) {
    document.querySelectorAll('.zone-filter').forEach(function(b) {
        b.classList.remove('s-btn-primary');
        b.classList.add('s-btn-ghost');
        b.classList.remove('active');
    });
    btn.classList.remove('s-btn-ghost');
    btn.classList.add('s-btn-primary');
    btn.classList.add('active');

    var groups = document.querySelectorAll('.zone-group');
    var visible = 0;
    groups.forEach(function(g) {
        if (zone === 'all' || g.dataset.zone === zone) {
            g.style.display = '';
            visible++;
        } else {
            g.style.display = 'none';
        }
    });

    var empty = document.getElementById('zone-empty');
    if (empty) empty.style.display = visible === 0 ? '' : 'none';
}
</script>
@endpush
@endsection
