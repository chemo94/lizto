@extends('fleet.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-md-6">
            <div class="card box--shadow2">
                <div class="card-header bg--primary text-white">
                    <h5 class="card-title mb-0 text-white">Configurar Tarifas de Flota</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Estas tarifas se aplicarán de forma prioritaria a los clientes que soliciten viajes y sean asignados a los conductores de tu flota. Deja los valores en blanco si deseas usar las tarifas generales del sistema.</p>
                    
                    <form action="{{ route('fleet.fares.update') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="form-label">Tarifa Base ($)</label>
                            <input type="number" step="0.01" name="base_fare" class="form-control" value="{{ $fleet->base_fare }}" placeholder="Ej: 5.00">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Tarifa por Kilómetro ($)</label>
                            <input type="number" step="0.01" name="rate_per_km" class="form-control" value="{{ $fleet->rate_per_km }}" placeholder="Ej: 1.50">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Tarifa por Minuto ($)</label>
                            <input type="number" step="0.01" name="rate_per_minute" class="form-control" value="{{ $fleet->rate_per_minute }}" placeholder="Ej: 0.20">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Ciudad / Zona de Operación (Ciudad)</label>
                            <select name="zone_id" class="form-control" style="padding: 0 10px;">
                                <option value="">-- Sin ciudad asignada --</option>
                                @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}" {{ $fleet->zone_id == $zone->id ? 'selected' : '' }}>{{ __($zone->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn--primary w-100 mt-3">Guardar Tarifas</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
