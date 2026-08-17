@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-12">
<div class="card">
<div class="card-header"><h5>{{ $pageTitle }}</h5></div>
<div class="card-body">
    <div class="row mb-4">
        <div class="col-md-4">
            <label>Seleccionar Tienda</label>
            <select class="form-select" id="bulk-store">
                <option value="">— Tienda —</option>
                @foreach($stores as $s)
                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->seller?->name }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label>Subir Carta (imagen, PDF o Excel)</label>
            <input type="file" class="form-control" id="menu-file" accept="image/*,.pdf,.xlsx,.xls,.csv" onchange="uploadAndParse(this)">
            <a href="{{ route('admin.delivery.products.bulk.template') }}" class="btn btn-sm btn-outline-success mt-1"><i class="las la-download"></i> Descargar Plantilla</a>
            <span id="ocr-status" class="small text-muted"></span>
        </div>
    </div>
    <div id="file-preview-container" style="display:none;margin-bottom:16px">
        <img id="file-preview-img" src="" style="max-width:100%;max-height:300px;border-radius:8px;border:1px solid #ddd">
    </div>

    <h6>Productos</h6>
    <div class="table-responsive">
        <table class="table table-sm" id="bulk-table">
            <thead><tr><th style="width:140px">Categoría</th><th style="width:180px">Nombre</th><th>Descripción</th><th style="width:90px">Precio</th><th></th></tr></thead>
            <tbody id="bulk-rows">
                @for($i = 0; $i < 10; $i++)
                <tr>
                    <td><input class="form-control form-control-sm bulk-cat" placeholder="Ej: Entradas"></td>
                    <td><input class="form-control form-control-sm bulk-name" placeholder="Nombre"></td>
                    <td><input class="form-control form-control-sm bulk-desc" placeholder="Descripción"></td>
                    <td><input class="form-control form-control-sm bulk-price" type="number" step="0.01" placeholder="17.00"></td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBulkRow()"><i class="las la-plus"></i> Agregar fila</button>

    <div class="mt-3">
        <button type="button" class="btn btn--primary" onclick="processBulk()"><i class="las la-save"></i> Procesar y Guardar</button>
        <span id="bulk-result" class="ms-2 fw-bold"></span>
    </div>
    <div id="bulk-result-card" class="mt-3" style="display:none">
        <h6>Resultado</h6>
        <div id="bulk-result-content"></div>
    </div>
</div></div></div></div>
@endsection

