@extends('admin.layouts.app')
@section('panel')
<div class="row"><div class="col-lg-12"><div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h5>{{ $pageTitle }}</h5>
<button class="btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBalanceModal"><i class="las la-plus"></i> Agregar/Deducir Saldo</button>
</div>
<div class="card-body p-0"><div class="table-responsive--md"><table class="table table--light">
<thead><tr><th>ID</th><th>Titular</th><th>Tipo</th><th>Balance Wallet</th><th>Balance Driver/User</th><th>Balance Transacciones (Historial)</th><th>Bloqueado</th><th>Transacciones</th></tr></thead>
<tbody>@foreach($wallets as $w)
<tr><td>{{ $w->id }}</td><td>{{ $w->holder?->fullname ?? $w->holder?->name ?? 'N/A' }}</td>
<td><span class="badge badge--primary">{{ class_basename($w->holder_type) }}</span></td>
<td>S/ {{ number_format($w->balance,2) }}</td>
<td>
    @if($w->holder_balance !== null)
        S/ {{ number_format($w->holder_balance,2) }}
    @else
        <span class="text-muted">N/A</span>
    @endif
</td>
<td>
    @if($w->trx_balance !== null)
        S/ {{ number_format($w->trx_balance,2) }}
    @else
        <span class="text-muted">N/A</span>
    @endif
</td>
<td>S/ {{ number_format($w->blocked_balance,2) }}</td>
<td><a href="{{ route('admin.delivery.wallet.transactions',$w->id) }}" class="btn btn-sm btn-outline--info">Ver</a></td></tr>
@endforeach</tbody></table></div></div>
<div class="card-footer">{{ $wallets->links() }}</div>
</div></div></div>

<div class="modal fade" id="addBalanceModal" tabindex="-1">
<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5>Gestionar Saldo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST" action="{{ route('admin.delivery.wallet.add') }}">@csrf
<div class="modal-body">
<label>Tipo de titular</label>
<select name="holder_type" id="holderType" class="form-select mb-2" required onchange="loadHolders()">
    <option value="">Seleccionar tipo...</option>
    <option value="user">Cliente</option>
    <option value="driver">Repartidor</option>
    <option value="seller">Tienda</option>
</select>

<label>Titular</label>
<select name="holder_id" id="holderId" class="form-select mb-2" required disabled>
    <option value="">Primero selecciona el tipo</option>
</select>

<div class="mb-2">
    <input type="text" id="holderSearch" class="form-control form-control-sm" placeholder="Buscar por nombre/email..." oninput="filterHolders()" disabled>
</div>

<label>Monto (positivo = agregar, negativo = deducir)</label>
<input type="number" step="0.01" name="amount" class="form-control mb-2" required>

<label>Concepto</label>
<input type="text" name="remark" class="form-control mb-2" placeholder="bonus, ajuste, comisión, etc.">
</div>
<div class="modal-footer"><button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn--primary">Confirmar</button></div>
</form></div></div></div>
@endsection

@push('script')
<script>
let allHolders = [];

function loadHolders() {
    const type = document.getElementById('holderType').value;
    const holderSelect = document.getElementById('holderId');
    const searchInput = document.getElementById('holderSearch');

    if (!type) {
        holderSelect.innerHTML = '<option value="">Primero selecciona el tipo</option>';
        holderSelect.disabled = true;
        searchInput.disabled = true;
        return;
    }

    holderSelect.innerHTML = '<option value="">Cargando...</option>';
    searchInput.disabled = false;
    searchInput.value = '';

    fetch('{{ route('admin.delivery.holders.by.type') }}?type=' + type)
        .then(r => r.json())
        .then(data => {
            allHolders = data.results || [];
            renderHolders(allHolders);
            holderSelect.disabled = false;
        })
        .catch(() => {
            holderSelect.innerHTML = '<option value="">Error al cargar</option>';
        });
}

function filterHolders() {
    const q = document.getElementById('holderSearch').value.toLowerCase();
    const filtered = allHolders.filter(h => h.text.toLowerCase().includes(q));
    renderHolders(filtered);
}

function renderHolders(list) {
    const holderSelect = document.getElementById('holderId');
    holderSelect.innerHTML = '<option value="">Seleccionar titular...</option>';
    list.forEach(h => {
        holderSelect.innerHTML += `<option value="${h.id}">${h.text}</option>`;
    });
}
</script>
@endpush
