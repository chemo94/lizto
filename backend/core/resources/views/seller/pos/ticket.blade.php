<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pre-Cuenta #{{ $order->order_no }}</title>
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
            width: 74mm; /* Adjusted to fit 80mm thermal paper with printable area safety margins */
        }
        .text-center {
            text-align: center;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 8px;
        }
        .store-logo {
            max-height: 70px;
            max-width: 180px;
            display: inline-block;
            filter: grayscale(100%);
        }
        h2 {
            text-align: center;
            font-size: 16px;
            margin: 0 0 4px;
            font-weight: 900;
            text-transform: uppercase;
        }
        h3 {
            text-align: center;
            font-size: 13px;
            margin: 0 0 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .divider {
            border-top: 2px dashed #000000;
            margin: 8px 0;
            height: 1px;
            clear: both;
        }
        .double-divider {
            border-top: 3px double #000000;
            margin: 8px 0;
            height: 3px;
            clear: both;
        }
        /* Float columns for maximum compatibility with thermal ticket rendering */
        .row {
            clear: both;
            overflow: hidden;
            padding: 3px 0;
            width: 100%;
        }
        .col-left {
            float: left;
            width: 70%;
            text-align: left;
            word-wrap: break-word;
        }
        .col-right {
            float: right;
            width: 30%;
            text-align: right;
            word-wrap: break-word;
        }
        .total {
            font-size: 18px;
            font-weight: 900;
            text-align: right;
            margin: 10px 0;
            clear: both;
        }
        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 16px;
            clear: both;
        }
    </style>
</head>
<body onload="window.print()">

    @if(!empty($logoBase64))
    <div class="logo-container">
        <img src="{{ $logoBase64 }}" class="store-logo" alt="Logo">
    </div>
    @endif

    <h2>{{ $order->store?->name ?? 'Lizto Delivery' }}</h2>
    <h3>Pre-Cuenta</h3>
    
    <div class="double-divider"></div>
    
    <div class="row">
        <div class="col-left">Pedido:</div>
        <div class="col-right">#{{ $order->order_no }}</div>
    </div>
    <div class="row">
        <div class="col-left">Mesa:</div>
        <div class="col-right">{{ $order->table?->name ?: '—' }}</div>
    </div>
    <div class="row">
        <div class="col-left">Cliente:</div>
        <div class="col-right">{{ $order->customer_name ?: '—' }}</div>
    </div>
    <div class="row">
        <div class="col-left">Fecha:</div>
        <div class="col-right">{{ $order->created_at->format('d/m/Y H:i') }}</div>
    </div>
    
    <div class="divider"></div>
    
    <!-- Items list header -->
    <div class="row" style="font-size: 11px;">
        <div class="col-left">CANT. DESCRIPCIÓN</div>
        <div class="col-right">TOTAL</div>
    </div>
    
    <div class="divider"></div>
    
    @foreach($order->items as $item)
    <div class="row">
        <div class="col-left">{{ $item->quantity }} x {{ $item->product_name }}</div>
        <div class="col-right">S/ {{ number_format($item->total_price, 2) }}</div>
    </div>
    @endforeach
    
    <div class="divider"></div>
    
    <div class="row">
        <div class="col-left">Subtotal:</div>
        <div class="col-right">S/ {{ number_format($order->subtotal, 2) }}</div>
    </div>
    @if($order->delivery_fee > 0)
    <div class="row">
        <div class="col-left">Delivery:</div>
        <div class="col-right">S/ {{ number_format($order->delivery_fee, 2) }}</div>
    </div>
    @endif
    @if($order->discount > 0)
    <div class="row">
        <div class="col-left">Descuento:</div>
        <div class="col-right">-S/ {{ number_format($order->discount, 2) }}</div>
    </div>
    @endif
    
    <div class="divider"></div>
    
    @if($order->kitchen_notes)
    <div class="row" style="font-size: 12px; font-style: italic; word-break: break-word; margin: 4px 0;">
        <div class="col-left" style="width:100%;">
            <strong>Notas:</strong> {{ $order->kitchen_notes }}
        </div>
    </div>
    <div class="divider"></div>
    @endif
    
    <div class="total">TOTAL: S/ {{ number_format($order->total, 2) }}</div>
    
    <div class="footer">
        *** Pre-Cuenta - No es comprobante fiscal ***<br>
        {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>
