<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Electrónico</title>
    <style>
        @page {
            margin: 12px 12px 8px 12px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            color: #000000;
            background-color: #ffffff;
            line-height: 1.3;
            width: 100%;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        
        .divider { border-top: 1.5px dashed #000000; margin: 6px 0; clear: both; }
        .double-divider { border-top: 3px double #000000; margin: 6px 0; clear: both; }

        /* Header */
        .store-logo { max-height: 70px; max-width: 190px; margin: 0 auto 6px auto; display: block; filter: grayscale(100%); }
        .store-title { font-size: 13px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; letter-spacing: 0.5px; }
        .store-subtitle { font-size: 11px; color: #000000; margin-bottom: 2px; }
        
        .doc-box { padding: 4px 0; }
        .doc-title { font-size: 12px; font-weight: bold; text-transform: uppercase; margin: 3px 0; }
        .doc-number { font-size: 14px; font-weight: bold; margin-top: 2px; }

        /* Client Info */
        .info-table { width: 100%; margin: 4px 0; border-collapse: collapse; }
        .info-table td { font-size: 11px; padding: 2px 0; vertical-align: top; color: #000000; }
        .info-table td.lbl { width: 35%; font-weight: bold; }
        .info-table td.val { width: 65%; font-weight: bold; }

        /* Items Table */
        .items-table { width: 100%; margin-top: 6px; border-collapse: collapse; }
        .items-table th { font-size: 11px; font-weight: bold; padding: 4px 0; border-bottom: 1.5px solid #000000; color: #000000; }
        .items-table td { font-size: 11px; padding: 5px 0; vertical-align: top; border-bottom: 1px dashed #000000; color: #000000; }
        
        /* Totals */
        .totals-table { width: 100%; margin-top: 6px; border-collapse: collapse; }
        .totals-table td { font-size: 11px; padding: 3px 0; color: #000000; font-weight: bold; }
        .totals-table tr.total-row td { font-size: 14px; font-weight: 900; padding-top: 6px; border-top: 2px double #000000; }

        .monto-letras { font-size: 10px; font-style: italic; margin-top: 8px; padding-left: 5px; border-left: 2px solid #000000; word-break: break-word; color: #000000; font-weight: bold; }

        /* QR & Hash Block */
        .qr-section { text-align: center; margin-top: 12px; clear: both; }
        .qr-img { width: 110px; height: 110px; margin: 0 auto 6px auto; display: block; }
        .hash-label { font-size: 9px; font-family: monospace; word-break: break-all; margin: 4px 0; background: #ffffff; padding: 3px; border: 1.5px dashed #000000; color: #000000; font-weight: bold; }
        .disclaimer { font-size: 9px; color: #000000; line-height: 1.3; margin-top: 6px; text-align: justify; font-weight: bold; }
        
        .footer-brand { text-align: center; font-size: 10px; color: #000000; margin-top: 14px; border-top: 1.5px dashed #000000; padding-top: 8px; font-weight: bold; }
    </style>
</head>
<body>

    <!-- Store Header -->
    <div class="text-center">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" class="store-logo" alt="Logo">
        @endif
        <div class="store-title">{{ $businessName }}</div>
        @if($tradeName && $tradeName !== $businessName)
            <div class="store-subtitle bold">{{ $tradeName }}</div>
        @endif
        <div class="store-subtitle">
            RUC: {{ $docNumber }}<br>
            @if($address)
                {{ $address }}<br>
            @endif
            @if($seller->tel || $store->phone)
                Telf: {{ $seller->tel ?? $store->phone }}
            @endif
        </div>
    </div>

    <div class="double-divider"></div>

    <!-- Document Info Header -->
    <div class="text-center doc-box">
        <div class="doc-title">{{ $invoice->tipo_doc === '01' ? 'FACTURA ELECTRÓNICA' : ($invoice->tipo_doc === '03' ? 'BOLETA DE VENTA ELECTRÓNICA' : ($invoice->tipo_doc === '07' ? 'NOTA DE CRÉDITO ELECTRÓNICA' : ($invoice->tipo_doc === 'NV' ? 'NOTA DE VENTA' : 'NOTA DE DÉBITO ELECTRÓNICA'))) }}</div>
        <div class="doc-number">{{ $invoice->serie }} - {{ str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="divider"></div>

    <!-- Client Info -->
    @php
        $paymentMethodLabel = 'Efectivo';
        if ($invoice->order) {
            if ($invoice->order->payment_method === 'split') {
                $splits = [];
                if (is_array($invoice->order->payment_details)) {
                    foreach ($invoice->order->payment_details as $method => $amount) {
                        $methodLabel = match($method) {
                            'cash' => 'Efe',
                            'yape' => 'Yap',
                            'plin' => 'Pli',
                            'card', 'pos' => 'Tar',
                            default => ucfirst($method)
                        };
                        $splits[] = $methodLabel . ":S/" . number_format($amount, 2);
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
    <table class="info-table">
        <tr>
            <td class="lbl">Adquirente:</td>
            <td class="val">{{ $invoice->cliente_nombre }}</td>
        </tr>
        <tr>
            <td class="lbl">{{ $invoice->cliente_tipo_doc === '6' ? 'RUC' : ($invoice->cliente_tipo_doc === '1' ? 'DNI' : 'DOC.') }}:</td>
            <td class="val">{{ $invoice->cliente_num_doc }}</td>
        </tr>
        <tr>
            <td class="lbl">F. Emisión:</td>
            <td class="val">{{ $invoice->fecha_emision?->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="lbl">F. Pago:</td>
            <td class="val">{{ $paymentMethodLabel }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Items Details -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 18%;">Cant.</th>
                <th class="text-left" style="width: 52%;">Descripción</th>
                <th class="text-right" style="width: 30%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @if($invoice->isConsumptionSummary())
                <tr><td>1.00 x {{ $invoice->consumption_description ?: 'Consumo' }}</td><td class="align-right">S/ {{ number_format($invoice->total, 2) }}</td></tr>
            @elseif(!empty($itemDetails) && count($itemDetails))
                @foreach($itemDetails as $detail)
                    <tr>
                        <td class="text-left">{{ number_format($detail['quantity'], 0) }} x</td>
                        <td class="text-left">{{ $detail['name'] }}</td>
                        <td class="text-right">S/ {{ number_format($detail['unit_price'] * $detail['quantity'], 2) }}</td>
                    </tr>
                @endforeach
            @elseif($invoice->order && $invoice->order->items->count())
                @foreach($invoice->order->items as $item)
                    <tr>
                        <td class="text-left">{{ number_format($item->quantity, 0) }} x</td>
                        <td class="text-left">{{ $item->product_name }}</td>
                        <td class="text-right">S/ {{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="3" class="text-center" style="padding: 5px;">No hay items.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totals -->
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
                <td class="text-left">Op. Gravada:</td>
                <td class="text-right">S/ {{ number_format($totGravada, 2) }}</td>
            </tr>
        @endif
        @if($totExonerada > 0)
            <tr>
                <td class="text-left">Op. Exonerada:</td>
                <td class="text-right">S/ {{ number_format($totExonerada, 2) }}</td>
            </tr>
        @endif
        @if($totInafecta > 0)
            <tr>
                <td class="text-left">Op. Inafecta:</td>
                <td class="text-right">S/ {{ number_format($totInafecta, 2) }}</td>
            </tr>
        @endif
        @if($totIgv > 0)
            <tr>
                <td class="text-left">IGV (18%):</td>
                <td class="text-right">S/ {{ number_format($totIgv, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td class="text-left">IMPORTE TOTAL:</td>
            <td class="text-right">S/ {{ number_format($totTotal, 2) }}</td>
        </tr>
    </table>

    <div class="monto-letras">
        SON: {{ $montoLetras }}
    </div>

    <div class="divider"></div>

    <!-- QR & Signature -->
    <div class="qr-section">
        @if($qrBase64)
            <img class="qr-img" src="{{ $qrBase64 }}" alt="QR Code">
        @endif
        <div class="bold" style="font-size: 9px; margin-bottom: 2px;">Resumen Hash:</div>
        <div class="hash-label">{{ $invoice->hash ?? '—' }}</div>
        <div class="disclaimer">
            Representación impresa de la {{ $invoice->tipo_doc === '01' ? 'FACTURA ELECTRÓNICA' : ($invoice->tipo_doc === '03' ? 'BOLETA DE VENTA ELECTRÓNICA' : ($invoice->tipo_doc === 'NV' ? 'NOTA DE VENTA' : 'COMPROBANTE ELECTRÓNICO')) }}, para ver el documento visita {{ request()->getHost() }}
        </div>
    </div>

    <div class="footer-brand">
        {{ $store->name ?? 'LIZTO' }} — Facturación Electrónica
    </div>

</body>
</html>
