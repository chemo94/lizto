@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-cash-register"></i></span> Facturación y Caja POS
@endsection

@section('topbar-actions')
<a href="{{ route('seller.pos') }}" class="s-btn s-btn-secondary s-btn-sm">
    <i class="las la-arrow-left"></i> Volver al POS
</a>
@endsection

@section('seller-content')
<style>
.billing-touch .s-table tbody td{padding-top:14px;padding-bottom:14px}.billing-touch .s-table tbody tr{transition:.15s}.billing-touch .s-table tbody tr:active{background:#fff7ed}.billing-touch .s-table td .s-btn{min-height:42px;padding:8px 12px;border-radius:9px;font-size:11px;touch-action:manipulation}.billing-touch .s-table td>div{flex-wrap:wrap}.billing-pay-dialog .s-input{min-height:48px;font-size:14px!important;border-radius:10px!important}.billing-pay-dialog select.s-input{padding-top:0!important;padding-bottom:0!important}.billing-pay-dialog input[type=checkbox],.billing-pay-dialog input[type=radio]{width:22px!important;height:22px!important;min-width:22px;accent-color:var(--s-primary)}.billing-pay-dialog label{line-height:1.3}.billing-pay-dialog .s-btn{min-height:48px;padding:10px 16px;border-radius:10px;font-size:13px;touch-action:manipulation}.billing-pay-dialog [id^="payment-row-"] button{min-width:44px;min-height:42px!important;padding:8px!important;border-radius:8px!important}.billing-pay-dialog [id^="payment-row-"] .s-input{min-height:46px!important;height:46px!important}.billing-pay-dialog #pay-submit-btn{min-height:54px;font-size:15px;font-weight:800}.billing-pay-dialog #pay-doc-result>div{min-height:46px;padding:11px 13px!important}.billing-touch .pagination .page-link{min-width:44px;min-height:44px;display:grid;place-items:center}@media(hover:none),(pointer:coarse){.billing-touch .module-hero{min-height:150px}.billing-touch .s-card{padding:20px}.billing-touch .s-table tbody td{font-size:13px}.billing-touch .s-table td .s-btn{min-height:48px;padding:10px 14px;font-size:12px}.billing-pay-dialog{width:min(720px,calc(100vw - 20px))!important;padding:22px!important}.billing-pay-dialog button:active,.billing-touch .s-btn:active{transform:scale(.97)}.billing-pay-dialog [style*="height: 28px"],.billing-pay-dialog [style*="height: 30px"]{height:48px!important;min-height:48px!important}.billing-pay-dialog [style*="width: 64px"]{width:86px!important}.billing-pay-dialog [style*="width: 100px"],.billing-pay-dialog [style*="width: 125px"],.billing-pay-dialog [style*="width: 180px"],.billing-pay-dialog [style*="width: 220px"]{width:100%!important}.billing-pay-dialog [style*="display: flex"]{row-gap:10px}.billing-pay-dialog [style*="position: absolute; top: 16px"]{width:46px;height:46px;top:8px!important;right:8px!important;border-radius:50%!important;background:#f8fafc!important}}
.billing-touch .s-table td .s-btn{min-width:42px}@media(hover:none),(pointer:coarse){.billing-touch .s-table td .s-btn{min-width:48px}}
</style>
<div class="s-content billing-touch">
    @php $pendingBillingTotal = $pendingOrders->sum('total'); $paidBillingTotal = $paidOrders->sum('total'); @endphp
    <section class="module-hero billing"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Finanzas &nbsp;/&nbsp; Cobros</div><h2><i class="las la-hand-holding-usd"></i> Cobros y Facturación POS</h2><p>Procesa cuentas pendientes, medios de pago y comprobantes electrónicos.</p></div><div class="module-hero-stats"><div><b>{{ $pendingOrders->count() }}</b><small>Por cobrar</small></div><div><b>S/ {{ number_format($pendingBillingTotal,0) }}</b><small>Saldo pendiente</small></div><div><b>S/ {{ number_format($paidBillingTotal,0) }}</b><small>Cobrado reciente</small></div></div></section>
    
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- PENDING ORDERS -->
        <div class="s-card seller-work-card">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-clock" style="color: var(--s-warning); font-size: 22px;"></i> Pedidos Pendientes de Cobro ({{ $pendingOrders->count() }})
            </h3>

            @if($pendingOrders->isEmpty())
            <div style="text-align: center; padding: 40px 20px; color: var(--s-text-muted);">
                <i class="las la-check-circle" style="font-size: 48px; color: var(--s-success); display: block; margin-bottom: 12px;"></i>
                <h4 style="color: var(--s-text-primary); font-weight: 600; margin-bottom: 4px;">¡Al día con las cuentas!</h4>
                <p style="font-size: 13px;">No hay comandas activas pendientes por registrar cobro.</p>
            </div>
            @else
            <div class="s-table-wrapper" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="s-table" style="min-width: 680px;">
                    <thead>
                        <tr>
                            <th>Nº Pedido</th>
                            <th>Cliente</th>
                            <th>Mesa</th>
                            <th style="text-align: center;">Items</th>
                            <th style="text-align: right;">Total Cuenta</th>
                            <th style="text-align: center;">Estado</th>
                            <th style="text-align: right; min-width: 220px;">Acciones Rápidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingOrders as $o)
                        <tr>
                            <td style="font-weight: 850; color: var(--s-text-primary);">#{{ $o->order_no }}</td>
                            <td style="font-weight: 600; color: var(--s-text-primary);">{{ $o->customer_name ?: 'Sin Nombre' }}</td>
                            <td>
                                @if($o->table)
                                <span class="s-badge s-badge-gray" style="font-weight: 700;"><i class="las la-utensils"></i> {{ $o->table->name }}</span>
                                @else
                                <span style="color: var(--s-text-muted); font-size: 12px;">
                                    @php $typeBadges = ['daz'=>'#e11d48','llama'=>'#f59e0b','rappi'=>'#8b5cf6','pedidosya'=>'#0891b2','lizto_delivery'=>'#16a34a','delivery'=>'var(--s-text-3)','takeaway'=>'var(--s-text-3)']; @endphp
                                    @if(isset($typeBadges[$o->order_type]))
                                    <span style="font-weight:900;font-size:9px;padding:2px 6px;border-radius:4px;color:#fff;background:{{ $typeBadges[$o->order_type] }}">{{ strtoupper($o->order_type) }}</span>
                                    @else
                                    Para llevar / delivery
                                    @endif
                                </span>
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: 600;">{{ $o->items->count() }}</td>
                            <td style="text-align: right; font-weight: 800; font-size: 15px; color: var(--s-primary);">
                                S/ {{ number_format($o->total, 2) }}
                            </td>
                            <td style="text-align: center;">
                                <span class="s-badge s-badge-yellow" style="font-size: 10px; padding: 2px 6px;">{{ $o->status }}</span>
                                @if($o->payment_status === 'credit')
                                <span class="s-badge s-badge-red" style="font-size: 10px; padding: 2px 6px; margin-top: 4px; display: block;">A CRÉDITO</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                    <a href="{{ route('seller.pos.order.ticket', $o->id) }}" target="_blank" class="s-btn s-btn-ghost s-btn-xs" style="color: var(--s-text-secondary); border: 1px solid var(--s-border);" title="Imprimir Pre-cuenta">
                                        <i class="las la-print" style="font-size: 16px;"></i> Pre-cuenta
                                    </a>
                                    
                                    <button class="s-btn s-btn-success s-btn-xs" style="gap: 4px;" onclick="openPayModal({{ $o->id }}, {{ $o->total }}, '#{{ $o->order_no }}')">
                                        <i class="las la-wallet" style="font-size: 14px;"></i> Cobrar y Emitir
                                    </button>
                                    <form method="POST" action="{{ route('seller.orders.cancel', $o->id) }}" style="display:inline;" onsubmit="return confirm('¿Anular el pedido #{{ $o->order_no }}? Esta acción lo retirará de los pendientes de cobro.');">
                                        @csrf
                                        <input type="hidden" name="reason" value="Anulado desde pedidos pendientes de cobro">
                                        <button type="submit" class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger);border:1px solid var(--s-danger);" title="Anular pedido sin cobro">
                                            <i class="las la-ban" style="font-size:14px;"></i> Anular
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <!-- PAID ORDERS -->
        @if($paidOrders->count())
        <div class="s-card seller-work-card">
            <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-check-circle" style="color: var(--s-success); font-size: 22px;"></i> Comprobantes y Pagos Recientes
            </h3>

            <div class="s-table-wrapper" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="s-table" style="min-width: 750px;">
                    <thead>
                        <tr>
                            <th>Nº Pedido</th>
                            <th>Cliente</th>
                            <th style="text-align: right;">Total</th>
                            <th>Comprobante</th>
                            <th style="text-align: center;">Estado SUNAT</th>
                            <th>Medio Pago</th>
                            <th>Hora Pago</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paidOrders as $o)
                        <tr>
                            <td style="font-weight: 700;">#{{ $o->order_no }}</td>
                            <td>{{ $o->customer_name ?: 'Cliente General' }}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--s-text-primary);">S/ {{ number_format($o->total,2) }}</td>
                            <td>
                                @if($o->invoice_series)
                                <span class="s-badge s-badge-green" style="font-weight: 700;">{{ $o->invoice_series }}-{{ $o->invoice_number }}</span>
                                @else
                                <span style="font-size:12px; color:var(--s-text-muted);">Sin comprobante (Ticket Interno)</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($o->sunatInvoice)
                                <span class="s-badge" style="background: {{ $o->sunatInvoice->statusColor() }}; color: #fff; font-size: 11px; padding: 4px 10px; font-weight: 800; border-radius: 6px;">
                                    {{ $o->sunatInvoice->statusLabel() }}
                                </span>
                                @else
                                <span class="s-badge s-badge-gray" style="font-size: 11px; padding: 4px 10px; font-weight: 800; border-radius: 6px;">No enviado</span>
                                @endif
                            </td>
                            <td>
                                @if($o->payment_method === 'split' && is_array($o->payment_details))
                                    @foreach($o->payment_details as $method => $amount)
                                        <span class="s-badge s-badge-blue" style="font-size:9.5px; text-transform: uppercase; margin-right: 2px;">{{ $method }}: S/{{ $amount }}</span>
                                    @endforeach
                                @else
                                    <span class="s-badge s-badge-gray" style="text-transform: uppercase;">{{ $o->payment_method ?? 'cash' }}</span>
                                @endif
                            </td>
                            <td style="font-size: 11px; color: var(--s-text-muted);">{{ $o->paid_at?->format('d/m H:i') }}</td>
                            <td style="text-align: center;">
                                @if($o->sunatInvoice)
                                <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
                                    <a href="{{ route('seller.invoice.detail', $o->sunatInvoice->id) }}" class="s-btn s-btn-ghost s-btn-xs" title="Ver detalle" style="padding:4px 8px; min-height: 28px; width: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="las la-eye" style="font-size:16px;color:var(--s-primary);"></i>
                                    </a>
                                    <a href="{{ route('seller.invoice.pdf', [$o->sunatInvoice->id, 'a4']) }}" class="s-btn s-btn-ghost s-btn-xs" title="Descargar PDF A4" style="padding:4px 8px; min-height: 28px; width: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="las la-file-pdf" style="font-size:16px;color:#dc2626;"></i>
                                    </a>
                                    <a href="{{ route('seller.invoice.pdf', [$o->sunatInvoice->id, 'ticket']) }}" class="s-btn s-btn-ghost s-btn-xs" title="Descargar Ticket" style="padding:4px 8px; min-height: 28px; width: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="las la-receipt" style="font-size:16px;color:var(--s-info);"></i>
                                    </a>
                                    <a href="{{ route('seller.invoice.xml', $o->sunatInvoice->id) }}" class="s-btn s-btn-ghost s-btn-xs" title="Descargar XML" style="padding:4px 8px; min-height: 28px; width: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="las la-file-code" style="font-size:16px;color:#8b5cf6;"></i>
                                    </a>
                                    @if(in_array($o->sunatInvoice->cdr_status, ['pending', 'error', 'rejected']))
                                    <form method="POST" action="{{ route('seller.invoice.resend', $o->sunatInvoice->id) }}" onsubmit="return confirm('¿Reenviar a SUNAT?');" style="display:inline; margin:0;">
                                        @csrf
                                        <button type="submit" class="s-btn s-btn-ghost s-btn-xs" title="Reenviar a SUNAT" style="padding:4px 8px; min-height: 28px; width: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="las la-redo" style="font-size:16px;color:var(--s-info);"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                @else
                                <span style="font-size:12px; color:var(--s-text-muted);">Ticket Interno</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($paidOrders->hasPages())
            <div style="margin-top: 20px; display: flex; justify-content: center;">
                {{ $paidOrders->links() }}
            </div>
            @endif
        </div>
        @endif

    </div>

