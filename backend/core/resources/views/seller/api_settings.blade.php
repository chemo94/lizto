@php
    $store = request()->get('external_store') ?? $store ?? null;
    $webhookUrl = url('api/external/v1');
@endphp

@extends('seller.layouts.app')
@section('page-title', 'Configuración API')
@section('seller-content')

<div class="s-card">
    <div class="s-card-header">
        <h3><i class="las la-key"></i> Token de Acceso API</h3>
        <p style="color:var(--s-muted);font-size:13px;margin:4px 0 0">
            Usa este token para conectar tu tienda con sistemas externos (menús digitales, apps de pedidos, ERPs, etc.).
        </p>
    </div>
    <div class="s-card-body" style="display:grid;gap:20px">

        @if($store->api_token)
        <div>
            <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px">Token actual</label>
            <div style="display:flex;gap:8px;align-items:center">
                <code id="apiToken" style="flex:1;padding:12px 16px;background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:8px;font-size:13px;word-break:break-all;user-select:all">{{ $store->api_token }}</code>
                <button class="s-btn s-btn-ghost" onclick="copyToken()" title="Copiar token" style="flex-shrink:0;padding:10px 14px;border-radius:8px;">
                    <i class="las la-copy"></i>
                </button>
            </div>
        </div>
        @else
        <div class="s-alert s-alert-info">
            <i class="las la-info-circle" style="font-size:18px;flex-shrink:0"></i>
            <span>Aún no has generado un token API. Presiona el botón para crear uno.</span>
        </div>
        @endif

        <div>
            <form method="POST" action="{{ route('seller.api.token.regenerate') }}" onsubmit="return confirm('{{ $store->api_token ? '¿Regenerar el token? El token anterior dejará de funcionar inmediatamente.' : '¿Generar token API?' }}')">
                @csrf
                <button type="submit" class="s-btn {{ $store->api_token ? 's-btn-warning' : 's-btn-primary' }}" style="gap:6px">
                    <i class="las la-sync"></i>
                    {{ $store->api_token ? 'Regenerar Token' : 'Generar Token' }}
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ══ QR PAGOS ══ --}}
<div class="s-card">
    <div class="s-card-header">
        <h3><i class="las la-qrcode"></i> Códigos QR de Pago (Yape / Plin)</h3>
        <p style="color:var(--s-muted);font-size:13px;margin:4px 0 0">
            Pega aquí la cadena de texto del QR de tu billetera digital. El repartidor la verá al confirmar una entrega con pago Yape o Plin.
        </p>
    </div>
    <div class="s-card-body">
        <form method="POST" action="{{ route('seller.qr.save') }}">
            @csrf
            <div style="display:grid;gap:20px">
                <div>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;margin-bottom:8px">
                        <span style="background:#7c3aed;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px">YAPE</span>
                        Cadena QR de Yape
                    </label>
                    <textarea name="yape_qr_string" class="s-input" rows="3"
                        placeholder="Pega aquí la cadena de texto del QR de Yape (ej. 00020101021226580014...)">{{ $store->yape_qr_string }}</textarea>
                    <small style="color:var(--s-muted);font-size:11px;display:block;margin-top:4px">
                        <i class="las la-info-circle"></i> Abre tu app Yape → Cobrar → Compartir → copia la cadena de texto del QR.
                    </small>
                </div>
                <div>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;margin-bottom:8px">
                        <span style="background:#0ea5e9;color:#fff;padding:3px 10px;border-radius:20px;font-size:11px">PLIN</span>
                        Cadena QR de Plin
                    </label>
                    <textarea name="plin_qr_string" class="s-input" rows="3"
                        placeholder="Pega aquí la cadena de texto del QR de Plin">{{ $store->plin_qr_string }}</textarea>
                    <small style="color:var(--s-muted);font-size:11px;display:block;margin-top:4px">
                        <i class="las la-info-circle"></i> Abre tu app Plin → Cobrar → QR estático → copia la cadena de texto.
                    </small>
                </div>
                <div>
                    <button type="submit" class="s-btn s-btn-primary" style="gap:6px">
                        <i class="las la-save"></i> Guardar QR de Pagos
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="s-card">
    <div class="s-card-header">
        <h3><i class="las la-bell"></i> Webhooks para Integración ERP</h3>
        <p style="color:var(--s-muted);font-size:13px;margin:4px 0 0">
            Recibe notificaciones en tiempo real en tu ERP externo cuando ocurran eventos en tu tienda (ej. pedidos creados, cobrados, etc.).
        </p>
    </div>
    <div class="s-card-body" style="display:grid;gap:20px">
        <form method="POST" action="{{ route('seller.api.webhook.save') }}">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px">URL del Webhook (Endpoint ERP)</label>
                <input type="url" name="webhook_url" value="{{ $store->webhook_url }}" class="s-input" placeholder="https://tu-sistema-erp.com/api/webhooks" style="padding: 10px 14px; background: var(--s-surface-2);" required>
            </div>

            @if($store->webhook_secret)
            <div style="margin-bottom: 20px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px">Clave Secreta de Firma (Sign Secret)</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <code id="webhookSecret" style="flex:1;padding:12px 16px;background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:8px;font-size:13px;word-break:break-all;user-select:all">{{ $store->webhook_secret }}</code>
                    <button type="button" class="s-btn s-btn-ghost" onclick="copyWebhookSecret()" title="Copiar firma" style="flex-shrink:0;padding:10px 14px;border-radius:8px;">
                        <i class="las la-copy"></i>
                    </button>
                </div>
                <small style="color: var(--s-muted); font-size: 11px; display: block; margin-top: 4px;">
                    Usa esta clave para validar la firma <code>X-LizToGo-Signature</code> (HMAC SHA-256) en las peticiones recibidas.
                </small>
            </div>
            @endif

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="s-btn s-btn-primary" style="gap:6px">
                    <i class="las la-save"></i>
                    Guardar Configuración
                </button>
                
                @if($store->webhook_url)
                <button type="button" class="s-btn s-btn-outline" onclick="document.getElementById('test-webhook-form').submit()" style="gap:6px; border: 1px solid var(--s-border); padding: 10px 16px; border-radius: var(--s-radius); cursor: pointer; font-size: 12px; font-weight: 700; background: transparent;">
                    <i class="las la-paper-plane"></i>
                    Enviar Webhook de Prueba
                </button>
                @endif
            </div>
        </form>

        @if($store->webhook_url)
        <form id="test-webhook-form" method="POST" action="{{ route('seller.api.webhook.test') }}" style="display: none;">
            @csrf
        </form>
        @endif
    </div><div class="s-card">
    <div class="s-card-header" style="border-bottom:1px solid var(--s-border);padding-bottom:14px">
        <h3><i class="las la-book"></i> Documentación de la API para ERP</h3>
        <p style="color:var(--s-muted);font-size:13px;margin:4px 0 0">
            Guía completa de endpoints y formato de datos para integrar tu sistema de facturación, almacén o ERP.
        </p>
    </div>
    <div class="s-card-body" style="display:grid;gap:20px;font-size:13px;padding-top:20px">

        <div class="s-alert s-alert-info">
            <i class="las la-info-circle" style="font-size:18px;flex-shrink:0"></i>
            <div>
                <strong>Autenticación:</strong> Todas las peticiones deben incluir la cabecera 
                <code>Authorization: Bearer TU_TOKEN_API</code>.
            </div>
        </div>

        <!-- ENDPOINT 1: GET STORE -->
        <div style="border: 1px solid var(--s-border); border-radius: 12px; overflow: hidden; background: var(--s-surface);">
            <div style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: var(--s-surface-2); border-bottom: 1px solid var(--s-border);">
                <span style="font-weight: 800; background: #3b82f6; color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 11px; text-transform: uppercase;">GET</span>
                <code style="font-weight: 700; color: var(--s-text);">/api/external/v1/store</code>
                <span style="color: var(--s-text-3); font-size: 12px; margin-left: auto;">Información de la Tienda y RUC</span>
            </div>
            <div style="padding: 16px; display: grid; gap: 10px;">
                <strong>Respuesta Exitosa (JSON 200):</strong>
                <pre style="margin: 0; padding: 12px; background: var(--s-surface-2); border-radius: 8px; font-size: 11px; overflow-x: auto; color: #059669;">
{
  "success": true,
  "data": {
    "id": 1,
    "name": "LizToGo Restaurante Selva",
    "slug": "liztogo-restaurante-selva",
    "address": "Jr. Tarapoto 123",
    "phone": "999888777",
    "latitude": -6.4912,
    "longitude": -76.3689,
    "is_open": true,
    "store_type": "restaurant",
    "company": {
      "ruc": "20123456789",
      "business_name": "CORPORACION COMERCIAL SELVA S.A.C.",
      "trade_name": "LIZTOGO RESTAURANTE",
      "address": "Jr. Tarapoto 123"
    }
  }
}</pre>
            </div>
        </div>

        <!-- ENDPOINT 2: GET PRODUCTS -->
        <div style="border: 1px solid var(--s-border); border-radius: 12px; overflow: hidden; background: var(--s-surface);">
            <div style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: var(--s-surface-2); border-bottom: 1px solid var(--s-border);">
                <span style="font-weight: 800; background: #3b82f6; color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 11px; text-transform: uppercase;">GET</span>
                <code style="font-weight: 700; color: var(--s-text);">/api/external/v1/products</code>
                <span style="color: var(--s-text-3); font-size: 12px; margin-left: auto;">Productos y Stock</span>
            </div>
            <div style="padding: 16px; display: grid; gap: 10px;">
                <p style="margin:0"><strong>Parámetros URL opcionales:</strong> <code>category_id</code>, <code>updated_since</code> (formato YYYY-MM-DD HH:MM:SS)</p>
                <strong>Respuesta Exitosa (JSON 200):</strong>
                <pre style="margin: 0; padding: 12px; background: var(--s-surface-2); border-radius: 8px; font-size: 11px; overflow-x: auto; color: #059669;">
{
  "success": true,
  "data": [
    {
      "id": 42,
      "name": "Juane de Gallina",
      "description": "Plato típico de la selva con gallina criolla",
      "price": 25.00,
      "discount_price": null,
      "final_price": 25.00,
      "image": "https://liztodelivery.com/storage/assets/images/product/juane.png",
      "category_id": 3,
      "stock_type": "prepared",
      "stock": 15,
      "status": true
    }
  ]
}</pre>
            </div>
        </div>

        <!-- ENDPOINT 3: POST ORDERS -->
        <div style="border: 1px solid var(--s-border); border-radius: 12px; overflow: hidden; background: var(--s-surface);">
            <div style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: var(--s-surface-2); border-bottom: 1px solid var(--s-border);">
                <span style="font-weight: 800; background: #10b981; color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 11px; text-transform: uppercase;">POST</span>
                <code style="font-weight: 700; color: var(--s-text);">/api/external/v1/orders</code>
                <span style="color: var(--s-text-3); font-size: 12px; margin-left: auto;">Crear Pedido Externo / POS</span>
            </div>
            <div style="padding: 16px; display: grid; gap: 12px;">
                <div>
                    <strong>Cuerpo del Request (JSON):</strong>
                    <pre style="margin: 5px 0 0; padding: 12px; background: var(--s-surface-2); border-radius: 8px; font-size: 11px; overflow-x: auto; color: #4b5563;">
{
  "order_type": "rappi", // dine_in, takeaway, delivery, rappi, pedidosya, llama, daz, lizto_delivery
  "customer_name": "Pedro Alva",
  "customer_phone": "999888111",
  "delivery_address": "Jr. Progreso 564",
  "notes": "Sin cebolla por favor",
  "items": [
    {
      "product_id": 42,
      "quantity": 2
    },
    {
      "name": "Inka Cola 1L",
      "quantity": 1,
      "price": 8.00
    }
  ]
}</pre>
                </div>
                <div>
                    <strong>Respuesta Exitosa (JSON 201):</strong>
                    <pre style="margin: 5px 0 0; padding: 12px; background: var(--s-surface-2); border-radius: 8px; font-size: 11px; overflow-x: auto; color: #059669;">
{
  "success": true,
  "message": "Pedido creado correctamente",
  "data": {
    "id": 142,
    "order_no": "EXT-20260619-AB89D",
    "total": 58.00,
    "status": "confirmed"
  }
}</pre>
                </div>
            </div>
        </div>

        <!-- ENDPOINT 4: POST PRODUCTS SYNC -->
        <div style="border: 1px solid var(--s-border); border-radius: 12px; overflow: hidden; background: var(--s-surface);">
            <div style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: var(--s-surface-2); border-bottom: 1px solid var(--s-border);">
                <span style="font-weight: 800; background: #10b981; color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 11px; text-transform: uppercase;">POST</span>
                <code style="font-weight: 700; color: var(--s-text);">/api/external/v1/products/sync</code>
                <span style="color: var(--s-text-3); font-size: 12px; margin-left: auto;">Sincronizar Productos Masivamente</span>
            </div>
            <div style="padding: 16px; display: grid; gap: 12px;">
                <p style="margin:0">Permite registrar o actualizar productos masivamente vinculándolos por <code>external_id</code>.</p>
                <div>
                    <strong>Cuerpo del Request (JSON):</strong>
                    <pre style="margin: 5px 0 0; padding: 12px; background: var(--s-surface-2); border-radius: 8px; font-size: 11px; overflow-x: auto; color: #4b5563;">
{
  "products": [
    {
      "external_id": "erp-prod-100",
      "name": "Hamburguesa Extrema",
      "price": 18.90,
      "category_external_id": "erp-cat-5",
      "description": "Doble carne, tocino y queso",
      "stock_type": "prepared", // prepared, packaged, none
      "status": true
    }
  ]
}</pre>
                </div>
            </div>
        </div>

        <!-- SECCIÓN WEBHOOKS -->
        <div style="margin-top: 10px; border-top: 1.5px solid var(--s-border); padding-top: 20px;">
            <h4 style="margin: 0 0 10px; font-size: 14px; font-weight: 800; color: var(--s-text); display: flex; align-items: center; gap: 8px;">
                <i class="las la-shield-alt" style="color: var(--s-accent-dark); font-size: 20px;"></i> Verificación de Firmas de Webhook (HMAC-SHA256)
            </h4>
            <p style="margin-bottom: 12px;">
                Cada evento enviado a tu ERP incluirá una firma criptográfica en el encabezado <code>X-LizToGo-Signature</code>. 
                Debes calcular el hash HMAC-SHA256 del cuerpo crudo (raw body) de la petición usando tu <strong>Sign Secret</strong> de firma y verificar que coincida para confirmar que la petición proviene de LizToGo.
            </p>
            
            <div style="background: var(--s-surface-2); border: 1px solid var(--s-border); border-radius: 8px; padding: 16px;">
                <h5 style="margin:0 0 8px; font-weight: 700;">Ejemplo: Estructura del Webhook (Pedido Creado - order.created)</h5>
                <pre style="margin:0; font-size: 11px; color:#475569; overflow-x:auto;">
{
  "event": "order.created",
  "timestamp": "2026-06-19T13:10:00Z",
  "store_id": 1,
  "data": {
    "id": 142,
    "order_no": "POS-20260619-F890A",
    "customer_name": "María Rojas",
    "customer_phone": "999000111",
    "total": 45.00,
    "order_type": "dine_in",
    "status": "confirmed",
    "payment_status": "pending",
    "created_at": "2026-06-19T13:10:00Z"
  }
}</pre>
            </div>
        </div>

    </div>
</div>

<script>
function copyToken() {
    var el = document.getElementById('apiToken');
    if (!el) return;
    var range = document.createRange();
    range.selectNode(el);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    document.execCommand('copy');
    window.getSelection().removeAllRanges();
    var btn = event.currentTarget;
    btn.innerHTML = '<i class="las la-check"></i>';
    setTimeout(function() { btn.innerHTML = '<i class="las la-copy"></i>'; }, 2000);
}

function copyWebhookSecret() {
    var el = document.getElementById('webhookSecret');
    if (!el) return;
    var range = document.createRange();
    range.selectNode(el);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    document.execCommand('copy');
    window.getSelection().removeAllRanges();
    var btn = event.currentTarget;
    btn.innerHTML = '<i class="las la-check"></i>';
    setTimeout(function() { btn.innerHTML = '<i class="las la-copy"></i>'; }, 2000);
}
</script>

@endsection
