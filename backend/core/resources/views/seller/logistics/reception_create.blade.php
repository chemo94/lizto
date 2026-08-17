@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; box-shadow: 0 4px 12px rgba(37,99,235,0.2);">
    <i class="las la-dolly"></i>
</span> 
<span style="font-weight: 800; letter-spacing: -0.5px;">Registrar Recepción de Mercadería</span>
@endsection

@section('seller-content')
<div class="s-content" style="padding: 24px; background: #f8fafc; min-height: 100vh;">

    <div class="s-card" style="max-width: 1000px; margin: 0 auto; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05); border: 1px solid var(--s-border); overflow: hidden; background: #fff;">
        
        <!-- Header Banner -->
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); padding: 24px; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
                    <i class="las la-dolly-flatbed" style="font-size: 24px; color: #3b82f6;"></i> Control de Ingreso y Recepción
                </h3>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8; font-weight: 500;">Registra el ingreso físico de los insumos y actualiza el stock en almacén</p>
            </div>
            
            @if($order)
            <div style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 10px; padding: 8px 16px; font-size: 12px; color: #93c5fd; display: flex; align-items: center; gap: 8px;">
                <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; box-shadow: 0 0 8px #3b82f6; animation: pulse 2s infinite;"></div>
                <span>Orden de Compra: <b>{{ $order->order_number }}</b></span>
            </div>
            @endif
        </div>

        <form method="POST" action="{{ route('seller.logistics.receptions.store') }}" onsubmit="return validateReceptionForm(event)" style="padding: 24px;">
            @csrf
            
            @if($order)
            <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">
            <div style="background: rgba(30, 41, 59, 0.03); border: 1px solid var(--s-border); border-left: 4px solid #3b82f6; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; color: var(--s-text-primary); display: flex; flex-direction: column; gap: 4px;">
                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; color: #1e293b;">
                    <i class="las la-building" style="font-size: 16px; color: #3b82f6;"></i> Información del Proveedor
                </div>
                <div>Razón Social: <b style="font-weight: 600;">{{ $order->supplier->name }}</b></div>
                <div style="font-size: 11px; color: var(--s-text-muted);">El stock recibido será ingresado directamente al almacén destino seleccionado.</div>
            </div>
            @endif

            <!-- Form Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px dashed var(--s-border);">
                <div>
                    <label class="s-input-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">Almacén de Ingreso *</label>
                    <select class="s-input" name="warehouse_id" required style="background-color: #fff; height: 38px; border-radius: 8px; border-color: var(--s-border); font-size: 13px;">
                        @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" {{ ($order && $order->warehouse_id == $w->id) || $w->is_default ? 'selected' : '' }}>
                            {{ $w->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="s-input-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">Fecha de Ingreso *</label>
                    <input class="s-input" type="date" name="reception_date" value="{{ now()->format('Y-m-d') }}" required style="background-color: #fff; height: 38px; border-radius: 8px; border-color: var(--s-border); font-size: 13px;">
                </div>
                <div>
                    <label class="s-input-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">Tipo Documento Ref.</label>
                    <select class="s-input" name="document_type" style="background-color: #fff; height: 38px; border-radius: 8px; border-color: var(--s-border); font-size: 13px;">
                        <option value="09">Guía de Remisión</option>
                        <option value="01">Factura</option>
                        <option value="03">Boleta de Venta</option>
                    </select>
                </div>
                <div>
                    <label class="s-input-label" style="font-weight: 700; color: #334155; margin-bottom: 6px;">Número Documento Ref.</label>
                    <input class="s-input" name="document_number" placeholder="Ej: T001-000124" style="background-color: #fff; height: 38px; border-radius: 8px; border-color: var(--s-border); font-size: 13px;">
                </div>
            </div>

            <!-- Details Section -->
            <div style="margin-top: 24px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
                <label class="s-input-label" style="margin:0; font-weight: 800; font-size: 14px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">Detalle de Recepción</label>
                @if(!$order)
                <button type="button" class="s-btn s-btn-primary s-btn-xs" style="height: 32px; border-radius: 6px; display: flex; align-items: center; gap: 6px; padding: 0 12px; font-weight: 700; background: #3b82f6; border: none; box-shadow: 0 2px 4px rgba(59,130,246,0.2);" onclick="addReceptionRow()">
                    <i class="las la-plus" style="font-size: 14px;"></i> Agregar Insumo
                </button>
                @endif
            </div>

            <!-- Table -->
            <div class="s-table-responsive" style="margin-bottom: 24px; border: 1px solid var(--s-border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); overflow: hidden;">
                <table class="s-table" style="margin-bottom: 0;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 14px; font-weight: 700; color: #475569; font-size: 12px;">Insumo / Producto</th>
                            <th style="width: 90px; text-align: center; padding: 14px; font-weight: 700; color: #475569; font-size: 12px;">Ordenado</th>
                            <th style="width: 110px; text-align: center; padding: 14px; font-weight: 700; color: #475569; font-size: 12px;">Recibido</th>
                            <th style="width: 110px; text-align: center; padding: 14px; font-weight: 700; color: #475569; font-size: 12px;">Dañado / Mermado</th>
                            <th style="width: 140px; text-align: right; padding: 14px; font-weight: 700; color: #475569; font-size: 12px;">Costo Compra</th>
                            <th style="width: 160px; padding: 14px; font-weight: 700; color: #475569; font-size: 12px;">F. Vencimiento</th>
                            @if(!$order)
                            <th style="width: 50px; padding: 14px;"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="reception-items" style="background: #fff;">
                        <!-- Dynamic items -->
                    </tbody>
                </table>
            </div>

            <!-- Notes Section -->
            <div style="margin-bottom: 24px; background: #f8fafc; padding: 16px; border-radius: 10px; border: 1px solid var(--s-border);">
                <label class="s-input-label" style="font-weight: 700; color: #475569; margin-bottom: 6px; display: block;">Comentarios / Observaciones de la Entrega</label>
                <textarea class="s-input" name="notes" placeholder="Escribe notas sobre productos faltantes, daños o cualquier observación relevante sobre el estado del envío..." rows="2" style="background: #fff; border-radius: 8px; font-size: 13px; padding: 10px; border-color: var(--s-border);"></textarea>
            </div>

            <input type="hidden" name="items" id="reception-items-json">
            
            <!-- Actions -->
            <div style="display: flex; gap: 12px; align-items: center; margin-top: 24px;">
                <a href="{{ route('seller.logistics.receptions') }}" class="s-btn s-btn-outline" style="flex: 1; justify-content: center; height: 42px; border-radius: 8px; font-weight: 700;">
                    Cancelar
                </a>
                <button type="submit" class="s-btn s-btn-success" style="flex: 2; justify-content: center; height: 42px; border-radius: 8px; font-weight: 800; background: #22c55e; border: none; box-shadow: 0 4px 12px rgba(34,197,94,0.2);">
                    <i class="las la-check-circle" style="font-size: 18px;"></i> Registrar Ingreso e Inventariar
                </button>
            </div>
        </form>
    </div>

</div>

@php
    $jsItems = $items->map(function($i) {
        return [
            'id' => $i->id,
            'name' => $i->name,
            'cost' => $i->cost
        ];
    });
    $jsOrderItems = [];
    if ($order) {
        $jsOrderItems = $order->items->map(function($oi) {
            return [
                'id' => $oi->item_id,
                'name' => $oi->item ? $oi->item->name : '',
                'qty' => $oi->quantity,
                'cost' => $oi->unit_cost
            ];
        });
    }
@endphp

@push('script')
<script>
var itemsSource = @json($jsItems);
var orderItems = @json($jsOrderItems);

function addReceptionRow(prefilled = null) {
    var tbody = document.getElementById('reception-items');
    var tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid #f1f5f9';
    tr.style.transition = 'background 0.15s';
    tr.onmouseover = function() { tr.style.background = '#f8fafc'; };
    tr.onmouseout = function() { tr.style.background = '#fff'; };
    
    // Insumo select
    var tdSel = document.createElement('td');
    tdSel.style.padding = '10px 14px';
    if (orderItems.length > 0 && prefilled) {
        tdSel.innerHTML = `<span style="font-weight:700; color:#1e293b;">${prefilled.name}</span><input type="hidden" class="item-select" value="${prefilled.id}">`;
    } else {
        var select = document.createElement('select');
        select.className = 's-input item-select';
        select.style.padding = '4px 8px';
        select.style.height = '34px';
        select.style.fontSize = '13px';
        select.style.borderRadius = '6px';
        select.style.background = '#fff';
        select.style.borderColor = 'var(--s-border)';
        select.required = true;
        select.onchange = function() {
            var opt = select.options[select.selectedIndex];
            var cost = opt.getAttribute('data-cost') || 0;
            inputCost.value = parseFloat(cost).toFixed(2);
            updateReceptionJSON();
        };
        
        var optDefault = document.createElement('option');
        optDefault.value = '';
        optDefault.textContent = 'Seleccionar Insumo...';
        select.appendChild(optDefault);
        
        itemsSource.forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.name;
            opt.setAttribute('data-cost', o.cost);
            select.appendChild(opt);
        });
        tdSel.appendChild(select);
    }
    tr.appendChild(tdSel);
    
    // Cantidad Ordenada
    var tdQtyOrd = document.createElement('td');
    tdQtyOrd.style.textAlign = 'center';
    tdQtyOrd.style.padding = '10px 14px';
    var qtyOrdVal = prefilled ? prefilled.qty : 0;
    tdQtyOrd.innerHTML = `<span class="s-badge s-badge-gray" style="font-weight:700; font-size:12px; padding: 4px 8px; border-radius:6px; background:#f1f5f9; color:#475569;">${qtyOrdVal}</span>`;
    tr.appendChild(tdQtyOrd);
    
    // Cantidad Recibida
    var tdQtyRec = document.createElement('td');
    tdQtyRec.style.padding = '10px 14px';
    var inputQtyRec = document.createElement('input');
    inputQtyRec.className = 's-input qty-received';
    inputQtyRec.type = 'number';
    inputQtyRec.step = '0.01';
    inputQtyRec.value = prefilled ? prefilled.qty : '1';
    inputQtyRec.style.padding = '4px 8px';
    inputQtyRec.style.height = '34px';
    inputQtyRec.style.fontSize = '13px';
    inputQtyRec.style.borderRadius = '6px';
    inputQtyRec.style.textAlign = 'center';
    inputQtyRec.style.background = '#fff';
    inputQtyRec.style.borderColor = 'var(--s-border)';
    inputQtyRec.required = true;
    inputQtyRec.oninput = updateReceptionJSON;
    tdQtyRec.appendChild(inputQtyRec);
    tr.appendChild(tdQtyRec);

    // Cantidad Dañada
    var tdQtyDmg = document.createElement('td');
    tdQtyDmg.style.padding = '10px 14px';
    var inputQtyDmg = document.createElement('input');
    inputQtyDmg.className = 's-input qty-damaged';
    inputQtyDmg.type = 'number';
    inputQtyDmg.step = '0.01';
    inputQtyDmg.value = '0';
    inputQtyDmg.style.padding = '4px 8px';
    inputQtyDmg.style.height = '34px';
    inputQtyDmg.style.fontSize = '13px';
    inputQtyDmg.style.borderRadius = '6px';
    inputQtyDmg.style.textAlign = 'center';
    inputQtyDmg.style.background = '#fff';
    inputQtyDmg.style.borderColor = 'var(--s-border)';
    inputQtyDmg.oninput = updateReceptionJSON;
    tdQtyDmg.appendChild(inputQtyDmg);
    tr.appendChild(tdQtyDmg);
    
    // Costo unitario compra
    var tdCost = document.createElement('td');
    tdCost.style.padding = '10px 14px';
    var inputCost = document.createElement('input');
    inputCost.className = 's-input unit-cost';
    inputCost.type = 'number';
    inputCost.step = '0.01';
    inputCost.value = prefilled ? parseFloat(prefilled.cost).toFixed(2) : '0.00';
    inputCost.style.padding = '4px 8px';
    inputCost.style.height = '34px';
    inputCost.style.fontSize = '13px';
    inputCost.style.borderRadius = '6px';
    inputCost.style.textAlign = 'right';
    inputCost.style.background = '#fff';
    inputCost.style.borderColor = 'var(--s-border)';
    inputCost.oninput = updateReceptionJSON;
    tdCost.appendChild(inputCost);
    tr.appendChild(tdCost);

    // Expiration date
    var tdExp = document.createElement('td');
    tdExp.style.padding = '10px 14px';
    var inputExp = document.createElement('input');
    inputExp.className = 's-input expiration-date';
    inputExp.type = 'date';
    inputExp.style.padding = '4px 8px';
    inputExp.style.height = '34px';
    inputExp.style.fontSize = '12px';
    inputExp.style.borderRadius = '6px';
    inputExp.style.background = '#fff';
    inputExp.style.borderColor = 'var(--s-border)';
    inputExp.oninput = updateReceptionJSON;
    tdExp.appendChild(inputExp);
    tr.appendChild(tdExp);
    
    // Delete action if direct reception
    if (orderItems.length === 0) {
        var tdDel = document.createElement('td');
        tdDel.style.padding = '10px 14px';
        tdDel.style.textAlign = 'center';
        var btnDel = document.createElement('button');
        btnDel.type = 'button';
        btnDel.className = 's-btn s-btn-ghost s-btn-xs';
        btnDel.style.color = 'var(--s-danger)';
        btnDel.style.padding = '0';
        btnDel.style.width = '30px';
        btnDel.style.height = '30px';
        btnDel.style.borderRadius = '50%';
        btnDel.style.display = 'flex';
        btnDel.style.alignItems = 'center';
        btnDel.style.justifyContent = 'center';
        btnDel.style.border = '1px solid transparent';
        btnDel.style.transition = 'all 0.2s';
        btnDel.onmouseover = function() {
            btnDel.style.borderColor = 'rgba(239, 68, 68, 0.2)';
            btnDel.style.background = 'rgba(239, 68, 68, 0.05)';
        };
        btnDel.onmouseout = function() {
            btnDel.style.borderColor = 'transparent';
            btnDel.style.background = 'transparent';
        };
        btnDel.innerHTML = '<i class="las la-trash-alt" style="font-size:18px;"></i>';
        btnDel.onclick = function() {
            tr.remove();
            updateReceptionJSON();
        };
        tdDel.appendChild(btnDel);
        tr.appendChild(tdDel);
    }
    
    tbody.appendChild(tr);
    updateReceptionJSON();
}