</div>

<!-- PAYMENT MODAL -->
<div id="pay-modal" class="s-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,25,35,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div class="billing-pay-dialog" style="background: #fff; width: min(620px, calc(100vw - 32px)); padding: 24px; border-radius: 16px; border: 1px solid var(--s-border); position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.15); max-height: calc(100vh - 32px); overflow-y: auto; overscroll-behavior:contain; -webkit-overflow-scrolling:touch;">
        <button onclick="closePayModal()" style="position: absolute; top: 16px; right: 16px; background: none; border: none; font-size: 20px; color: var(--s-text-secondary); cursor: pointer;">✕</button>
        <h3 style="margin-bottom: 8px; font-weight: 800; font-size: 18px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-wallet" style="color: var(--s-success); font-size: 24px;"></i> Registrar Cobro <span id="pay-order-no"></span>
        </h3>
        <p style="font-size: 13px; color: var(--s-text-muted); margin-bottom: 20px;">Cuenta Total: <b style="font-size: 15px; color: var(--s-primary);">S/ <span id="pay-total-display">0.00</span></b></p>
        
        <form method="POST" action="" id="pay-form">
            @csrf
            <input type="hidden" name="customer_name" id="pay-customer-name">
            <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                <label style="display:flex; align-items:center; justify-content:space-between; gap:12px; cursor:pointer; padding:12px 14px; border:1px solid var(--s-border); border-radius:12px; background:var(--s-bg-light);">
                    <span style="display:flex; align-items:center; gap:10px;">
                        <span style="width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:rgba(21,155,18,.12); color:var(--s-primary);"><i class="las la-box"></i></span>
                        <span>
                            <strong style="display:block; font-size:13px; color:var(--s-text-primary);">Agregar Tupper</strong>
                            <small style="color:var(--s-text-muted);">Se incluirá en el comprobante.</small>
                        </span>
                    </span>
                    <span style="display:flex; align-items:center; gap:9px; font-weight:800; color:var(--s-primary);">+ S/ 1.00 <input type="checkbox" name="include_tupper" value="1" id="pay-include-tupper" onchange="updateTupperFee()" style="width:18px; height:18px; accent-color:var(--s-primary);"></span>
                </label>
                @if($invoiceTypes->count())
                <div>
                    <label class="s-label" style="font-weight:700;">Comprobante a emitir</label>
                    <select class="s-input" name="series_id" id="pay-series" style="height:44px; border-radius:10px;" onchange="onBillingSeriesChange()">
                        <option value="" data-code="NV">Nota de Venta (Clientes Varios - Por defecto)</option>
                        @foreach($invoiceTypes as $type)
                        @if(in_array($type->code, ['01', '03', 'NV']))
                        <optgroup label="{{ $type->code }} - {{ $type->name }}">
                            @foreach($type->series as $s)
                            <option value="{{ $s->id }}" data-code="{{ $type->code }}">{{ $s->series }} (Siguiente: {{ $s->nextNumber() }})</option>
                            @endforeach
                        </optgroup>
                        @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="s-label" style="font-weight:700;">Detalle que verá el cliente</label>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:7px;">
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;cursor:pointer;"><input type="radio" name="detail_mode" value="detailed" checked onchange="toggleConsumptionDescription()"> Por ítems</label>
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;cursor:pointer;"><input type="radio" name="detail_mode" value="consumption" onchange="toggleConsumptionDescription()"> Por consumo</label>
                    </div>
                    <div id="consumption-description-wrap" style="display:none;margin-top:10px;">
                        <input class="s-input" name="consumption_description" id="consumption-description" maxlength="250" placeholder="Descripción para el comprobante. Ej.: Consumo en restaurante" style="height:42px;border-radius:10px;">
                        <small style="display:block;margin-top:5px;color:var(--s-text-muted);">Se mostrará una sola línea en lugar de los productos consumidos.</small>
                    </div>
                </div>
                @endif

                <div style="display: flex; gap: 6px;">
                    <select class="s-input" id="pay-tipo-doc" name="tipo_doc" style="width: 85px; padding: 6px; font-size: 12px; background: var(--s-surface-2);" onchange="clearDocResult()">
                        <option value="1">DNI</option>
                        <option value="6">RUC</option>
                    </select>
                    <div style="flex: 1; position: relative;">
                        <input class="s-input" id="pay-num-doc" name="num_doc" placeholder="N° Documento cliente (opcional)" style="padding: 6px 12px; font-size: 12px; width: 100%; box-sizing: border-box; background: var(--s-surface-2);" autocomplete="off">
                        <div id="pay-doc-result" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--s-border); border-radius: 0 0 8px 8px; padding: 8px 12px; font-size: 12px; font-weight: 600; z-index: 10; display: none;"></div>
                    </div>
                </div>
                <div id="pay-client-badge" style="display:none; background:rgba(34,197,94,0.12); color:#15803d; border:1px solid rgba(34,197,94,0.3); border-radius:8px; padding:6px 10px; font-size:12px; font-weight:700; display:none; align-items:center; gap:6px; margin-top:4px;">
                    <i class="las la-user-check"></i>
                    <span id="pay-client-badge-name"></span>
                    <button type="button" onclick="clearDocResult()" style="margin-left:auto; background:none; border:none; color:#15803d; cursor:pointer; font-size:14px; line-height:1;">✕</button>
                </div>

                <div style="background: var(--s-bg-light); border: 1px solid var(--s-border); border-radius: 12px; padding: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h4 style="margin: 0; font-size: 12px; text-transform: uppercase; font-weight: 800; color: var(--s-text-secondary); letter-spacing: 0.5px;">Métodos de Pago</h4>
                        <select id="add-payment-method-pay" class="s-input" style="width: 180px; height: 28px; padding: 2px 6px; font-size: 11px; border-radius: 6px; background: #fff;" onchange="showPaymentMethod('pay', this.value); this.value='';">
                            <option value="">+ Agregar método...</option>
                            <option value="cash">Efectivo</option>
                            <option value="card">POS / Tarjeta</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="transfer">Transferencia</option>
                            <option value="credit">Crédito (Pago Posterior)</option>
                            <option value="courtesy">Cortesía (Consumo Libre)</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; background: #fff; border: 1px dashed var(--s-border); border-radius: 10px; padding: 10px 12px; margin-bottom: 12px;">
                        <span style="font-size: 11px; font-weight: 800; color: var(--s-text-secondary); text-transform: uppercase;">Dividir cuenta</span>
                        <input type="number" min="2" max="50" value="2" id="pay-split-people" class="s-input" style="width: 64px; height: 30px; padding: 2px 8px; font-size: 12px; font-weight: 800; text-align: center;">
                        <span style="font-size: 12px; color: var(--s-text-muted);">partes</span>
                        <select id="pay-split-method" class="s-input" style="width: 125px; height: 30px; padding: 2px 6px; font-size: 11px; border-radius: 6px;">
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="transfer">Transferencia</option>
                            <option value="credit">Crédito</option>
                        </select>
                        <button type="button" onclick="applyEqualSplit('pay')" class="s-btn s-btn-ghost s-btn-xs" style="height: 30px; border: 1px solid var(--s-border); font-weight: 800;">
                            Aplicar 1 parte
                        </button>
                        <span id="pay-split-preview" style="font-size: 12px; color: var(--s-text-muted); font-weight: 700;"></span>
                    </div>
                    <div style="background: #fff; border: 1px dashed var(--s-border); border-radius: 10px; padding: 10px 12px; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                            <span style="font-size: 11px; font-weight: 800; color: var(--s-text-secondary); text-transform: uppercase;">Por productos</span>
                            <input type="text" id="pay-product-person" class="s-input" value="Persona 1" style="width: 100px; height: 30px; padding: 2px 8px; font-size: 12px; font-weight: 700;">
                            <select id="pay-product-method" class="s-input" style="width: 125px; height: 30px; padding: 2px 6px; font-size: 11px; border-radius: 6px;">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                                <option value="transfer">Transferencia</option>
                                <option value="credit">Crédito</option>
                            </select>
                            <button type="button" onclick="applyProductSplit('pay')" class="s-btn s-btn-ghost s-btn-xs" style="height: 30px; border: 1px solid var(--s-border); font-weight: 800;">
                                Aplicar consumo
                            </button>
                            <span id="pay-product-split-total" style="font-size: 12px; color: var(--s-primary); font-weight: 900;">S/ 0.00</span>
                        </div>
                        <div id="pay-product-split-items" style="display: flex; flex-direction: column; gap: 6px; max-height: 150px; overflow-y: auto;"></div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @php
                            $methods = [
                                'cash' => ['label' => 'Efectivo', 'icon' => '<i class="las la-money-bill-wave" style="color: #22c55e; font-size: 20px;"></i>'],
                                'card' => ['label' => 'POS / Tarjeta', 'icon' => '<i class="las la-credit-card" style="color: #f97316; font-size: 20px;"></i>'],
                                'yape' => ['label' => 'Yape', 'icon' => '<span style="background: #74226C; color: #fff; font-weight: 900; font-size: 9px; padding: 2px 6px; border-radius: 6px; text-transform: uppercase; box-shadow: 0 2px 4px rgba(116,34,108,0.3); letter-spacing: 0.5px; display: inline-block;">Yape</span>'],
                                'plin' => ['label' => 'Plin', 'icon' => '<span style="background: #00d2c4; color: #fff; font-weight: 900; font-size: 9px; padding: 2px 6px; border-radius: 6px; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,210,196,0.3); letter-spacing: 0.5px; display: inline-block;">Plin</span>'],
                                'transfer' => ['label' => 'Transferencia', 'icon' => '<i class="las la-university" style="color: #3b82f6; font-size: 20px;"></i>'],
                                'credit' => ['label' => 'Crédito (Pago Posterior)', 'icon' => '<i class="las la-clock" style="color: #6366f1; font-size: 20px;"></i>'],
                                'courtesy' => ['label' => 'Cortesía (Consumo Libre)', 'icon' => '<i class="las la-gift" style="color: #a855f7; font-size: 20px;"></i>'],
                            ];
                        @endphp
                        @foreach($methods as $name => $data)
                        <div id="payment-row-pay-{{ $name }}" style="display: {{ $name === 'cash' ? 'flex' : 'none' }}; flex-direction: column; gap: 6px; border-bottom: 1px dashed var(--s-border); padding-bottom: 10px; margin-bottom: 6px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                    {!! $data['icon'] !!}
                                    <span style="font-weight: 700; font-size: 13px; color: var(--s-text-primary);">{{ $data['label'] }}</span>
                                    @if($name !== 'cash')
                                    <button type="button" onclick="hidePaymentMethod('pay', '{{ $name }}')" style="background: none; border: none; color: var(--s-danger); cursor: pointer; padding: 0 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-left: 4px;" title="Quitar">
                                        <i class="las la-trash-alt"></i>
                                    </button>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <button type="button" onclick="fillPayment('{{ $name }}', 'all')" style="padding: 2px 6px; font-size: 10px; font-weight: 800; background: var(--s-primary); color: #fff; border: none; border-radius: 4px; cursor: pointer; transition: opacity 0.15s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Todo</button>
                                    <button type="button" onclick="fillPayment('{{ $name }}', 'rest')" style="padding: 2px 6px; font-size: 10px; font-weight: 800; background: var(--s-info); color: #fff; border: none; border-radius: 4px; cursor: pointer; transition: opacity 0.15s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Resto</button>
                                    <span style="font-size: 12px; color: var(--s-text-muted);">S/</span>
                                    <input type="number" step="0.01" min="0" class="s-input split-pay-input" name="payments[{{ $name }}]" id="pay-input-{{ $name }}" data-method="{{ $name }}" value="0.00" style="width: 100px; height: 34px; text-align: right; padding: 4px 8px; border-radius: 8px; font-weight: 700;" oninput="updateSplitTotal('pay')">
                                </div>
                            </div>
                            
                            @if($name !== 'cash' && $name !== 'courtesy' && $name !== 'credit')
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; font-size: 11px;">
                                <span style="color: var(--s-text-secondary); font-weight: 600;">Destinar a:</span>
                                <select class="s-input" name="payment_accounts[{{ $name }}]" style="width: 220px; height: 28px; padding: 2px 6px; font-size: 11px; border-radius: 6px; background: #fff;">
                                    @php
                                        $typeMapping = [
                                            'yape' => 'wallet',
                                            'plin' => 'wallet',
                                            'card' => 'pos_card',
                                            'transfer' => 'bank'
                                        ];
                                        $targetType = $typeMapping[$name] ?? 'bank';
                                        $filteredAccounts = $bankAccounts->where('type', $targetType);
                                    @endphp
                                    <option value="">-- Cuenta Defectiva --</option>
                                    @foreach($filteredAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ strtoupper($acc->bank_name) }})</option>
                                    @endforeach
                                    @if($filteredAccounts->isEmpty())
                                    @foreach($bankAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ strtoupper($acc->bank_name) }})</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-top: 1px dashed var(--s-border); padding-top: 14px;">
                <span style="font-weight: 800; color: var(--s-text-primary);">Monto Ingresado:</span>
                <span style="font-size: 16px; font-weight: 950; color: var(--s-primary);">S/ <span id="pay-entered-display">0.00</span></span>
            </div>
            <div id="pay-validation-alert" style="display:none; font-size:12px; color:var(--s-danger-text); background:var(--s-danger-bg); padding:8px 12px; border-radius:8px; margin-bottom:15px; font-weight:700;"></div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="s-btn s-btn-ghost" onclick="closePayModal()">Cancelar</button>
                <button type="submit" class="s-btn s-btn-success" id="pay-submit-btn">
                    <i class="las la-check-circle"></i> Confirmar Cobro
                </button>
            </div>
        </form>
    </div>
