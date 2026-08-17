@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-shopping-cart"></i></span>
Ventas Directas
@endsection

@section('seller-content')
<div class="s-content">

{{-- Resumen ──────────────────────────────────────────────────────────────── --}}
@php
    $totalVentas  = $sales->where('status','completed')->sum('total');
    $totalGravado = $sales->where('status','completed')->sum('subtotal_gravado');
    $totalIGV     = $sales->where('status','completed')->sum('igv');
@endphp
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    <div class="s-card" style="text-align:center;padding:20px 16px;">
        <div style="font-size:24px;font-weight:900;color:var(--s-accent);">S/ {{ number_format($totalVentas,2) }}</div>
        <div style="font-size:12px;color:var(--s-text-3);">Total ventas (página)</div>
    </div>
    <div class="s-card" style="text-align:center;padding:20px 16px;">
        <div style="font-size:24px;font-weight:900;color:var(--s-success);">S/ {{ number_format($totalGravado,2) }}</div>
        <div style="font-size:12px;color:var(--s-text-3);">Base gravada</div>
    </div>
    <div class="s-card" style="text-align:center;padding:20px 16px;">
        <div style="font-size:24px;font-weight:900;color:var(--s-warning);">S/ {{ number_format($totalIGV,2) }}</div>
        <div style="font-size:12px;color:var(--s-text-3);">IGV (18%)</div>
    </div>
</div>

{{-- Botón nueva venta --}}
<div class="s-card" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <h3 class="s-card-title" style="margin:0;"><i class="las la-shopping-cart"></i> Ventas Directas de Inventario</h3>
        <button class="s-btn s-btn-primary" onclick="document.getElementById('sale-modal').style.display='flex'">
            <i class="las la-plus"></i> Nueva Venta
        </button>
    </div>
</div>

{{-- Tabla de ventas --}}
<div class="s-card">
    <div style="overflow-x:auto;">
    <table class="s-table">
        <thead>
            <tr>
                <th>#</th><th>Fecha</th><th>Gravado</th><th>Exonerado</th><th>Inafecto</th>
                <th>IGV</th><th>Total</th><th>Pago</th><th>Estado</th><th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($sales as $sale)
        <tr style="{{ $sale->status === 'voided' ? 'opacity:.5;' : '' }}">
            <td style="font-size:11px;color:var(--s-text-3);">#{{ $sale->id }}</td>
            <td style="font-size:12px;">{{ $sale->document_date?->format('d/m/Y') }}</td>
            <td style="color:var(--s-success);">S/ {{ number_format($sale->subtotal_gravado,2) }}</td>
            <td style="color:var(--s-info);">S/ {{ number_format($sale->subtotal_exonerado,2) }}</td>
            <td style="color:var(--s-text-3);">S/ {{ number_format($sale->subtotal_inafecto,2) }}</td>
            <td style="color:var(--s-warning);">S/ {{ number_format($sale->igv,2) }}</td>
            <td><b style="color:var(--s-accent);">S/ {{ number_format($sale->total,2) }}</b></td>
            <td>
                <span class="s-badge s-badge-gray" style="font-size:10px;text-transform:capitalize;">
                    {{ match($sale->payment_method) {
                        'cash' => 'Efectivo', 'card' => 'Tarjeta',
                        'transfer' => 'Transferencia', default => $sale->payment_method
                    } }}
                </span>
            </td>
            <td>
                @if($sale->status === 'completed')
                    <span class="s-badge s-badge-green">Completada</span>
                @else
                    <span class="s-badge s-badge-red">Anulada</span>
                @endif
            </td>
            <td>
                {{-- Detalle de items --}}
                <button class="s-btn s-btn-ghost s-btn-xs" onclick="showSaleDetail({{ $sale->id }})"
                    title="Ver detalle"><i class="las la-eye"></i></button>
                @if($sale->status === 'completed')
                <form method="POST" action="{{ route('seller.inventory.sales.void', $sale->id) }}"
                    onsubmit="return confirm('¿Anular venta y devolver stock?')" style="display:inline;">
                    @csrf
                    <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" title="Anular">
                        <i class="las la-times-circle"></i>
                    </button>
                </form>
                @endif
            </td>
        </tr>
        {{-- Detalle colapsable --}}
        <tr id="sale-detail-{{ $sale->id }}" style="display:none;background:var(--s-bg-2);">
            <td colspan="10" style="padding:12px 20px;">
                <table style="width:100%;font-size:12px;border-collapse:collapse;">
                    <thead><tr>
                        <th style="text-align:left;padding:4px 8px;border-bottom:1px solid var(--s-border);">Producto</th>
                        <th style="padding:4px 8px;border-bottom:1px solid var(--s-border);">Cant.</th>
                        <th style="padding:4px 8px;border-bottom:1px solid var(--s-border);">P. Unit.</th>
                        <th style="padding:4px 8px;border-bottom:1px solid var(--s-border);">Tipo</th>
                        <th style="padding:4px 8px;border-bottom:1px solid var(--s-border);">IGV</th>
                        <th style="padding:4px 8px;border-bottom:1px solid var(--s-border);">Total</th>
                    </tr></thead>
                    <tbody>
                    @foreach($sale->items as $si)
                    <tr>
                        <td style="padding:4px 8px;">{{ $si->item->name ?? '—' }}</td>
                        <td style="padding:4px 8px;text-align:center;">{{ $si->quantity }}</td>
                        <td style="padding:4px 8px;text-align:center;">S/ {{ number_format($si->unit_price,2) }}</td>
                        <td style="padding:4px 8px;text-align:center;">
                            <span class="s-badge {{ match($si->tax_type) {
                                'exonerado' => 's-badge-blue',
                                'inafecto'  => 's-badge-gray',
                                default     => 's-badge-green'
                            } }}" style="font-size:9px;text-transform:capitalize;">{{ $si->tax_type }}</span>
                        </td>
                        <td style="padding:4px 8px;text-align:center;">S/ {{ number_format($si->igv,2) }}</td>
                        <td style="padding:4px 8px;text-align:center;font-weight:700;">S/ {{ number_format($si->total,2) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
        @empty
        <tr><td colspan="10" style="text-align:center;color:var(--s-text-3);padding:30px;">Sin ventas registradas</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $sales->links() }}
