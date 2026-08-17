@extends('admin.layouts.app')
@section('panel')
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('admin.delivery.withdrawals') }}" class="btn btn--primary btn-sm">Todos</a>
                    <a href="{{ route('admin.delivery.withdrawals', ['status' => 'pending']) }}" class="btn btn--warning btn-sm">
                        Pendientes ({{ $pendingCount }})
                    </a>
                    <a href="{{ route('admin.delivery.withdrawals', ['status' => 'approved']) }}" class="btn btn--success btn-sm">
                        Aprobados ({{ $approvedCount }})
                    </a>
                    <a href="{{ route('admin.delivery.withdrawals', ['status' => 'rejected']) }}" class="btn btn--danger btn-sm">
                        Rechazados ({{ $rejectedCount }})
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5>{{ $pageTitle }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--md">
                    <table class="table table--light">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Titular</th>
                                <th>TRX</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($withdrawals as $w)
                            <tr>
                                <td>{{ $w->id }}</td>
                                <td>
                                    @if($w->user_type)
                                        <span class="badge badge--info">{{ class_basename($w->user_type) }}</span>
                                    @elseif($w->driver_id)
                                        <span class="badge badge--primary">Driver</span>
                                    @else
                                        <span class="badge badge--dark">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($w->user_type === 'App\Models\Seller')
                                        {{ optional(\App\Models\Seller::find($w->user_id))->name ?? 'Vendedor #'.$w->user_id }}
                                    @elseif($w->user_type === 'App\Models\Driver' || $w->user_type)
                                        {{ optional(\App\Models\Driver::find($w->user_id))->fullname ?? 'Repartidor #'.$w->user_id }}
                                    @elseif($w->driver_id)
                                        {{ $w->driver?->fullname ?? 'Repartidor #'.$w->driver_id }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td><small>{{ $w->trx }}</small></td>
                                <td>S/ {{ number_format($w->amount, 2) }}</td>
                                <td>@php echo $w->statusBadge @endphp</td>
                                <td>{{ showDateTime($w->created_at) }}</td>
                                <td>
                                    @if($w->status == App\Constants\Status::PAYMENT_PENDING)
                                        <div class="d-flex gap-2">
                                            <form method="POST" action="{{ route('admin.delivery.withdrawal.approve') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $w->id }}">
                                                <button type="submit" class="btn btn-sm btn--success" onclick="return confirm('¿Aprobar este retiro?')">
                                                    <i class="las la-check"></i> Aprobar
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn--danger reject-btn"
                                                data-id="{{ $w->id }}">
                                                <i class="las la-times"></i> Rechazar
                                            </button>
                                        </div>
                                    @else
                                        @if($w->rejection_reason)
                                            <small class="text--danger" title="{{ $w->rejection_reason }}">
                                                {{ Str::limit($w->rejection_reason, 30) }}
                                            </small>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center">No hay retiros</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($withdrawals->hasPages())
            <div class="card-footer">{{ paginateLinks($withdrawals) }}</div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Rechazar Retiro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.delivery.withdrawal.reject') }}">
                @csrf
                <input type="hidden" name="id" id="rejectWithdrawalId">
                <div class="modal-body">
                    <label>Motivo del rechazo</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Motivo opcional..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn--danger">Rechazar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('rejectWithdrawalId').value = this.dataset.id;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        });
    });
</script>
@endpush