</div>

<!-- PRINT / RECEIPT SELECTION MODAL -->
<div id="print-select-modal" class="s-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,25,35,0.6); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div style="background: #fff; width: 550px; padding: 24px; border-radius: 16px; border: 1px solid var(--s-border); position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 16px;">
        <h3 style="margin: 0; font-weight: 800; font-size: 18px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-print" style="color: var(--s-primary); font-size: 24px;"></i> Comprobante Generado Exitosamente
        </h3>
        <p style="font-size: 13px; color: var(--s-text-muted); margin: 0;">Selecciona el formato que deseas imprimir o descargar. El ticket térmico se muestra a continuación.</p>
        
        <!-- Ticket Iframe Preview -->
        <div style="border: 1px solid var(--s-border); border-radius: 8px; overflow: hidden; background: #f8fafc; height: 300px; width: 100%;">
            <iframe id="print-preview-iframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 6px;">
            <button type="button" class="s-btn s-btn-success" onclick="printThermalTicket()" style="justify-content: center; height: 42px; font-weight: 700; grid-column: 1 / -1;">
                <i class="las la-receipt" style="font-size: 18px;"></i> Imprimir Ticket Térmico (80mm)
            </button>
            <a id="download-pdf-a4" href="#" target="_blank" class="s-btn s-btn-outline" style="justify-content: center; height: 42px; font-weight: 700; text-decoration: none; display: flex; align-items: center; color: var(--s-text-primary); border: 1px solid var(--s-border);">
                <i class="las la-file-pdf" style="color: #dc2626; font-size: 18px;"></i> Formato PDF A4
            </a>
            <a id="download-pdf-a5" href="#" target="_blank" class="s-btn s-btn-outline" style="justify-content: center; height: 42px; font-weight: 700; text-decoration: none; display: flex; align-items: center; color: var(--s-text-primary); border: 1px solid var(--s-border);">
                <i class="las la-file-pdf" style="color: #dc2626; font-size: 18px;"></i> Formato PDF A5
            </a>
        </div>
        
        <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--s-border); padding-top: 12px; margin-top: 4px;">
            <button type="button" class="s-btn s-btn-primary" onclick="closePrintModalAndReload()" style="font-weight: 700; min-width: 100px; justify-content: center;">Listo / Cerrar</button>
        </div>
    </div>
