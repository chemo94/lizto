@extends('admin.layouts.app')
@section('panel')
<form method="POST" action="{{ route('admin.delivery.commission.update') }}">
@csrf
<div class="row">
    <!-- Comisión Base Repartidor -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header bg--primary text-white d-flex align-items-center justify-content-between">
                <h5 class="text-white mb-0"><i class="las la-motorcycle"></i> Configuración General Repartidor</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="fw-bold">Tipo de Comisión</label>
                    <select name="courier_commission_type" class="form-select">
                        <option value="percent" {{ ($commission->courier_commission_type ?? 'percent') == 'percent' ? 'selected' : '' }}>Porcentaje (%)</option>
                        <option value="fixed" {{ ($commission->courier_commission_type ?? 'percent') == 'fixed' ? 'selected' : '' }}>Monto Fijo (S/)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">% Comisión Base Delivery</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="delivery_percent" class="form-control" value="{{ $commission->delivery_percent ?? 10 }}">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Porcentaje referencial base para envíos de comida y paquetes.</small>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">% Comisión Favor / Mandados</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="favor_percent" class="form-control" value="{{ $commission->favor_percent ?? 15 }}">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Monto Fijo Repartidor (Si aplica tipo Fijo)</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" step="0.01" name="courier_fixed_amount" class="form-control" value="{{ $commission->courier_fixed_amount ?? 0 }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comisión Tienda y Mínimos -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header bg--dark text-white d-flex align-items-center justify-content-between">
                <h5 class="text-white mb-0"><i class="las la-store"></i> Comisión Tienda y Límites</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="fw-bold">Tipo de Comisión Tienda</label>
                    <select name="store_commission_type" class="form-select">
                        <option value="percent" {{ ($commission->store_commission_type ?? 'percent') == 'percent' ? 'selected' : '' }}>Porcentaje (%)</option>
                        <option value="fixed" {{ ($commission->store_commission_type ?? 'percent') == 'fixed' ? 'selected' : '' }}>Monto Fijo (S/)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">% Comisión Tienda (sobre subtotal productos)</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="store_commission_percent" class="form-control" value="{{ $commission->store_commission_percent ?? 5 }}">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Monto Fijo Tienda</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" step="0.01" name="store_fixed_amount" class="form-control" value="{{ $commission->store_fixed_amount ?? 0 }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Comisión Mínima por Pedido</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" step="0.01" name="min_commission" class="form-control" value="{{ $commission->min_commission ?? 1 }}">
                    </div>
                    <small class="text-muted">Monto mínimo en soles que la plataforma cobrará por pedido.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NIVELES DE COMISIÓN POR ENTREGAS (GAMIFICACIÓN) -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4 border-success">
            <div class="card-header bg--success text-white d-flex align-items-center justify-content-between">
                <h5 class="text-white mb-0"><i class="las la-trophy"></i> Niveles de Comisión para Repartidores (Por Entregas Acumuladas)</h5>
                <span class="badge bg-white text-success fw-bold">Gamificación Activa</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-4 d-flex align-items-center gap-2">
                    <i class="las la-info-circle fs-4"></i>
                    <div>
                        A medida que los repartidores completan pedidos, suben de nivel y obtienen mejores comisiones. 
                        <strong>El nivel Preferente (30+ entregas) ahora se cobra al 10.00%</strong> (o el porcentaje que configures abajo), en lugar de estar en 0%.
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Nivel Inicial -->
                    <div class="col-md-3">
                        <div class="card h-100 border-primary shadow-none" style="background:#f0f9ff">
                            <div class="card-body text-center">
                                <div class="fs-1 mb-1">🛵</div>
                                <h6 class="fw-bold text-primary mb-1">Nivel Inicial</h6>
                                <span class="badge bg-primary mb-2">0 a 9 pedidos</span>
                                <p class="text-muted small mb-2">Comisión para repartidores recién incorporados.</p>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" name="tier_inicial_percent" class="form-control text-center fw-bold" value="{{ $commission->tier_inicial_percent ?? 15.0 }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nivel Bronce -->
                    <div class="col-md-3">
                        <div class="card h-100 border-warning shadow-none" style="background:#fffbeb">
                            <div class="card-body text-center">
                                <div class="fs-1 mb-1">🥉</div>
                                <h6 class="fw-bold text-warning mb-1">Nivel Bronce</h6>
                                <span class="badge bg-warning text-dark mb-2">10 a 19 pedidos</span>
                                <p class="text-muted small mb-2">Primer beneficio por constancia en entregas.</p>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" name="tier_bronce_percent" class="form-control text-center fw-bold" value="{{ $commission->tier_bronce_percent ?? 13.0 }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nivel Plata -->
                    <div class="col-md-3">
                        <div class="card h-100 border-secondary shadow-none" style="background:#f8fafc">
                            <div class="card-body text-center">
                                <div class="fs-1 mb-1">🥈</div>
                                <h6 class="fw-bold text-secondary mb-1">Nivel Plata</h6>
                                <span class="badge bg-secondary mb-2">20 a 29 pedidos</span>
                                <p class="text-muted small mb-2">Repartidores activos con buen volumen.</p>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" name="tier_plata_percent" class="form-control text-center fw-bold" value="{{ $commission->tier_plata_percent ?? 11.0 }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nivel Preferente -->
                    <div class="col-md-3">
                        <div class="card h-100 border-success shadow-sm" style="background:#f0fdf4; border-width: 2px;">
                            <div class="card-body text-center">
                                <div class="fs-1 mb-1">⭐</div>
                                <h6 class="fw-bold text-success mb-1">Nivel Preferente</h6>
                                <span class="badge bg-success mb-2">30 o más pedidos</span>
                                <p class="text-muted small mb-2">Repartidores VIP con la tarifa preferencial.</p>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" name="tier_preferente_percent" class="form-control text-center fw-bold text-success" value="{{ $commission->tier_preferente_percent ?? 10.0 }}">
                                    <span class="input-group-text bg-success text-white fw-bold">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn--primary px-5 py-2">
                        <i class="las la-save me-1"></i> Guardar Toda la Configuración
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

<!-- VISTA PREVIA DE CÁLCULO POR NIVELES -->
<div class="row mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="las la-calculator"></i> Simulación de Liquidación para Repartidores (Envío S/ 30.00)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle text-center mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Nivel Repartidor</th>
                                <th>Entregas Acumuladas</th>
                                <th>% Comisión</th>
                                <th>Tarifa Envío</th>
                                <th>Comisión Plataforma</th>
                                <th>Ganancia Neta Repartidor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $tIni = (float)($commission->tier_inicial_percent ?? 15.0);
                                $tBro = (float)($commission->tier_bronce_percent ?? 13.0);
                                $tPla = (float)($commission->tier_plata_percent ?? 11.0);
                                $tPre = (float)($commission->tier_preferente_percent ?? 10.0);
                                $feeSim = 30.00;
                            @endphp
                            <tr>
                                <td><span class="badge bg-primary">🛵 Inicial</span></td>
                                <td>0 a 9 pedidos</td>
                                <td class="fw-bold">{{ number_format($tIni, 2) }}%</td>
                                <td>S/ {{ number_format($feeSim, 2) }}</td>
                                <td class="text-danger fw-bold">S/ {{ number_format($feeSim * $tIni / 100, 2) }}</td>
                                <td class="text-success fw-bold">S/ {{ number_format($feeSim - ($feeSim * $tIni / 100), 2) }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark">🥉 Bronce</span></td>
                                <td>10 a 19 pedidos</td>
                                <td class="fw-bold">{{ number_format($tBro, 2) }}%</td>
                                <td>S/ {{ number_format($feeSim, 2) }}</td>
                                <td class="text-danger fw-bold">S/ {{ number_format($feeSim * $tBro / 100, 2) }}</td>
                                <td class="text-success fw-bold">S/ {{ number_format($feeSim - ($feeSim * $tBro / 100), 2) }}</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-secondary">🥈 Plata</span></td>
                                <td>20 a 29 pedidos</td>
                                <td class="fw-bold">{{ number_format($tPla, 2) }}%</td>
                                <td>S/ {{ number_format($feeSim, 2) }}</td>
                                <td class="text-danger fw-bold">S/ {{ number_format($feeSim * $tPla / 100, 2) }}</td>
                                <td class="text-success fw-bold">S/ {{ number_format($feeSim - ($feeSim * $tPla / 100), 2) }}</td>
                            </tr>
                            <tr class="table-success">
                                <td><span class="badge bg-success">⭐ Preferente</span></td>
                                <td>30 o más pedidos</td>
                                <td class="fw-bold text-success">{{ number_format($tPre, 2) }}%</td>
                                <td>S/ {{ number_format($feeSim, 2) }}</td>
                                <td class="text-danger fw-bold">S/ {{ number_format($feeSim * $tPre / 100, 2) }}</td>
                                <td class="text-success fw-bold fs-6">S/ {{ number_format($feeSim - ($feeSim * $tPre / 100), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
