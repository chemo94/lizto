@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-exclamation-triangle"></i></span>
Mermas
@endsection

@section('seller-content')
<div class="s-content">

{{-- Resumen ──────────────────────────────────────────────────────────────── --}}
@php
    $totalMermas     = $wastes->where('status','active')->sum(fn($w) => $w->quantity * $w->item->cost);
    $countActive     = $wastes->where('status','active')->count();
    $countVoided     = $wastes->where('status','voided')->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    <div class="s-card" style="text-align:center;padding:20px 16px;">
        <div style="font-size:26px;font-weight:900;color:var(--s-danger);">{{ $countActive }}</div>
        <div style="font-size:12px;color:var(--s-text-3);">Mermas activas</div>
    </div>
    <div class="s-card" style="text-align:center;padding:20px 16px;">
        <div style="font-size:26px;font-weight:900;color:var(--s-warning);">S/ {{ number_format($totalMermas, 2) }}</div>
        <div style="font-size:12px;color:var(--s-text-3);">Costo mermas activas</div>
    </div>
    <div class="s-card" style="text-align:center;padding:20px 16px;">
        <div style="font-size:26px;font-weight:900;color:var(--s-text-3);">{{ $countVoided }}</div>
        <div style="font-size:12px;color:var(--s-text-3);">Anuladas</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start;" class="waste-layout">

    {{-- ── Formulario nueva merma ─────────────────────────────────────── --}}
    <div class="s-card">
        <h3 class="s-card-title"><i class="las la-plus-circle"></i> Registrar Merma</h3>
        <form method="POST" action="{{ route('seller.inventory.wastes.store') }}">
            @csrf
            <div class="s-input-group" style="margin-bottom:12px;">
                <label class="s-input-label">Insumo / Producto *</label>
                <select class="s-input" name="item_id" required>
                    <option value="">— Selecciona —</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->name }}
                        ({{ number_format($item->stock, 2) }} {{ $item->unit }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="s-input-group" style="margin-bottom:12px;">
                <label class="s-input-label">Cantidad *</label>
                <input class="s-input" type="number" name="quantity" step="0.001" min="0.001" required
                    placeholder="Ej: 0.5">
            </div>
            <div class="s-input-group" style="margin-bottom:12px;">
                <label class="s-input-label">Motivo *</label>
                <select class="s-input" name="reason" required>
                    <option value="caducidad">Caducidad / Vencimiento</option>
                    <option value="rotura">Rotura / Derrame</option>
                    <option value="preparacion">Merma de preparación</option>
                    <option value="accidente">Accidente</option>
                    <option value="calidad">Baja calidad</option>
                    <option value="hurto">Hurto / Robo</option>
                    <option value="conteo">Error de conteo</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="s-input-group" style="margin-bottom:12px;">
                <label class="s-input-label">Fecha *</label>
                <input class="s-input" type="date" name="waste_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="s-input-group" style="margin-bottom:16px;">
                <label class="s-input-label">Observaciones</label>
                <input class="s-input" name="notes" placeholder="Detalle adicional...">
            </div>
            <button type="submit" class="s-btn s-btn-primary" style="width:100%;justify-content:center;">
                <i class="las la-save"></i> Registrar Merma
            </button>
        </form>
    </div>

    {{-- ── Historial de mermas ─────────────────────────────────────────── --}}
    <div class="s-card">
        <h3 class="s-card-title"><i class="las la-history"></i> Historial de Mermas</h3>
        <div style="overflow-x:auto;">
        <table class="s-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Insumo</th>
                    <th>Cantidad</th>
                    <th>Costo</th>
                    <th>Motivo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($wastes as $waste)
            <tr style="{{ $waste->status === 'voided' ? 'opacity:.55;' : '' }}">
                <td style="font-size:11px;color:var(--s-text-3);">#{{ $waste->id }}</td>
                <td>
                    <b>{{ $waste->item->name ?? '—' }}</b>
                    <div style="font-size:11px;color:var(--s-text-3);">{{ $waste->item->unit ?? '' }}</div>
                </td>
                <td>
                    <b style="color:var(--s-danger)">{{ number_format($waste->quantity, 3) }}</b>
                    <span style="font-size:11px;color:var(--s-text-3);">{{ $waste->unit }}</span>
                </td>
                <td style="color:var(--s-warning);font-size:13px;">
                    S/ {{ number_format($waste->quantity * ($waste->item->cost ?? 0), 2) }}
                </td>
                <td>
                    <span class="s-badge s-badge-gray" style="font-size:10px;text-transform:capitalize;">
                        {{ str_replace('_', ' ', $waste->reason) }}
                    </span>
                </td>
                <td style="font-size:12px;color:var(--s-text-2);">
                    {{ $waste->waste_date?->format('d/m/Y') }}
                </td>
                <td>
                    @if($waste->status === 'active')
                        <span class="s-badge s-badge-red">Activa</span>
                    @else
                        <span class="s-badge s-badge-gray">Anulada</span>
                    @endif
                </td>
                <td>
                    @if($waste->status === 'active')
                    <form method="POST" action="{{ route('seller.inventory.wastes.void', $waste->id) }}"
                        onsubmit="return confirm('¿Anular merma y devolver stock?')" style="display:inline;">
                        @csrf
                        <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-warning);" title="Anular merma">
                            <i class="las la-undo"></i>
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;color:var(--s-text-3);padding:30px;">
                <i class="las la-check-circle" style="font-size:30px;margin-bottom:6px;display:block;color:var(--s-success)"></i>
                Sin mermas registradas
            </td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $wastes->links() }}
    </div>
</div>

</div>

@push('style')
<style>
@media(max-width:768px) {
    .waste-layout { grid-template-columns: 1fr !important; }
}
</style>
@endpush
@endsection