</div>

@push('script')
<script>
let currentOrderTotal = 0;
let currentOrderBaseTotal = 0;
let currentOrderItems = [];
let productSplitStarted = { pay: false };
@php
$billingPaymentData = $pendingOrders->mapWithKeys(function($order) {
    return [$order->id => $order->items->map(function($item) {
        $quantity = max(1, (int) $item->quantity);
        return [
            'id' => $item->id,
            'name' => $item->product_name ?: ($item->product?->name ?? 'Producto'),
            'quantity' => $quantity,
            'unit_price' => round(((float) $item->total_price) / $quantity, 2),
        ];
    })->values()];
});
@endphp
const orderPaymentItems = @json($billingPaymentData);

function fillPayment(method, fillType) {
    let inputs = document.querySelectorAll('.split-pay-input');
    
    if (fillType === 'all') {
        inputs.forEach(input => {
            if (input.getAttribute('data-method') === method) {
                input.value = currentOrderTotal.toFixed(2);
            } else {
                input.value = '0.00';
            }
        });
    } else if (fillType === 'rest') {
        let sumOfOthers = 0;
        inputs.forEach(input => {
            if (input.getAttribute('data-method') !== method) {
                let val = parseFloat(input.value);
                if (!isNaN(val) && val > 0) {
                    sumOfOthers += val;
                }
            }
        });
        let rest = Math.max(0, currentOrderTotal - sumOfOthers);
        inputs.forEach(input => {
            if (input.getAttribute('data-method') === method) {
                input.value = rest.toFixed(2);
            }
        });
    }
    updateSplitTotal('pay');
}

