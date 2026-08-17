@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-motorcycle"></i></span> Delivery Apps
@endsection

@section('seller-content')
<div class="s-content">
@php
$platforms = [
    ['key'=>'rappi','name'=>'Rappi','icon'=>'📦','color'=>'#ff4411','bg'=>'#fff1ef','desc'=>'Conecta tu tienda con Rappi para recibir pedidos automáticamente.','fields'=>['restaurant_id'=>'ID del Restaurante','api_key'=>'API Key']],
    ['key'=>'pedidosya','name'=>'PedidosYa','icon'=>'🛒','color'=>'#e4002b','bg'=>'#fff0f2','desc'=>'Integración con PedidosYa. Recibe pedidos y gestiona entregas.','fields'=>['store_id'=>'ID de Tienda','api_token'=>'API Token']],
    ['key'=>'llama','name'=>'Llama Food','icon'=>'🦙','color'=>'#7c3aed','bg'=>'#f5f0ff','desc'=>'Plataforma peruana de delivery. Conecta tu restaurante.','fields'=>['restaurant_code'=>'Código Restaurante','secret_key'=>'Secret Key']],
    ['key'=>'daz','name'=>'Daz','icon'=>'🚀','color'=>'#0891b2','bg'=>'#f0f9ff','desc'=>'Delivery express. Vincula tu tienda para recibir pedidos.','fields'=>['merchant_id'=>'Merchant ID','api_secret'=>'API Secret']],
];
@endphp

    <!-- PLATFORMS GRID -->
    <div class="s-grid-4" style="margin-bottom:24px">
        @foreach($platforms as $p)
        <div class="s-card" style="text-align:center;transition:all .22s;cursor:default" onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--s-shadow)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
            <div style="width:60px;height:60px;border-radius:16px;background:{{ $p['bg'] }};display:grid;place-items:center;font-size:30px;margin:0 auto 14px">{{ $p['icon'] }}</div>
            <h4 style="font-size:15px;font-weight:800;color:var(--s-text);margin:0 0 6px">{{ $p['name'] }}</h4>
            <p style="font-size:12px;color:var(--s-text-3);margin:0 0 16px;line-height:1.5">{{ $p['desc'] }}</p>
            <span class="s-badge s-badge-gray" style="margin-bottom:12px">No conectado</span>
            <br>
            <button class="s-btn s-btn-outline s-btn-sm" style="margin-top:4px" onclick="openPlatform('{{ $p['key'] }}')">
                <i class="las la-plug"></i> Conectar
            </button>
        </div>
        @endforeach
    </div>

    <!-- REGISTER EXTERNAL ORDER + TABLE -->
    <div class="s-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
            <div>
                <h3 class="s-card-title" style="margin:0 0 4px"><i class="las la-list"></i> Pedidos Externos</h3>
                <p style="font-size:12px;color:var(--s-text-3);margin:0">Registra manualmente los pedidos de Rappi, PedidosYa, etc. para unificar tu gestión.</p>
            </div>
            <button class="s-btn s-btn-primary s-btn-sm" onclick="openExternalOrder()">
                <i class="las la-plus"></i> Registrar Pedido Externo
            </button>
        </div>

        @php
        $extOrders = \App\Models\PosOrder::where('seller_id', $seller->id)
            ->whereIn('order_type', ['rappi','pedidosya','llama','daz'])
            ->with('items')->latest()->limit(20)->get();
        @endphp

        <div style="overflow-x:auto">
        <table class="s-table">
            <thead>
                <tr>
                    <th>Plataforma</th>
                    <th># Pedido</th>
                    <th>Cliente</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($extOrders as $o)
                @php $icons=['rappi'=>'📦','pedidosya'=>'🛒','llama'=>'🦙','daz'=>'🚀']; @endphp
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:20px">{{ $icons[$o->order_type] ?? '📋' }}</span>
                            <span style="font-size:12px;font-weight:700;color:var(--s-text-2);text-transform:capitalize">{{ $o->order_type }}</span>
                        </div>
                    </td>
                    <td><b style="font-size:12px">{{ $o->order_no }}</b></td>
                    <td style="color:var(--s-text-2)">{{ $o->customer_name ?: '—' }}</td>
                    <td><span class="s-badge s-badge-gray">{{ $o->items->count() }}</span></td>
                    <td><b style="color:var(--s-accent-dark)">S/ {{ number_format($o->total,2) }}</b></td>
                    <td>
                        <span class="s-badge {{ $o->status==='delivered'?'s-badge-green':($o->status==='cancelled'?'s-badge-red':'s-badge-amber') }}">
                            {{ $o->status }}
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--s-text-3);white-space:nowrap">{{ $o->created_at->format('d/m H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="s-empty">
                            <i class="las la-motorcycle"></i>
                            <p>Sin pedidos externos registrados aún</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- PLATFORM MODAL -->
