<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9px; }
        h1 { color: #15803d; font-size: 18px; margin: 0 0 4px; }
        .period { color: #4b5563; margin-bottom: 14px; }
        .summary { width: 100%; margin-bottom: 14px; border-collapse: separate; border-spacing: 8px 0; }
        .summary td { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px; text-align: center; width: 33%; }
        .summary b { color: #15803d; display: block; font-size: 14px; }
        table.sales { width: 100%; border-collapse: collapse; }
        .sales th { background: #15803d; color: #fff; padding: 6px; text-align: left; font-size: 8px; }
        .sales td { border-bottom: 1px solid #e5e7eb; padding: 5px; vertical-align: top; }
        .amount { text-align: right; white-space: nowrap; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>Reporte de ventas generales</h1>
    <div class="period">{{ $store?->name }} · Período: {{ $dateFrom }} al {{ $dateTo }}</div>
    <table class="summary">
        <tr>
            <td><b>S/ {{ number_format($totalSales, 2) }}</b>Total ventas</td>
            <td><b>{{ $totalOrders }}</b>Total pedidos</td>
            <td><b>S/ {{ number_format($totalOrders ? $totalSales / $totalOrders : 0, 2) }}</b>Ticket promedio</td>
        </tr>
    </table>
    <table class="sales">
        <thead><tr><th>Origen</th><th>Pedido</th><th>Cliente</th><th>Tipo</th><th>Artículos</th><th>Pago</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td>{{ $sale->source }}</td><td>{{ $sale->orderNo }}</td><td>{{ $sale->customer }}</td>
                    <td>{{ $sale->type }}</td><td>{{ $sale->items }}</td><td>{{ $sale->paymentMethod }}</td>
                    <td class="amount">S/ {{ number_format($sale->total, 2) }}</td><td>{{ $sale->status }}</td>
                    <td class="muted">{{ $sale->date->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">No hay ventas en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