function applyEqualSplit(type) {
    let peopleInput = document.getElementById(type + '-split-people');
    let methodSelect = document.getElementById(type + '-split-method');
    let preview = document.getElementById(type + '-split-preview');
    if (!peopleInput || !methodSelect) return;

    let people = Math.max(2, parseInt(peopleInput.value || '2', 10));
    let method = methodSelect.value || 'cash';
    let perPerson = Math.floor((currentOrderTotal / people) * 100) / 100;
    let remainder = +(currentOrderTotal - (perPerson * people)).toFixed(2);

    showPaymentMethod(type, method);
    let input = document.getElementById(type + '-input-' + method);
    if (input) {
        input.value = perPerson.toFixed(2);
    }
    if (preview) {
        preview.textContent = 'S/ ' + perPerson.toFixed(2) + ' por parte' + (remainder > 0 ? ' (+ S/ ' + remainder.toFixed(2) + ' de ajuste)' : '');
    }
    updateSplitTotal(type);
}

function renderProductSplit(type) {
    let container = document.getElementById(type + '-product-split-items');
    if (!container) return;
    if (!currentOrderItems.length) {
        container.innerHTML = '<div style="font-size:12px;color:var(--s-text-muted);padding:6px 0;">Esta orden no tiene productos detallados para dividir.</div>';
        return;
    }

    container.innerHTML = currentOrderItems.map(item => {
        let max = parseInt(item.quantity || 1, 10);
        let unit = parseFloat(item.unit_price || 0);
        return '<div style="display:flex;align-items:center;gap:8px;justify-content:space-between;border-bottom:1px solid var(--s-border);padding-bottom:6px;">'
            + '<div style="min-width:0;flex:1;"><div style="font-size:12px;font-weight:800;color:var(--s-text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(item.name) + '</div>'
            + '<div style="font-size:11px;color:var(--s-text-muted);">S/ ' + unit.toFixed(2) + ' c/u · disponible: ' + max + '</div></div>'
            + '<input type="number" min="0" max="' + max + '" step="1" value="0" data-unit="' + unit.toFixed(2) + '" class="s-input product-split-' + type + '-qty" style="width:64px;height:30px;text-align:center;font-size:12px;font-weight:800;">'
            + '</div>';
    }).join('');
    container.querySelectorAll('.product-split-' + type + '-qty').forEach(input => {
        input.addEventListener('input', function() { updateProductSplitTotal(type); });
    });
    updateProductSplitTotal(type);
}