</div>

</div>

{{-- ════ Modal: Nueva Venta ═════════════════════════════════════════════════ --}}
<div id="sale-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(10,20,35,.65);align-items:center;justify-content:center;backdrop-filter:blur(4px);">
<div class="s-card" style="width:100%;max-width:720px;max-height:92vh;overflow-y:auto;padding:24px;border-radius:18px;" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3 style="margin:0;font-weight:900;"><i class="las la-shopping-cart" style="color:var(--s-accent)"></i> Nueva Venta Directa</h3>
        <button class="s-btn s-btn-ghost" onclick="closeSaleModal()">✕</button>
    </div>

    <form id="sale-form" method="POST" action="{{ route('seller.inventory.sales.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px;">
            <div class="s-input-group">
                <label class="s-input-label">Fecha *</label>
                <input class="s-input" type="date" name="document_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Método de pago</label>
                <select class="s-input" name="payment_method">
                    <option value="cash">Efectivo</option>
                    <option value="card">Tarjeta</option>
                    <option value="transfer">Transferencia</option>
                    <option value="yape">Yape/Plin</option>
                </select>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Tipo comprobante</label>
                <select class="s-input" name="document_type">
                    <option value="00">Sin comprobante</option>
                    <option value="03">Boleta</option>
                    <option value="01">Factura</option>
                </select>
            </div>
        </div>

        {{-- Líneas de venta dinámicas --}}
        <div style="border-top:1px solid var(--s-border);padding-top:14px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <b style="font-size:13px;"><i class="las la-list"></i> Productos</b>
                <button type="button" class="s-btn s-btn-ghost s-btn-sm" onclick="addSaleLine()">
                    <i class="las la-plus"></i> Agregar
                </button>
            </div>
            <div id="sale-lines-container"></div>
            <div id="no-sale-lines-msg" style="text-align:center;color:var(--s-text-3);font-size:12px;padding:16px;">
                Haz clic en <b>+ Agregar</b>
            </div>
        </div>

        {{-- Totales --}}
        <div style="background:var(--s-bg-2);border-radius:12px;padding:14px;margin-bottom:16px;font-size:13px;">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;text-align:center;">
                <div>
                    <div style="font-size:11px;color:var(--s-text-3);">Op. Gravadas</div>
                    <div id="tot-gravado" style="font-weight:700;color:var(--s-success);">S/ 0.00</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--s-text-3);">Exoneradas</div>
                    <div id="tot-exonerado" style="font-weight:700;color:var(--s-info);">S/ 0.00</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--s-text-3);">IGV (18%)</div>
                    <div id="tot-igv" style="font-weight:700;color:var(--s-warning);">S/ 0.00</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--s-text-3);">TOTAL</div>
                    <div id="tot-total" style="font-weight:900;font-size:16px;color:var(--s-accent);">S/ 0.00</div>
                </div>
            </div>
        </div>

        <input type="hidden" name="items" id="sale-items-json">
        <button type="submit" class="s-btn s-btn-primary" style="width:100%;justify-content:center;"
            onclick="buildSaleJson(event)">
            <i class="las la-check"></i> Registrar Venta
        </button>
    </form>
</div>
</div>

@push('style')
<style>
.sale-line {
    display: grid;
    grid-template-columns: 1fr 80px 90px 110px 32px;
    gap: 6px;
    align-items: center;
    background: var(--s-bg-2);
    padding: 8px 10px;
    border-radius: 10px;
    margin-bottom: 8px;
}
@media(max-width:600px) { .sale-line { grid-template-columns: 1fr 1fr 1fr; } }
</style>
@endpush

