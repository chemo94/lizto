@extends('seller.layouts.app')

@section('seller-content')
<div class="s-content">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="las la-cash-register me-2 text-success"></i>Cajas Registradoras</h4>
            <p class="text-muted small mb-0 mt-1">Administra los puntos de caja de tu negocio</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                {{ $registers->count() }} / {{ $maxRegisters }} cajas permitidas
            </span>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevaCaja">
                <i class="las la-plus"></i> Nueva Caja
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- REGISTERS GRID --}}
    @if($registers->isEmpty())
        <div class="text-center py-5">
            <div style="font-size:64px;color:#d1d5db;"><i class="las la-cash-register"></i></div>
            <h5 class="text-muted mt-3">Aún no tienes cajas configuradas</h5>
            <p class="text-muted">Crea tu primera caja para comenzar a gestionar sesiones y arqueos.</p>
            <button class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#modalNuevaCaja">
                <i class="las la-plus"></i> Crear primera caja
            </button>
        </div>
    @else
        <div class="row g-4">
            @foreach($registers as $reg)
            @php
                $session = $reg->openSession;
                $hasOpen = !is_null($session);
                $assignedStaff = $reg->staff;
            @endphp
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius:16px;overflow:hidden;">
                    {{-- Header --}}
                    <div class="card-header border-0 d-flex align-items-center justify-content-between py-3 px-4"
                        style="background:{{ $hasOpen ? 'linear-gradient(135deg,#16a34a,#22c55e)' : 'linear-gradient(135deg,#1e293b,#334155)' }}">
                        <div>
                            <span class="badge {{ $reg->is_active ? 'bg-white text-success' : 'bg-secondary' }} mb-1">
                                {{ $reg->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                            <h5 class="text-white mb-0 fw-bold">{{ strtoupper($reg->name) }}</h5>
                            @if($reg->description)
                                <small class="text-white-50">{{ $reg->description }}</small>
                            @endif
                        </div>
                        <div class="text-white-50">
                            <i class="las la-cash-register" style="font-size:36px;opacity:0.4;"></i>
                        </div>
                    </div>

                    <div class="card-body px-4 py-3">
                        {{-- Session Status --}}
                        @if($hasOpen)
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="dot-pulse"></span>
                                <span class="text-success fw-semibold small">Sesión abierta</span>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <small class="text-muted d-block">Apertura</small>
                                        <strong>S/ {{ number_format($session->opening_balance, 2) }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <small class="text-muted d-block">Ventas</small>
                                        <strong class="text-success">S/ {{ number_format($session->total_sales, 2) }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <small class="text-muted d-block">Ingresos</small>
                                        <strong class="text-primary">S/ {{ number_format($session->total_cash_in, 2) }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-mini-box">
                                        <small class="text-muted d-block">Egresos</small>
                                        <strong class="text-danger">S/ {{ number_format($session->total_cash_out + $session->total_expenses, 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="text-muted small mb-2">
                                <i class="las la-clock"></i>
                                Abierta: {{ $session->opened_at?->format('d/m/Y H:i') }}
                            </div>
                        @else
                            <div class="text-center py-3 text-muted">
                                <i class="las la-lock" style="font-size:32px;opacity:0.3;"></i>
                                <p class="small mb-0 mt-1">Sin sesión activa</p>
                                <small>{{ $reg->cash_sessions_count }} sesión(es) registrada(s)</small>
                            </div>
                        @endif

                        {{-- Assigned Staff --}}
                        @if($assignedStaff->count() > 0)
                        <div class="border-top pt-2 mt-2">
                            <small class="text-muted d-block mb-1"><i class="las la-users"></i> Personal asignado:</small>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($assignedStaff as $s)
                                    <span class="badge bg-light text-dark border">{{ $s->name }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="card-footer border-0 bg-light px-4 py-3">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('seller.cash') }}?register={{ $reg->id }}" class="btn btn-sm {{ $hasOpen ? 'btn-success' : 'btn-outline-success' }}">
                                <i class="las la-{{ $hasOpen ? 'eye' : 'play' }}"></i>
                                {{ $hasOpen ? 'Ver caja' : 'Abrir sesión' }}
                            </a>

                            {{-- Assign Staff Button --}}
                            @if($staff->count() > 0)
                            <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalAsignarStaff{{ $reg->id }}">
                                <i class="las la-user-plus"></i>
                            </button>
                            @endif

                            {{-- Toggle active --}}
                            <form action="{{ route('seller.registers.toggle', $reg->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $reg->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    title="{{ $reg->is_active ? 'Desactivar' : 'Activar' }}">
                                    <i class="las la-{{ $reg->is_active ? 'pause' : 'play-circle' }}"></i>
                                </button>
                            </form>

                            {{-- Delete (only if no open session) --}}
                            @if(!$hasOpen)
                            <form action="{{ route('seller.registers.delete', $reg->id) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('¿Eliminar la caja {{ $reg->name }}? Se eliminarán también su historial de sesiones.')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="las la-trash-alt"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Assign Staff Modal --}}
            @if($staff->count() > 0)
            <div class="modal fade" id="modalAsignarStaff{{ $reg->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Asignar personal — {{ $reg->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('seller.registers.assign-staff', $reg->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small">Selecciona los usuarios que trabajarán en esta caja. Al abrir sesión, usarán automáticamente esta caja.</p>
                                @foreach($staff as $member)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        name="staff_ids[]"
                                        value="{{ $member->id }}"
                                        id="staff_{{ $reg->id }}_{{ $member->id }}"
                                        {{ $member->pos_register_id == $reg->id ? 'checked' : '' }}>
                                    <label class="form-check-label" for="staff_{{ $reg->id }}_{{ $member->id }}">
                                        {{ $member->name }}
                                        @if($member->pos_register_id && $member->pos_register_id != $reg->id)
                                            <small class="text-warning">(actualmente en otra caja)</small>
                                        @endif
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar asignación</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            @endforeach
        </div>
    @endif

    {{-- REGISTER LIMIT INFO --}}
    <div class="mt-5 p-4 rounded-3 border bg-light">
        <div class="d-flex align-items-center gap-3">
            <div style="font-size:36px;"><i class="las la-info-circle text-primary"></i></div>
            <div>
                <strong>Límite de cajas según tu plan:</strong>
                <p class="mb-0 text-muted small">Tu plan actual permite hasta <strong>{{ $maxRegisters }}</strong> caja(s).
                Puedes asignar cajeros a cada caja, y cada caja manejará sus propios arqueos, ingresos y egresos de forma independiente.</p>
            </div>
        </div>
    </div>
</div>

{{-- Modal Nueva Caja --}}
<div class="modal fade" id="modalNuevaCaja" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="las la-plus-circle me-2"></i>Nueva Caja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('seller.registers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre de la caja <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg"
                            placeholder="Ej: Caja 01, Caja Bar, Caja Principal..."
                            required maxlength="60">
                        <div class="form-text">Usa un nombre descriptivo. Ej: Caja 01, Caja 02, Caja Bar, Caja Delivery</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción <span class="text-muted">(opcional)</span></label>
                        <input type="text" name="description" class="form-control"
                            placeholder="Ej: Caja principal del salón" maxlength="120">
                    </div>

                    <div class="alert alert-info py-2 mb-0">
                        <small><i class="las la-lightbulb"></i>
                        Usarás <strong>{{ $registers->count() + 1 }}</strong> de <strong>{{ $maxRegisters }}</strong> cajas permitidas en tu plan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="las la-check"></i> Crear caja</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
.stat-mini-box {
    background: #f8fafc;
    border-radius: 10px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
}
.dot-pulse {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #22c55e;
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}
</style>
@endpush
