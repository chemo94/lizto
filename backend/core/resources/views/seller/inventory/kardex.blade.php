@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-list-alt"></i></span> Kardex e Historial
@endsection

@section('seller-content')
<div class="s-content">

    <!-- SELECTOR DE INSUMO -->
    <div class="s-card" style="margin-bottom: 24px;">
        <form method="GET" action="{{ route('seller.inventory.kardex') }}">
            <div style="display: flex; flex-direction: column; gap: 6px; max-width: 400px;">
                <label class="s-label" style="font-weight: 700;">Seleccionar Insumo para ver Movimientos</label>
                <select class="s-input" name="item_id" onchange="this.form.submit()">
                    <option value="">Seleccionar insumo...</option>
                    @foreach($items as $it)
                    <option value="{{ $it->id }}" {{ $selectedItem && $selectedItem->id == $it->id ? 'selected' : '' }}>
                        {{ $it->name }} ({{ number_format($it->stock, 2) }} {{ $it->unit }})
                    </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if($selectedItem)
    <div class="s-grid-2" style="grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
        
        <!-- RESUMEN & AJUSTE RÁPIDO -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- TARJETA INSUMO DETALLE -->
            <div class="s-card">
                <h3 style="margin-bottom: 16px; font-weight: 700; font-size: 15px; color: var(--s-text-primary);">
                    Insumo Seleccionado
                </h3>
                
                <div style="font-weight: 800; font-size: 18px; color: var(--s-text-primary); margin-bottom: 20px;">
                    {{ $selectedItem->name }}
                </div>

                <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1; background: var(--s-bg-light); padding: 12px; border-radius: 8px; text-align: center;">
                        <span style="display: block; font-size: 11px; color: var(--s-text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Stock Actual</span>
                        <span style="font-size: 16px; font-weight: 800; color: var(--s-text-primary);">
                            {{ number_format($selectedItem->stock, 2) }} <span style="font-size:12px; font-weight: 600; color:var(--s-text-secondary);">{{ $selectedItem->unit }}</span>
                        </span>
                    </div>

                    <div style="flex: 1; background: var(--s-bg-light); padding: 12px; border-radius: 8px; text-align: center;">
                        <span style="display: block; font-size: 11px; color: var(--s-text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Costo Promedio</span>
                        <span style="font-size: 16px; font-weight: 800; color: var(--s-success);">
                            S/ {{ number_format($selectedItem->cost, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- AJUSTE RÁPIDO -->
            <div class="s-card">
                <h3 style="margin-bottom: 16px; font-weight: 700; font-size: 15px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="las la-tools" style="color: var(--s-primary); font-size: 18px;"></i> Ajuste de Stock
                </h3>

                <form method="POST" action="{{ route('seller.inventory.stock-adjust') }}">
                    @csrf
                    <input type="hidden" name="item_id" value="{{ $selectedItem->id }}">
                    
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label class="s-label">Tipo de Movimiento</label>
                            <select class="s-input" name="type" required>
                                <option value="entrada">Entrada (Ingreso)</option>
                                <option value="salida">Salida (Egreso/Mermas)</option>
                            </select>
                        </div>

                        <div>
                            <label class="s-label">Cantidad ({{ $selectedItem->unit }})</label>
                            <input class="s-input" type="number" name="quantity" step="0.01" placeholder="Ej: 5.50" required>
                        </div>

                        <div>
                            <label class="s-label">Motivo u Observación</label>
                            <input class="s-input" name="description" placeholder="Ej: Corrección de stock / Merma de insumo" required>
                        </div>

                        <button class="s-btn s-btn-primary" style="margin-top: 4px; justify-content: center; width: 100%;">
                            <i class="las la-check-circle" style="font-size:16px;"></i> Aplicar Ajuste
                        </button>
                    </div>
                </form>
            </div>
            
        </div>

        <!-- TABLA KARDEX MOVIMIENTOS -->
        <div class="s-card">
            <h3 style="margin-bottom: 16px; font-weight: 700; font-size: 16px; color: var(--s-text-primary);">
                Historial de Movimientos (Kardex)
            </h3>

            <div class="s-table-wrapper">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th style="width: 100px;">Tipo</th>
                            <th>Referencia</th>
                            <th style="text-align: right; width: 80px;">Entrada</th>
                            <th style="text-align: right; width: 80px;">Salida</th>
                            <th style="text-align: right; width: 95px;">Saldo Stock</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                        <tr>
                            <td style="font-size: 11px; white-space: nowrap;">
                                {{ $m->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                @if($m->type === 'entrada')
                                <span class="s-badge s-badge-green" style="font-size: 10px; padding: 2px 6px;">Entrada</span>
                                @else
                                <span class="s-badge s-badge-red" style="font-size: 10px; padding: 2px 6px;">Salida</span>
                                @endif
                            </td>
                            <td style="font-size: 11px; color: var(--s-text-secondary);">
                                {{ $m->reference_type ?: 'Ajuste Manual' }}
                                @if($m->reference_id)
                                 #{{ $m->reference_id }}
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 600; color: var(--s-success);">
                                {{ $m->quantity > 0 ? number_format($m->quantity, 2) : '—' }}
                            </td>
                            <td style="text-align: right; font-weight: 600; color: var(--s-danger);">
                                {{ $m->quantity < 0 ? number_format(abs($m->quantity), 2) : '—' }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--s-text-primary);">
                                {{ number_format($m->balance_stock, 2) }}
                            </td>
                            <td style="font-size: 11px; color: var(--s-text-secondary); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $m->description }}">
                                {{ $m->description }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px 20px; color: var(--s-text-muted);">
                                <i class="las la-history" style="font-size: 40px; color: var(--s-border); display: block; margin-bottom: 8px;"></i>
                                Ningún movimiento registrado para este insumo.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    @else
    <div class="s-card" style="text-align: center; padding: 60px 20px; color: var(--s-text-muted);">
        <i class="las la-arrow-up" style="font-size: 44px; color: var(--s-primary); display: block; margin-bottom: 12px; animation: bounce 2s infinite;"></i>
        <h4 style="color: var(--s-text-primary); margin-bottom: 4px; font-weight: 600;">Seleccione un insumo</h4>
        <p style="font-size: 13px;">Elija un insumo de la lista superior para visualizar e interactuar con su Kardex y aplicar ajustes.</p>
    </div>
    @endif

</div>

<style>
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
</style>
@endsection
