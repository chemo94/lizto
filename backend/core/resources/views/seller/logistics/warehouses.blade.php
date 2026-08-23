@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-warehouse"></i></span> Almacenes y Control de Stock
@endsection

@section('seller-content')
<div class="s-content">
    <section class="module-hero logistics"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Logística &nbsp;/&nbsp; Almacenes</div><h2><i class="las la-warehouse"></i> Almacenes y Stock</h2><p>Administra ubicaciones y consulta la distribución de existencias.</p></div><div class="module-hero-stats"><div><b>{{ $warehouses->count() }}</b><small>Almacenes</small></div><div><b>{{ $items->count() }}</b><small>Insumos</small></div><div><b>{{ $warehouses->where('status','active')->count() }}</b><small>Activos</small></div></div></section>

    <div class="s-grid-3 logistics-workspace" style="margin-bottom:14px;gap:14px">
        <!-- AGREGAR ALMACÉN -->
        <div class="s-card seller-work-card" style="grid-column: span 1">
            <h3 class="s-card-title"><i class="las la-plus"></i> Nuevo Almacén</h3>
            <form method="POST" action="{{ route('seller.logistics.warehouses.store') }}" class="s-form-grid">
                @csrf
                <div class="s-input-group">
                    <label class="s-input-label">Nombre del Almacén *</label>
                    <input class="s-input" name="name" placeholder="Ej: Almacén Barra, Depósito Principal" required>
                </div>
                <div class="s-input-group">
                    <label class="s-input-label">Dirección / Ubicación</label>
                    <input class="s-input" name="address" placeholder="Ej: Av. Larco 456">
                </div>
                <div class="s-input-group" style="flex-direction:row;align-items:center;gap:10px;margin-top:5px">
                    <input type="checkbox" id="is_default" name="is_default" style="width:16px;height:16px">
                    <label for="is_default" class="s-input-label" style="margin:0;cursor:pointer">Establecer como Predeterminado</label>
                </div>
                <button class="s-btn s-btn-primary" style="width:100%;margin-top:10px">
                    <i class="las la-save"></i> Registrar Almacén
                </button>
            </form>
        </div>

        <!-- LISTADO DE ALMACENES -->
        <div class="s-card seller-work-card" style="grid-column: span 2">
            <h3 class="s-card-title"><i class="las la-list"></i> Almacenes Registrados</h3>
            <div class="s-table-responsive">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Predeterminado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($warehouses as $w)
                        <tr>
                            <td><b>{{ $w->name }}</b></td>
                            <td>{{ $w->address ?: '—' }}</td>
                            <td>
                                @if($w->is_default)
                                <span class="s-badge s-badge-green"><i class="las la-check"></i> Predeterminado</span>
                                @else
                                <span class="s-badge s-badge-gray">Secundario</span>
                                @endif
                            </td>
                            <td>
                                <span class="s-badge {{ $w->status === 'active' ? 's-badge-green' : 's-badge-red' }}">
                                    {{ $w->status === 'active' ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <button class="s-btn s-btn-outline s-btn-xs" onclick="openEditWarehouseModal({{ json_encode($w) }})">
                                        <i class="las la-edit"></i>
                                    </button>
                                    @if(!$w->is_default)
                                    <form method="POST" action="{{ route('seller.logistics.warehouses.delete', $w->id) }}" onsubmit="return confirm('¿Eliminar este almacén? Se perderán las relaciones de stock.')">
                                        @csrf
                                        <button class="s-btn s-btn-danger s-btn-xs">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- STOCK POR ALMACÉN -->
    <div class="s-card seller-work-card">
        <h3 class="s-card-title"><i class="las la-boxes"></i> Matriz de Stock por Almacén</h3>
        <div class="s-table-responsive" style="margin-top:15px">
            <table class="s-table">
                <thead>
                    <tr>
                        <th>Insumo / Producto</th>
                        <th>Unidad</th>
                        @foreach($warehouses as $w)
                        <th>{{ $w->name }}</th>
                        @endforeach
                        <th>Stock Global</th>
                        <th>Stock Mín.</th>
                        <th>Estado Alerta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            <b>{{ $item->name }}</b>
                            <br><small style="color:var(--s-text-3)">{{ ucfirst($item->category ?? 'Sin Categoría') }}</small>
                        </td>
                        <td><span class="s-badge s-badge-gray">{{ $item->unit }}</span></td>
                        
                        @foreach($warehouses as $w)
                        <td>
                            @php
                                $wStock = $stocks->where('warehouse_id', $w->id)->where('item_id', $item->id)->first();
                                $qty = $wStock ? $wStock->stock : 0;
                            @endphp
                            <span style="font-weight:600; color: {{ $qty <= 0 ? 'var(--s-danger)' : 'var(--s-text)' }}">
                                {{ number_format($qty, 2) }}
                            </span>
                        </td>
                        @endforeach

                        <td>
                            <b style="font-size:14px">{{ number_format($item->stock, 2) }}</b>
                        </td>
                        <td>{{ number_format($item->min_stock, 2) }}</td>
                        <td>
                            @if($item->stock <= 0)
                            <span class="s-badge s-badge-red">Agotado</span>
                            @elseif($item->min_stock > 0 && $item->stock <= $item->min_stock)
                            <span class="s-badge s-badge-amber">Stock Bajo</span>
                            @else
                            <span class="s-badge s-badge-green">Normal</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL EDITAR ALMACÉN -->
<div class="s-modal" id="edit-warehouse-modal">
    <div class="s-modal-bg" onclick="closeEditWarehouseModal()"></div>
    <div class="s-modal-box">
        <div class="s-modal-head">
            <h3 class="s-modal-title">Editar Almacén</h3>
            <button class="s-modal-close" onclick="closeEditWarehouseModal()">✕</button>
        </div>
        <div class="s-modal-body">
            <form id="edit-warehouse-form" method="POST" action="">
                @csrf
                <div class="s-form-grid">
                    <div class="s-input-group">
                        <label class="s-input-label">Nombre del Almacén *</label>
                        <input class="s-input" id="edit-w-name" name="name" required>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Dirección</label>
                        <input class="s-input" id="edit-w-address" name="address">
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Estado</label>
                        <select class="s-input" id="edit-w-status" name="status">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                        </select>
                    </div>
                    <div class="s-input-group" style="flex-direction:row;align-items:center;gap:10px">
                        <input type="checkbox" id="edit-w-default" name="is_default" style="width:16px;height:16px">
                        <label for="edit-w-default" class="s-input-label" style="margin:0;cursor:pointer">Establecer como Predeterminado</label>
                    </div>
                </div>
                <button class="s-btn s-btn-primary" style="margin-top:20px;width:100%">
                    Guardar Cambios
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openEditWarehouseModal(warehouse) {
    const modal = document.getElementById('edit-warehouse-modal');
    const form = document.getElementById('edit-warehouse-form');
    
    form.action = `/seller/logistics/warehouses/${warehouse.id}/update`;
    
    document.getElementById('edit-w-name').value = warehouse.name;
    document.getElementById('edit-w-address').value = warehouse.address || '';
    document.getElementById('edit-w-status').value = warehouse.status;
    document.getElementById('edit-w-default').checked = warehouse.is_default ? true : false;
    
    modal.classList.add('open');
}

function closeEditWarehouseModal() {
    document.getElementById('edit-warehouse-modal').classList.remove('open');
}
</script>
@endsection
