@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <!-- Packages List -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>Paquetes Empresariales</h5>
                <button type="button" class="btn btn-sm btn--primary" data-bs-toggle="modal" data-bs-target="#packageModal" onclick="resetForm()">
                    <i class="las la-plus"></i> Nuevo Paquete
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Nombre</th><th>Tipo</th><th>Precio</th><th>Duración</th><th>Activo</th><th>Acción</th></tr></thead>
                        <tbody>
                            @forelse($packages as $pkg)
                            <tr>
                                <td><strong>{{ $pkg->name }}</strong></td>
                                <td><span class="badge badge--primary">{{ $pkg->type }}</span></td>
                                <td>S/ {{ number_format($pkg->price, 2) }}</td>
                                <td>{{ $pkg->duration_days }} días</td>
                                <td>
                                    <a href="{{ route('admin.delivery.packages.status', $pkg->id) }}" class="btn btn-sm {{ $pkg->status ? 'btn--success' : 'btn--danger' }}">
                                        {{ $pkg->status ? 'Sí' : 'No' }}
                                    </a>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline--warning" onclick='editPackage(@json($pkg))'><i class="las la-edit"></i></button>
                                    <form method="POST" action="{{ route('admin.delivery.packages.delete', $pkg->id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar paquete?')">
                                        @csrf<button class="btn btn-sm btn-outline--danger"><i class="las la-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center">Sin paquetes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscriptions -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5>Suscripciones Pendientes</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Tienda</th><th>Paquete</th><th>Estado</th><th>Acción</th></tr></thead>
                        <tbody>
                            @forelse($subscriptions as $sub)
                            <tr>
                                <td>{{ $sub->store?->name }}</td>
                                <td>{{ $sub->package?->name }}</td>
                                <td><span class="badge badge--{{ $sub->status === 'active' ? 'success' : ($sub->status === 'cancelled' ? 'danger' : 'warning') }}">{{ $sub->status }}</span></td>
                                <td>
                                    @if($sub->status === 'pending')
                                    <form method="POST" action="{{ route('admin.delivery.subscription.approve', $sub->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn--success"><i class="las la-check"></i></button></form>
                                    <form method="POST" action="{{ route('admin.delivery.subscription.reject', $sub->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn--danger"><i class="las la-times"></i></button></form>
                                    @else
                                    <small>{{ $sub->status }}</small>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center">Sin suscripciones</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">{{ $subscriptions->links() }}</div>
        </div>
    </div>
</div>

<!-- Package Create/Edit Modal -->
<div class="modal fade" id="packageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.delivery.packages.save') }}" id="packageForm">
                @csrf
                <div class="modal-header"><h5 class="modal-title" id="packageModalTitle">Nuevo Paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_method" id="pkgMethod" value="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" id="pkgName" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="type" id="pkgType" class="form-select" required>
                                <option value="basic">Básico</option>
                                <option value="featured">Destacado</option>
                                <option value="premium">Premium</option>
                                <option value="qr">QR Order</option>
                                <option value="sms">SMS</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Precio (S/)</label>
                            <input type="number" name="price" id="pkgPrice" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Días</label>
                            <input type="number" name="duration_days" id="pkgDays" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" id="pkgDesc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icono (emoji)</label>
                        <input type="text" name="icon" id="pkgIcon" class="form-control" placeholder="⭐">
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Características y Límites</label>
                            <button type="button" class="btn btn-xs btn--success" onclick="addFeatureRow()">
                                <i class="las la-plus"></i> Agregar
                            </button>
                        </div>
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Control Key</th>
                                        <th>Nombre / Descripción</th>
                                        <th>Límite / Valor</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="featuresBody">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Orden</label>
                            <input type="number" name="sort_order" id="pkgSort" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4"><input type="checkbox" name="status" id="pkgStatus" class="form-check-input" checked><label class="form-check-label">Activo</label></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
let featureIndex = 0;

function addFeatureRow(key = 'general', name = '', value = '') {
    const tbody = document.getElementById('featuresBody');
    const tr = document.createElement('tr');
    tr.id = `feature-row-${featureIndex}`;
    
    tr.innerHTML = `
        <td>
            <select name="features[${featureIndex}][key]" class="form-select form-select-sm" required>
                <option value="general" ${key === 'general' ? 'selected' : ''}>General / Info</option>
                <option value="invoices" ${key === 'invoices' ? 'selected' : ''}>Comprobantes (límite)</option>
                <option value="registers" ${key === 'registers' ? 'selected' : ''}>Cajas (límite)</option>
                <option value="users" ${key === 'users' ? 'selected' : ''}>Personal (límite)</option>
                <option value="companies" ${key === 'companies' ? 'selected' : ''}>Empresas (límite)</option>
            </select>
        </td>
        <td>
            <input type="text" name="features[${featureIndex}][name]" class="form-control form-control-sm" value="${name}" placeholder="ej. Cajas registradoras" required>
        </td>
        <td>
            <input type="text" name="features[${featureIndex}][value]" class="form-control form-control-sm" value="${value}" placeholder="ej. 2 o Ilimitado" required>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline--danger" onclick="removeFeatureRow(${featureIndex})">
                <i class="las la-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    featureIndex++;
}

function removeFeatureRow(idx) {
    const row = document.getElementById(`feature-row-${idx}`);
    if (row) row.remove();
}

function resetForm() {
    document.getElementById('packageForm').action = "{{ route('admin.delivery.packages.save') }}";
    document.getElementById('pkgMethod').value = 'POST';
    document.getElementById('packageModalTitle').textContent = 'Nuevo Paquete';
    document.getElementById('pkgName').value = '';
    document.getElementById('pkgType').value = 'featured';
    document.getElementById('pkgPrice').value = '';
    document.getElementById('pkgDays').value = 30;
    document.getElementById('pkgDesc').value = '';
    document.getElementById('pkgIcon').value = '';
    document.getElementById('featuresBody').innerHTML = '';
    featureIndex = 0;
    document.getElementById('pkgSort').value = '0';
    document.getElementById('pkgStatus').checked = true;
}

function editPackage(pkg) {
    document.getElementById('packageForm').action = "{{ route('admin.delivery.packages.save', '') }}/" + pkg.id;
    document.getElementById('pkgMethod').value = 'POST';
    document.getElementById('packageModalTitle').textContent = 'Editar Paquete';
    document.getElementById('pkgName').value = pkg.name;
    document.getElementById('pkgType').value = pkg.type;
    document.getElementById('pkgPrice').value = pkg.price;
    document.getElementById('pkgDays').value = pkg.duration_days;
    document.getElementById('pkgDesc').value = pkg.description || '';
    document.getElementById('pkgIcon').value = pkg.icon || '';
    
    document.getElementById('featuresBody').innerHTML = '';
    featureIndex = 0;
    
    let featuresList = [];
    if (typeof pkg.features === 'string') {
        try {
            featuresList = JSON.parse(pkg.features);
        } catch(e) {
            featuresList = [];
        }
    } else if (Array.isArray(pkg.features)) {
        featuresList = pkg.features;
    }
    
    if (Array.isArray(featuresList)) {
        featuresList.forEach(f => {
            if (typeof f === 'object' && f !== null) {
                addFeatureRow(f.key || 'general', f.name || '', f.value || '');
            } else if (typeof f === 'string') {
                addFeatureRow('general', f, 'Sí');
            }
        });
    }
    
    document.getElementById('pkgSort').value = pkg.sort_order;
    document.getElementById('pkgStatus').checked = pkg.status == 1;
    new bootstrap.Modal(document.getElementById('packageModal')).show();
}
</script>
@endpush
