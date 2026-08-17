@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-shopping-cart"></i></span> Compras e Ingresos
@endsection

@section('seller-content')
<div class="s-content">

    <div class="s-grid-2" style="grid-template-columns: 1fr 1.2fr; gap: 24px; align-items: start;">
        
        <!-- REGISTRAR COMPRA -->
        <div class="s-card">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-plus-circle" style="color: var(--s-primary); font-size: 20px;"></i> Registrar Compra
            </h3>

            <form method="POST" action="{{ route('seller.inventory.purchases.store') }}" onsubmit="return validatePurchaseForm(event)">
                @csrf
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div style="grid-column: span 2;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label class="s-label" style="margin:0;">Proveedor *</label>
                            <a href="javascript:void(0)" onclick="toggleQuickSupplier()" style="font-size:11px; color:var(--s-primary); font-weight:700; text-decoration:none;">
                                <i class="las la-plus-circle"></i> Nuevo Proveedor
                            </a>
                        </div>
                        <select class="s-input" name="supplier_id" id="purchase-supplier-select" required>
                            <option value="">Seleccionar proveedor...</option>
                            @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                        
                        <!-- QUICK SUPPLIER SECTION -->
                        <div id="quick-supplier-section" style="display:none; background:var(--s-surface-2); border:1px solid var(--s-border); border-radius:8px; padding:12px; margin-top:8px;">
                            <h5 style="margin:0 0 10px 0; font-size:12px; font-weight:800; color:var(--s-text-primary);">Registrar Proveedor Rápido</h5>
                            <div style="display:flex; gap:6px; margin-bottom:8px;">
                                <input class="s-input" id="quick-sup-ruc" placeholder="N° RUC..." style="flex:1; padding:4px 8px; font-size:11px; background:#fff;" maxlength="11">
                                <button type="button" class="s-btn s-btn-primary s-btn-xs" style="padding:4px 10px; font-size:11px; height:34px;" onclick="searchSupplierRuc()">Buscar RUC</button>
                            </div>
                            <div id="quick-sup-result" style="font-size:11px; font-weight:700; margin-bottom:8px; min-height:14px;"></div>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <input class="s-input" id="quick-sup-name" placeholder="Razón Social / Nombre..." style="padding:6px; font-size:11px; background:#fff;">
                                <input class="s-input" id="quick-sup-phone" placeholder="Teléfono (opcional)..." style="padding:6px; font-size:11px; background:#fff;">
                                <button type="button" class="s-btn s-btn-success s-btn-xs" style="justify-content:center; font-size:12px; padding:6px;" onclick="saveQuickSupplier()">Guardar y Seleccionar</button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="s-label">Tipo Documento</label>
                        <select class="s-input" name="document_type">
                            <option value="01">Factura</option>
                            <option value="03">Boleta</option>
                        </select>
                    </div>

                    <div>
                        <label class="s-label">Fecha Compra *</label>
                        <input class="s-input" type="date" name="document_date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>

                    <div>
                        <label class="s-label">Serie Doc.</label>
                        <input class="s-input" name="document_series" placeholder="F001">
                    </div>

                    <div>
                        <label class="s-label">Número Doc.</label>
                        <input class="s-input" name="document_number" placeholder="000123">
                    </div>
                </div>

                <div style="margin-top: 20px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <label class="s-label" style="margin: 0; font-weight: 700;">Detalle de Insumos</label>
                    <button type="button" class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-primary)" onclick="addPurchaseRow()">
                        <i class="las la-plus"></i> Agregar fila
                    </button>
                </div>

                <div class="s-table-wrapper" style="margin-bottom: 16px; border: 1px solid var(--s-border); border-radius: 8px; max-height: 250px; overflow-y: auto;">
                    <table class="s-table">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th style="width: 80px; text-align: center;">Cant.</th>
                                <th style="width: 100px; text-align: right;">Costo U.</th>
                                <th style="width: 100px; text-align: right;">Total</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="purchase-items">
                            <!-- Items dinámicos -->
                        </tbody>
                    </table>
                </div>

                <div style="background: var(--s-bg-light); border-radius: 8px; padding: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; color: var(--s-text-secondary); font-size: 13px;">Monto Total Estimado:</span>
                    <span id="purchase-total" style="font-weight: 800; color: var(--s-primary); font-size: 18px;">S/ 0.00</span>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="s-label">Notas o Comentarios</label>
                    <textarea class="s-input" name="notes" placeholder="Escriba aquí notas de la compra..." rows="2"></textarea>
                </div>

                <input type="hidden" name="items" id="purchase-items-json">
                
                <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; gap: 8px;">
                    <i class="las la-save" style="font-size: 16px;"></i> Registrar Compra
                </button>
            </form>
        </div>

        <!-- HISTORIAL -->
        <div class="s-card">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-history" style="color: var(--s-primary); font-size: 20px;"></i> Historial de Compras
            </h3>

            <div class="s-table-wrapper">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Documento</th>
                            <th style="text-align: center;">Items</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $p)
                        <tr>
                            <td style="font-weight: 500;">
                                {{ $p->document_date ? $p->document_date->format('d/m/Y') : '' }}
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--s-text-primary);">{{ $p->supplier?->name }}</div>
                                <div style="font-size: 11px; color: var(--s-text-muted);">{{ $p->supplier?->phone }}</div>
                            </td>
                            <td>
                                <span class="s-badge s-badge-gray">
                                    {{ $p->document_type == '01' ? 'Factura' : 'Boleta' }} {{ $p->document_series }}-{{ $p->document_number }}
                                </span>
                            </td>
                            <td style="text-align: center; font-weight: 600;">{{ $p->items->count() }}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--s-text-primary);">
                                S/ {{ number_format($p->total, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--s-text-muted);">
                                <i class="las la-shopping-cart" style="font-size: 40px; color: var(--s-border); display: block; margin-bottom: 8px;"></i>
                                Sin compras registradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

@push('script')
<script>
var itemsSource = @json($items->map(fn($i) => ['id'=>$i->id,'name'=>$i->name]));

function addPurchaseRow() {
    var tbody = document.getElementById('purchase-items');
    if (!itemsSource.length) {
        alert("Primero registre insumos en Stock.");
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
    select.onchange = updatePurchaseTotal;
    
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
    inputQty.oninput = updatePurchaseTotal;
    tdQty.appendChild(inputQty);
    tr.appendChild(tdQty);
    
    // Costo unitario
    var tdCost = document.createElement('td');
    var inputCost = document.createElement('input');
    inputCost.className = 's-input';
    inputCost.type = 'number';
    inputCost.step = '0.01';
    inputCost.value = '0.00';
    inputCost.style.padding = '4px 8px';
    inputCost.style.height = '32px';
    inputCost.style.textAlign = 'right';
    inputCost.oninput = updatePurchaseTotal;
    tdCost.appendChild(inputCost);
    tr.appendChild(tdCost);
    
    // Subtotal row
    var tdTotal = document.createElement('td');
    tdTotal.style.textAlign = 'right';
    tdTotal.style.fontWeight = '700';
    tdTotal.style.color = 'var(--s-text-primary)';
    tdTotal.textContent = 'S/ 0.00';
    tr.appendChild(tdTotal);
    
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
        updatePurchaseTotal();
    };
    tdDel.appendChild(btnDel);
    tr.appendChild(tdDel);
    
    tbody.appendChild(tr);
    updatePurchaseTotal();
}

function updatePurchaseTotal() {
    var total = 0; 
    var rows = [];
    
    document.querySelectorAll('#purchase-items tr').forEach(function(tr) {
        var sel = tr.querySelector('select'); 
        var qtyInput = tr.querySelectorAll('input')[0]; 
        var costInput = tr.querySelectorAll('input')[1];
        var tdTotal = tr.querySelectorAll('td')[3];
        
        if (sel && qtyInput && costInput) {
            var qty = parseFloat(qtyInput.value) || 0;
            var cost = parseFloat(costInput.value) || 0;
            var rowTotal = qty * cost;
            
            if (tdTotal) {
                tdTotal.textContent = 'S/ ' + rowTotal.toFixed(2);
            }
            
            total += rowTotal;
            if (sel.value) {
                rows.push({
                    item_id: parseInt(sel.value), 
                    quantity: qty, 
                    unit_cost: cost
                });
            }
        }
    });
    
    document.getElementById('purchase-total').textContent = 'S/ ' + total.toFixed(2);
    document.getElementById('purchase-items-json').value = JSON.stringify(rows);
}

function validatePurchaseForm(e) {
    var val = document.getElementById('purchase-items-json').value;
    if (!val || JSON.parse(val).length === 0) {
        alert("Debe agregar al menos un insumo con proveedor seleccionado.");
        e.preventDefault();
        return false;
    }
    return true;
}

// Quick supplier helper functions
function toggleQuickSupplier() {
    var sec = document.getElementById('quick-supplier-section');
    if (sec.style.display === 'none') {
        sec.style.display = 'block';
        document.getElementById('quick-sup-ruc').focus();
    } else {
        sec.style.display = 'none';
    }
}

function searchSupplierRuc() {
    var ruc = document.getElementById('quick-sup-ruc').value.trim();
    if (ruc.length !== 11) {
        alert('El RUC debe tener 11 dígitos.');
        return;
    }
    var res = document.getElementById('quick-sup-result');
    res.innerHTML = '<span style="color:var(--s-accent);"><i class="las la-spinner la-spin"></i> Buscando en SUNAT...</span>';
    
    fetch('{{ route("seller.sunat") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ numdoc: ruc, tpdoc: '6' })
    })
    .then(x => x.json())
    .then(d => {
        if (d.status && d.nombre) {
            document.getElementById('quick-sup-name').value = d.nombre;
            res.innerHTML = '<span style="color:var(--s-success)">✓ RUC Encontrado</span>';
        } else {
            res.innerHTML = '<span style="color:var(--s-danger);">' + (d.result || 'No encontrado') + '</span>';
        }
    })
    .catch(() => {
        res.innerHTML = '<span style="color:var(--s-danger);">Error de conexión</span>';
    });
}

function saveQuickSupplier() {
    var ruc = document.getElementById('quick-sup-ruc').value.trim();
    var name = document.getElementById('quick-sup-name').value.trim();
    var phone = document.getElementById('quick-sup-phone').value.trim();
    
    if (!name) {
        alert('Por favor ingrese el nombre del proveedor.');
        return;
    }
    
    var data = {
        name: name,
        document_type: ruc ? '6' : null,
        document_number: ruc || null,
        phone: phone || null
    };
    
    fetch('{{ route("seller.inventory.suppliers.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(x => x.json())
    .then(d => {
        if (d.status && d.supplier) {
            // Add new option and select it
            var select = document.getElementById('purchase-supplier-select');
            var opt = document.createElement('option');
            opt.value = d.supplier.id;
            opt.textContent = d.supplier.name;
            opt.selected = true;
            select.appendChild(opt);
            
            // Clean up and hide
            document.getElementById('quick-sup-ruc').value = '';
            document.getElementById('quick-sup-name').value = '';
            document.getElementById('quick-sup-phone').value = '';
            document.getElementById('quick-sup-result').innerHTML = '';
            document.getElementById('quick-supplier-section').style.display = 'none';
            
            alert('Proveedor registrado y seleccionado correctamente.');
        } else {
            alert('Error al registrar proveedor.');
        }
    })
    .catch(() => {
        alert('Error de red al guardar el proveedor.');
    });
}

// Inicializar con una fila vacía
document.addEventListener('DOMContentLoaded', function() {
    addPurchaseRow();
});
</script>
@endpush
@endsection
