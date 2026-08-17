@extends('seller.layouts.app')
@section('panel')
<div style="padding: 24px; max-width: 1200px; margin: 0 auto;">
    
    <!-- BACK BUTTON & HEADER -->
    <div style="margin-bottom: 24px;">
        <a href="{{ route('seller.crm.customers') }}" class="s-btn s-btn-ghost s-btn-sm" style="border: 1px solid var(--s-border); border-radius: 8px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; font-weight: 700; text-decoration: none; font-size: 13px;">
            <i class="las la-arrow-left"></i> Volver a Clientes
        </a>
    </div>

    <!-- PROFILE CARD -->
    <div class="s-card" style="padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
        <div style="background: rgba(var(--s-primary-rgb), 0.1); color: var(--s-primary); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px;">
            <i class="las la-user-tie"></i>
        </div>
        <div>
            <h2 style="font-weight: 800; font-size: 22px; color: var(--s-text-primary); margin: 0;">{{ $customer->customer_name ?: 'Cliente Sin Nombre' }}</h2>
            <p style="color: var(--s-text-muted); margin: 4px 0 0 0; font-size: 14px; display: flex; align-items: center; gap: 6px;">
                @if($customer->customer_doc)
                    <span class="s-badge s-badge-gray" style="font-size: 11px; padding: 2px 6px; font-weight: 700;">
                        {{ $customer->customer_doc_type == '6' ? 'RUC' : ($customer->customer_doc_type == '1' ? 'DNI' : 'OTRO') }}
                    </span>
                    <span style="font-weight: 700; color: var(--s-text-primary);">{{ $customer->customer_doc }}</span>
                @elseif($customer->customer_phone)
                    <span class="s-badge s-badge-gray" style="font-size: 11px; padding: 2px 6px; font-weight: 700;">TEL</span>
                    <span style="font-weight: 700; color: var(--s-text-primary);">{{ $customer->customer_phone }}</span>
                @else
                    <span style="color: var(--s-text-muted);">Sin documento registrado</span>
                @endif
            </p>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="s-card" style="padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px;">
            <div style="background: rgba(var(--s-primary-rgb), 0.08); color: var(--s-primary); width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="las la-shopping-bag"></i>
            </div>
            <div>
                <span style="font-size: 12px; color: var(--s-text-muted); text-transform: uppercase; font-weight: 700;">Total Pedidos</span>
                <h4 style="margin: 2px 0 0 0; font-weight: 800; font-size: 18px; color: var(--s-text-primary);">{{ $stats->total_orders }}</h4>
            </div>
        </div>
        <div class="s-card" style="padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px;">
            <div style="background: rgba(var(--s-success-rgb), 0.08); color: var(--s-success); width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="las la-wallet"></i>
            </div>
            <div>
                <span style="font-size: 12px; color: var(--s-text-muted); text-transform: uppercase; font-weight: 700;">Consumo Total</span>
                <h4 style="margin: 2px 0 0 0; font-weight: 800; font-size: 18px; color: var(--s-text-primary);">S/ {{ number_format($stats->total_spent, 2) }}</h4>
            </div>
        </div>
        <div class="s-card" style="padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px;">
            <div style="background: rgba(220, 38, 38, 0.08); color: #dc2626; width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="las la-exclamation-circle"></i>
            </div>
            <div>
                <span style="font-size: 12px; color: var(--s-text-muted); text-transform: uppercase; font-weight: 700;">Deuda (Créditos)</span>
                <h4 style="margin: 2px 0 0 0; font-weight: 800; font-size: 18px; color: #dc2626;">S/ {{ number_format($stats->total_credit, 2) }}</h4>
            </div>
        </div>
    </div>

    <!-- INVOICES HISTORY -->
    <div class="s-card" style="padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 20px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="las la-file-invoice" style="color: var(--s-primary); font-size: 22px;"></i> Historial de Comprobantes y Ventas
        </h3>

        <div class="s-table-wrapper" style="margin: 0 -24px -24px -24px;">
            <table class="s-table">
                <thead>
                    <tr>
                        <th>Nº Pedido</th>
                        <th>Comprobante</th>
                        <th style="text-align: right;">Total</th>
                        <th>Estado de Pago</th>
                        <th style="text-align: center;">Estado SUNAT</th>
                        <th>Medio Pago</th>
                        <th>Fecha</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                    <tr>
                        <td style="font-weight: 700;">#{{ $o->order_no }}</td>
                        <td>
                            @if($o->invoice_series)
                            <span class="s-badge s-badge-green" style="font-weight: 700;">{{ $o->invoice_series }}-{{ $o->invoice_number }}</span>
                            @else
                            <span style="font-size:12px; color:var(--s-text-muted);">Sin comprobante</span>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--s-text-primary);">S/ {{ number_format($o->total, 2) }}</td>
                        <td>
                            @if($o->payment_status === 'paid')
                                <span class="s-badge s-badge-green" style="font-size: 10px; font-weight: 800;">PAGADO</span>
                            @elseif($o->payment_status === 'credit')
                                <span class="s-badge" style="font-size: 10px; font-weight: 800; background: #dc2626; color: #fff;">A CRÉDITO</span>
                            @else
                                <span class="s-badge s-badge-gray" style="font-size: 10px; font-weight: 800;">PENDIENTE</span>
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
                        <td style="text-transform: uppercase; font-size: 12px; font-weight: 600;">
                            {{ $o->payment_method ?? 'CASH' }}
                        </td>
                        <td style="font-size: 11px; color: var(--s-text-muted);">
                            {{ $o->created_at?->format('d/m/Y H:i') }}
                        </td>
                        <td style="text-align: center;">
                            @if($o->sunatInvoice)
                            <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
                                <a href="{{ route('seller.invoice.detail', $o->sunatInvoice->id) }}" class="s-btn s-btn-ghost s-btn-xs" title="Ver detalle" style="padding:4px; min-height: 26px; width: 26px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="las la-eye" style="font-size:16px;color:var(--s-primary);"></i>
                                </a>
                                <a href="{{ route('seller.invoice.pdf', [$o->sunatInvoice->id, 'a4']) }}" class="s-btn s-btn-ghost s-btn-xs" title="Descargar PDF A4" style="padding:4px; min-height: 26px; width: 26px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="las la-file-pdf" style="font-size:16px;color:#dc2626;"></i>
                                </a>
                                <a href="{{ route('seller.invoice.pdf', [$o->sunatInvoice->id, 'ticket']) }}" class="s-btn s-btn-ghost s-btn-xs" title="Descargar Ticket" style="padding:4px; min-height: 26px; width: 26px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="las la-receipt" style="font-size:16px;color:var(--s-info);"></i>
                                </a>
                                <a href="{{ route('seller.invoice.xml', $o->sunatInvoice->id) }}" class="s-btn s-btn-ghost s-btn-xs" title="Descargar XML" style="padding:4px; min-height: 26px; width: 26px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="las la-file-code" style="font-size:16px;color:#8b5cf6;"></i>
                                </a>
                                @if(in_array($o->sunatInvoice->cdr_status, ['pending', 'error', 'rejected']))
                                <form method="POST" action="{{ route('seller.invoice.resend', $o->sunatInvoice->id) }}" onsubmit="return confirm('¿Reenviar a SUNAT?');" style="display:inline; margin:0;">
                                    @csrf
                                    <button type="submit" class="s-btn s-btn-ghost s-btn-xs" title="Reenviar a SUNAT" style="padding:4px; min-height: 26px; width: 26px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="las la-redo" style="font-size:16px;color:var(--s-info);"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                            @else
                            <span style="font-size:12px; color:var(--s-text-muted); font-weight: 700;">Venta POS</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--s-text-muted);">
                            No hay historial de compras registrado para este cliente.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- PAGINATION -->
        @if ($orders->hasPages())
        <div style="padding: 20px; display: flex; justify-content: center; border-top: 1px solid var(--s-border); margin-top: 24px;">
            {{ $orders->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
