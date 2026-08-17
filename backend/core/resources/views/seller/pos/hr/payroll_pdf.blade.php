<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Pago #{{ $payroll->id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.5;
            background-color: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 30px;
            border-radius: 8px;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .company-info {
            width: 60%;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #111;
            margin: 0;
        }
        .company-details {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .doc-title-box {
            width: 40%;
            text-align: right;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            background-color: #333;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            text-transform: uppercase;
        }
        .doc-number {
            font-size: 12px;
            margin-top: 5px;
            font-weight: bold;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            background-color: #f9f9f9;
            border-radius: 6px;
            padding: 15px;
            box-sizing: border-box;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            padding: 6px 12px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
        }
        .info-value {
            color: #111;
            font-weight: 500;
        }
        .table-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #111;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .payroll-table th {
            background-color: #f1f1f1;
            color: #333;
            font-weight: bold;
            text-align: left;
            padding: 10px 12px;
            border: 1px solid #ddd;
            font-size: 11px;
            text-transform: uppercase;
        }
        .payroll-table td {
            padding: 10px 12px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .text-right {
            text-align: right;
        }
        .amount-col {
            width: 120px;
        }
        .summary-box {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .summary-cell {
            display: table-cell;
            vertical-align: top;
        }
        .summary-left {
            width: 60%;
            font-size: 11px;
            color: #666;
        }
        .summary-right {
            width: 40%;
        }
        .total-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #ddd;
            padding: 8px 0;
        }
        .total-row.grand-total {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .total-label {
            display: table-cell;
            text-align: left;
            padding-left: 10px;
            font-size: 12px;
        }
        .total-amount {
            display: table-cell;
            text-align: right;
            padding-right: 10px;
            font-size: 14px;
        }
        .signatures {
            display: table;
            width: 100%;
            margin-top: 60px;
            padding-top: 20px;
        }
        .signature-line {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }
        .signature-space {
            height: 70px;
        }
        .signature-text {
            border-top: 1px solid #666;
            display: inline-block;
            width: 70%;
            padding-top: 8px;
            font-size: 11px;
            color: #555;
        }
        .print-btn-container {
            text-align: right;
            margin-bottom: 15px;
        }
        .print-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .print-btn-container {
                display: none;
            }
            body {
                padding: 0;
            }
            .container {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="print-btn-container">
        <button class="print-btn" onclick="window.print()"><i class="las la-print"></i> Imprimir Boleta</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="header-cell company-info">
                <h1 class="company-name">{{ $store ? $store->name : 'Nuestra Empresa' }}</h1>
                <div class="company-details">
                    RUC: {{ $store && $store->ruc ? $store->ruc : '—' }} <br>
                    Dirección: {{ $store ? $store->address : '—' }} <br>
                    Teléfono: {{ $store ? $store->phone : '—' }}
                </div>
            </div>
            <div class="header-cell doc-title-box">
                <div class="doc-title">Boleta de Pago</div>
                <div class="doc-number">Nº 001 - {{ str_pad($payroll->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell">
                    <span class="info-label">Colaborador:</span><br>
                    <span class="info-value">{{ $payroll->staff->name }}</span>
                </div>
                <div class="info-cell">
                    <span class="info-label">Nº Documento:</span><br>
                    <span class="info-value">{{ $payroll->staff->document_number ?: '—' }}</span>
                </div>
                <div class="info-cell">
                    <span class="info-label">Cargo:</span><br>
                    <span class="info-value" style="text-transform: capitalize;">{{ $payroll->staff->position }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="padding-top: 12px;">
                    <span class="info-label">Periodo Liquidado:</span><br>
                    <span class="info-value">{{ $payroll->period_start->format('d/m/Y') }} al {{ $payroll->period_end->format('d/m/Y') }}</span>
                </div>
                <div class="info-cell" style="padding-top: 12px;">
                    <span class="info-label">Fecha de Ingreso:</span><br>
                    <span class="info-value">{{ $payroll->staff->hire_date ? $payroll->staff->hire_date->format('d/m/Y') : '—' }}</span>
                </div>
                <div class="info-cell" style="padding-top: 12px;">
                    <span class="info-label">Tipo de Sueldo:</span><br>
                    <span class="info-value">
                        @if($payroll->staff->salary_type === 'monthly')
                            Mensual (S/ {{ number_format($payroll->staff->base_salary, 2) }})
                        @elseif($payroll->staff->salary_type === 'daily')
                            Diario (S/ {{ number_format($payroll->staff->base_salary, 2) }} /día)
                        @else
                            Por Hora (S/ {{ number_format($payroll->staff->base_salary, 2) }} /hora)
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="table-title">Detalle de Conceptos</div>
        <table class="payroll-table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="text-right amount-col">Ingresos (S/)</th>
                    <th class="text-right amount-col">Descuentos (S/)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Sueldo Base Ganado (Días/Horas trabajados del periodo)</td>
                    <td class="text-right">S/ {{ number_format($payroll->base_salary_earned, 2) }}</td>
                    <td class="text-right">—</td>
                </tr>
                <tr>
                    <td>Comisiones POS de Ventas Atribuidas</td>
                    <td class="text-right">S/ {{ number_format($payroll->commissions_earned, 2) }}</td>
                    <td class="text-right">—</td>
                </tr>
                @if($payroll->bonuses > 0)
                <tr>
                    <td>Bonificaciones / Incentivos Adicionales</td>
                    <td class="text-right">S/ {{ number_format($payroll->bonuses, 2) }}</td>
                    <td class="text-right">—</td>
                </tr>
                @endif
                @if($payroll->deductions > 0)
                <tr>
                    <td>Descuentos / Adelantos de Sueldo / Sanciones</td>
                    <td class="text-right">—</td>
                    <td class="text-right">S/ {{ number_format($payroll->deductions, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="summary-box">
            <div class="summary-cell summary-left">
                <strong>Notas / Glosa:</strong><br>
                {{ $payroll->notes ?: 'Sin observaciones adicionales para esta boleta de pago.' }}
                <br><br>
                <strong>Detalles del Pago:</strong><br>
                Fecha Pago: {{ $payroll->payment_date ? $payroll->payment_date->format('d/m/Y') : now()->format('d/m/Y') }}<br>
                Método de Pago: {{ strtoupper($payroll->payment_method === 'cash' ? 'Efectivo' : ($payroll->payment_method === 'transfer' ? 'Transferencia Bancaria' : 'Depósito en Cuenta')) }}
            </div>
            
            <div class="summary-cell summary-right">
                @php
                    $totalIngresos = $payroll->base_salary_earned + $payroll->commissions_earned + $payroll->bonuses;
                    $totalEgresos = $payroll->deductions;
                @endphp
                <div class="total-row">
                    <span class="total-label">Total Ingresos:</span>
                    <span class="total-amount">S/ {{ number_format($totalIngresos, 2) }}</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Total Descuentos:</span>
                    <span class="total-amount">S/ {{ number_format($totalEgresos, 2) }}</span>
                </div>
                <div class="total-row grand-total">
                    <span class="total-label">Neto Recibido:</span>
                    <span class="total-amount">S/ {{ number_format($payroll->net_salary, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="signatures">
            <div class="signature-line">
                <div class="signature-space"></div>
                <div class="signature-text">
                    <strong>Firma del Empleador</strong><br>
                    RUC: {{ $store ? $store->ruc : '—' }}
                </div>
            </div>
            <div class="signature-line">
                <div class="signature-space"></div>
                <div class="signature-text">
                    <strong>Firma del Colaborador</strong><br>
                    DNI/RUC: {{ $payroll->staff->document_number ?: '—' }}
                </div>
            </div>
        </div>
    </div>

</body>
</html>