function updateProductSplitTotal(type) {
    let inputs = document.querySelectorAll('.product-split-' + type + '-qty');
    let total = 0;
    inputs.forEach(input => {
        let qty = Math.max(0, parseInt(input.value || '0', 10));
        let max = Math.max(0, parseInt(input.max || '0', 10));
        if (qty > max) {
            qty = max;
            input.value = max;
        }
        total += qty * parseFloat(input.dataset.unit || '0');
    });
    let label = document.getElementById(type + '-product-split-total');
    if (label) label.textContent = 'S/ ' + total.toFixed(2);
    return total;
}

function applyProductSplit(type) {
    let methodSelect = document.getElementById(type + '-product-method');
    let method = methodSelect ? methodSelect.value : 'cash';
    let total = updateProductSplitTotal(type);
    if (total <= 0) return;

    if (!productSplitStarted[type]) {
        document.querySelectorAll('.split-' + type + '-input').forEach(input => { input.value = '0.00'; });
        productSplitStarted[type] = true;
    }

    showPaymentMethod(type, method);
    let input = document.getElementById(type + '-input-' + method);
    if (input) {
        let existing = parseFloat(input.value || '0');
        input.value = ((isNaN(existing) ? 0 : existing) + total).toFixed(2);
    }
    document.querySelectorAll('.product-split-' + type + '-qty').forEach(input => { input.value = '0'; });
    updateProductSplitTotal(type);
    updateSplitTotal(type);
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(char) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
}

function showPaymentMethod(type, method) {
    let row = document.getElementById('payment-row-' + type + '-' + method);
    if (!row) return;
    row.style.display = 'flex';
    
    // Auto fill remaining balance if not cash and it's being shown
    if (method !== 'cash') {
        let inputs = document.querySelectorAll('.split-' + type + '-input');
        let sumOfOthers = 0;
        inputs.forEach(input => {
            if (input.getAttribute('data-method') !== method) {
                let val = parseFloat(input.value);
                if (!isNaN(val) && val > 0) {
                    sumOfOthers += val;
                }
            }
        });
        let rest = Math.max(0, currentOrderTotal - sumOfOthers);
        let input = document.getElementById(type + '-input-' + method);
        if (input) {
            input.value = rest.toFixed(2);
        }
    }
    
    updatePaymentDropdown(type);
    updateSplitTotal(type);
}

function hidePaymentMethod(type, method) {
    let row = document.getElementById('payment-row-' + type + '-' + method);
    if (!row) return;
    row.style.display = 'none';
    
    let input = document.getElementById(type + '-input-' + method);
    if (input) {
        input.value = '0.00';
    }
    
    updatePaymentDropdown(type);
    updateSplitTotal(type);
}

