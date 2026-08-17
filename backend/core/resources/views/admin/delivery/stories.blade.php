@extends('admin.layouts.app')
@section('panel')
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5>{{ $pageTitle }}</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('admin.delivery.stories.index') }}" class="btn btn-sm btn--primary">Todas ({{ $stats['total'] }})</a>
                    <a href="{{ route('admin.delivery.stories.index', ['status' => 'pending']) }}" class="btn btn-sm btn--warning">Pendientes ({{ $stats['pending'] }})</a>
                    <a href="{{ route('admin.delivery.stories.index', ['status' => 'active']) }}" class="btn btn-sm btn--success">Activas ({{ $stats['active'] }})</a>
                    <a href="{{ route('admin.delivery.stories.index', ['status' => 'rejected']) }}" class="btn btn-sm btn--danger">Rechazadas ({{ $stats['rejected'] }})</a>
                </div>

                <div class="table-responsive">
                    <table class="table table--light">
                        <thead><tr>
                            <th>ID</th><th>Tienda</th><th>Media</th><th>Caption</th>
                            <th>Presupuesto</th><th>Impresiones</th><th>Estado</th><th>Duración</th><th>Acción</th>
                        </tr></thead>
                        <tbody>
                        @forelse($stories as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>{{ $s->store->name ?? 'N/A' }}</td>
                            <td>
                                @if($s->media_type === 'image')
                                    <img src="{{ asset('assets/images/store_stories/'.$s->media_path) }}" height="50" class="rounded">
                                @else
                                    <span class="badge badge--info">Video</span>
                                @endif
                            </td>
                            <td>{{ \Str::limit($s->caption, 30) }}</td>
                            <td>S/ {{ number_format($s->budget, 2) }}</td>
                            <td>{{ $s->consumed_impressions }} / {{ $s->total_impressions }}<br>
                                <small class="text-muted">CPI: S/ {{ number_format($s->cpi, 4) }}</small>
                            </td>
                            <td>
                                @if($s->status === 'pending')
                                    <span class="badge badge--warning">Pendiente</span>
                                @elseif($s->status === 'active')
                                    <span class="badge badge--success">Activa</span>
                                @elseif($s->status === 'paused')
                                    <span class="badge badge--dark">Pausada</span>
                                @elseif($s->status === 'rejected')
                                    <span class="badge badge--danger">Rechazada</span>
                                @elseif($s->status === 'completed')
                                    <span class="badge badge--primary">Completada</span>
                                @endif
                            </td>
                            <td>
                                @if($s->ends_at)
                                    {{ $s->ends_at->diffForHumans() }}<br>
                                    <small>{{ $s->ends_at->format('d/m H:i') }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="button--group">
                                    @if($s->status === 'pending')
                                        <form action="{{ route('admin.delivery.stories.approve', $s->id) }}" method="POST" style="display:inline">
                                            @csrf
                                            <button class="btn btn-sm btn--success" title="Aprobar"><i class="las la-check"></i></button>
                                        </form>
                                        <button class="btn btn-sm btn--danger rejectBtn" data-id="{{ $s->id }}" title="Rechazar"><i class="las la-times"></i></button>
                                    @endif
                                    <form action="{{ route('admin.delivery.stories.delete', $s->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar permanentemente?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn--danger"><i class="las la-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center">No hay historias</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $stories->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST" id="rejectForm">
            @csrf
            <div class="modal-header"><h5>Rechazar Historia</h5></div>
            <div class="modal-body">
                <label>Motivo del rechazo</label>
                <textarea name="reason" class="form-control" rows="3" required placeholder="Explica por qué se rechaza..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn--danger">Rechazar</button>
            </div>
        </form>
    </div></div>
</div>
@endsection

@push('script')
<script>
(function($){ "use strict";
    $('.rejectBtn').on('click', function(){
        var modal = $('#rejectModal');
        modal.find('form').attr('action', '{{ route('admin.delivery.stories.reject', '') }}/' + $(this).data('id'));
        modal.modal('show');
    });
})(jQuery);
</script>
@endpush
