@extends('fleet.layouts.app')
@section('panel')
    <div class="row gy-4">
        <!-- Total Drivers Card -->
        <div class="col-xxl-4 col-sm-6">
            <div class="card bg--primary has-link-box box--shadow2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-4">
                            <i class="las la-users f-size--56 text-white"></i>
                        </div>
                        <div class="col-8 text-end">
                            <span class="text-white fs-18">Conductores en Flota</span>
                            <h2 class="text-white fw-bold">{{ $driversCount }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Rides Card -->
        <div class="col-xxl-4 col-sm-6">
            <div class="card bg--success has-link-box box--shadow2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-4">
                            <i class="las la-taxi f-size--56 text-white"></i>
                        </div>
                        <div class="col-8 text-end">
                            <span class="text-white fs-18">Viajes Procesados</span>
                            <h2 class="text-white fw-bold">{{ $ridesCount }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Rides Card -->
        <div class="col-xxl-4 col-sm-6">
            <div class="card bg--warning has-link-box box--shadow2">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-4">
                            <i class="las la-clock f-size--56 text-white"></i>
                        </div>
                        <div class="col-8 text-end">
                            <span class="text-white fs-18">Viajes Activos</span>
                            <h2 class="text-white fw-bold">{{ $activeRides }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fleet configuration summary -->
    <div class="card mt-4 box--shadow2">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Información de la Flota</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>Código de Invitación para Conductores</th>
                            <td><strong class="text--primary">{{ $fleet->invite_code }}</strong></td>
                        </tr>
                        <tr>
                            <th>Nombre de Flota</th>
                            <td>{{ $fleet->name }}</td>
                        </tr>
                        <tr>
                            <th>Email de Contacto</th>
                            <td>{{ $fleet->email }}</td>
                        </tr>
                        <tr>
                            <th>Comisión cobrada por Lizto</th>
                            <td>{{ $fleet->lizto_commission_rate }}% por viaje</td>
                        </tr>
                        <tr>
                            <th>Comisión cobrada a tus conductores</th>
                            <td>{{ $fleet->driver_commission_rate }}% por viaje</td>
                        </tr>
                        <tr>
                            <th>Tarifa Base (Personalizada)</th>
                            <td>{{ $fleet->base_fare ? '$' . number_format($fleet->base_fare, 2) : 'Predeterminada del sistema' }}</td>
                        </tr>
                        <tr>
                            <th>Tarifa por KM (Personalizada)</th>
                            <td>{{ $fleet->rate_per_km ? '$' . number_format($fleet->rate_per_km, 2) : 'Predeterminada del sistema' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