function updatePaymentDropdown(type) {
    let select = document.getElementById('add-payment-method-' + type);
    if (!select) return;
    
    for (let option of select.options) {
        if (!option.value) continue;
        let row = document.getElementById('payment-row-' + type + '-' + option.value);
        if (row && row.style.display !== 'none') {
            option.disabled = true;
            option.style.display = 'none';
        } else {
            option.disabled = false;
            option.style.display = 'block';
        }
    }
}

function openPayModal(orderId, total, orderNo){
    currentOrderBaseTotal = parseFloat(total);
    currentOrderTotal = currentOrderBaseTotal;
    currentOrderItems = orderPaymentItems[orderId] || [];
    productSplitStarted.pay = false;
    document.getElementById('pay-order-no').innerText = orderNo;
    document.getElementById('pay-total-display').innerText = currentOrderTotal.toFixed(2);
    document.getElementById('pay-include-tupper').checked = false;
    document.getElementById('pay-form').action = '{{ route('seller.pos.order.pay', '__ID__') }}'.replace('__ID__', orderId);
    const detailedOption = document.querySelector('#pay-form input[name="detail_mode"][value="detailed"]');
    if (detailedOption) detailedOption.checked = true;
    const consumptionDescription = document.getElementById('consumption-description');
    if (consumptionDescription) consumptionDescription.value = '';
    toggleConsumptionDescription();
    
    // Set default payment to full cash
    let inputs = document.querySelectorAll('.split-pay-input');
    inputs.forEach(input => {
        if(input.getAttribute('data-method') === 'cash') {
            input.value = currentOrderTotal.toFixed(2);
        } else {
            input.value = '0.00';
        }
    });
    
    showPaymentMethod('pay', 'cash');
    ['card', 'yape', 'plin', 'transfer', 'courtesy', 'credit'].forEach(m => hidePaymentMethod('pay', m));
    
    updateSplitTotal('pay');
    renderProductSplit('pay');
    clearDocResult();
    document.getElementById('pay-num-doc').value = '';
    onBillingSeriesChange();
    document.getElementById('pay-modal').style.display = 'flex';
}

function onBillingSeriesChange() {
    const seriesSelect = document.getElementById('pay-series');
    if (!seriesSelect) return;
    const selectedOption = seriesSelect.options[seriesSelect.selectedIndex];
    const docCode = selectedOption ? (selectedOption.getAttribute('data-code') || '') : '';
    const tipoDocSelect = document.getElementById('pay-tipo-doc');
    const numDocInput = document.getElementById('pay-num-doc');
    if (!tipoDocSelect || !numDocInput) return;

    if (docCode === '01') {
        tipoDocSelect.value = '6';
        numDocInput.placeholder = 'N° RUC cliente (11 dígitos - obligatorio)';
        numDocInput.required = true;
    } else if (docCode === '03') {
        tipoDocSelect.value = '1';
        numDocInput.placeholder = 'N° DNI cliente (8 dígitos - opcional)';
        numDocInput.required = false;
    } else {
        tipoDocSelect.value = '1';
        numDocInput.placeholder = 'N° Documento cliente (opcional)';
        numDocInput.required = false;
    }
    clearDocResult();
}

function updateTupperFee() {
    const includeTupper = document.getElementById('pay-include-tupper')?.checked;
    currentOrderTotal = currentOrderBaseTotal + (includeTupper ? 1 : 0);
    currentOrderItems = currentOrderItems.filter(item => !item.is_tupper);
    if (includeTupper) {
        currentOrderItems.push({ id: 'tupper', name: 'Tupper', quantity: 1, unit_price: 1, is_tupper: true });
    }
    document.getElementById('pay-total-display').innerText = currentOrderTotal.toFixed(2);
    // Keep the default cash amount in sync; when the account was split, the
    // remaining amount is assigned to cash and validation stays accurate.
    fillPayment('cash', 'rest');
    renderProductSplit('pay');
}

function closePayModal(){
    document.getElementById('pay-modal').style.display = 'none';
}

function toggleConsumptionDescription() {
    const mode = document.querySelector('input[name="detail_mode"]:checked')?.value;
    const wrap = document.getElementById('consumption-description-wrap');
    const input = document.getElementById('consumption-description');
    if (!wrap || !input) return;
    const isConsumption = mode === 'consumption';
    wrap.style.display = isConsumption ? 'block' : 'none';
    input.required = isConsumption;
    if (isConsumption && !input.value) input.value = 'Consumo';
}

function updateSplitTotal(type) {
    let inputs = document.querySelectorAll('.split-' + type + '-input');
    let sum = 0;
    inputs.forEach(input => {
        let val = parseFloat(input.value);
        if(!isNaN(val) && val > 0) {
            sum += val;
        }
    });
    
    document.getElementById(type + '-entered-display').innerText = sum.toFixed(2);
    
    let alertDiv = document.getElementById(type + '-validation-alert');
    let submitBtn = document.getElementById(type + '-submit-btn');
    
    // The sum must match the order total
    let diff = Math.abs(sum - currentOrderTotal);
    if(diff > 0.01) {
        alertDiv.style.display = 'block';
        alertDiv.innerText = 'El monto total ingresado (S/ ' + sum.toFixed(2) + ') debe coincidir exactamente con el total de la cuenta (S/ ' + currentOrderTotal.toFixed(2) + ')';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
    } else {
        alertDiv.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
    }
}

// ── Document auto-search ──
let docSearchTimer = null;

function clearDocResult() {
    var r = document.getElementById('pay-doc-result');
    r.style.display = 'none';
    r.innerHTML = '';
    var b = document.getElementById('pay-client-badge');
    b.style.display = 'none';
    document.getElementById('pay-customer-name').value = '';
}

function selectClient(name) {
    document.getElementById('pay-customer-name').value = name;
    var r = document.getElementById('pay-doc-result');
    r.style.display = 'none';
    r.innerHTML = '';
    var b = document.getElementById('pay-client-badge');
    document.getElementById('pay-client-badge-name').textContent = name;
    b.style.display = 'flex';
}

