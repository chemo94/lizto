@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-none-30">
        <div class="col-xl-4 col-lg-5 mb-30">
            <div class="card b-radius--10 overflow-hidden box--shadow1">
                <div class="card-body p-0">
                    <div class="p-3 bg--white text-center">
                        <h4 class="mt-2">{{ $fleet->name }}</h4>
                        <span class="text--small text-muted">Empresa de Transporte</span>
                        <div class="mt-2">
                            <span class="badge badge--primary">Código: {{ $fleet->invite_code }}</span>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Dueño
                            <span class="fw-bold">{{ $fleet->owner->firstname }} {{ $fleet->owner->lastname }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Usuario del Dueño
                            <span class="fw-bold">{{ $fleet->owner->username }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Teléfono
                            <span>{{ $fleet->owner->phone }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Email Contacto
                            <span>{{ $fleet->email }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Ciudad de Operación
                            <span class="fw-bold text--primary">{{ $fleet->zone ? __($fleet->zone->name) : 'No configurada' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Plan Actual
                            @if($fleet->commission_type == 'subscription')
                                <span class="badge badge--success">Plan Lizto Partner</span>
                            @else
                                <span class="badge badge--warning">Porcentaje</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Estado
                            @if($fleet->status == 1)
                                <span class="badge badge--success">Activo</span>
                            @else
                                <span class="badge badge--danger">Suspendido</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Saldo de Billetera
                            <span class="fw-bold text--success">S/ {{ showAmount($fleet->owner->wallet?->balance ?? 0) }}</span>
                        </li>
                    </ul>
                    <div class="p-3">
                        <button type="button" class="btn btn--primary w-100" data-bs-toggle="modal" data-bs-target="#adjustModal">
                            <i class="las la-money-bill-wave"></i> Ajustar Saldo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7 mb-30">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Configurar Tasas y Comisiones</h5>
                    <form action="{{ route('admin.fleet.update', $fleet->id) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Nombre de la Empresa</label>
                                <input type="text" name="name" class="form-control" value="{{ $fleet->name }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Email de Contacto</label>
                                <input type="email" name="email" class="form-control" value="{{ $fleet->email }}" required>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6 form-group">
                                <label>Tasa de Comisión de Lizto (Solo Plan Porcentaje)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="lizto_commission_rate" class="form-control" value="{{ $fleet->lizto_commission_rate }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">No se aplicará si el plan actual es Plan Lizto Partner (Suscripción).</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Tasa de Comisión de Flota (Cobrado a Conductores)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="driver_commission_rate" class="form-control" value="{{ $fleet->driver_commission_rate }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Porcentaje cobrado por el dueño de la flota a sus conductores vinculados por cada viaje.</small>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4 form-group">
                                <label>Plan / Tipo de Comisión</label>
                                <select name="commission_type" class="form-control" required>
                                    <option value="subscription" {{ $fleet->commission_type == 'subscription' ? 'selected' : '' }}>Plan Lizto Partner (Suscripción Fija)</option>
                                    <option value="percentage" {{ $fleet->commission_type == 'percentage' ? 'selected' : '' }}>Comisión por Porcentaje</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Ciudad / Zona de Operación</label>
                                <select name="zone_id" class="form-control">
                                    <option value="">-- Sin Ciudad --</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ $fleet->zone_id == $zone->id ? 'selected' : '' }}>{{ __($zone->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Estado de la Cuenta</label>
                                <select name="status" class="form-control" required>
                                    <option value="1" {{ $fleet->status == 1 ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ $fleet->status == 0 ? 'selected' : '' }}>Suspendido</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn--primary w-100 h-45">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Conductores Asignados ({{ $fleet->drivers->count() }})</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Conductor</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fleet->drivers as $driver)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $driver->firstname }} {{ $driver->lastname }}</span>
                                            <span class="d-block small text-muted">{{ $driver->username }}</span>
                                        </td>
                                        <td>{{ $driver->email }}</td>
                                        <td>{{ $driver->mobile }}</td>
                                        <td>
                                            @if($driver->status == 1)
                                                <span class="badge badge--success">Activo</span>
                                            @else
                                                <span class="badge badge--danger">Bloqueado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">No hay conductores vinculados a esta flota</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Transactions Log -->
            <div class="card mt-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Historial de Transacciones de Billetera</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Trx</th>
                                    <th>Monto</th>
                                    <th>Post Saldo</th>
                                    <th>Detalles</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                    <tr>
                                        <td><span class="fw-bold">{{ $trx->trx }}</span></td>
                                        <td>
                                            <span class="fw-bold @if($trx->trx_type == '+') text--success @else text--danger @endif">
                                                {{ $trx->trx_type }}{{ showAmount($trx->amount) }} PEN
                                            </span>
                                        </td>
                                        <td>S/ {{ showAmount($trx->post_balance) }}</td>
                                        <td>{{ $trx->details }}</td>
                                        <td>{{ showDateTime($trx->created_at) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">No hay transacciones registradas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Adjust Balance Modal -->
    <div id="adjustModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajustar Saldo de la Billetera</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.fleet.adjust.balance', $fleet->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tipo de Ajuste</label>
                            <select name="type" class="form-control" required>
                                <option value="1">Agregar Saldo (Crédito)</option>
                                <option value="2">Descontar Saldo (Débito)</option>
                            </select>
                        </div>
                        <div class="form-group mt-3">
                            <label>Monto</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="form-group mt-3">
                            <label>Detalle / Glosa / Motivo</label>
                            <textarea name="remark" class="form-control" rows="3" placeholder="Ej: Recarga manual o pago de membresía" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn--primary">Confirmar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
