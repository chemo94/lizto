@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-tags"></i></span> Categorías
@endsection

@section('seller-content')
<div class="s-content">
    <div style="max-width:720px">
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