@php
    $jsSalesItems = $items->map(function($i) {
        return [
            'id'       => $i->id,
            'name'     => $i->name,
            'unit'     => $i->unit,
            'tax_type' => $i->tax_type,
            'price'    => $i->sale_price,
            'stock'    => $i->stock,
        ];
    });
@endphp

@push('script')
<script>
const saleItems_data = @json($jsSalesItems);

let saleLines = [];

function addSaleLine() {
    saleLines.push({ item_id: '', quantity: 1, unit_price: 0, tax_type: 'gravado' });
    renderSaleLines();
}

function removeSaleLine(idx) {
    saleLines.splice(idx, 1);
    renderSaleLines();
}

function renderSaleLines() {
    const container = document.getElementById('sale-lines-container');
    const noMsg = document.getElementById('no-sale-lines-msg');
    noMsg.style.display = saleLines.length ? 'none' : 'block';

    const itemOpts = saleItems_data.map(i =>
        `<option value="${i.id}" data-price="${i.price}" data-tax="${i.tax_type}">${i.name} (Stock: ${i.stock} ${i.unit})</option>`
    ).join('');

    container.innerHTML = saleLines.map((line, idx) => `
        <div class="sale-line">
            <select class="s-input" style="font-size:12px;"
                onchange="onSaleItemChange(${idx}, this)">
                <option value="">— Producto —</option>${itemOpts}
            </select>
            <input class="s-input" type="number" step="0.001" min="0.001"
                value="${line.quantity}" placeholder="Cant."
                style="font-size:12px;"
                onchange="saleLines[${idx}].quantity=parseFloat(this.value)||0;recalcSale()">
            <input class="s-input" type="number" step="0.01" min="0"
                value="${line.unit_price}" placeholder="P. Unit (inc.IGV)"
                id="price-${idx}" style="font-size:12px;"
                onchange="saleLines[${idx}].unit_price=parseFloat(this.value)||0;recalcSale()">
            <select class="s-input" style="font-size:12px;"
                onchange="saleLines[${idx}].tax_type=this.value;recalcSale()">
                <option value="gravado"  ${line.tax_type==='gravado'?'selected':''}>Gravado (IGV)</option>
                <option value="exonerado" ${line.tax_type==='exonerado'?'selected':''}>Exonerado</option>
                <option value="inafecto"  ${line.tax_type==='inafecto'?'selected':''}>Inafecto</option>
            </select>
            <button type="button" class="s-btn s-btn-ghost" style="color:var(--s-danger);padding:4px;"
                onclick="removeSaleLine(${idx})"><i class="las la-times"></i></button>
        </div>
    `).join('');

    // Restore selected item
    saleLines.forEach((line, idx) => {
        const sel = container.querySelectorAll('select[onchange^="onSaleItemChange"]')[idx];
        if (sel && line.item_id) sel.value = line.item_id;
    });
    recalcSale();
}

function onSaleItemChange(idx, sel) {
    const opt = sel.options[sel.selectedIndex];
    saleLines[idx].item_id   = opt.value;
    saleLines[idx].unit_price = parseFloat(opt.dataset.price) || 0;
    saleLines[idx].tax_type  = opt.dataset.tax || 'gravado';
    const priceInput = document.getElementById('price-' + idx);
    if (priceInput) priceInput.value = saleLines[idx].unit_price;
    renderSaleLines();
}

function recalcSale() {
    let gravado = 0, exonerado = 0, inafecto = 0, igv = 0;
    saleLines.forEach(line => {
        const total = (line.quantity || 0) * (line.unit_price || 0);
        if (line.tax_type === 'gravado') {
            const base = total / 1.18;
            gravado += base;
            igv     += base * 0.18;
        } else if (line.tax_type === 'exonerado') {
            exonerado += total;
        } else {
            inafecto += total;
        }
    });
    const f = v => 'S/ ' + v.toFixed(2);
    document.getElementById('tot-gravado').textContent   = f(gravado);
    document.getElementById('tot-exonerado').textContent = f(exonerado);
    document.getElementById('tot-igv').textContent       = f(igv);
    document.getElementById('tot-total').textContent     = f(gravado + exonerado + inafecto + igv);
}

function buildSaleJson(e) {
    if (!saleLines.length || !saleLines.some(l => l.item_id)) {
        e.preventDefault();
        alert('Agrega al menos un producto a la venta');
        return;
    }
    document.getElementById('sale-items-json').value = JSON.stringify(saleLines);
}

function closeSaleModal() {
    document.getElementById('sale-modal').style.display = 'none';
}

function showSaleDetail(saleId) {
    const row = document.getElementById('sale-detail-' + saleId);
    if (row) row.style.display = row.style.display === 'none' ? '' : 'none';
}

document.getElementById('sale-modal').addEventListener('click', closeSaleModal);
</script>
@endpush
@endsection
