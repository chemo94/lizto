@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-cloud-upload-alt"></i></span> Carga Masiva de Productos
@endsection

@section('seller-content')
<div class="s-content">
    
    <div class="s-grid-2" style="grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
        
        <!-- STEP 1: UPLOAD & INSTRUCTIONS -->
        <div class="s-card">
            <h3 style="margin-bottom: 16px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-file-upload" style="color: var(--s-primary); font-size: 20px;"></i> Subir Carta/Menú
            </h3>
            
            <p style="font-size: 13px; color: var(--s-text-secondary); line-height: 1.6; margin-bottom: 16px;">
                Sube una foto, PDF o Excel de tu carta. Nuestro sistema inteligente <strong style="color: var(--s-primary);">detectará automáticamente</strong> los productos, descripciones y precios.
            </p>
            
            <div style="background: var(--s-bg-light); border-left: 4px solid var(--s-primary); padding: 12px; border-radius: 4px 8px 8px 4px; margin-bottom: 20px;">
                <a href="{{ route('seller.products.bulk-template') }}" style="color: var(--s-primary); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 13px;">
                    <i class="las la-download" style="font-size: 16px;"></i> Descargar Plantilla Excel
                </a>
                <span style="display: block; font-size: 11px; color: var(--s-text-muted); margin-top: 4px;">Usa esta plantilla para importar de manera estructurada y limpia.</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <label class="s-label" style="font-weight: 700;">Seleccionar Archivo</label>
                <input type="file" id="menu-file" accept="image/*,.pdf,.xlsx,.xls,.csv" class="s-input" onchange="uploadAndParse(this)">
                <span id="ocr-status" style="font-size: 12px; font-weight: 600;"></span>
            </div>

            <div id="file-preview-container" style="margin-top: 20px; display: none; border: 1px solid var(--s-border); border-radius: 8px; overflow: hidden; background: var(--s-bg-light); padding: 8px;">
                <img id="file-preview-img" src="" style="width: 100%; max-height: 300px; border-radius: 6px; object-fit: contain;">
            </div>
        </div>

        <!-- STEP 2: BULK ENTRY FORM -->
        <div class="s-card">
            <h3 style="margin-bottom: 10px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-list-alt" style="color: var(--s-primary); font-size: 20px;"></i> Ingresar o Corregir Productos
            </h3>
            <p style="font-size: 13px; color: var(--s-text-secondary); margin-bottom: 20px;">
                Cada fila representa un producto. La categoría se creará automáticamente en tu tienda si no existe.
            </p>

            <div class="s-table-wrapper" style="border: 1px solid var(--s-border); border-radius: 8px; max-height: 450px; overflow-y: auto;">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Categoría</th>
                            <th style="width: 25%;">Nombre del Producto</th>
                            <th style="width: 25%;">Descripción</th>
                            <th style="width: 12%;">Precio</th>
                            <th style="width: 12%;">Tributario</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="bulk-rows">
                        @for($i = 0; $i < 6; $i++)
                        <tr>
                            <td><input class="s-input bulk-cat" placeholder="Ej: Entradas" style="height: 34px; padding: 4px 8px;"></td>
                            <td><input class="s-input bulk-name" placeholder="Nombre del plato" style="height: 34px; padding: 4px 8px;"></td>
                            <td><input class="s-input bulk-desc" placeholder="Descripción breve" style="height: 34px; padding: 4px 8px;"></td>
                            <td><input class="s-input bulk-price" type="number" step="0.01" placeholder="0.00" style="height: 34px; padding: 4px 8px; text-align: right;"></td>
                            <td>
                                <select class="s-input bulk-tax" style="height: 34px; padding: 2px 4px; font-size:12px;">
                                    <option value="gravado">Gravado</option>
                                    <option value="exonerado">Exonerado</option>
                                    <option value="inafecto">Inafecto</option>
                                </select>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-danger);" onclick="this.closest('tr').remove()">
                                    <i class="las la-times" style="font-size: 16px;"></i>
                                </button>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--s-border);">
                <button type="button" class="s-btn s-btn-secondary" onclick="addBulkRow()">
                    <i class="las la-plus"></i> Agregar Fila
                </button>
                
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span id="bulk-result" style="font-size: 13px; font-weight: 600;"></span>
                    <button type="button" class="s-btn s-btn-primary" onclick="processBulk()">
                        <i class="las la-save"></i> Guardar Productos
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- RESULT CARD -->
    <div id="bulk-result-card" class="s-card" style="display: none; margin-top: 24px;">
        <h3 style="margin-bottom: 16px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-check-circle" style="color: var(--s-success); font-size: 22px;"></i> Resultado de la Operación
        </h3>
        <div id="bulk-result-content" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; background: var(--s-bg-light); padding: 16px; border-radius: 8px; border: 1px solid var(--s-border);"></div>
    </div>

