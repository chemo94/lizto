@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-file-invoice"></i></span> {{ $pageTitle }}
@endsection

@section('topbar-actions')
<a href="{{ route('seller.invoicing') }}" class="s-btn s-btn-ghost s-btn-sm" style="border:1px solid var(--s-border)">
    <i class="las la-arrow-left"></i> Volver a Facturación
</a>
@endsection

@section('seller-content')
<style>
.invoice-detail-page{width:100%;max-width:none;margin:0;padding:clamp(16px,2vw,32px)}
.invoice-detail-hero{background:linear-gradient(115deg,#14532d,#15803d 58%,#34a36b);border-radius:18px;padding:clamp(22px,3vw,38px);color:#fff;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;box-shadow:0 14px 34px rgba(21,128,61,.18)}
.invoice-detail-hero h2{margin:7px 0 0;font-size:clamp(24px,3vw,34px);font-weight:850;letter-spacing:-.6px;line-height:1.15}
.invoice-detail-hero .invoice-kind{font-size:11px;text-transform:uppercase;letter-spacing:1.4px;opacity:.82;font-weight:800}
.invoice-detail-hero .invoice-number{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.78em;opacity:.9}
.invoice-detail-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(360px,.85fr);gap:20px;align-items:stretch}
.invoice-detail-card{border:1px solid var(--s-border);border-radius:16px;padding:clamp(18px,2vw,26px);box-shadow:0 5px 18px rgba(15,23,42,.045);min-width:0}
.invoice-detail-card>h3{font-size:16px!important;margin-bottom:18px!important}
.invoice-data-grid{grid-template-columns:minmax(130px,.38fr) minmax(0,1fr)!important;gap:13px 22px!important;font-size:13px!important}
.invoice-data-grid>div{min-width:0;overflow-wrap:anywhere}
.invoice-actions-grid{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px!important}
.invoice-actions-grid>.s-btn,.invoice-actions-grid>form{min-width:0;height:100%}
.invoice-actions-grid>.s-btn{min-height:76px;align-items:center!important;padding:14px!important;background:#fff;transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease}
.invoice-actions-grid>.s-btn:hover{transform:translateY(-2px);border-color:#86c99e!important;box-shadow:0 8px 20px rgba(15,23,42,.07)}
.invoice-actions-grid>form .s-btn{height:100%;min-height:76px;align-items:center!important;padding:14px!important}
.invoice-related{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:12px;margin:18px 0}
.invoice-related-item{display:flex;align-items:center;gap:10px;padding:15px 17px;border:1px solid var(--s-border);border-radius:12px;background:#fff;color:var(--s-text-primary);font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 4px 14px rgba(15,23,42,.035)}
.invoice-related-item:hover{border-color:#86c99e;color:var(--s-primary)}
.invoice-detail-page>.s-card{border-radius:16px!important;padding:clamp(18px,2vw,26px)!important;box-shadow:0 5px 18px rgba(15,23,42,.045)}
.invoice-detail-page pre{white-space:pre-wrap;overflow-wrap:anywhere}
@media(max-width:900px){.invoice-detail-grid{grid-template-columns:1fr}.invoice-actions-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.invoice-detail-page{padding:12px}.invoice-detail-hero{border-radius:14px;padding:20px}.invoice-detail-hero .s-badge{font-size:12px!important}.invoice-actions-grid{grid-template-columns:1fr}.invoice-data-grid{grid-template-columns:1fr!important;gap:5px!important}.invoice-data-grid>div:nth-child(even){margin-bottom:10px}.invoice-related{grid-template-columns:1fr}}
</style>
<div class="s-content invoice-detail-page">

    <!-- HEADER -->
    <div class="invoice-detail-hero">
        <div>
            <span class="invoice-kind">
                {{ $invoice->tipo_doc === '01' ? 'Factura Electrónica' : ($invoice->tipo_doc === '03' ? 'Boleta Electrónica' : ($invoice->tipo_doc === '07' ? 'Nota de Crédito' : ($invoice->tipo_doc === 'NV' ? 'Nota de Venta' : ($invoice->tipo_doc === 'RA' ? 'Comunicación de baja' : 'Nota de Débito')))) }}
            </span>
            <h2>
                <span class="invoice-number">{{ $invoice->serie }}-{{ str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) }}</span>
            </h2>
        </div>
        <span class="s-badge" style="background:{{ $invoice->statusColor() }}; color:#fff; font-size:14px; padding:6px 16px; font-weight:800; border-radius:8px;">
            {{ $invoice->statusLabel() }}
        </span>
    </div>

    <div class="invoice-detail-grid">
        <!-- INFO CARD -->
        <div class="s-card invoice-detail-card">
            <h3 style="margin-bottom:20px; font-weight:800; font-size:16px; display:flex; align-items:center; gap:8px;">
                <i class="las la-info-circle" style="color:var(--s-primary); font-size:20px;"></i> Datos del Comprobante
            </h3>

            <div class="invoice-data-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px 24px; font-size:13px;">
                <div style="color:var(--s-text-3); font-weight:600;">Cliente</div>
                <div style="font-weight:700; color:var(--s-text);">{{ $invoice->cliente_nombre }}</div>

                <div style="color:var(--s-text-3); font-weight:600;">Documento</div>
                <div style="font-weight:700; color:var(--s-text);">{{ $invoice->cliente_tipo_doc === '6' ? 'RUC' : 'DNI' }}: {{ $invoice->cliente_num_doc }}</div>

                @if($invoice->total_gravada > 0)
                <div style="color:var(--s-text-3); font-weight:600;">Total Gravada</div>
                <div style="font-weight:700; color:var(--s-text);">S/ {{ number_format($invoice->total_gravada, 2) }}</div>
                @endif

                @if($invoice->total_exonerada > 0)
                <div style="color:var(--s-text-3); font-weight:600;">Total Exonerada</div>
                <div style="font-weight:700; color:var(--s-text);">S/ {{ number_format($invoice->total_exonerada, 2) }}</div>
                @endif

                @if($invoice->total_inafecta > 0)
                <div style="color:var(--s-text-3); font-weight:600;">Total Inafecta</div>
                <div style="font-weight:700; color:var(--s-text);">S/ {{ number_format($invoice->total_inafecta, 2) }}</div>
                @endif

                <div style="color:var(--s-text-3); font-weight:600;">IGV</div>
                <div style="font-weight:700; color:var(--s-text);">S/ {{ number_format($invoice->total_igv, 2) }}</div>

                <div style="color:var(--s-text-3); font-weight:600; font-size:14px; border-top:1px solid var(--s-border); padding-top:8px;">Total</div>
                <div style="font-weight:900; color:var(--s-primary); font-size:16px; border-top:1px solid var(--s-border); padding-top:8px;">S/ {{ number_format($invoice->total, 2) }}</div>

                <div style="color:var(--s-text-3); font-weight:600;">Moneda</div>
                <div style="font-weight:700; color:var(--s-text);">{{ $invoice->moneda ?? 'PEN' }}</div>

                <div style="color:var(--s-text-3); font-weight:600;">Fecha Emisión</div>
                <div style="font-weight:700; color:var(--s-text);">{{ $invoice->fecha_emision?->format('d/m/Y H:i') }}</div>

                <div style="color:var(--s-text-3); font-weight:600;">Hash</div>
                <div style="font-weight:600; color:var(--s-text-3); font-size:11px; word-break:break-all;">{{ $invoice->hash }}</div>
            </div>
        </div>

        <!-- ACTIONS CARD -->
        <div class="s-card invoice-detail-card">
            <h3 style="margin-bottom:20px; font-weight:800; font-size:16px; display:flex; align-items:center; gap:8px;">
                <i class="las la-download" style="color:var(--s-primary); font-size:20px;"></i> Descargas y Acciones
            </h3>

            <div class="invoice-actions-grid" style="display:flex; flex-direction:column; gap:12px;">
                @if($invoice->tipo_doc !== 'RA')
                <a href="{{ route('seller.invoice.pdf', [$invoice->id, 'a4']) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-file-pdf" style="color:#dc2626; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Previsualizar PDF A4</b><br><small style="color:var(--s-text-3); font-size:11px;">Vista previa e impresión formato carta</small></div>
                </a>
                <a href="{{ route('seller.invoice.pdf', [$invoice->id, 'a4']) }}?download=1" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-download" style="color:#dc2626; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar PDF A4</b><br><small style="color:var(--s-text-3); font-size:11px;">Descarga directa del comprobante en A4</small></div>
                </a>

                <a href="{{ route('seller.invoice.pdf', [$invoice->id, 'a5']) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-file-pdf" style="color:#f59e0b; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Previsualizar PDF A5</b><br><small style="color:var(--s-text-3); font-size:11px;">Vista previa e impresión media carta</small></div>
                </a>
                <a href="{{ route('seller.invoice.pdf', [$invoice->id, 'a5']) }}?download=1" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-download" style="color:#f59e0b; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar PDF A5</b><br><small style="color:var(--s-text-3); font-size:11px;">Descarga directa del comprobante en A5</small></div>
                </a>

                <a href="{{ route('seller.invoice.pdf', [$invoice->id, 'ticket']) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-receipt" style="color:var(--s-info); font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Previsualizar Ticket</b><br><small style="color:var(--s-text-3); font-size:11px;">Vista previa formato ticket 80mm térmica</small></div>
                </a>

                <a href="{{ route('seller.invoice.xml', $invoice->id) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-file-code" style="color:#8b5cf6; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar XML</b><br><small style="color:var(--s-text-3); font-size:11px;">Archivo XML firmado enviado a SUNAT</small></div>
                </a>

                @endif
                <a href="{{ route('seller.invoice.cdr', $invoice->id) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-check-double" style="color:#16a34a; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar CDR</b><br><small style="color:var(--s-text-3); font-size:11px;">Constancia de Recepción SUNAT</small></div>
                </a>

                @if(!$invoice->original_invoice_id && (($invoice->tipo_doc === 'NV' && in_array($invoice->cdr_status, ['generated', 'pending'])) || (in_array($invoice->tipo_doc, ['01','03','07','08','RA']) && $invoice->cdr_status === 'accepted')))
                <button onclick="toggleModal('modal-void')" class="s-btn" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px; background:var(--s-danger-bg); color:var(--s-danger-text); border:1px solid rgba(220,38,38,0.2);">
                    <i class="las la-ban" style="color:var(--s-danger); font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">{{ in_array($invoice->tipo_doc, ['01','03']) ? 'Emitir nota de crédito' : ($invoice->tipo_doc === 'NV' ? 'Anular Nota de Venta' : 'Anular Comprobante') }}</b><br><small style="color:var(--s-danger-text); font-size:11px;">{{ $invoice->tipo_doc === 'NV' ? 'Revierte caja y devuelve stock al inventario' : (in_array($invoice->tipo_doc ?? '', ['07', '08']) ? 'Envía Comunicación de Baja (RA)' : 'Emite Nota de Crédito por anulación') }}</small></div>
                </button>
                @endif

                @if($invoice->tipo_doc === '01' && !$invoice->original_invoice_id && $invoice->cdr_status === 'accepted')
                <button onclick="toggleModal('modal-baja')" class="s-btn s-btn-outline" style="justify-content:flex-start; padding:12px 16px;">Solicitar comunicación de baja (RA)</button>
                @endif
                @if($invoice->tipo_doc === 'RA')
                <p><strong>Ticket SUNAT:</strong> {{ $invoice->ticket ?: 'Aún no recibido' }}<br>Estado de la baja: {{ $invoice->statusLabel() }}</p>
                @endif

                @if(($invoice->original_invoice_id && in_array($invoice->cdr_status, ['generated', 'error', 'pending', 'accepted']) && !$invoice->cancellation_applied_at) || (!$invoice->original_invoice_id && in_array($invoice->cdr_status, ['pending', 'error', 'rejected'])))
                <form method="POST" action="{{ route('seller.invoice.resend', $invoice->id) }}"  style="margin:0;">
                    @csrf
                    <button type="submit" class="s-btn" style="width:100%; justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px; background:var(--s-info-bg); color:var(--s-info-text); border:1px solid rgba(59,130,246,0.2);">
                        <i class="las la-redo" style="color:var(--s-info); font-size:20px;"></i>
                        <div style="text-align:left;"><b style="font-size:14px;">{{ $invoice->tipo_doc === 'RA' ? ($invoice->ticket ? 'Consultar ticket / completar baja' : 'Reintentar comunicación de baja') : 'Reenviar a SUNAT' }}</b><br><small style="color:var(--s-info-text); font-size:11px;">{{ $invoice->tipo_doc === 'RA' && $invoice->ticket ? 'Consultar el resultado sin volver a enviar la baja' : 'Reintentar envío del comprobante electrónico' }}</small></div>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    @if($cancellationNotes->isNotEmpty() || $invoice->original_invoice_id)
    <div class="invoice-related">
        @foreach($cancellationNotes as $note)
        <a class="invoice-related-item" href="{{ route('seller.invoice.detail', $note->id) }}"><i class="las la-file-invoice" style="font-size:20px;color:var(--s-primary)"></i><span>{{ $note->tipo_doc === 'RA' ? 'Comunicación de baja' : 'Nota de crédito' }} {{ $note->serie }}-{{ $note->correlativo }}<small style="display:block;color:var(--s-text-3);font-size:11px;margin-top:3px">{{ $note->statusLabel() }}</small></span><i class="las la-arrow-right" style="margin-left:auto;color:#94a3b8"></i></a>
        @endforeach
        @if($invoice->original_invoice_id)
        <a class="invoice-related-item" href="{{ route('seller.invoice.detail', $invoice->original_invoice_id) }}"><i class="las la-reply" style="font-size:20px;color:var(--s-info)"></i><span>Ver comprobante original afectado<small style="display:block;color:var(--s-text-3);font-size:11px;margin-top:3px">Consultar documento relacionado</small></span><i class="las la-arrow-right" style="margin-left:auto;color:#94a3b8"></i></a>
        @endif
    </div>
    @endif
    @include('seller.partials.credit_note_result')
    <!-- XML PREVIEW -->
    @if($xmlFormatted)
    <div class="s-card" style="margin-top:24px; border:1px solid var(--s-border);">
        <h3 style="margin-bottom:16px; font-weight:800; font-size:16px; display:flex; align-items:center; gap:8px;">
            <i class="las la-code" style="color:var(--s-purple); font-size:20px;"></i> XML Enviado a SUNAT
            <button class="s-btn s-btn-ghost s-btn-xs" onclick="copyXml()" style="margin-left:auto;" title="Copiar XML">
                <i class="las la-copy"></i> Copiar
            </button>
        </h3>
        <pre id="xml-content" style="background:var(--s-bg-light); padding:16px; border-radius:10px; overflow:auto; max-height:400px; font-size:12px; line-height:1.6; color:var(--s-text); border:1px solid var(--s-border);">{{ $xmlFormatted }}</pre>
    </div>
    @endif

    <!-- CDR RESPONSE -->
    @if($cdrData)
    <div class="s-card" style="margin-top:24px; border:1px solid var(--s-border);">
        <h3 style="margin-bottom:16px; font-weight:800; font-size:16px; display:flex; align-items:center; gap:8px;">
            <i class="las la-reply" style="color:var(--s-info); font-size:20px;"></i> Respuesta SUNAT (CDR)
        </h3>
        
        @php
            $cdrCode = null;
            $cdrDesc = null;
            $cdrNotes = [];
            $cdrRef = null;
            $isRawJson = true;

            if (is_array($cdrData)) {
                $cdrCode = $cdrData['code'] ?? null;
                $cdrDesc = $cdrData['description'] ?? $cdrData['desc'] ?? null;
                $cdrNotes = $cdrData['notes'] ?? [];
                $cdrRef = $cdrData['reference'] ?? null;
                $isRawJson = false;
            } elseif (is_string($cdrData)) {
                $decoded = json_decode($cdrData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $cdrCode = $decoded['code'] ?? null;
                    $cdrDesc = $decoded['description'] ?? $decoded['desc'] ?? null;
                    $cdrNotes = $decoded['notes'] ?? [];
                    $cdrRef = $decoded['reference'] ?? null;
                    $isRawJson = false;
                } else {
                    $cdrDesc = $cdrData;
                }
            }
        @endphp

        @if($isRawJson)
            <pre style="background:var(--s-bg-light); padding:16px; border-radius:10px; overflow:auto; max-height:300px; font-size:12px; line-height:1.6; color:var(--s-text); border:1px solid var(--s-border);">{{ is_string($cdrData) ? $cdrData : json_encode($cdrData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @else
            <div style="background:var(--s-bg-light); padding:20px; border-radius:12px; border:1px solid var(--s-border);">
                <div style="display:grid; grid-template-columns:160px 1fr; gap:16px 24px; font-size:13px; align-items:start;">
                    <div style="font-weight:700; color:var(--s-text-3);">Estado CDR</div>
                    <div>
                        @if($cdrCode === '0')
                            <span class="s-badge" style="background:var(--s-success-bg); color:var(--s-success-text); font-weight:800; font-size:11px; padding:4px 10px; border-radius:6px;">
                                Aceptado (Código: {{ $cdrCode }})
                            </span>
                        @else
                            <span class="s-badge" style="background:var(--s-danger-bg); color:var(--s-danger-text); font-weight:800; font-size:11px; padding:4px 10px; border-radius:6px;">
                                Rechazado/Pendiente (Código: {{ $cdrCode }})
                            </span>
                        @endif
                    </div>
                    
                    <div style="font-weight:700; color:var(--s-text-3);">Descripción SUNAT</div>
                    <div style="font-weight:700; color:var(--s-text); font-size:14px;">{{ $cdrDesc ?? 'Sin descripción' }}</div>
                    
                    @if($cdrRef)
                    <div style="font-weight:700; color:var(--s-text-3);">Nº de Referencia / Ticket</div>
                    <div style="font-weight:600; color:var(--s-text-2); font-family:monospace;">{{ $cdrRef }}</div>
                    @endif

                    @if(!empty($cdrNotes))
                    <div style="font-weight:700; color:var(--s-text-3);">Observaciones de SUNAT</div>
                    <div>
                        <div style="background:var(--s-warning-bg); border-left:4px solid var(--s-warning); padding:12px 16px; border-radius:4px 10px 10px 4px; color:var(--s-warning-text); font-weight:600; line-height:1.5;">
                            <ul style="margin:0; padding-left:16px;">
                                @foreach($cdrNotes as $note)
                                <li>{{ $note }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
    @endif

    <!-- ERROR DETAILS -->
    @if($errors)
    <div class="s-card" style="margin-top:24px; border:1px solid rgba(220,38,38,0.2); background:var(--s-danger-bg); padding:20px; border-radius:16px;">
        <h3 style="margin-bottom:16px; font-weight:800; font-size:16px; color:var(--s-danger-text); display:flex; align-items:center; gap:8px;">
            <i class="las la-exclamation-triangle" style="font-size:20px;"></i> Detalle del Error (Conexión / SOAP / Firma)
        </h3>
        
        @php
            $errorList = [];
            $isRawErrorJson = true;
            if (is_array($errors)) {
                $errorList = $errors;
                $isRawErrorJson = false;
            } elseif (is_string($errors)) {
                $decoded = json_decode($errors, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $errorList = $decoded;
                    $isRawErrorJson = false;
                } else {
                    $errorList = [$errors];
                }
            }
        @endphp

        @if($isRawErrorJson)
            <pre style="background:transparent; padding:0; overflow:auto; max-height:300px; font-size:12px; line-height:1.6; color:var(--s-danger-text); border:none;">{{ is_string($errors) ? $errors : json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @else
            <div style="font-size:13px; color:var(--s-danger-text); font-weight:600; line-height:1.6;">
                <ul style="margin:0; padding-left:20px;">
                    @foreach($errorList as $err)
                        @if(is_array($err))
                            <li style="margin-bottom:6px;">
                                <strong>Código:</strong> <span style="font-family:monospace; background:rgba(220,38,38,0.1); padding:2px 6px; border-radius:4px;">{{ $err['code'] ?? 'N/A' }}</span> <br>
                                <strong>Mensaje:</strong> {{ $err['message'] ?? 'Error no especificado' }}
                            </li>
                        @else
                            <li style="margin-bottom:6px;">{{ $err }}</li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    @endif

</div>

<!-- MODAL ANULAR -->
@php
    $isVoidedDoc = in_array($invoice->tipo_doc ?? '', ['07', '08']);
    $isNotaVenta = ($invoice->tipo_doc ?? '') === 'NV';
@endphp
<div id="modal-void" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,25,35,0.6); align-items:center; justify-content:center; z-index:9999; backdrop-filter:blur(4px);">
    <div class="s-card" style="width:100%; max-width:760px; max-height:90vh; overflow:auto; padding:24px; border-radius:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; font-weight:900; font-size:18px; color:var(--s-text);"><i class="las la-ban" style="color:var(--s-danger);"></i> {{ in_array($invoice->tipo_doc,['01','03']) ? 'Emitir nota de crédito' : ($invoice->tipo_doc === 'NV' ? 'Anular Nota de Venta' : 'Anular Comprobante') }}</h3>
            <button class="s-btn s-btn-ghost" onclick="toggleModal('modal-void')" style="font-size:20px; color:var(--s-text-3);">✕</button>
        </div>

        @if($isNotaVenta)
            <div style="background:var(--s-warning-bg); color:var(--s-warning-text); padding:12px; border-radius:10px; margin-bottom:16px; font-size:12px; font-weight:700;">
                <i class="las la-exclamation-triangle"></i> Se anulará esta Nota de Venta, se revertirá el efectivo en caja y se devolverá el stock al inventario. Esta acción es irreversible.
            </div>
        @elseif($isVoidedDoc)
            <div style="background:var(--s-warning-bg); color:var(--s-warning-text); padding:12px; border-radius:10px; margin-bottom:16px; font-size:12px; font-weight:700;">
                <i class="las la-exclamation-triangle"></i> Se enviará una Comunicación de Baja Electrónica (RA) por anulación. Esta acción es irreversible.
            </div>
        @else
            <div style="background:var(--s-warning-bg); color:var(--s-warning-text); padding:12px; border-radius:10px; margin-bottom:16px; font-size:12px; font-weight:700;">
                <i class="las la-exclamation-triangle"></i> Se emitirá una Nota de Crédito Electrónica según el motivo seleccionado. Esta acción es irreversible.
            </div>
        @endif

        <form method="POST" action="{{ route('seller.invoice.void', $invoice->id) }}">
            @csrf
            @if(in_array($invoice->tipo_doc, ['01', '03']))
            <p>Comprobante afectado: <strong>{{ $invoice->serie }}-{{ $invoice->correlativo }}</strong><br>
                Emisor: {{ $invoice->company?->business_name }}<br>
                Importe total: {{ $invoice->moneda }} {{ number_format($invoice->total, 2) }}</p>
            <div class="s-input-group" style="margin-bottom:16px;">
                <label class="s-input-label" for="credit-note-series">Serie de nota de crédito *</label>
                <select id="credit-note-series" name="credit_note_series_id" class="s-input" required>
                    <option value="">Selecciona una serie {{ $invoice->tipo_doc === '03' ? 'BC' : 'FC' }}</option>
                    @foreach($creditNoteSeries as $series)
                    <option value="{{ $series->id }}" @selected(old('credit_note_series_id') == $series->id)>{{ $series->series }}</option>
                    @endforeach
                </select>
                @if($creditNoteSeries->isEmpty())<small>No hay series activas de nota de crédito para este emisor. Configura una serie {{ $invoice->tipo_doc === '03' ? 'BC' : 'FC' }} de cuatro caracteres.</small>@endif
            </div>
            <div class="s-input-group" style="margin-bottom:16px;">
                <label class="s-input-label" for="credit-note-reason">Motivo SUNAT *</label>
                <select id="credit-note-reason" name="note_motivo" class="s-input" required>
                    @foreach(\App\Services\CreditNoteReasons::LABELS as $code=>$label)
                    <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @include('seller.partials.credit_note_adjustments')
            <p style="font-size:12px;">El ajuste se aplica después de la aceptación de SUNAT. Un reintento recupera la nota pendiente con su misma serie y número.</p>
            @endif
            <div class="s-input-group" style="margin-bottom:20px;">
                <label class="s-input-label">Sustento del motivo *</label>
                <textarea class="s-input" name="reason" rows="3" maxlength="250" placeholder="Explica la causa del ajuste seleccionado" required style="resize:vertical;"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="s-btn s-btn-ghost" onclick="toggleModal('modal-void')">Cancelar</button>
                <button type="submit" class="s-btn s-btn-danger" style="gap:6px;">
                    <i class="las la-ban"></i> {{ $isNotaVenta ? 'Anular Nota de Venta' : ($isVoidedDoc ? 'Enviar Comunicación de Baja' : 'Emitir nota de crédito') }}
                </button>
            </div>
        </form>
    </div>
</div>

@include('seller.partials.invoice_baja')

@push('script')
<script>
function toggleModal(id) {
    var m = document.getElementById(id);
    m.style.display = m.style.display === 'flex' ? 'none' : 'flex';
}
function copyXml() {
    var xml = document.getElementById('xml-content').textContent;
    navigator.clipboard.writeText(xml).then(function() {
        alert('XML copiado al portapapeles');
    });
}
</script>
@endpush
@endsection