function searchDoc() {
    var tipo = document.getElementById('pay-tipo-doc').value;
    var num = document.getElementById('pay-num-doc').value.trim();
    var r = document.getElementById('pay-doc-result');

    var minLen = tipo === '1' ? 8 : 11;
    if (num.length < minLen) { r.style.display = 'none'; return; }

    r.style.display = 'block';
    r.innerHTML = '<span style="color:var(--s-text-muted);">Buscando...</span>';

    fetch('{{ route("seller.sunat") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ numdoc: num, tpdoc: tipo })
    })
    .then(function(x) { return x.json(); })
    .then(function(d) {
        if (d.status && d.nombre) {
            r.innerHTML = '<div onclick="selectClient(\'' + d.nombre.replace(/'/g, "\\'") + '\')" onmouseover="this.style.background=\'rgba(34,197,94,0.2)\'" onmouseout="this.style.background=\'rgba(34,197,94,0.1)\'" style="cursor:pointer; background:rgba(34,197,94,0.1); color:#15803d; border:1px solid rgba(34,197,94,0.3); border-radius:6px; padding:8px 12px; font-weight:700; display:flex; justify-content:space-between; align-items:center; transition:background 0.2s;"><span>✓ ' + d.nombre + '</span><span style="font-size:10px; background:#22c55e; color:#fff; padding:2px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px;">Seleccionar</span></div>';
        } else {
            r.innerHTML = '<div style="color:var(--s-danger-text); padding:4px 6px;">✗ ' + (d.result || 'No encontrado') + '</div>';
        }
    })
    .catch(function() {
        r.innerHTML = '<div style="color:var(--s-danger-text); padding:4px 6px;">✗ Error de conexión</div>';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var numInput = document.getElementById('pay-num-doc');
    if (numInput) {
        numInput.addEventListener('input', function() {
            clearTimeout(docSearchTimer);
            docSearchTimer = setTimeout(searchDoc, 400);
        });
    }

    var payForm = document.getElementById('pay-form');
    if (payForm) {
        payForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var seriesSelect = document.getElementById('pay-series');
            var selectedOpt = seriesSelect ? seriesSelect.options[seriesSelect.selectedIndex] : null;
            var docCode = selectedOpt ? (selectedOpt.getAttribute('data-code') || '') : '';
            var numDocInput = document.getElementById('pay-num-doc');

            if (docCode === '01') {
                var rucVal = numDocInput ? numDocInput.value.trim() : '';
                if (rucVal.length !== 11 || !/^\d{11}$/.test(rucVal)) {
                    alert('Para emitir una Factura Electrónica debes ingresar un número de RUC válido de 11 dígitos.');
                    if (numDocInput) numDocInput.focus();
                    return;
                }
            }

            var submitBtn = document.getElementById('pay-submit-btn');
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.innerHTML = '<i class="las la-spinner la-spin"></i> Procesando...';
            
            var formData = new FormData(payForm);
            
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrfMeta ? csrfMeta.content : '';

            fetch(payForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
            .then(function(res) {
                var contentType = res.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    return res.json().then(function(data) {
                        return { ok: res.ok, status: res.status, data: data };
                    });
                } else {
                    return res.text().then(function(text) {
                        return { ok: false, status: res.status, errorText: text };
                    });
                }
            })
            .then(function(result) {
                if (result.ok && result.data && result.data.status) {
                    var data = result.data;
                    closePayModal();
                    
                    // Set iframe source to load the thermal ticket view
                    var ticketUrl = '{{ route("seller.pos.order.ticket", "__ID__") }}'.replace('__ID__', data.order_id);
                    var iframe = document.getElementById('print-preview-iframe');
                    if (iframe) iframe.src = ticketUrl;
                    
                    // Configure download / view links for A4 and A5 PDFs
                    var a4Btn = document.getElementById('download-pdf-a4');
                    var a5Btn = document.getElementById('download-pdf-a5');
                    if (data.has_invoice && data.invoice_id) {
                        var a4Url = '{{ route("seller.invoice.pdf", ["__INV_ID__", "a4"]) }}'.replace('__INV_ID__', data.invoice_id);
                        var a5Url = '{{ route("seller.invoice.pdf", ["__INV_ID__", "a5"]) }}'.replace('__INV_ID__', data.invoice_id);
                        if (a4Btn) { a4Btn.href = a4Url; a4Btn.style.display = 'inline-flex'; }
                        if (a5Btn) { a5Btn.href = a5Url; a5Btn.style.display = 'inline-flex'; }
                    } else {
                        if (a4Btn) a4Btn.style.display = 'none';
                        if (a5Btn) a5Btn.style.display = 'none';
                    }
                    
                    // Open the print select modal
                    var printModal = document.getElementById('print-select-modal');
                    if (printModal) printModal.style.display = 'flex';
                } else {
                    var msg = (result.data && (result.data.message || result.data.error))
                        ? (result.data.message || result.data.error)
                        : (result.errorText ? 'Error en el servidor (' + result.status + ')' : 'Ocurrió un error inesperado al procesar el pago.');

                    if (result.data && result.data.errors && typeof result.data.errors === 'object') {
                        var errMsgs = [];
                        Object.keys(result.data.errors).forEach(function(k) {
                            var val = result.data.errors[k];
                            if (Array.isArray(val)) {
                                errMsgs.push(val.join(', '));
                            } else if (typeof val === 'string') {
                                errMsgs.push(val);
                            }
                        });
                        if (errMsgs.length > 0) msg += '\n' + errMsgs.join('\n');
                    }

                    alert(msg);
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = '<i class="las la-check-circle"></i> Confirmar Cobro';
                }
            })
            .catch(function(err) {
                console.error('Error en payForm submit:', err);
                var errDetail = (err && err.message) ? err.message : String(err);
                alert('Error al procesar la solicitud:\n' + errDetail);
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.innerHTML = '<i class="las la-check-circle"></i> Confirmar Cobro';
            });
        });
    }
});

function printThermalTicket() {
    var iframe = document.getElementById('print-preview-iframe');
    if (iframe && iframe.contentWindow) {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }
}

function closePrintModalAndReload() {
    document.getElementById('print-select-modal').style.display = 'none';
    window.location.reload();
}
</script>
@endpush
@endsection