</div>

@push('script')
<script>
function previewFile(input) {
    var file = input.files[0];
    var container = document.getElementById('file-preview-container');
    var img = document.getElementById('file-preview-img');
    if (!file || !file.type.startsWith('image/')) { container.style.display = 'none'; return; }
    var reader = new FileReader();
    reader.onload = function(e) { img.src = e.target.result; container.style.display = 'block'; };
    reader.readAsDataURL(file);
}

function uploadAndParse(input) {
    var file = input.files[0];
    if (!file) return;
    previewFile(input);
    var status = document.getElementById('ocr-status');
    var ext = file.name.split('.').pop().toLowerCase();

    // Excel/CSV → guardar directo
    if (ext === 'xlsx' || ext === 'xls' || ext === 'csv') {
        status.innerHTML = '<span style="color:var(--s-primary)"><i class="las la-spinner la-spin"></i> Procesando Excel...</span>';
        var formData = new FormData();
        formData.append('image', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch('/seller/products/ocr-parse', { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'success' && d.items.length > 0) {
                status.innerHTML = '<span style="color:var(--s-primary)"><i class="las la-spinner la-spin"></i> Guardando ' + d.items.length + ' productos...</span>';
                fetch('/seller/products/bulk-store', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ items: d.items })
                })
                .then(function(r2) { return r2.json(); })
                .then(function(d2) {
                    if (d2.status === 'success') {
                        status.innerHTML = '<span style="color:var(--s-success)">✓ ' + d2.created + ' productos guardados con éxito</span>';
                        showResultsCard(d2.details);
                    }
                });
            } else {
                status.innerHTML = '<span style="color:var(--s-danger)">⚠ No se detectaron productos en el Excel. Revisa el formato.</span>';
            }
        })
        .catch(function() { status.innerHTML = '<span style="color:var(--s-danger)">Error de conexión</span>'; });
        return;
    }

    // Imagen/PDF → IA OCR
    status.innerHTML = '<span style="color:var(--s-primary)"><i class="las la-spinner la-spin"></i> Analizando imagen con IA...</span>';

    var formData = new FormData();
    formData.append('image', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('/seller/products/ocr-parse', { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.status === 'success' && d.items.length > 0) {
            fillTableWithParsedData(d.items);
            status.innerHTML = '<span style="color:var(--s-success)">✓ Se detectaron ' + d.items.length + ' productos. Revisa y confirma abajo.</span>';
        } else {
            status.innerHTML = '<span style="color:var(--s-danger)">⚠ No se detectaron productos. Ingresa manualmente.</span>';
        }
    })
    .catch(function() { status.innerHTML = '<span style="color:var(--s-danger)">Error al procesar archivo</span>'; });
}

function fillTableWithParsedData(items) {
    var tbody = document.getElementById('bulk-rows');
    tbody.innerHTML = '';
    items.forEach(function(item) {
        var taxType = item.tax_type || 'gravado';
        var taxSelect = '<select class="s-input bulk-tax" style="height:34px; padding:2px 4px; font-size:12px;">';
        taxSelect += '<option value="gravado"' + (taxType === 'gravado' ? ' selected' : '') + '>Gravado</option>';
        taxSelect += '<option value="exonerado"' + (taxType === 'exonerado' ? ' selected' : '') + '>Exonerado</option>';
        taxSelect += '<option value="inafecto"' + (taxType === 'inafecto' ? ' selected' : '') + '>Inafecto</option>';
        taxSelect += '</select>';
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><input class="s-input bulk-cat" value="' + htmlEscape(item.category) + '" style="height:34px; padding:4px 8px;"></td>' +
            '<td><input class="s-input bulk-name" value="' + htmlEscape(item.name) + '" style="height:34px; padding:4px 8px;"></td>' +
            '<td><input class="s-input bulk-desc" value="' + htmlEscape(item.description || '') + '" style="height:34px; padding:4px 8px;"></td>' +
            '<td><input class="s-input bulk-price" type="number" step="0.01" value="' + item.price.toFixed(2) + '" style="height:34px; padding:4px 8px; text-align:right;"></td>' +
            '<td>' + taxSelect + '</td>' +
            '<td style="text-align:center;"><button type="button" class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" onclick="this.closest(\'tr\').remove()"><i class="las la-times" style="font-size:16px;"></i></button></td>';
        tbody.appendChild(tr);
    });
}

