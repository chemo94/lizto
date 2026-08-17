@extends('fleet.layouts.app')
@section('panel')
    <div class="row gy-4">
        <!-- Add Driver Form -->
        <div class="col-md-4">
            <div class="card box--shadow2">
                <div class="card-header bg--primary text-white">
                    <h5 class="card-title mb-0 text-white">Vincular Conductor</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('fleet.drivers.add') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Username o Email del Conductor</label>
                            <input type="text" name="username_or_email" class="form-control" required placeholder="Ej: joao_driver">
                        </div>
                        <button type="submit" class="btn btn--primary w-100 mt-3">Vincular a mi Flota</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Drivers List -->
        <div class="col-md-8">
            <div class="card box--shadow2">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Conductores de mi Flota</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Celular</th>
                                    <th>Vehículo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($drivers as $driver)
                                    <tr>
                                        <td>{{ $driver->fullname }}</td>
                                        <td>{{ $driver->email }}</td>
                                        <td>{{ $driver->mobile }}</td>
                                        <td>{{ @$driver->vehicle->model ?? 'Sin asignar' }}</td>
                                        <td>{!! $driver->statusBadge !!}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No tienes conductores vinculados en tu flota.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($drivers->hasPages())
                    <div class="card-footer py-4">
                        {{ $drivers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
