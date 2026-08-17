@extends('admin.layouts.app')
@section('panel')
<div class="row gy-4">
    <!-- LEFT PANEL: CUSTOMER & CONTRACTED GOODS -->
    <div class="col-xl-5 col-lg-6">
        <div class="card b-radius--10 mb-4">
            <div class="card-header bg--dark">
                <h5 class="card-title text-white mb-0"><i class="las la-user-tag me-1"></i> Información del Reclamante</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <span class="text-muted">Nombre Completo</span>
                        <span class="fw-bold">{{ $complaint->full_name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <span class="text-muted">Documento</span>
                        <span>{{ $complaint->document_type }} - {{ $complaint->document_number }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <span class="text-muted">Teléfono / Celular</span>
                        <span>{{ $complaint->phone }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <span class="text-muted">Email</span>
                        <a href="mailto:{{ $complaint->email }}" class="fw-bold text--primary">{{ $complaint->email }}</a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <span class="text-muted">Domicilio</span>
                        <span class="text-end" style="max-width: 60%;">{{ $complaint->address }}</span>
                    </li>
                    
                    @if($complaint->is_minor)
                    <li class="list-group-item px-0 pt-3">
                        <div class="p-3 bg--light border rounded-3">
                            <h6 class="fw-bold mb-2 text--danger"><i class="las la-user-shield me-1"></i> Apoderado / Representante</h6>
                            <p class="mb-1 text-dark"><strong>Nombre:</strong> {{ $complaint->guardian_name }}</p>
                            <p class="mb-0 text-muted"><strong>Doc:</strong> {{ $complaint->guardian_document_type }} - {{ $complaint->guardian_document_number }}</p>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="card b-radius--10">
            <div class="card-header bg--dark">
                <h5 class="card-title text-white mb-0"><i class="las la-shopping-bag me-1"></i> Detalle del Bien Contratado</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <span class="text-muted">Tipo de Bien</span>
                        <span class="badge badge--dark">{{ $complaint->item_type_name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <span class="text-muted">Monto Reclamado</span>
                        <span class="fw-bold text--success">S/ {{ number_format($complaint->amount_claimed, 2) }}</span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1">Descripción del bien:</span>
                        <div class="p-3 bg--light border rounded-3 fs-13 text-dark">
                            {{ $complaint->item_description }}
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL: DISCONFORMITY DETAILS & RESOLUTION FORM -->
    <div class="col-xl-7 col-lg-6">
        <div class="card b-radius--10 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="las la-exclamation-triangle me-1"></i> Detalles de la Disconformidad</h5>
                <span class="badge {{ $complaint->claim_type == 1 ? 'badge--danger' : 'badge--warning' }} px-3 py-2 fs-12">
                    {{ $complaint->type_name }}
                </span>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="text-muted fw-bold mb-1">Detalle de Reclamación:</label>
                    <div class="p-3 bg--light border rounded-3 text-dark fs-13" style="white-space: pre-wrap;">{{ $complaint->detail }}</div>
                </div>
                <div class="mb-2">
                    <label class="text-muted fw-bold mb-1">Pedido Concreto del Consumidor:</label>
                    <div class="p-3 bg--light border rounded-3 text-dark fs-13" style="white-space: pre-wrap;">{{ $complaint->request }}</div>
                </div>
            </div>
        </div>

        <!-- RESOLUTION ACTION FORM -->
        <div class="card b-radius--10">
            <div class="card-header bg--dark">
                <h5 class="card-title text-white mb-0"><i class="las la-reply me-1"></i> Acciones del Proveedor (Respuesta / Solución)</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.complaints.update', $complaint->id) }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="fw-bold">Estado de Atención</label>
                        <select name="status" class="form-control form-select" required>
                            <option value="1" {{ $complaint->status == 1 ? 'selected' : '' }}>En Proceso</option>
                            <option value="2" {{ $complaint->status == 2 ? 'selected' : '' }}>Resuelto</option>
                            <option value="3" {{ $complaint->status == 3 ? 'selected' : '' }}>Rechazado</option>
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label class="fw-bold">Detalle de las Acciones Adoptadas (Respuesta oficial)</label>
                        <textarea name="provider_actions" rows="8" class="form-control" placeholder="Escribe aquí las medidas adoptadas, investigación realizada o respuesta detallada hacia el consumidor..." required>{{ old('provider_actions', $complaint->provider_actions) }}</textarea>
                    </div>

                    @if($complaint->responded_at)
                    <div class="alert alert--info p-3 mb-4 rounded-3">
                        <i class="las la-info-circle me-1"></i> Respuesta guardada y notificada el <strong>{{ $complaint->responded_at->format('d/m/Y h:i A') }}</strong>. Puedes modificarla si es necesario.
                    </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('complaints.pdf', $complaint->ticket_number) }}" target="_blank" class="btn btn-outline-secondary">
                            <i class="las la-file-pdf"></i> Ver PDF de Hoja
                        </a>
                        <button type="submit" class="btn btn--primary"><i class="las la-check-circle"></i> Registrar Respuesta y Enviar Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