function htmlEscape(str) {
    return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;');
}

function addBulkRow() {
    var tbody = document.getElementById('bulk-rows');
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input class="s-input bulk-cat" placeholder="Ej: Entradas" style="height:34px; padding:4px 8px;"></td>' +
        '<td><input class="s-input bulk-name" placeholder="Nombre del plato" style="height:34px; padding:4px 8px;"></td>' +
        '<td><input class="s-input bulk-desc" placeholder="Descripción breve" style="height:34px; padding:4px 8px;"></td>' +
        '<td><input class="s-input bulk-price" type="number" step="0.01" placeholder="0.00" style="height:34px; padding:4px 8px; text-align:right;"></td>' +
        '<td><select class="s-input bulk-tax" style="height:34px; padding:2px 4px; font-size:12px;"><option value="gravado">Gravado</option><option value="exonerado">Exonerado</option><option value="inafecto">Inafecto</option></select></td>' +
        '<td style="text-align:center;"><button type="button" class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" onclick="this.closest(\'tr\').remove()"><i class="las la-times" style="font-size:16px;"></i></button></td>';
    tbody.appendChild(tr);
}

function processBulk() {
    var rows = document.querySelectorAll('#bulk-rows tr');
    var items = [];
    rows.forEach(function(row) {
        var cat = row.querySelector('.bulk-cat').value.trim();
        var name = row.querySelector('.bulk-name').value.trim();
        var desc = row.querySelector('.bulk-desc').value.trim();
        var price = row.querySelector('.bulk-price').value.trim();
        var tax = row.querySelector('.bulk-tax').value;
        if (name && price) {
            items.push({ category: cat || 'General', name: name, description: desc, price: parseFloat(price), tax_type: tax || 'gravado' });
        }
    });

    if (!items.length) { alert('Ingresa al menos un producto con nombre y precio'); return; }

    var result = document.getElementById('bulk-result');
    result.innerHTML = '<span style="color:var(--s-primary)"><i class="las la-spinner la-spin"></i> Guardando...</span>';

    fetch('/seller/products/bulk-store', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ items: items })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.status === 'success') {
            result.innerHTML = '<span style="color:var(--s-success)">✓ Guardado exitoso</span>';
            showResultsCard(d.details);
        } else {
            result.innerHTML = '<span style="color:var(--s-danger)">Error al guardar</span>';
        }
    })
    .catch(function() { result.innerHTML = '<span style="color:var(--s-danger)">Error de conexión</span>'; });
}

function showResultsCard(details) {
    var card = document.getElementById('bulk-result-card');
    card.style.display = 'block';
    var content = document.getElementById('bulk-result-content');
    content.innerHTML = details.map(function(g) {
        return '<div style="background:var(--s-bg-card); padding:12px; border-radius:6px; border:1px solid var(--s-border);">' +
            '<div style="font-weight:700; color:var(--s-primary); font-size:13px; margin-bottom:6px; border-bottom:1px solid var(--s-border); padding-bottom:4px;">' + htmlEscape(g.category) + ' (' + g.count + ')</div>' +
            g.items.map(function(i) { 
                return '<div style="font-size:12px; color:var(--s-text-primary); margin-bottom:2px; display:flex; justify-content:space-between;">' +
                    '<span>✓ ' + htmlEscape(i.name) + '</span>' +
                    '<span style="font-weight:600;">S/ ' + parseFloat(i.price).toFixed(2) + '</span>' +
                    '</div>'; 
            }).join('') + 
            '</div>';
    }).join('');
}
</script>
@endpush
@endsection