function updateReceptionJSON() {
    var rows = [];
    
    document.querySelectorAll('#reception-items tr').forEach(function(tr) {
        var sel = tr.querySelector('.item-select'); 
        var qtyOrdSpan = tr.querySelectorAll('td')[1].querySelector('span');
        var qtyRecInput = tr.querySelector('.qty-received');
        var qtyDmgInput = tr.querySelector('.qty-damaged');
        var costInput = tr.querySelector('.unit-cost');
        var expInput = tr.querySelector('.expiration-date');
        
        if (sel && qtyRecInput) {
            var itemId = parseInt(sel.value);
            var qtyOrd = qtyOrdSpan ? parseFloat(qtyOrdSpan.textContent) : 0;
            var qtyRec = parseFloat(qtyRecInput.value) || 0;
            var qtyDmg = parseFloat(qtyDmgInput.value) || 0;
            var cost = parseFloat(costInput.value) || 0;
            var exp = expInput.value || null;
            
            if (itemId) {
                rows.push({
                    item_id: itemId, 
                    quantity_ordered: qtyOrd || qtyRec,
                    quantity_received: qtyRec,
                    quantity_damaged: qtyDmg,
                    unit_cost: cost,
                    expiration_date: exp
                });
            }
        }
    });
    
    document.getElementById('reception-items-json').value = JSON.stringify(rows);
}

function validateReceptionForm(e) {
    var val = document.getElementById('reception-items-json').value;
    if (!val || JSON.parse(val).length === 0) {
        alert("Debe agregar al menos un insumo.");
        e.preventDefault();
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    if (orderItems.length > 0) {
        orderItems.forEach(function(oi) {
            addReceptionRow(oi);
        });
    } else {
        addReceptionRow();
    }
});
</script>
<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}
</style>
@endpush
@endsection
