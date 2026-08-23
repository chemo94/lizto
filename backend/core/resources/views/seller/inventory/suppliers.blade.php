@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-truck"></i></span> Proveedores
@endsection

@section('seller-content')
<div class="s-content">
    <section class="module-hero suppliers"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Abastecimiento &nbsp;/&nbsp; Proveedores</div><h2><i class="las la-truck-loading"></i> Gestión de Proveedores</h2><p>Centraliza contactos, documentos y socios de abastecimiento.</p></div><div class="module-hero-stats"><div><b>{{ $suppliers->count() }}</b><small>Proveedores</small></div><div><b>{{ $suppliers->where('document_type','6')->count() }}</b><small>Empresas RUC</small></div><div><b>{{ $suppliers->whereNotNull('phone')->count() }}</b><small>Con contacto</small></div></div></section>

    <!-- ADD FORM -->
    <div class="s-card seller-work-card" style="margin-bottom:14px">
        <h3 class="s-card-title"><i class="las la-plus-circle"></i> Agregar Proveedor</h3>
        <form method="POST" action="{{ route('seller.inventory.suppliers.store') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            @csrf
            <div class="s-input-group" style="flex:1;min-width:180px">
                <label class="s-input-label">Nombre / Razón Social *</label>
                <input class="s-input" id="supplier-name" name="name" placeholder="Ej: Distribuidora Norte SAC" required>
            </div>
            <div class="s-input-group" style="min-width:240px">
                <label class="s-input-label">Documento</label>
                @include('seller.partials.sunat_lookup', [
                    'prefix'      => 'supplier',
                    'defaultType' => '6',
                    'nameTarget'  => 'supplier-name',
                    'tpdocName'   => 'document_type',
                    'numdocName'  => 'document_number',
                ])
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Teléfono</label>
                <input class="s-input" name="phone" placeholder="999 999 999" style="width:140px">
            </div>
            <button class="s-btn s-btn-primary" style="margin-bottom:1px">
                <i class="las la-plus"></i> Agregar
            </button>
        </form>
    </div>

    <!-- LIST -->
    <div class="s-card seller-work-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
            <h3 class="s-card-title" style="margin:0"><i class="las la-truck"></i> Proveedores</h3>
            <div style="display:flex;align-items:center;gap:8px"><input id="supplierSearch" class="s-input" type="search" placeholder="Buscar proveedor..." style="width:220px;height:36px;font-size:10px"><span class="s-badge s-badge-blue">{{ $suppliers->count() }} registros</span></div>
        </div>

        @if($suppliers->count())
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px">
            @foreach($suppliers as $s)
            <div class="supplier-data-card" style="border:1.5px solid var(--s-border);border-radius:14px;padding:16px;transition:all .2s" onmouseenter="this.style.borderColor='var(--s-accent)';this.style.boxShadow='var(--s-shadow)'" onmouseleave="this.style.borderColor='var(--s-border)';this.style.boxShadow=''">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0">
                        <div style="width:42px;height:42px;border-radius:12px;background:var(--s-info-bg);display:grid;place-items:center;font-size:20px;flex-shrink:0">🏢</div>
                        <div style="min-width:0">
                            <b style="font-size:14px;color:var(--s-text);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $s->name }}</b>
                            <div style="display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap">
                                <span class="s-badge s-badge-blue" style="font-size:9px">{{ $s->document_type === '6' ? 'RUC' : 'DNI' }}</span>
                                <span style="font-size:12px;color:var(--s-text-3)">{{ $s->document_number ?: '—' }}</span>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('seller.inventory.suppliers.delete', $s->id) }}" onsubmit="return confirm('¿Eliminar proveedor {{ addslashes($s->name) }}?')">
                        @csrf
                        <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" title="Eliminar">
                            <i class="las la-trash"></i>
                        </button>
                    </form>
                </div>
                @if($s->phone || $s->email)
                <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--s-border);display:flex;flex-wrap:wrap;gap:10px">
                    @if($s->phone)
                    <span style="font-size:12px;color:var(--s-text-3)"><i class="las la-phone"></i> {{ $s->phone }}</span>
                    @endif
                    @if($s->email)
                    <span style="font-size:12px;color:var(--s-text-3)"><i class="las la-envelope"></i> {{ $s->email }}</span>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="s-empty">
            <i class="las la-truck"></i>
            <p>Sin proveedores registrados. Agrega el primero arriba.</p>
        </div>
        @endif
    </div>
</div>
@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var search = document.getElementById('supplierSearch');
    if (!search) return;
    search.addEventListener('input', function() {
        var query = search.value.trim().toLowerCase();
        document.querySelectorAll('.supplier-data-card').forEach(function(card) {
            card.style.display = !query || card.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
    });
});
</script>
@endpush
@endsection
