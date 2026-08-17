<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra {{ $order->order_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; font-size: 13px; line-height: 1.5; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #22c55e; padding-bottom: 15px; }
        .header table { width: 100%; }
        .company-name { font-size: 20px; font-weight: bold; color: #16a34a; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: right; text-transform: uppercase; }
        .doc-number { font-size: 14px; color: #666; text-align: right; margin-top: 5px; }
        .info-section { margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 0; vertical-align: top; }
        .info-block { width: 48%; }
        .info-block-title { font-weight: bold; font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 5px; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table th { background-color: #f3f4f6; color: #4b5563; font-weight: bold; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #d1d5db; }
        .items-table td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 40%; margin-left: 60%; margin-top: 15px; border-collapse: collapse; }
        .totals-table td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        .totals-table tr.total-row td { font-weight: bold; font-size: 14px; border-top: 1.5px solid #16a34a; color: #16a34a; border-bottom: none; }
        .notes { margin-top: 30px; background-color: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb; }
        .notes-title { font-weight: bold; font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="company-name">{{ $company->business_name ?? $seller->business_name ?? $seller->name }}</div>
                    <div>RUC: {{ $company->document_number ?? $seller->document_number }}</div>
                    <div>{{ $company->address ?? $seller->address }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="doc-title">Orden de Compra</div>
                    <div class="doc-number">Número: {{ $order->order_number }}</div>
                    <div>Fecha: {{ $order->order_date->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <table style="width: 100%;">
            <tr>
                <td style="width: 48%; padding-right: 4%;">
                    <div class="info-block-title">Proveedor</div>
                    <div><strong>{{ $order->supplier->name }}</strong></div>
                    @if($order->supplier->document_number)
                        <div>Documento: {{ $order->supplier->document_number }}</div>
                    @endif
                    @if($order->supplier->phone)
                        <div>Teléfono: {{ $order->supplier->phone }}</div>
                    @endif
                    @if($order->supplier->email)
                        <div>Email: {{ $order->supplier->email }}</div>
                    @endif
                    @if($order->supplier->address)
                        <div>Dirección: {{ $order->supplier->address }}</div>
                    @endif
                </td>
                <td style="width: 48%;">
                    <div class="info-block-title">Enviar a / Destino</div>
                    <div><strong>{{ $order->warehouse->name }}</strong></div>
                    @if($order->warehouse->address)
                        <div>Dirección: {{ $order->warehouse->address }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Insumo / Producto</th>
                <th class="text-center">Unidad</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Costo Unitario</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td><strong>{{ $item->item->name }}</strong></td>
                <td class="text-center">{{ $item->item->unit }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">S/ {{ number_format($item->unit_cost, 2) }}</td>
                <td class="text-right">S/ {{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">S/ {{ number_format($order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>IGV (18%):</td>
            <td class="text-right">S/ {{ number_format($order->igv, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td>Total:</td>
            <td class="text-right">S/ {{ number_format($order->total, 2) }}</td>
        </tr>
    </table>

    @if($order->notes)
    <div class="notes">
        <div class="notes-title">Notas / Comentarios</div>
        <div>{{ $order->notes }}</div>
    </div>
    @endif

    <div class="footer">
        Este documento es una orden de compra comercial interna.
        <br>
        Generado automáticamente por el Módulo de Logística.
    </div>

</body>
</html>
