@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card b-radius--10 ">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0">{{ $pageTitle }}</h5>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar #ticket o nombre..." value="{{ request('search') }}">
                    <select name="status" class="form-select form-select-sm" style="width:auto">
                        <option value="">Todos los Estados</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pendiente</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>En Proceso</option>
                        <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Resuelto</option>
                        <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Rechazado</option>
                    </select>
                    <button type="submit" class="btn btn--primary btn-sm"><i class="las la-search"></i> Buscar</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--md table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th># Ticket</th>
                                <th>Consumidor</th>
                                <th>Tipo</th>
                                <th>Bien</th>
                                <th>Monto Reclamado</th>
                                <th>Estado</th>
                                <th>Fecha de Registro</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($complaints as $c)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ $c->ticket_number }}</span>
                                </td>
                                <td>
                                    <span class="d-block">{{ $c->full_name }}</span>
                                    <span class="text-muted fs-11">{{ $c->document_type }}: {{ $c->document_number }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $c->claim_type == 1 ? 'badge--danger' : 'badge--warning' }}">
                                        {{ $c->type_name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="d-block">{{ $c->item_type_name }}</span>
                                    <span class="text-muted fs-11">{{ strLimit($c->item_description, 25) }}</span>
                                </td>
                                <td class="fw-bold">
                                    S/ {{ number_format($c->amount_claimed, 2) }}
                                </td>
                                <td>
                                    {!! $c->status_badge !!}
                                </td>
                                <td>
                                    {{ $c->created_at->format('d/m/Y H:i A') }}
                                </td>
                                <td>
                                    <div class="button--group">
                                        <a href="{{ route('admin.complaints.details', $c->id) }}" class="btn btn-sm btn-outline--primary">
                                            <i class="las la-eye"></i> Resolver
                                        </a>
                                        <a href="{{ route('complaints.pdf', $c->ticket_number) }}" target="_blank" class="btn btn-sm btn-outline--dark">
                                            <i class="las la-file-pdf"></i> PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="8">No hay reclamos ni quejas registrados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($complaints->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($complaints) }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
