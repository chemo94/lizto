@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-exchange-alt"></i></span> Transferencias entre Almacenes
@endsection

@section('seller-content')
<div class="s-content">

    <div class="s-grid-2" style="grid-template-columns: 1fr 1.3fr; gap: 24px; align-items: start;">
        
        <!-- REGISTRAR TRANSFERENCIA -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-plus-circle"></i> Nueva Transferencia</h3>

            <form method="POST" action="{{ route('seller.logistics.transfers.store') }}" onsubmit="return validateTransferForm(event)">
                @csrf
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <label class="s-input-label">Almacén Origen *</label>
                        <select class="s-input" id="from_warehouse_id" name="from_warehouse_id" required>
                            <option value="">Seleccionar origen...</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="s-input-label">Almacén Destino *</label>
                        <select class="s-input" id="to_warehouse_id" name="to_warehouse_id" required>
                            <option value="">Seleccionar destino...</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="margin-top: 20px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <label class="s-input-label" style="margin:0;font-weight:700">Insumos a Transferir</label>
                    <button type="button" class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-accent-dark)" onclick="addTransferRow()">
                        <i class="las la-plus"></i> Agregar Insumo
                    </button>
                </div>

                <div style="margin-bottom: 16px; border: 1px solid var(--s-border); border-radius: 8px; max-height: 250px; overflow-y: auto;">
                    <table class="s-table">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th style="width: 100px; text-align: center;">Cantidad</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="transfer-items">
                            <!-- Items dinámicos -->
                        </tbody>
                    </table>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="s-input-label">Notas o Justificación</label>
                    <textarea class="s-input" name="notes" placeholder="Motivo de la transferencia..." rows="2"></textarea>
                </div>

                <input type="hidden" name="items" id="transfer-items-json">
                
                <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; gap: 8px;">
                    <i class="las la-exchange-alt" style="font-size: 16px;"></i> Registrar Transferencia
                </button>
            </form>
        </div>

        <!-- HISTORIAL Y ACCIONES -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-history"></i> Historial de Envios</h3>

            <div class="s-table-responsive">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>ID / Fecha</th>
                            <th>Origen ➔ Destino</th>
                            <th>Items</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $t)
                        <tr>
                            <td>
                                <b>#{{ $t->id }}</b>
                                <br><small style="color:var(--s-text-3)">{{ $t->created_at->format('d/m/Y H:i') }}</small>
                            </td>
                            <td>
                                <span style="font-weight:600;color:var(--s-danger)">{{ $t->fromWarehouse->name }}</span>
                                <br><i class="las la-arrow-down" style="font-size:10px"></i><br>
                                <span style="font-weight:600;color:var(--s-success-text)">{{ $t->toWarehouse->name }}</span>
                            </td>
                            <td>{{ $t->items->count() }} insumos</td>
                            <td>
                                @php
                                    $badge = match($t->status) {
                                        'pending' => 's-badge-amber',
                                        'sent' => 's-badge-purple',
                                        'received' => 's-badge-green',
                                        default => 's-badge-red',
                                    };
                                @endphp
                                <span class="s-badge {{ $badge }}">
                                    {{ App\Models\InvWarehouseTransfer::statuses()[$t->status] }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;flex-wrap:wrap">
                                    @if($t->status === 'pending')
                                    <form method="POST" action="{{ route('seller.logistics.transfers.status', $t->id) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="sent">
                                        <button class="s-btn s-btn-primary s-btn-xs">Enviar Stock</button>
                                    </form>
                                    @endif

                                    @if($t->status === 'sent')
                                    <form method="POST" action="{{ route('seller.logistics.transfers.status', $t->id) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="received">
                                        <button class="s-btn s-btn-green s-btn-xs" style="background:#22c55e!important;color:#fff!important">Recibir Stock</button>
                                    </form>
                                    @endif

                                    @if($t->status === 'pending' || $t->status === 'sent')
                                    <form method="POST" action="{{ route('seller.logistics.transfers.status', $t->id) }}" onsubmit="return confirm('¿Anular transferencia?')">
                                        @csrf
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" title="Anular">Anular</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:30px">
                                Sin transferencias registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:15px">
                {{ $transfers->links() }}
            </div>
        </div>

    </div>

</div>

@push('script')
<script>
var itemsSource = @json($items->map(fn($i) => ['id'=>$i->id,'name'=>$i->name]));

function addTransferRow() {
    var tbody = document.getElementById('transfer-items');
    if (!itemsSource.length) {
        alert("Primero registre insumos en la base de datos.");
        return;
    }
    
    var tr = document.createElement('tr');
    
    // Insumo select
    var tdSel = document.createElement('td');
    var select = document.createElement('select');
    select.className = 's-input';
    select.style.padding = '4px 8px';
    select.style.height = '32px';
    select.required = true;
    select.onchange = updateTransferTotal;
    
    var optDefault = document.createElement('option');
    optDefault.value = '';
    optDefault.textContent = 'Seleccionar...';
    select.appendChild(optDefault);
    
    itemsSource.forEach(function(o) {
        var opt = document.createElement('option');
        opt.value = o.id;
        opt.textContent = o.name;
        select.appendChild(opt);
    });
    tdSel.appendChild(select);
    tr.appendChild(tdSel);
    
    // Cantidad
    var tdQty = document.createElement('td');
    var inputQty = document.createElement('input');
    inputQty.className = 's-input';
    inputQty.type = 'number';
    inputQty.step = '0.01';
    inputQty.value = '1';
    inputQty.style.padding = '4px 8px';
    inputQty.style.height = '32px';
    inputQty.style.textAlign = 'center';
    inputQty.required = true;
    inputQty.oninput = updateTransferTotal;
    tdQty.appendChild(inputQty);
    tr.appendChild(tdQty);
    
    // Delete action
    var tdDel = document.createElement('td');
    tdDel.style.textAlign = 'center';
    var btnDel = document.createElement('button');
    btnDel.type = 'button';
    btnDel.className = 's-btn s-btn-ghost s-btn-xs';
    btnDel.style.color = 'var(--s-danger)';
    btnDel.innerHTML = '<i class="las la-times" style="font-size:16px;"></i>';
    btnDel.onclick = function() {
        tr.remove();
        updateTransferTotal();
    };
    tdDel.appendChild(btnDel);
    tr.appendChild(tdDel);
    
    tbody.appendChild(tr);
    updateTransferTotal();
}

function updateTransferTotal() {
    var rows = [];
    
    document.querySelectorAll('#transfer-items tr').forEach(function(tr) {
        var sel = tr.querySelector('select'); 
        var qtyInput = tr.querySelector('input'); 
        
        if (sel && qtyInput) {
            var qty = parseFloat(qtyInput.value) || 0;
            if (sel.value) {
                rows.push({
                    item_id: parseInt(sel.value), 
                    quantity: qty
                });
            }
        }
    });
    
    document.getElementById('transfer-items-json').value = JSON.stringify(rows);
}

function validateTransferForm(e) {
    var val = document.getElementById('transfer-items-json').value;
    var fromW = document.getElementById('from_warehouse_id').value;
    var toW = document.getElementById('to_warehouse_id').value;

    if (fromW === toW) {
        alert("El almacén de origen y destino deben ser diferentes.");
        e.preventDefault();
        return false;
    }

    if (!val || JSON.parse(val).length === 0) {
        alert("Debe agregar al menos un insumo.");
        e.preventDefault();
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    addTransferRow();
});
</script>
@endpush
@endsection
