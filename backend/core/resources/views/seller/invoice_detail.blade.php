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
<div class="s-content" style="max-width: 1100px; margin:0 auto; padding:20px;">

    <!-- HEADER -->
    <div style="background:linear-gradient(135deg, var(--s-primary) 0%, #1e8a3f 100%); border-radius:16px; padding:24px 32px; color:#fff; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <span style="font-size:11px; text-transform:uppercase; letter-spacing:1.5px; opacity:0.85; font-weight:800;">
                {{ $invoice->tipo_doc === '01' ? 'Factura Electrónica' : ($invoice->tipo_doc === '03' ? 'Boleta Electrónica' : ($invoice->tipo_doc === '07' ? 'Nota de Crédito' : ($invoice->tipo_doc === 'NV' ? 'Nota de Venta' : 'Nota de Débito'))) }}
            </span>
            <h2 style="margin:6px 0; font-size:28px; font-weight:900; letter-spacing:-0.5px;">
                {{ $invoice->serie }}-{{ str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) }}
            </h2>
        </div>
        <span class="s-badge" style="background:{{ $invoice->statusColor() }}; color:#fff; font-size:14px; padding:6px 16px; font-weight:800; border-radius:8px;">
            {{ $invoice->statusLabel() }}
        </span>
    </div>

    <div class="s-grid-2" style="gap:24px; align-items:start;">
        <!-- INFO CARD -->
        <div class="s-card" style="border:1px solid var(--s-border);">
            <h3 style="margin-bottom:20px; font-weight:800; font-size:16px; display:flex; align-items:center; gap:8px;">
                <i class="las la-info-circle" style="color:var(--s-primary); font-size:20px;"></i> Datos del Comprobante
            </h3>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 24px; font-size:13px;">
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
        <div class="s-card" style="border:1px solid var(--s-border);">
            <h3 style="margin-bottom:20px; font-weight:800; font-size:16px; display:flex; align-items:center; gap:8px;">
                <i class="las la-download" style="color:var(--s-primary); font-size:20px;"></i> Descargas y Acciones
            </h3>

            <div style="display:flex; flex-direction:column; gap:12px;">
                <a href="{{ route('seller.invoice.pdf', [$invoice->id, 'a4']) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-file-pdf" style="color:#dc2626; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar PDF A4</b><br><small style="color:var(--s-text-3); font-size:11px;">Formato carta para impresión</small></div>
                </a>

                <a href="{{ route('seller.invoice.pdf', [$invoice->id, 'a5']) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-file-pdf" style="color:#f59e0b; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar PDF A5</b><br><small style="color:var(--s-text-3); font-size:11px;">Media carta</small></div>
                </a>

                <a href="{{ route('seller.invoice.pdf', [$invoice->id, 'ticket']) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-receipt" style="color:var(--s-info); font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar Ticket</b><br><small style="color:var(--s-text-3); font-size:11px;">Formato ticket 80mm térmica</small></div>
                </a>

                <a href="{{ route('seller.invoice.xml', $invoice->id) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-file-code" style="color:#8b5cf6; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar XML</b><br><small style="color:var(--s-text-3); font-size:11px;">Archivo XML firmado enviado a SUNAT</small></div>
                </a>

                <a href="{{ route('seller.invoice.cdr', $invoice->id) }}" class="s-btn s-btn-outline" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px;">
                    <i class="las la-check-double" style="color:#16a34a; font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Descargar CDR</b><br><small style="color:var(--s-text-3); font-size:11px;">Constancia de Recepción SUNAT</small></div>
                </a>

                @if(in_array($invoice->cdr_status, ['accepted', 'pending']))
                <button onclick="toggleModal('modal-void')" class="s-btn" style="justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px; background:var(--s-danger-bg); color:var(--s-danger-text); border:1px solid rgba(220,38,38,0.2);">
                    <i class="las la-ban" style="color:var(--s-danger); font-size:20px;"></i>
                    <div style="text-align:left;"><b style="font-size:14px;">Anular Comprobante</b><br><small style="color:var(--s-danger-text); font-size:11px;">{{ in_array($invoice->tipo_doc ?? '', ['01', '07', '08']) ? 'Envía Comunicación de Baja (RA)' : 'Emite Nota de Crédito por anulación' }}</small></div>
                </button>
                @endif

                @if(in_array($invoice->cdr_status, ['pending', 'error', 'rejected']))
                <form method="POST" action="{{ route('seller.invoice.resend', $invoice->id) }}" onsubmit="return confirm('¿Reenviar este comprobante a SUNAT?');" style="margin:0;">
                    @csrf
                    <button type="submit" class="s-btn" style="width:100%; justify-content:flex-start; gap:10px; border-radius:10px; padding:12px 16px; background:var(--s-info-bg); color:var(--s-info-text); border:1px solid rgba(59,130,246,0.2);">
                        <i class="las la-redo" style="color:var(--s-info); font-size:20px;"></i>
                        <div style="text-align:left;"><b style="font-size:14px;">Reenviar a SUNAT</b><br><small style="color:var(--s-info-text); font-size:11px;">Reintentar envío del comprobante electrónico</small></div>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

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
@php $isVoidedDoc = in_array($invoice->tipo_doc ?? '', ['01', '07', '08']); @endphp
<div id="modal-void" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,25,35,0.6); align-items:center; justify-content:center; z-index:9999; backdrop-filter:blur(4px);">
    <div class="s-card" style="width:100%; max-width:480px; padding:24px; border-radius:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; font-weight:900; font-size:18px; color:var(--s-text);"><i class="las la-ban" style="color:var(--s-danger);"></i> Anular Comprobante</h3>
            <button class="s-btn s-btn-ghost" onclick="toggleModal('modal-void')" style="font-size:20px; color:var(--s-text-3);">✕</button>
        </div>

        @if($isVoidedDoc)
            <div style="background:var(--s-warning-bg); color:var(--s-warning-text); padding:12px; border-radius:10px; margin-bottom:16px; font-size:12px; font-weight:700;">
                <i class="las la-exclamation-triangle"></i> Se enviará una Comunicación de Baja Electrónica (RA) por anulación. Esta acción es irreversible.
            </div>
        @else
            <div style="background:var(--s-warning-bg); color:var(--s-warning-text); padding:12px; border-radius:10px; margin-bottom:16px; font-size:12px; font-weight:700;">
                <i class="las la-exclamation-triangle"></i> Se emitirá una Nota de Crédito Electrónica por anulación. Esta acción es irreversible.
            </div>
        @endif

        <form method="POST" action="{{ route('seller.invoice.void', $invoice->id) }}">
            @csrf
            <div class="s-input-group" style="margin-bottom:20px;">
                <label class="s-input-label">Motivo de Anulación *</label>
                <textarea class="s-input" name="reason" rows="3" placeholder="Ej: Error en los datos del comprobante, datos del cliente incorrectos, etc." required style="resize:vertical;"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="s-btn s-btn-ghost" onclick="toggleModal('modal-void')">Cancelar</button>
                <button type="submit" class="s-btn s-btn-danger" style="gap:6px;">
                    <i class="las la-ban"></i> {{ $isVoidedDoc ? 'Enviar Comunicación de Baja' : 'Anular con Nota de Crédito' }}
                </button>
            </div>
        </form>
    </div>
</div>

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
