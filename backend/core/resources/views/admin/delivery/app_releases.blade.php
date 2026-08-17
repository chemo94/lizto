@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Registrar versión</h6>
                <form method="POST" action="{{ route('admin.delivery.app.releases.save') }}">
                    @csrf
                    <div class="row">
                        <div class="col-6 form-group"><label>Aplicación</label><select class="form-control" name="app" required>@foreach(['delivery' => 'Delivery', 'passenger' => 'Pasajero', 'driver' => 'Driver', 'courier' => 'Repartidor', 'seller' => 'Seller'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                        <div class="col-6 form-group"><label>Plataforma</label><select class="form-control" name="platform" required><option value="android">Android</option><option value="ios">iOS</option></select></div>
                        <div class="col-6 form-group"><label>Versión visible</label><input class="form-control" name="latest_version" placeholder="4.1.2" required></div>
                        <div class="col-6 form-group"><label>Build más reciente</label><input class="form-control" type="number" min="1" name="latest_build" required></div>
                        <div class="col-12 form-group"><label>Build mínimo permitido</label><input class="form-control" type="number" min="1" name="minimum_build" required><small class="text-muted">Los builds menores quedarán bloqueados.</small></div>
                        <div class="col-12 form-group"><label>URL de tienda</label><input class="form-control" type="url" name="store_url"></div>
                        <div class="col-12 form-group"><label>Novedades</label><textarea class="form-control" name="release_notes" rows="3"></textarea></div>
                        <div class="col-6 form-group"><div class="form-check"><input class="form-check-input" type="checkbox" name="force_update" value="1" id="force"><label class="form-check-label" for="force">Forzar actualización</label></div></div>
                        <div class="col-6 form-group"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="active"><label class="form-check-label" for="active">Activa</label></div></div>
                    </div>
                    <button class="btn btn--primary w-100" type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7"><div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table--light style--two mb-0"><thead><tr><th>App</th><th>Plataforma</th><th>Última</th><th>Mínima</th><th>Modo</th></tr></thead><tbody>@forelse($releases as $release)<tr><td>{{ ucfirst($release->app) }}</td><td>{{ strtoupper($release->platform) }}</td><td>{{ $release->latest_version }}+{{ $release->latest_build }}</td><td>{{ $release->minimum_build }}</td><td>{{ $release->force_update ? 'Obligatoria' : 'Opcional' }}</td></tr>@empty<tr><td colspan="5" class="text-center">Sin versiones configuradas</td></tr>@endforelse</tbody></table></div></div></div></div>
</div>
@endsection
