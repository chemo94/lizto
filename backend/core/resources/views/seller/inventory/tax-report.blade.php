@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-file-invoice-dollar"></i></span>
Reporte Tributario
@endsection

@section('seller-content')
<div class="s-content">

{{-- Selector de periodo ──────────────────────────────────────────────────── --}}
<div class="s-card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('seller.inventory.tax-report') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div class="s-input-group" style="margin:0;">
            <label class="s-input-label">Período</label>
            <input class="s-input" type="month" name="month" value="{{ $month }}"
                style="width:170px;" required>
        </div>
        <button class="s-btn s-btn-primary"><i class="las la-search"></i> Generar</button>
        <div style="margin-left:auto;font-size:12px;color:var(--s-text-3);align-self:center;">
            {{ \Carbon\Carbon::parse($start)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($end)->format('d/m/Y') }}
        </div>
    </form>
</div>

{{-- ── RESUMEN VENTAS ───────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    <div class="s-card" style="text-align:center;padding:20px 14px;">
        <div style="font-size:11px;color:var(--s-text-3);margin-bottom:4px;">OPE. GRAVADAS</div>
        <div style="font-size:22px;font-weight:900;color:var(--s-success);">
            S/ {{ number_format($salesData->total_gravado ?? 0, 2) }}
        </div>
        <div style="font-size:11px;color:var(--s-text-3);">Af. IGV 10 (18%)</div>
    </div>
    <div class="s-card" style="text-align:center;padding:20px 14px;">
        <div style="font-size:11px;color:var(--s-text-3);margin-bottom:4px;">OPE. EXONERADAS</div>
        <div style="font-size:22px;font-weight:900;color:var(--s-info);">
            S/ {{ number_format($salesData->total_exonerado ?? 0, 2) }}
        </div>
        <div style="font-size:11px;color:var(--s-text-3);">Af. IGV 20</div>
    </div>
    <div class="s-card" style="text-align:center;padding:20px 14px;">
        <div style="font-size:11px;color:var(--s-text-3);margin-bottom:4px;">OPE. INAFECTAS</div>
        <div style="font-size:22px;font-weight:900;color:var(--s-text-3);">
            S/ {{ number_format($salesData->total_inafecto ?? 0, 2) }}
        </div>
        <div style="font-size:11px;color:var(--s-text-3);">Af. IGV 30</div>
    </div>
    <div class="s-card" style="text-align:center;padding:20px 14px;border:2px solid var(--s-warning);">
        <div style="font-size:11px;color:var(--s-warning);margin-bottom:4px;">IGV VENTAS</div>
        <div style="font-size:22px;font-weight:900;color:var(--s-warning);">
            S/ {{ number_format($salesData->total_igv_ventas ?? 0, 2) }}
        </div>
        <div style="font-size:11px;color:var(--s-text-3);">Débito fiscal</div>
    </div>
    <div class="s-card" style="text-align:center;padding:20px 14px;border:2px solid var(--s-accent);">
        <div style="font-size:11px;color:var(--s-accent);margin-bottom:4px;">TOTAL VENTAS</div>
        <div style="font-size:22px;font-weight:900;color:var(--s-accent);">
            S/ {{ number_format($salesData->total_ventas ?? 0, 2) }}
        </div>
        <div style="font-size:11px;color:var(--s-text-3);">{{ $salesData->num_ventas ?? 0 }} documentos</div>
    </div>
</div>

{{-- ── IGV: DÉBITO vs CRÉDITO ───────────────────────────────────────────── --}}
@php
    $igvVentas  = $salesData->total_igv_ventas ?? 0;
    $igvCompras = $purchasesData->total_igv_compras ?? 0;
    $igvNeto    = $igvVentas - $igvCompras;
@endphp
<div class="s-card" style="margin-bottom:20px;">
    <h3 class="s-card-title"><i class="las la-balance-scale"></i> Balance IGV — {{ \Carbon\Carbon::parse($start)->format('F Y') }}</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;text-align:center;padding:10px 0;">
        <div>
            <div style="font-size:13px;color:var(--s-text-3);margin-bottom:4px;">IGV Débito (ventas)</div>
            <div style="font-size:26px;font-weight:900;color:var(--s-warning);">S/ {{ number_format($igvVentas,2) }}</div>
        </div>
        <div>
            <div style="font-size:13px;color:var(--s-text-3);margin-bottom:4px;">IGV Crédito (compras)</div>
            <div style="font-size:26px;font-weight:900;color:var(--s-success);">S/ {{ number_format($igvCompras,2) }}</div>
        </div>
        <div style="border-left:2px solid var(--s-border);">
            <div style="font-size:13px;color:var(--s-text-3);margin-bottom:4px;">
                {{ $igvNeto >= 0 ? 'IGV A PAGAR' : 'SALDO A FAVOR' }}
            </div>
            <div style="font-size:28px;font-weight:900;color:{{ $igvNeto >= 0 ? 'var(--s-danger)' : 'var(--s-success)' }};">
                S/ {{ number_format(abs($igvNeto),2) }}
            </div>
            <div style="font-size:11px;color:var(--s-text-3);">
                {{ $igvNeto >= 0 ? 'Débito − Crédito' : 'Crédito > Débito' }}
            </div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;" class="tax-grid">

    {{-- ── Resumen Compras ─────────────────────────────────────────────── --}}
    <div class="s-card">
        <h3 class="s-card-title"><i class="las la-shopping-bag"></i> Compras del Período</h3>
        <div style="display:flex;gap:20px;text-align:center;padding:10px 0 20px;">
            <div style="flex:1;">
                <div style="font-size:22px;font-weight:900;color:var(--s-text);">
                    S/ {{ number_format($purchasesData->total_compras ?? 0, 2) }}
                </div>
                <div style="font-size:12px;color:var(--s-text-3);">Total compras ({{ $purchasesData->num_compras ?? 0 }} docs.)</div>
            </div>
            <div style="flex:1;">
                <div style="font-size:22px;font-weight:900;color:var(--s-success);">
                    S/ {{ number_format($igvCompras, 2) }}
                </div>
                <div style="font-size:12px;color:var(--s-text-3);">IGV crédito fiscal</div>
            </div>
        </div>
    </div>

    {{-- ── Mermas del Período ──────────────────────────────────────────── --}}
    <div class="s-card">
        <h3 class="s-card-title"><i class="las la-exclamation-triangle" style="color:var(--s-warning)"></i> Mermas del Período</h3>
        <div style="display:flex;gap:20px;text-align:center;padding:10px 0 20px;">
            <div style="flex:1;">
                <div style="font-size:22px;font-weight:900;color:var(--s-danger);">{{ $wastesData->count() }}</div>
                <div style="font-size:12px;color:var(--s-text-3);">Eventos de merma</div>
            </div>
            <div style="flex:1;">
                <div style="font-size:22px;font-weight:900;color:var(--s-warning);">
                    S/ {{ number_format($totalWasteCost, 2) }}
                </div>
                <div style="font-size:12px;color:var(--s-text-3);">Costo total mermas</div>
            </div>
        </div>
        @if($wastesData->count())
        <div style="overflow-x:auto;margin-top:8px;">
        <table class="s-table" style="font-size:12px;">
            <thead><tr><th>Insumo</th><th>Cant.</th><th>Costo</th></tr></thead>
            <tbody>
            @foreach($wastesData->take(8) as $w)
            <tr>
                <td>{{ $w->item->name ?? '—' }}</td>
                <td>{{ number_format($w->quantity,3) }} {{ $w->item->unit ?? '' }}</td>
                <td style="color:var(--s-warning);">S/ {{ number_format($w->quantity * ($w->item->cost ?? 0),2) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

{{-- ── Nota SUNAT ───────────────────────────────────────────────────────── --}}
<div class="s-card" style="margin-top:20px;border-left:4px solid var(--s-info);">
    <div style="display:flex;gap:12px;align-items:flex-start;">
        <i class="las la-info-circle" style="color:var(--s-info);font-size:22px;flex-shrink:0;"></i>
        <div style="font-size:13px;color:var(--s-text-2);">
            <b>Referencia SUNAT / IGV</b><br>
            Los tipos de afectación IGV usados en este sistema corresponden a:
            <b>10 = Gravado</b> (operaciones afectas al 18%),
            <b>20 = Exonerado</b> (productos/servicios en lista de exoneración),
            <b>30 = Inafecto</b> (fuera del ámbito del IGV).
            Verifique con su contador los productos de su empresa y configure correctamente el tipo tributario en cada insumo/producto.
        </div>
    </div>
</div>

</div>

@push('style')
<style>
@media(max-width:768px) { .tax-grid { grid-template-columns: 1fr !important; } }
</style>
@endpush
@endsection