<div id="platform-modal" class="s-modal">
    <div class="s-modal-bg" onclick="this.parentElement.classList.remove('open')"></div>
    <div class="s-modal-box">
        <div class="s-modal-head">
            <h3 class="s-modal-title" id="platform-modal-title">Configurar Integración</h3>
            <button class="s-modal-close" onclick="this.closest('.s-modal').classList.remove('open')">✕</button>
        </div>
        <div class="s-modal-body">
            <p style="color:var(--s-text-3);font-size:13px;margin:0 0 18px">Ingresa las credenciales de tu cuenta para activar la integración.</p>
            <div id="platform-fields" class="s-form-grid"></div>
            <button class="s-btn s-btn-primary" style="width:100%;justify-content:center;margin-top:16px" onclick="savePlatform()">
                <i class="las la-save"></i> Guardar Configuración
            </button>
        </div>
    </div>
</div>

<!-- EXTERNAL ORDER MODAL -->
<div id="ext-order-modal" class="s-modal">
    <div class="s-modal-bg" onclick="this.parentElement.classList.remove('open')"></div>
    <div class="s-modal-box">
        <div class="s-modal-head">
            <h3 class="s-modal-title">Registrar Pedido Externo</h3>
            <button class="s-modal-close" onclick="this.closest('.s-modal').classList.remove('open')">✕</button>
        </div>
        <div class="s-modal-body">
            <form method="POST" action="{{ route('seller.pos.order.create') }}">
                @csrf
                <div class="s-form-grid">
                    <div class="s-input-group">
                        <label class="s-input-label">Plataforma *</label>
                        <select class="s-input" name="order_type" required>
                            <option value="">Seleccionar plataforma...</option>
                            <option value="rappi">📦 Rappi</option>
                            <option value="pedidosya">🛒 PedidosYa</option>
                            <option value="llama">🦙 Llama Food</option>
                            <option value="daz">🚀 Daz</option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="s-input-group">
                            <label class="s-input-label">Nombre del cliente</label>
                            <input class="s-input" name="customer_name" placeholder="Cliente">
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Teléfono</label>
                            <input class="s-input" name="customer_phone" placeholder="999 999 999">
                        </div>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Dirección de entrega</label>
                        <input class="s-input" id="delivery-addr" name="delivery_address" placeholder="Dirección completa">
                        @include('seller.partials.google_address', [
                            'addressId' => 'delivery-addr',
                            'latId'     => 'delivery-lat',
                            'lngId'     => 'delivery-lng',
                            'callback'  => 'initDeliveryAddress',
                        ])
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="s-input-group">
                            <label class="s-input-label">Latitud</label>
                            <input class="s-input" id="delivery-lat" name="delivery_lat" placeholder="-12.046..." readonly>
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Longitud</label>
                            <input class="s-input" id="delivery-lng" name="delivery_lng" placeholder="-77.042..." readonly>
                        </div>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Notas del pedido</label>
                        <textarea class="s-input" name="kitchen_notes" rows="2" placeholder="Instrucciones especiales..."></textarea>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Productos (JSON) *</label>
                        <textarea class="s-input" name="items" rows="3" placeholder='[{"product_id":1,"name":"Pizza","price":25,"quantity":2}]' required style="font-family:monospace;font-size:12px"></textarea>
                    </div>
                    <button type="submit" class="s-btn s-btn-primary" style="justify-content:center">
                        <i class="las la-save"></i> Registrar Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
var platforms = @json($platforms);

function openPlatform(key) {
    var p = platforms.find(function(x){ return x.key === key; });
    document.getElementById('platform-modal-title').textContent = p.icon + ' Conectar con ' + p.name;
    var html = '';
    for (var f in p.fields) {
        html += '<div class="s-input-group"><label class="s-input-label">' + p.fields[f] + '</label><input class="s-input" name="' + f + '" placeholder="' + p.fields[f] + '"></div>';
    }
    document.getElementById('platform-fields').innerHTML = html;
    document.getElementById('platform-modal').classList.add('open');
}

function savePlatform() {
    alert('✓ Configuración guardada. La integración se activará próximamente.');
    document.getElementById('platform-modal').classList.remove('open');
}

function openExternalOrder() {
    document.getElementById('ext-order-modal').classList.add('open');
}
</script>
@endpush
@endsection