@push('script')
<script>
function previewFile(input) {
    var f = input.files[0], c = document.getElementById('file-preview-container'), img = document.getElementById('file-preview-img');
    if (!f || !f.type.startsWith('image/')) { c.style.display = 'none'; return; }
    var r = new FileReader(); r.onload = function(e) { img.src = e.target.result; c.style.display = 'block'; }; r.readAsDataURL(f);
}
function uploadAndParse(input) {
    var file = input.files[0]; if (!file) return;
    previewFile(input);
    var ext = file.name.split('.').pop().toLowerCase();
    var status = document.getElementById('ocr-status');

    // Excel → guardar directo
    if (ext === 'xlsx' || ext === 'xls' || ext === 'csv') {
        var sid = document.getElementById('bulk-store').value;
        if (!sid) { alert('Selecciona una tienda primero'); return; }
        status.innerHTML = '<span class="text-info"><i class="las la-spinner la-spin"></i> Procesando Excel...</span>';
        var fd = new FormData(); fd.append('image', file); fd.append('_token', '{{ csrf_token() }}');
        fetch('/admin/delivery/products/ocr-parse', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(d => {
            if (d.status === 'success' && d.items.length) {
                status.innerHTML = '<span class="text-info"><i class="las la-spinner la-spin"></i> Guardando '+d.items.length+' productos...</span>';
                fetch('/admin/delivery/products/bulk-store', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({ store_id: sid, items: d.items }) })
                .then(r2 => r2.json()).then(d2 => {
                    if (d2.status === 'success') {
                        status.innerHTML = '<span class="text-success">✓ '+d2.created+' productos en '+d2.categories+' categorías</span>';
                        var card = document.getElementById('bulk-result-card'); card.style.display = 'block';
                        document.getElementById('bulk-result-content').innerHTML = d2.details.map(g => '<div class="mb-2"><strong class="text--green">'+g.category+'</strong> ('+g.count+')<br>'+g.items.map(i => '<small class="text-muted">✓ '+i.name+' — S/'+parseFloat(i.price).toFixed(2)+'</small>').join('<br>')+'</div>').join('');
                    }
                });
            } else status.innerHTML = '<span class="text-warning">⚠ No se detectaron productos</span>';
        });
        return;
    }

    // Imagen → preview
    status.innerHTML = '<span class="text-info"><i class="las la-spinner la-spin"></i> Analizando imagen...</span>';
    var fd2 = new FormData(); fd2.append('image', file); fd2.append('_token', '{{ csrf_token() }}');
    fetch('/admin/delivery/products/ocr-parse', { method: 'POST', body: fd2, headers: { 'Accept': 'application/json' } })
    .then(r => r.json()).then(d => {
        if (d.status === 'success' && d.items.length) {
            fillTable(d.items);
            status.innerHTML = '<span class="text-success">✓ '+d.items.length+' productos detectados. Revisa y guarda.</span>';
        } else status.innerHTML = '<span class="text-warning">⚠ No se detectaron productos.</span>';
    });
}
function fillTable(items) {
    var tbody = document.getElementById('bulk-rows'); tbody.innerHTML = '';
    items.forEach(function(i) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><input class="form-control form-control-sm bulk-cat" value="'+htmlEscape(i.category)+'"></td><td><input class="form-control form-control-sm bulk-name" value="'+htmlEscape(i.name)+'"></td><td><input class="form-control form-control-sm bulk-desc" value="'+htmlEscape(i.description||'')+'"></td><td><input class="form-control form-control-sm bulk-price" type="number" step="0.01" value="'+i.price.toFixed(2)+'"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()">×</button></td>';
        tbody.appendChild(tr);
    });
}
function htmlEscape(s) { return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&#39;'); }
function addBulkRow() {
    var t = document.getElementById('bulk-rows');
    var r = document.createElement('tr');
    r.innerHTML = '<td><input class="form-control form-control-sm bulk-cat" placeholder="Categoría"></td><td><input class="form-control form-control-sm bulk-name" placeholder="Nombre"></td><td><input class="form-control form-control-sm bulk-desc" placeholder="Descripción"></td><td><input class="form-control form-control-sm bulk-price" type="number" step="0.01"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()">×</button></td>';
    t.appendChild(r);
}
function processBulk() {
    var sid = document.getElementById('bulk-store').value;
    if (!sid) { alert('Selecciona una tienda'); return; }
    var items = [];
    document.querySelectorAll('#bulk-rows tr').forEach(function(r) {
        var c = r.querySelector('.bulk-cat').value.trim(), n = r.querySelector('.bulk-name').value.trim(), d = r.querySelector('.bulk-desc').value.trim(), p = r.querySelector('.bulk-price').value.trim();
        if (n && p) items.push({ category: c || 'General', name: n, description: d, price: parseFloat(p) });
    });
    if (!items.length) { alert('Ingresa al menos un producto'); return; }
    document.getElementById('bulk-result').innerHTML = '<span class="text-info">Procesando...</span>';
    fetch('/admin/delivery/products/bulk-store', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ store_id: sid, items: items })
    }).then(r => r.json()).then(d => {
        if (d.status === 'success') {
            document.getElementById('bulk-result').innerHTML = '<span class="text-success">✓ '+d.created+' productos en '+d.categories+' categorías</span>';
            var card = document.getElementById('bulk-result-card'); card.style.display = 'block';
            document.getElementById('bulk-result-content').innerHTML = d.details.map(g => '<div class="mb-2"><strong class="text--green">'+g.category+'</strong> ('+g.count+' prod)<br>'+g.items.map(i => '<small class="text-muted">✓ '+i.name+' — S/'+parseFloat(i.price).toFixed(2)+'</small>').join('<br>')+'</div>').join('');
        } else document.getElementById('bulk-result').innerHTML = '<span class="text-danger">'+d.message+'</span>';
    });
}
</script>
@endpush
