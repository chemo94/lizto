<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante Electrónico</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; padding: 25px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        
        /* Layout Structure using tables for perfect DomPDF rendering */
        .header-table td { vertical-align: top; }
        .logo-col { width: 55%; padding-right: 15px; }
        .ruc-col { width: 45%; }
        
        .logo-img { max-height: 75px; max-width: 250px; margin-bottom: 8px; }
        .store-name { font-size: 14px; font-weight: bold; color: #111; margin-bottom: 4px; }
        .store-info { font-size: 9px; color: #555; line-height: 1.3; }
        
        /* RUC Box Style */
        .ruc-box { border: 2.5px solid #000; text-align: center; padding: 12px; border-radius: 4px; background-color: #fff; }
        .ruc-number { font-size: 14px; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 5px; }
        .doc-title { font-size: 11px; font-weight: bold; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 5px; color: #000; }
        .doc-number { font-size: 16px; font-weight: bold; letter-spacing: 1px; }

        /* Block Section Headers */
        .section-header { background-color: #e2e8f0; color: #1e293b; font-weight: bold; font-size: 10px; padding: 5px 8px; border-radius: 2px; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px; }
        
        /* Data Tables */
        .data-table { width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 15px; }
        .data-table td { padding: 5px 8px; font-size: 9.5px; vertical-align: middle; border: 1px solid #e2e8f0; }
        .data-table td.label { font-weight: bold; width: 18%; background-color: #f8fafc; color: #334155; text-transform: uppercase; font-size: 8.5px; }
        .data-table td.value { width: 32%; color: #0f172a; }

        /* Details Table */
        .details-table { width: 100%; margin-bottom: 15px; border: 1px solid #cbd5e1; }
        .details-table th { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; font-size: 9px; padding: 6px 4px; border: 1px solid #334155; text-transform: uppercase; }
        .details-table td { padding: 6px 8px; font-size: 9.5px; border: 1px solid #e2e8f0; vertical-align: middle; }
        .details-table tr:nth-child(even) td { background-color: #f8fafc; }
        
        .align-center { text-align: center; }
        .align-right { text-align: right; }
        .align-left { text-align: left; }

        /* Summary & Totals */
        .totals-table { width: 100%; margin-top: 5px; }
        .totals-table td { padding: 4px 8px; font-size: 10px; border-bottom: 1px solid #e2e8f0; }
        .totals-table tr.total-row td { font-size: 12px; font-weight: bold; background-color: #1e293b; color: #ffffff; border-bottom: none; }
        
        .son-letras { font-style: italic; font-size: 10px; font-weight: bold; color: #1e293b; padding: 6px 0; border-left: 3px solid #1e293b; padding-left: 8px; margin-bottom: 15px; }

        /* Footer Hash & QR Box */
        .signature-box { border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px; margin-top: 20px; background-color: #f8fafc; }
        .signature-table td { vertical-align: middle; }
        .qr-col { width: 18%; text-align: center; padding-right: 15px; }
        .qr-img { width: 85px; height: 85px; display: block; margin: 0 auto 4px auto; }
        .qr-legend { font-size: 7.5px; color: #64748b; line-height: 1.1; }
        
        .digest-col { width: 82%; font-size: 9px; color: #334155; line-height: 1.4; }
        .digest-title { font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .digest-hash { font-family: 'Courier New', Courier, monospace; font-size: 9.5px; font-weight: bold; background-color: #f1f5f9; padding: 3px 6px; border-radius: 2px; border: 1px dashed #cbd5e1; display: inline-block; margin-bottom: 6px; word-break: break-all; }
        .digest-text { font-size: 8.5px; color: #64748b; }

        .footer-brand { text-align: center; font-size: 8.5px; color: #94a3b8; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 8px; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    @include('seller.partials.credit_note_result')
    @if($invoice->original_invoice_id)
    @php $affected = \App\Models\SunatInvoice::find($invoice->original_invoice_id); @endphp
    <p>Comprobante afectado: {{ $affected?->serie }}-{{ $affected?->correlativo }} ({{ $invoice->note_affected_type === '03' ? 'Boleta' : 'Factura' }})<br>
    Motivo {{ $invoice->note_motivo }}: {{ $invoice->note_description }}</p>
    @endif

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="logo-col">
                @if($logoBase64)
                    <img class="logo-img" src="{{ $logoBase64 }}" alt="Logo">
                @endif
                <div class="store-name">{{ $businessName }}</div>
                <div class="store-info">
                    @if($tradeName && $tradeName !== $businessName)
                        <strong>{{ $tradeName }}</strong><br>
                    @endif
                    @if($address)
                        {{ $address }}<br>
                    @endif
                    @if($seller->tel || $store->phone)
                        Telf: {{ $seller->tel ?? $store->phone }}
                    @endif
                    @if($seller->email || $store->email)
                        | Email: {{ $seller->email ?? $store->email }}
                    @endif
                </div>
            </td>
            <td class="ruc-col">
                <div class="ruc-box">
                    <div class="ruc-number">RUC: {{ $docNumber }}</div>
                    <div class="doc-title">{{ $invoice->tipo_doc === '01' ? 'FACTURA ELECTRÓNICA' : ($invoice->tipo_doc === '03' ? 'BOLETA DE VENTA ELECTRÓNICA' : ($invoice->tipo_doc === '07' ? 'NOTA DE CRÉDITO ELECTRÓNICA' : ($invoice->tipo_doc === 'NV' ? 'NOTA DE VENTA' : 'NOTA DE DÉBITO ELECTRÓNICA'))) }}</div>
                    <div class="doc-number">{{ $invoice->serie }} - {{ str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Document Info Block -->
    <div class="section-header">Datos del Comprobante</div>
    <table class="data-table">
        @php
            $paymentMethodLabel = 'Efectivo';
            if ($invoice->order) {
                if ($invoice->order->payment_method === 'split') {
                    $splits = [];
                    if (is_array($invoice->order->payment_details)) {
                        foreach ($invoice->order->payment_details as $method => $amount) {
                            $methodLabel = match($method) {
                                'cash' => 'Efectivo',
                                'yape' => 'Yape',
                                'plin' => 'Plin',
                                'card', 'pos' => 'Tarjeta',
                                default => ucfirst($method)
                            };
                            $splits[] = $methodLabel . ": S/ " . number_format($amount, 2);
                        }
                        $paymentMethodLabel = implode(', ', $splits);
                    } else {
                        $paymentMethodLabel = 'Múltiple';
                    }
                } else {
                    $paymentMethodLabel = match($invoice->order->payment_method) {
                        'cash' => 'Efectivo',
                        'yape' => 'Yape',
                        'plin' => 'Plin',
                        'card', 'pos', 'stripe' => 'Tarjeta',
                        default => ucfirst($invoice->order->payment_method ?? 'Efectivo')
                    };
                }
            }
        @endphp
        <tr>
            <td class="label">Señor(es):</td>
            <td class="value" colspan="3"><strong>{{ $invoice->cliente_nombre }}</strong></td>
        </tr>
        <tr>
            <td class="label">{{ $invoice->cliente_tipo_doc === '6' ? 'RUC' : ($invoice->cliente_tipo_doc === '1' ? 'DNI' : 'DOC.') }}:</td>
            <td class="value">{{ $invoice->cliente_num_doc }}</td>
            <td class="label">Fecha Emisión:</td>
            <td class="value">{{ $invoice->fecha_emision?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Dirección:</td>
            <td class="value">{{ $customerAddress ?: '—' }}</td>
            <td class="label">Moneda:</td>
            <td class="value">{{ ($invoice->moneda ?? 'PEN') === 'PEN' ? 'SOLES (PEN)' : (($invoice->moneda ?? 'PEN') === 'USD' ? 'DÓLARES (USD)' : ($invoice->moneda ?? 'PEN')) }}</td>
        </tr>
        <tr>
            <td class="label">Fecha Vcto.:</td>
            <td class="value">{{ $invoice->fecha_emision?->format('d/m/Y') }}</td>
            <td class="label">Forma Pago:</td>
            <td class="value">{{ $paymentMethodLabel }}</td>
        </tr>
    </table>

    <!-- Items Detail Table -->
    <div class="section-header">Detalle</div>
    <table class="details-table">
        <thead>
            <tr>
                <th style="width: 8%;">Cant.</th>
                <th style="width: 8%;">Und.</th>
                <th style="width: 10%;">Código</th>
                <th style="width: 44%;">Descripción</th>
                <th style="width: 10%;">Afect.</th>
                <th style="width: 10%;">V. Unit.</th>
                <th style="width: 5%;">Dto.</th>
                <th style="width: 10%;">Valor Venta</th>
            </tr>
        </thead>
        <tbody>
            @if($invoice->isConsumptionSummary() && !$invoice->original_invoice_id)
                @php
                    $isExonerado = ($invoice->total_exonerada > 0) || (($invoice->total_gravada ?? 0) == 0 && ($invoice->total_inafecta ?? 0) == 0 && ($invoice->total_igv ?? 0) == 0);
                    $isInafecto  = ($invoice->total_inafecta > 0);
                    $isGravado   = !$isExonerado && !$isInafecto;
                    
                    $afectLabel = $isExonerado ? 'Exonerado' : ($isInafecto ? 'Inafecto' : 'Gravado');
                    $vUnit  = $isGravado ? round($invoice->total / 1.18, 2) : $invoice->total;
                    $vVenta = $vUnit;
                @endphp
                <tr>
                    <td class="align-center">1.00</td>
                    <td class="align-center">NIU</td>
                    <td class="align-center">—</td>
                    <td class="align-left">{{ $invoice->consumption_description ?: 'Consumo' }}</td>
                    <td class="align-center">{{ $afectLabel }}</td>
                    <td class="align-right">{{ number_format($vUnit, 2) }}</td>
                    <td class="align-center">—</td>
                    <td class="align-right">{{ number_format($vVenta, 2) }}</td>
                </tr>
            @elseif(!empty($itemDetails) && count($itemDetails))
                @foreach($itemDetails as $detail)
                    @php
                        $taxType = $detail['tax_type'];
                        $afectLabel = $taxType === 'exonerado' ? 'Exonerado' : ($taxType === 'inafecto' ? 'Inafecto' : 'Gravado');
                        
                        if (isset($detail['xml_unit_value'])) {
                            $vUnit = $detail['xml_unit_value'];
                            $vVenta = $detail['xml_line_value'];
                        } elseif ($taxType === 'exonerado' || $taxType === 'inafecto') {
                            $vUnit = $detail['unit_price'];
                            $vVenta = $detail['unit_price'] * $detail['quantity'];
                        } else {
                            $vUnit = $detail['unit_price'] / 1.18;
                            $vVenta = $vUnit * $detail['quantity'];
                        }
                    @endphp
                    <tr>
                        <td class="align-center">{{ number_format($detail['quantity'], 2) }}</td>
                        <td class="align-center">{{ $detail['unit_code'] }}</td>
                        <td class="align-center">{{ $detail['sunat_code'] ?? '—' }}</td>
                        <td class="align-left">{{ $detail['name'] }}</td>
                        <td class="align-center">{{ $afectLabel }}</td>
                        <td class="align-right">{{ number_format($vUnit, 2) }}</td>
                        <td class="align-center">—</td>
                        <td class="align-right">{{ number_format($vVenta, 2) }}</td>
                    </tr>
                @endforeach
            @elseif($invoice->order && $invoice->order->items->count())
                @foreach($invoice->order->items as $item)
                    @php
                        $product = $item->product;
                        $taxType = $item->tax_type ?? ($product?->tax_type ?? 'gravado');
                        $afectLabel = $taxType === 'exonerado' ? 'Exonerado' : ($taxType === 'inafecto' ? 'Inafecto' : 'Gravado');
                        
                        if (isset($detail['xml_unit_value'])) {
                            $vUnit = $detail['xml_unit_value'];
                            $vVenta = $detail['xml_line_value'];
                        } elseif ($taxType === 'exonerado' || $taxType === 'inafecto') {
                            $vUnit = $item->unit_price;
                            $vVenta = $item->unit_price * $item->quantity;
                        } else {
                            $vUnit = $item->unit_price / 1.18;
                            $vVenta = $vUnit * $item->quantity;
                        }
                    @endphp
                    <tr>
                        <td class="align-center">{{ number_format($item->quantity, 2) }}</td>
                        <td class="align-center">NIU</td>
                        <td class="align-center">—</td>
                        <td class="align-left">{{ $item->product_name }}</td>
                        <td class="align-center">{{ $afectLabel }}</td>
                        <td class="align-right">{{ number_format($vUnit, 2) }}</td>
                        <td class="align-center">—</td>
                        <td class="align-right">{{ number_format($vVenta, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" class="align-center" style="color: #94a3b8; padding: 15px;">No hay items registrados para este comprobante.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Son letras block & Totals layout -->
    <table style="width: 100%; margin-top: 5px;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="son-letras">
                    SON: {{ $montoLetras }}
                </div>
            </td>
            <td style="width: 40%; vertical-align: top;">
                @php
                    $totGravada = (double)($invoice->total_gravada ?? 0);
                    $totExonerada = (double)($invoice->total_exonerada ?? 0);
                    $totInafecta = (double)($invoice->total_inafecta ?? 0);
                    $totIgv = (double)($invoice->total_igv ?? 0);
                    $totTotal = (double)($invoice->total ?? 0);
                    
                    // Fallback para comprobantes antiguos sin columnas de desglose
                    if ($totGravada == 0 && $totExonerada == 0 && $totInafecta == 0 && $totTotal > 0) {
                        if ($totIgv > 0) {
                            $totGravada = round($totTotal - $totIgv, 2);
                        } else {
                            $totExonerada = $totTotal;
                        }
                    }
                @endphp
                <table class="totals-table">
                    @if($totGravada > 0)
                        <tr>
                            <td class="align-left">Op. Gravada</td>
                            <td class="align-right">{{ $invoice->moneda === 'PEN' ? 'S/' : $invoice->moneda }} {{ number_format($totGravada, 2) }}</td>
                        </tr>
                    @endif
                    @if($totExonerada > 0)
                        <tr>
                            <td class="align-left">Op. Exonerada</td>
                            <td class="align-right">{{ $invoice->moneda === 'PEN' ? 'S/' : $invoice->moneda }} {{ number_format($totExonerada, 2) }}</td>
                        </tr>
                    @endif
                    @if($totInafecta > 0)
                        <tr>
                            <td class="align-left">Op. Inafecta</td>
                            <td class="align-right">{{ $invoice->moneda === 'PEN' ? 'S/' : $invoice->moneda }} {{ number_format($totInafecta, 2) }}</td>
                        </tr>
                    @endif
                    @if($totIgv > 0)
                        <tr>
                            <td class="align-left">IGV (18%)</td>
                            <td class="align-right">{{ $invoice->moneda === 'PEN' ? 'S/' : $invoice->moneda }} {{ number_format($totIgv, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td class="align-left" style="border-top-left-radius: 4px; border-bottom-left-radius: 4px;">IMPORTE TOTAL</td>
                        <td class="align-right" style="border-top-right-radius: 4px; border-bottom-right-radius: 4px;">{{ $invoice->moneda === 'PEN' ? 'S/' : $invoice->moneda }} {{ number_format($totTotal, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Signature and QR Section -->
    <div class="signature-box">
        <table class="signature-table">
            <tr>
                <td class="qr-col">
                    @if($qrBase64)
                        <img class="qr-img" src="{{ $qrBase64 }}" alt="QR Code">
                    @endif
                    <div class="qr-legend">Consultar en SUNAT</div>
                </td>
                <td class="digest-col">
                    <div class="digest-title">RESUMEN DE FIRMA ELECTRÓNICA (DigestValue):</div>
                    <div class="digest-hash">{{ $invoice->hash ?? '—' }}</div>
                    <div class="digest-text">
                        Representación impresa de la {{ $invoice->tipo_doc === '01' ? 'FACTURA ELECTRÓNICA' : ($invoice->tipo_doc === '03' ? 'BOLETA DE VENTA ELECTRÓNICA' : ($invoice->tipo_doc === 'NV' ? 'NOTA DE VENTA' : 'COMPROBANTE ELECTRÓNICO')) }}, para ver el documento visita {{ request()->getHost() }}/consultacpe
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer Brand -->
    <div class="footer-brand">
        {{ $store->name ?? 'Lizto' }} — Facturación Electrónica — {{ request()->getHost() }}
    </div>

</body>
</html>
