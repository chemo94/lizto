<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Reclamación {{ $complaint->ticket_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .title-area {
            text-align: center;
        }
        .title-area h2 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }
        .title-area p {
            margin: 3px 0 0;
            font-size: 10px;
            color: #666;
        }
        .ticket-box {
            border: 2px solid #000;
            padding: 8px;
            text-align: center;
            background-color: #f3f4f6;
        }
        .ticket-box strong {
            display: block;
            font-size: 12px;
        }
        .section-title {
            background-color: #e5e7eb;
            font-weight: bold;
            padding: 4px 8px;
            margin-top: 15px;
            margin-bottom: 8px;
            border-left: 3px solid #000;
            font-size: 11px;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table td {
            padding: 5px;
            vertical-align: top;
            border: 1px solid #ddd;
        }
        .data-table td.label {
            font-weight: bold;
            width: 25%;
            background-color: #fafafa;
        }
        .text-box {
            border: 1px solid #ddd;
            padding: 8px;
            background-color: #fafafa;
            min-height: 50px;
            white-space: pre-wrap;
        }
        .footer-note {
            margin-top: 30px;
            font-size: 9px;
            color: #666;
            text-align: justify;
            line-height: 1.3;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }
        .signature-area {
            margin-top: 40px;
            width: 100%;
        }
        .signature-area td {
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        .signature-line {
            width: 80%;
            border-top: 1px solid #000;
            margin: 0 auto 5px;
        }
    </style>
</head>
<body>

    <!-- CABECERA -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: middle;">
                <h3 style="margin: 0 0 4px; font-size: 14px; color: #000;">{{ gs('site_name') }}</h3>
                <p style="margin: 0; font-size: 10px; color: #555;">Plataforma Digital de Delivery y Taxi</p>
                <p style="margin: 2px 0 0; font-size: 9px; color: #777;">RUC: 10770468341 (JSoft)</p>
                <p style="margin: 2px 0 0; font-size: 9px; color: #777;">Establecimiento Virtual: {{ request()->getHost() }}</p>
            </td>
            <td style="width: 40%; vertical-align: middle;">
                <div class="ticket-box">
                    <p style="margin: 0 0 3px; font-size: 9px; font-weight: bold; color: #555;">LIBRO DE RECLAMACIONES</p>
                    <strong>HOJA DE RECLAMACIÓN</strong>
                    <span style="font-size: 13px; font-weight: bold; color: #d32f2f;">{{ $complaint->ticket_number }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td><strong>Fecha de Registro:</strong> {{ $complaint->created_at->format('d/m/Y') }}</td>
            <td style="text-align: right;"><strong>Hora:</strong> {{ $complaint->created_at->format('h:i A') }}</td>
        </tr>
    </table>

    <!-- 1. IDENTIFICACIÓN DEL CONSUMIDOR -->
    <div class="section-title">1. Identificación del Consumidor Reclamante</div>
    <table class="data-table">
        <tr>
            <td class="label">Nombres y Apellidos</td>
            <td>{{ $complaint->full_name }}</td>
        </tr>
        <tr>
            <td class="label">Documento de Identidad</td>
            <td>{{ $complaint->document_type }} - {{ $complaint->document_number }}</td>
        </tr>
        <tr>
            <td class="label">Domicilio</td>
            <td>{{ $complaint->address }}</td>
        </tr>
        <tr>
            <td class="label">Teléfono / Celular</td>
            <td>{{ $complaint->phone }}</td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td>{{ $complaint->email }}</td>
        </tr>
        @if($complaint->is_minor)
        <tr>
            <td class="label">Padre / Madre / Apoderado</td>
            <td>
                <strong>Nombre:</strong> {{ $complaint->guardian_name }}<br>
                <strong>Doc. Identidad:</strong> {{ $complaint->guardian_document_type }} - {{ $complaint->guardian_document_number }}
            </td>
        </tr>
        @endif
    </table>

    <!-- 2. DETALLE DEL BIEN CONTRATADO -->
    <div class="section-title">2. Identificación del Bien Contratado</div>
    <table class="data-table">
        <tr>
            <td class="label">Tipo de Bien</td>
            <td>{{ $complaint->item_type_name }}</td>
        </tr>
        <tr>
            <td class="label">Monto Reclamado</td>
            <td>S/. {{ number_format($complaint->amount_claimed, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Descripción del Bien</td>
            <td>{{ $complaint->item_description }}</td>
        </tr>
    </table>

    <!-- 3. DETALLE DE LA RECLAMACIÓN -->
    <div class="section-title">3. Detalle de la Reclamación y Pedido del Consumidor</div>
    <table class="data-table">
        <tr>
            <td class="label">Tipo de Solicitud</td>
            <td><strong>{{ $complaint->type_name }}</strong></td>
        </tr>
        <tr>
            <td class="label">Detalle de Disconformidad</td>
            <td><div class="text-box">{{ $complaint->detail }}</div></td>
        </tr>
        <tr>
            <td class="label">Pedido Concreto (Pretensión)</td>
            <td><div class="text-box">{{ $complaint->request }}</div></td>
        </tr>
    </table>

    <!-- 4. RESPUESTA DEL PROVEEDOR -->
    <div class="section-title">4. Acciones Adoptadas por el Proveedor</div>
    <table class="data-table">
        <tr>
            <td class="label">Estado de Atención</td>
            <td><strong>{{ $complaint->status_name }}</strong></td>
        </tr>
        <tr>
            <td class="label">Respuesta del Proveedor</td>
            <td>
                <div class="text-box">@if($complaint->provider_actions) {{ $complaint->provider_actions }} @else (Pendiente de respuesta) @endif</div>
            </td>
        </tr>
        @if($complaint->responded_at)
        <tr>
            <td class="label">Fecha de Respuesta</td>
            <td>{{ $complaint->responded_at->format('d/m/Y h:i A') }}</td>
        </tr>
        @endif
    </table>

    <table class="signature-area">
        <tr>
            <td>
                <div class="signature-line"></div>
                <span style="font-size: 10px; color: #555;">Firma del Consumidor</span>
            </td>
            <td>
                <div class="signature-line"></div>
                <span style="font-size: 10px; color: #555;">Firma del Proveedor (Digital)</span>
            </td>
        </tr>
    </table>

    <!-- NOTA PIE DE PÁGINA -->
    <div class="footer-note">
        * RECLAMO: Disconformidad relacionada a los productos o servicios.<br>
        * QUEJA: Disconformidad no relacionada a los productos o servicios; malestar o descontento respecto a la atención al público.<br>
        * De conformidad con lo establecido en el Código de Protección y Defensa del Consumidor, el proveedor cuenta con un plazo de quince (15) días hábiles improrrogables para responder el reclamo o queja, contados a partir del día siguiente de su registro.
    </div>

</body>
</html>
