<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Arqueo de Caja #{{ $s->id }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            background-color: #ffffff;
            margin: 0;
            padding: 8px 12px;
            width: 74mm;
        }
        .center { text-align: center; }
        .bold { font-weight: 900; }
        .big { font-size: 14px; }
        .divider { border-top: 2px dashed #000000; margin: 6px 0; clear: both; height: 1px; }
        
        /* Float columns for maximum compatibility with thermal ticket rendering */
        .row {
            clear: both;
            overflow: hidden;
            padding: 3px 0;
            width: 100%;
        }
        .col-left {
            float: left;
            width: 65%;
            text-align: left;
            word-wrap: break-word;
        }
        .col-right {
            float: right;
            width: 35%;
            text-align: right;
            word-wrap: break-word;
        }
        h2 { font-size: 16px; margin: 4px 0; font-weight: 900; text-transform: uppercase; }
        h3 { font-size: 13px; margin: 8px 0 4px; font-weight: 900; text-transform: uppercase; clear: both; }
        .denom { font-size: 12px; padding-left: 8px; }
        .total-row { font-size: 14px; font-weight: 900; border-top: 1.5px solid #000000; padding-top: 4px; margin-top: 4px; clear: both; }
        .diff-row { font-weight: 900; clear: both; }
        .diff-row.positive { color: #000000; }
        .diff-row.negative { color: #000000; }
        .footer { font-size: 11px; text-align: center; margin-top: 16px; clear: both; }
        
        .signatures {
            margin-top: 30px;
            clear: both;
            overflow: hidden;
            width: 100%;
        }
        .sig-box {
            float: left;
            width: 50%;
            text-align: center;
            font-size: 11px;
        }
    </style>
</head>
<body onload="window.print()">
<div class="center">
    <h2>{{ $store->name ?? 'LIZTO DELIVERY' }}</h2>
    <span style="font-size: 11px; font-weight: bold; text-transform: uppercase;">Arqueo de Caja</span>
    <div class="big bold" style="margin-top: 2px;">Sesión #{{ $s->id }}</div>
</div>
<div class="divider"></div>

<div class="row">
    <div class="col-left">Fecha:</div>
    <div class="col-right">{{ $s->created_at->format('d/m/Y H:i') }}</div>
</div>
<div class="row">
    <div class="col-left">Apertura:</div>
    <div class="col-right">{{ $s->opened_at?->format('H:i') }}</div>
</div>
<div class="row">
    <div class="col-left">Cierre:</div>
    <div class="col-right">{{ $s->closed_at?->format('H:i') }}</div>
</div>
<div class="divider"></div>

<h3>Resumen</h3>
<div class="row">
    <div class="col-left">Saldo Inicial</div>
    <div class="col-right">S/ {{ number_format($s->opening_balance, 2) }}</div>
</div>
<div class="row">
    <div class="col-left">+ Ventas</div>
    <div class="col-right">S/ {{ number_format($s->total_sales, 2) }}</div>
</div>
<div class="row">
    <div class="col-left">+ Ingresos</div>
    <div class="col-right">S/ {{ number_format($s->total_cash_in, 2) }}</div>
</div>
<div class="row">
    <div class="col-left">- Gastos</div>
    <div class="col-right">S/ {{ number_format($s->total_expenses, 2) }}</div>
</div>
<div class="row">
    <div class="col-left">- Egresos</div>
    <div class="col-right">S/ {{ number_format($s->total_cash_out, 2) }}</div>
</div>
<div class="total-row row">
    <div class="col-left">Efectivo Esperado</div>
    <div class="col-right">S/ {{ number_format($s->opening_balance + $s->total_sales + $s->total_cash_in - $s->total_expenses - $s->total_cash_out, 2) }}</div>
</div>
<div class="divider"></div>

<h3>Conteo Físico</h3>
@if(count($denominations))
    @foreach($denominations as $denom => $qty)
    <div class="denom row">
        <div class="col-left">{{ $qty }} x S/ {{ number_format((float)$denom, str_contains((string)$denom, '.') ? 2 : 0) }}</div>
        <div class="col-right">S/ {{ number_format($qty * (float)$denom, 2) }}</div>
    </div>
    @endforeach
    <div class="total-row row">
        <div class="col-left">TOTAL CONTADO</div>
        <div class="col-right">S/ {{ number_format($s->closing_balance ?? 0, 2) }}</div>
    </div>
@else
    <div class="row">
        <div class="col-left">Saldo Final</div>
        <div class="col-right">S/ {{ number_format($s->closing_balance ?? 0, 2) }}</div>
    </div>
@endif
<div class="divider"></div>

@php $diffDisplay = $diff ?? 0; @endphp
<div class="diff-row row {{ $diffDisplay > 0 ? 'positive' : ($diffDisplay < 0 ? 'negative' : '') }}">
    <div class="col-left">Diferencia:</div>
    <div class="col-right">S/ {{ number_format($diffDisplay, 2) }}</div>
</div>

@if($notesText)
<div class="divider"></div>
<div style="font-size: 11px; clear: both;"><b>Notas:</b> {{ $notesText }}</div>
@endif

<div class="divider"></div>
<div class="signatures">
    <div class="sig-box">
        ________________<br>Encargado
    </div>
    <div class="sig-box">
        ________________<br>Supervisor
    </div>
</div>
<div class="footer">Generado por Lizto - {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
