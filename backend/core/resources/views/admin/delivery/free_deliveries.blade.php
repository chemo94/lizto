@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-5">
        <div class="card"><div class="card-header"><h5>Asignar Envío Gratis</h5></div><div class="card-body">
            <form method="POST" action="{{ route('admin.delivery.free.deliveries.store') }}">
                @csrf
                <div class="mb-2"><label>Usuario</label><select name="user_id" class="form-select" required><option value="">Seleccionar...</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->fullname }} ({{ $u->email }})</option>@endforeach</select></div>
                <div class="mb-2"><label>Envíos gratis</label><input type="number" name="remaining" class="form-control" value="1" min="1" required></div>
                <div class="mb-2"><label>Notas</label><input type="text" name="notes" class="form-control" placeholder="Promoción de bienvenida"></div>
                <button type="submit" class="btn btn--primary">Asignar</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-header"><h5>Envíos Gratis Activos</h5></div><div class="card-body p-0">
            <div class="table-responsive"><table class="table"><thead><tr><th>Usuario</th><th>Restantes</th><th>Notas</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
                @forelse($deliveries as $d)
                <tr><td>{{ $d->user?->fullname }}</td><td>{{ $d->remaining }}</td><td>{{ $d->notes }}</td><td><span class="badge {{ $d->status ? 'badge--success' : 'badge--danger' }}">{{ $d->status ? 'Activo' : 'Inactivo' }}</span></td>
                <td><form method="POST" action="{{ route('admin.delivery.free.deliveries.delete', $d->id) }}" onsubmit="return confirm('¿Eliminar?')">@csrf<button class="btn btn-sm btn-outline-danger"><i class="las la-trash"></i></button></form></td></tr>
                @empty
                <tr><td colspan="5" class="text-center">Sin envíos gratis asignados</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</div>
@endsection
