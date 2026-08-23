@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-tags"></i></span> Categorías
@endsection

@section('seller-content')
<style>
.category-head{min-height:118px;margin-bottom:16px;padding:20px 22px;border-radius:14px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:18px;background:linear-gradient(100deg,#6b2fad,#8d50c5 58%,#b58add)}.category-head h2{font:800 22px 'Plus Jakarta Sans','Inter',sans-serif;margin:0 0 4px}.category-head p{font-size:10px;color:rgba(255,255,255,.72);margin:0}.category-count{min-width:105px;padding:12px 15px;text-align:center;border:1px solid rgba(255,255,255,.2);border-radius:11px;background:rgba(255,255,255,.11)}.category-count b{display:block;font-size:20px}.category-count small{font-size:8px;color:rgba(255,255,255,.72)}.category-workspace{max-width:980px!important;display:grid;grid-template-columns:minmax(280px,.8fr) minmax(420px,1.4fr);gap:14px;align-items:start}.category-workspace>.s-card{margin:0!important;border-radius:14px;box-shadow:var(--s-shadow-sm)}@media(max-width:850px){.category-workspace{grid-template-columns:1fr}.category-head{align-items:flex-start}}
</style>
<div class="s-content">
    <section class="category-head"><div><h2><i class="las la-tags"></i> Categorías del Menú</h2><p>Organiza los productos para agilizar la navegación del POS y de tu tienda.</p></div><div class="category-count"><b>{{ $categories->count() }}</b><small>Categorías creadas</small></div></section>
    <div class="category-workspace">
        <!-- FORM ADD -->
        <div class="s-card" style="margin-bottom:20px">
            <h3 class="s-card-title"><i class="las la-plus-circle"></i> Nueva Categoría</h3>
            <form method="POST" action="{{ route('seller.categories.store') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                @csrf
                <div class="s-input-group" style="flex:1;min-width:180px">
                    <label class="s-input-label">Nombre de la categoría *</label>
                    <input class="s-input" name="name" placeholder="Ej: Entradas, Bebidas..." required>
                </div>
                <div class="s-input-group">
                    <label class="s-input-label">Orden</label>
                    <input class="s-input" type="number" name="sort_order" value="0" style="width:90px">
                </div>
                <button class="s-btn s-btn-primary" style="margin-bottom:1px">
                    <i class="las la-plus"></i> Agregar
                </button>
            </form>
        </div>

        <!-- LIST -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-layer-group"></i> Categorías ({{ $categories->count() }})</h3>
            @forelse($categories as $cat)
            <div style="display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid var(--s-border);transition:background .15s" onmouseenter="this.style.background='var(--s-surface-2)'" onmouseleave="this.style.background=''">
                <!-- Drag handle -->
                <div style="color:var(--s-text-3);cursor:grab;font-size:18px;padding:0 2px">
                    <i class="las la-grip-vertical"></i>
                </div>
                <!-- Info -->
                <div style="flex:1;min-width:0">
                    <span style="font-size:14px;font-weight:700;color:var(--s-text)">{{ $cat->name }}</span>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
                        <span class="s-badge {{ $cat->status ? 's-badge-green' : 's-badge-red' }}" style="font-size:9px">
                            {{ $cat->status ? 'Activa' : 'Inactiva' }}
                        </span>
                        <span style="font-size:11px;color:var(--s-text-3)">Orden #{{ $cat->sort_order }}</span>
                    </div>
                </div>
                <!-- Edit form -->
                <form method="POST" action="{{ route('seller.categories.update', $cat->id) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                    @csrf
                    <input class="s-input" name="name" value="{{ $cat->name }}" style="width:150px;padding:8px 10px;font-size:12px">
                    <input class="s-input" type="number" name="sort_order" value="{{ $cat->sort_order }}" style="width:65px;padding:8px 10px;font-size:12px">
                    <label style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--s-text-2);cursor:pointer;white-space:nowrap">
                        <input type="checkbox" name="status" {{ $cat->status ? 'checked' : '' }} style="accent-color:var(--s-accent);width:14px;height:14px"> Activa
                    </label>
                    <button class="s-btn s-btn-primary s-btn-xs" title="Guardar">
                        <i class="las la-save"></i>
                    </button>
                </form>
                <form method="POST" action="{{ route('seller.categories.delete', $cat->id) }}" onsubmit="return confirm('¿Eliminar la categoría {{ addslashes($cat->name) }}?')">
                    @csrf
                    <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" title="Eliminar">
                        <i class="las la-trash"></i>
                    </button>
                </form>
            </div>
            @empty
            <div class="s-empty">
                <i class="las la-tags"></i>
                <p>Aún no tienes categorías. ¡Crea la primera arriba!</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
