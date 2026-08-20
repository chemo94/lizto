@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-cash-register"></i></span> Terminal de Ventas (POS)
@if($store->isRestaurant())
<span id="table-label" style="font-size:13px;color:var(--s-text-muted);font-weight:400;margin-left:8px;"></span>
@endif
@endsection

@section('topbar-actions')
<div style="display: flex; gap: 8px;">
    @if($store->isRestaurant())
    <a href="{{ route('seller.pos.kitchen') }}" class="s-btn s-btn-primary s-btn-sm" style="border-radius: 10px;">
        <i class="las la-utensils"></i> Cocina
    </a>
    @endif
</div>
@endsection

@section('seller-content')
<div class="s-content" style="max-width: 100%; padding-top: 10px;">
    <!-- RESPONSIVE COMANDA ELEMENTS -->
    <div id="comanda-backdrop" onclick="toggleComandaDrawer()"></div>
    <button id="floating-cart-btn" onclick="toggleComandaDrawer()" style="display: none; position: fixed; bottom: 24px; right: 24px; z-index: 99999; background: var(--s-primary); color: #fff; border: none; border-radius: 50px; padding: 14px 24px; font-weight: 800; font-size: 14px; box-shadow: 0 8px 24px rgba(34, 197, 94, 0.4); align-items: center; gap: 8px; cursor: pointer; transition: transform 0.2s;">
        <i class="las la-shopping-basket" style="font-size: 20px;"></i>
        <span>Ver Comanda</span>
        <span class="s-badge s-badge-white" id="floating-cart-badge" style="font-size: 11px; padding: 2px 6px; border-radius: 12px; background: #fff; color: var(--s-primary); font-weight: 900; margin-left: 4px;">0</span>
    </button>
    
    <div class="pos-layout-grid">
        
        <!-- SECCIÓN IZQUIERDA: PRODUCTOS Y MESAS -->
        <div style="min-width: 0;">

            <!-- TIPO DE PEDIDO -->
            <div style="display: flex; gap: 4px; margin-bottom: 20px; background: var(--s-surface-2); border: 1px solid var(--s-border); border-radius: var(--s-radius); padding: 5px; flex-wrap: wrap;">
                @if($store->isRestaurant())
                <button class="pos-type-btn active" id="type-dine_in" onclick="setOrderType('dine_in')">
                    <i class="las la-utensils"></i> <span>Mesa</span>
                </button>
                @endif
                <button class="pos-type-btn {{ $store->isRestaurant() ? '' : 'active' }}" id="type-takeaway" onclick="setOrderType('takeaway')">
                    <i class="las la-shopping-bag"></i> <span>{{ $store->isRestaurant() ? 'Para Llevar' : 'Venta Directa' }}</span>
                </button>
                <button class="pos-type-btn" id="type-delivery" onclick="setOrderType('delivery')">
                    <i class="las la-motorcycle"></i> <span>Delivery (LIZTO)</span>
                </button>
                <button class="pos-type-btn" id="type-courtesy" onclick="setOrderType('courtesy')" style="border-color: #a855f7;">
                    <i class="las la-gift" style="color: #a855f7;"></i> <span style="color: #a855f7;">Cortesía</span>
                </button>
                <div style="width:1px;height:28px;background:var(--s-border);margin:0 4px;"></div>
                <button class="pos-type-btn" id="type-daz" onclick="setOrderType('daz')">
                    <span style="font-weight:900;font-size:10px;background:#e11d48;color:#fff;padding:1px 5px;border-radius:4px;">DAZ DAZ</span>
                </button>
                <button class="pos-type-btn" id="type-llama" onclick="setOrderType('llama')">
                    <span style="font-weight:900;font-size:10px;background:#f59e0b;color:#fff;padding:1px 5px;border-radius:4px;">LLAMA FOOD</span>
                </button>
                <!--<button class="pos-type-btn" id="type-rappi" onclick="setOrderType('rappi')">
                    <span style="font-weight:900;font-size:10px;background:#8b5cf6;color:#fff;padding:1px 5px;border-radius:4px;">RAPPI</span>
                </button>
                <button class="pos-type-btn" id="type-pedidosya" onclick="setOrderType('pedidosya')">
                    <span style="font-weight:900;font-size:10px;background:#0891b2;color:#fff;padding:1px 5px;border-radius:4px;">PEDIDOSYA</span>
                </button>   
-->             
            </div>

            @if($store->isRestaurant())
            <!-- TIRA DE MESAS -->
            @php
                $groups = [];
                foreach ($tables as $t) {
                    if ($t->linked_to_table_id) {
                        $pid = $t->linked_to_table_id;
                        if (!isset($groups[$pid])) {
                            $parent = $tables->firstWhere('id', $pid);
                            $groups[$pid] = ['primary' => $parent, 'children' => collect()];
                        }
                        $groups[$pid]['children']->push($t);
                    }
                }
                $freeTables = $tables->filter(fn($t) => !$t->linked_to_table_id && !isset($groups[$t->id]));
            @endphp

            <!-- ZONE TABS -->
            <div style="display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; align-items: center; padding: 8px 0;">
                <button class="pos-zone-tab active" data-zone="all" onclick="filterPosZone('all', this)">
                    Todas
                </button>
                @foreach($areas as $area)
                @php $areaCount = $tables->where('pos_area_id', $area->id)->count(); @endphp
                <button class="pos-zone-tab" data-zone="area-{{ $area->id }}" onclick="filterPosZone('area-{{ $area->id }}', this)">
                    {{ $area->name }} <span style="font-size: 10px; font-weight: 800; background: var(--s-surface-3); color: var(--s-text-3); padding: 1px 5px; border-radius: 10px; margin-left: 3px;">{{ $areaCount }}</span>
                </button>
                @endforeach
                @php $noAreaCount = $tables->whereNull('pos_area_id')->count(); @endphp
                @if($noAreaCount)
                <button class="pos-zone-tab" data-zone="sin-area" onclick="filterPosZone('sin-area', this)" style="border-style: dashed;">
                    Sin área <span style="font-size: 10px; font-weight: 800; background: var(--s-surface-3); color: var(--s-text-3); padding: 1px 5px; border-radius: 10px; margin-left: 3px;">{{ $noAreaCount }}</span>
                </button>
                @endif
            </div>

            <!-- TIRA DE MESAS -->
            <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;" id="tables-strip">
                <div class="pos-table-btn active" onclick="selectTable(null)" id="table-direct">
                    <i class="las la-plus-circle"></i>
                    <span>Mostrador / Barra</span>
                </div>
                @foreach($freeTables as $table)
                @php
                    $isBusy = $table->status !== 'free';
                @endphp
                <div class="pos-table-btn {{ $isBusy ? 'busy' : 'free' }}" data-zone="{{ $table->pos_area_id ? 'area-'.$table->pos_area_id : 'sin-area' }}" data-area="{{ $table->area }}" data-table-id="{{ $table->id }}" id="table-{{ $table->id }}" onclick="selectTable({{ $table->id }})">
                    <i class="las la-chair"></i>
                    <span>{{ $table->name }}</span>
                    @if($isBusy)
                        <small>Ocupada</small>
                        @if($table->active_order && $table->active_order->staff)
                            <div class="table-staff-name" style="font-size: 8px; font-weight: 800; margin-top: 2px; color: #ffe4e6; text-align: center; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $table->active_order->staff->name }}
                            </div>
                        @endif
                    @else
                        <small>Disponible</small>
                    @endif
                </div>
                @endforeach
                @foreach($groups as $groupId => $group)
                @php
                    $primary = $group['primary'];
                    $children = $group['children'];
                    $allNames = collect([$primary])->merge($children)->pluck('name')->implode('+');
                    $allIds = collect([$primary->id])->merge($children->pluck('id'))->toArray();
                    $isGroupBusy = $primary->status !== 'free';
                @endphp
                <div class="pos-table-btn grouped {{ $isGroupBusy ? 'busy' : 'free' }}"
                     data-zone="{{ $primary->pos_area_id ? 'area-'.$primary->pos_area_id : 'sin-area' }}"
                     data-area="{{ $primary->area }}"
                     data-table-id="{{ $primary->id }}"
                     data-group-tables="{{ json_encode($allIds) }}"
                     id="table-{{ $primary->id }}"
                     onclick="selectTable({{ $primary->id }})"
                     style="min-width: 90px;">
                    <i class="las la-object-group"></i>
                    <span>{{ $allNames }}</span>
                    @if($isGroupBusy)
                        <small>Ocupado</small>
                        @if($primary->active_order && $primary->active_order->staff)
                            <div class="table-staff-name" style="font-size: 8px; font-weight: 800; margin-top: 2px; color: #ffe4e6; text-align: center; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $primary->active_order->staff->name }}
                            </div>
                        @endif
                    @else
                        <small>Disponible</small>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            <!-- BÚSQUEDA INTELIGENTE -->
            <div style="position: relative; margin-bottom: 24px;">
                <i class="las la-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--s-text-3); font-size: 20px; z-index: 1;"></i>
                <input type="text" id="product-search" placeholder="Buscar por plato, postre, bebidas, precio..." class="s-input" style="padding: 14px 16px 14px 48px; font-size: 14px; border-radius: var(--s-radius); background: var(--s-surface); border: 1.5px solid var(--s-border); transition: all 0.25s;" oninput="filterProducts(this.value)" autocomplete="off">
                <button onclick="document.getElementById('product-search').value='';filterProducts('');" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--s-text-3); cursor: pointer; font-size: 18px; display: none;" id="clear-search" title="Limpiar búsqueda">✕</button>
            </div>

            <!-- RESULTADOS BÚSQUEDA -->
            <div id="search-results" style="display: none; margin-bottom: 24px;"></div>

            <!-- TABS DE CATEGORÍAS -->
            <div class="pos-category-tabs-container" style="margin-bottom: 20px; overflow-x: auto; display: flex; gap: 8px; padding-bottom: 6px; scrollbar-width: thin;">
                <button class="pos-cat-tab active" onclick="switchCategory('all')" id="cat-tab-all">
                    Todos
                </button>
                @foreach($categories as $cat)
                @php $catProds = $products->where('store_category_id', $cat->id); @endphp
                @if($catProds->count())
                <button class="pos-cat-tab" onclick="switchCategory({{ $cat->id }})" id="cat-tab-{{ $cat->id }}">
                    {{ $cat->name }} <span style="font-size: 10px; font-weight: 800; background: var(--s-surface-3); color: var(--s-text-3); padding: 1px 6px; border-radius: 20px; margin-left: 4px;">{{ $catProds->count() }}</span>
                </button>
                @endif
                @endforeach
            </div>

            <!-- GRILLA DE PRODUCTOS POR CATEGORÍA -->
            <div id="products-by-category">
                @foreach($categories as $cat)
                @php $catProds = $products->where('store_category_id', $cat->id); @endphp
                @if($catProds->count())
                <div class="category-section" id="cat-section-{{ $cat->id }}" style="margin-bottom: 28px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; border-bottom: 1.5px solid var(--s-border); padding-bottom: 8px;">
                        <h4 style="font-size: 13px; font-weight: 800; color: var(--s-text); text-transform: uppercase; letter-spacing: 0.8px; margin: 0;">{{ $cat->name }}</h4>
                        <span style="font-size: 11px; color: var(--s-text-3); font-weight: 700; background: var(--s-surface-2); padding: 3px 8px; border-radius: 20px; border: 1px solid var(--s-border);">{{ $catProds->count() }} platos</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(135px, 1fr)); gap: 14px;">
                        @foreach($catProds as $p)
                        <div class="pos-prod" onclick="addItem({{ $p->id }})">
                            <div class="pos-prod-img">
                                @if($p->image)
                                <img src="{{ asset('storage/'.$p->image) }}" alt="{{ $p->name }}" onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'las la-utensils\'></i>'">
                                @else
                                <i class="las la-utensils"></i>
                                @endif
                            </div>
                            
                            <div class="pos-prod-info">
                                <b title="{{ $p->name }}">{{ $p->name }}</b>
                                <span>S/ {{ number_format($p->finalPrice(), 2) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach
            </div>

        </div>

        <!-- SECCIÓN DERECHA: COMANDA Y DETALLES -->
        <div class="pos-right-sticky">
            
            <!-- TARJETA COMANDA -->
            <div class="s-card" style="box-shadow: var(--s-shadow-lg); border: 1px solid var(--s-border); padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: var(--s-text); display: flex; align-items: center; gap: 8px;">
                        <i class="las la-receipt" style="color: var(--s-accent-dark); font-size: 20px;"></i> Comanda
                    </h3>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="s-badge s-badge-gray" id="order-item-count" style="font-weight: 700; font-size: 10px; border-radius: 6px;">0 ítems</span>
                        <button class="s-btn s-btn-xs s-btn-ghost comanda-close-btn" onclick="toggleComandaDrawer()" style="display: none; padding: 4px; font-size: 18px; line-height: 1; border-radius: 50%;">✕</button>
                    </div>
                </div>

                <!-- ACCIONES DE MESA (TRANSFERIR, AGRUPAR, DESAGRUPAR) -->
                @if($store->isRestaurant())
                <div id="table-actions-container" style="display: none; background: var(--s-surface-2); padding: 10px 12px; border-radius: var(--s-radius); border: 1px solid var(--s-border); margin-bottom: 14px; flex-direction: column; gap: 8px;">
                    <div style="font-size: 11.5px; font-weight: 800; color: var(--s-text-secondary); display: flex; align-items: center; gap: 4px; white-space: nowrap;">
                        <i class="las la-chair" style="color:var(--s-primary);"></i> <span id="active-table-name-action">Mesa</span>
                    </div>
                    <div style="display: flex; gap: 4px; align-items: center; width: 100%;">
                        <button type="button" class="s-btn s-btn-xs s-btn-outline" onclick="openTransferModal()" style="flex: 1; font-size: 10px; padding: 4px; border-radius: 6px; height: 28px; white-space: nowrap; justify-content: center;">
                            <i class="las la-exchange-alt"></i> Mover
                        </button>
                        <button type="button" class="s-btn s-btn-xs s-btn-outline" onclick="openGroupModal()" style="flex: 1; font-size: 10px; padding: 4px; border-radius: 6px; height: 28px; white-space: nowrap; justify-content: center;">
                            <i class="las la-object-group"></i> Agrupar
                        </button>
                        <button type="button" class="s-btn s-btn-xs s-btn-ghost" id="btn-ungroup-group" onclick="submitUngroupAll()" style="flex: 1; font-size: 10px; padding: 4px; border-radius: 6px; color: var(--s-danger); display: none; height: 28px; white-space: nowrap; justify-content: center;">
                            <i class="las la-object-ungroup"></i> Desagrupar
                        </button>
                        <button type="button" class="s-btn s-btn-xs s-btn-ghost" id="btn-ungroup" onclick="submitUngroup()" style="flex: 1; font-size: 10px; padding: 4px; border-radius: 6px; color: var(--s-danger); display: none; height: 28px; white-space: nowrap; justify-content: center;">
                            <i class="las la-object-ungroup"></i> Desunir
                        </button>
                    </div>
                </div>
                @endif

                <!-- MESERO / PERSONAL -->
                @if($staff->count())
                <div style="margin-bottom: 14px;">
                    <label class="s-label" style="font-weight: 700; font-size: 11px; margin-bottom: 6px; display: flex; align-items: center; gap: 4px; color: var(--s-text-2);">
                        <i class="las la-user-tag" style="color: var(--s-primary);"></i> Mesero / Responsable
                    </label>
                    <select class="s-input" id="cust-staff-id" style="padding: 8px 12px; font-size: 12px; background: #fff;">
                        <option value="">Selecciona Mesero...</option>
                        @foreach($staff as $member)
                        <option value="{{ $member->id }}">{{ $member->name }} ({{ number_format($member->commission_rate, 1) }}%)</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- BUSCAR CLIENTE / SUNAT -->
                <div style="margin-bottom: 14px; background: var(--s-surface-2); border: 1px solid var(--s-border); padding: 12px; border-radius: var(--s-radius);">
                    <div style="display: flex; gap: 6px; margin-bottom: 8px;">
                        <select class="s-input" id="sunat-tpdoc" style="width: 70px; padding: 4px 6px; height: 34px; font-size: 11px; background: #fff;">
                            <option value="1">DNI</option>
                            <option value="6">RUC</option>
                        </select>
                        <input class="s-input" id="sunat-numdoc" placeholder="Nº Documento..." style="padding: 4px 10px; height: 34px; font-size: 11px; flex: 1; background: #fff;">
                        <button type="button" class="s-btn s-btn-primary s-btn-xs" style="height: 34px; width: 34px; justify-content: center; padding: 0; border-radius: 8px;" onclick="searchSunat()">
                            <i class="las la-search" style="font-size:14px;"></i>
                        </button>
                    </div>
                    
                    <div id="sunat-result" style="font-size: 11px; font-weight: 700; margin-bottom: 6px; min-height: 14px;"></div>
                    
                    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 6px;">
                        <input class="s-input" id="cust-name" placeholder="Nombre cliente" style="padding: 4px 8px; height: 32px; font-size: 11px; background: #fff;">
                        <input class="s-input" id="cust-phone" placeholder="Celular" style="padding: 4px 8px; height: 32px; font-size: 11px; background: #fff;">
                    </div>
                </div>

                <!-- EN CASO DE DELIVERY -->
                <div id="delivery-fields" style="display: none; margin-bottom: 14px; padding-top: 10px; border-top: 1px dashed var(--s-border);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label class="s-label" style="font-weight: 700; display: flex; align-items: center; gap: 4px; font-size: 11px; margin:0;"><i class="las la-map-marker-alt" style="color:var(--s-danger);"></i> Dirección de entrega</label>
                        <button type="button" class="s-btn s-btn-xs" id="pos-open-map-btn" onclick="openPosMapModal()" style="padding:3px 8px; font-size:10.5px; font-weight:700; border-radius:8px; background:rgba(34,197,94,0.12); color:#16a34a; border:1px solid rgba(34,197,94,0.3); display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                            <i class="las la-map-marked-alt" style="font-size:13px;"></i> Seleccionar en mapa
                        </button>
                    </div>
                    <input class="s-input" id="delivery-addr" placeholder="Dirección exacta..." style="padding: 6px 10px; font-size: 12px; background: #fff;" autocomplete="off">
                    <input type="hidden" id="delivery-lat">
                    <input type="hidden" id="delivery-lng">
                    <div id="fee-info" style="font-size: 11px; color: var(--s-accent-dark); margin-top: 4px; font-weight: 700;"></div>
                </div>

                <!-- CONTENEDOR ITEMS COMANDA -->
                <div id="order-items" style="max-height: 400px; overflow-y: auto; margin-bottom: 14px; min-height: 180px; border: 1.5px solid var(--s-border); border-radius: var(--s-radius); padding: 8px; background: #ffffff;">
                    <div style="text-align: center; color: var(--s-text-3); padding: 48px 12px; font-size: 12px;">
                        <i class="las la-shopping-basket" style="font-size: 32px; display: block; margin-bottom: 8px; color: var(--s-border);"></i>
                        Selecciona productos de la lista
                    </div>
                </div>

                <!-- RESUMEN TOTAL -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 6px; border-top: 1.5px solid var(--s-border); margin-bottom: 12px; background: var(--s-surface-2); border-radius: 8px; margin-top: 4px;">
                    <span style="font-weight: 700; color: var(--s-text-2); font-size: 13px; padding-left: 6px;">Total Cuenta:</span>
                    <strong id="order-total" style="font-size: 22px; color: var(--s-accent-dark); font-weight: 950; padding-right: 6px;">S/ 0.00</strong>
                </div>

                <!-- NOTAS DE COCINA -->
                <div style="margin-bottom: 14px;">
                    <input class="s-input" id="kitchen-notes" placeholder="Notas internas / especificaciones cocina..." style="padding: 8px 12px; font-size: 12px; background: #fff;">
                </div>

                <!-- ENVIAR PEDIDO -->
                <button class="s-btn s-btn-primary" style="width: 100%; padding: 12px; font-size: 14px; font-weight: 800; justify-content: center; border-radius: 12px; gap: 8px;" id="btn-submit" onclick="submitOrder()">
                    <i class="las la-paper-plane" style="font-size: 16px;"></i> Enviar Comanda
                </button>
            </div>

            <!-- PEDIDOS PENDIENTES DE PAGO -->
            @if($pendingPayment->count())
            <div class="s-card" style="border: 1px solid var(--s-border); padding: 16px; box-shadow: var(--s-shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <strong style="font-size: 12px; color: var(--s-text-2); display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="las la-clock" style="color: var(--s-warning); font-size: 18px;"></i> Cuentas por Cobrar
                    </strong>
                    <span class="s-badge s-badge-amber" style="font-size: 10px; font-weight: 700; border-radius: 6px;">{{ $pendingPayment->count() }}</span>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 8px; max-height: 180px; overflow-y: auto; padding-right: 4px;">
                    @foreach($pendingPayment as $po)
                    <div class="pending-payment-item" 
                         onclick="openChargeModal({{ $po->id }},'{{ $po->order_no }}',{{ $po->total }},'{{ addslashes($po->customer_name) }}','{{ $po->table?->name }}')">
                        <div>
                            <span class="no">#{{ $po->order_no }}</span>
                            <span class="dest">{{ $po->table?->name ?: $po->customer_name ?: 'Venta Directa' }}</span>
                        </div>
                        <b class="val">S/ {{ number_format($po->total, 2) }}</b>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

    </div>

</div>

<!-- CHARGE MODAL OVERLAY -->
<div id="charge-modal" class="s-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,25,35,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div style="background: var(--s-surface); width: min(620px, calc(100vw - 32px)); padding: 28px; border-radius: 20px; border: 1px solid var(--s-border); position: relative; box-shadow: var(--s-shadow-lg); animation: sBoxIn .25s ease; max-height: calc(100vh - 32px); overflow-y: auto;">
        
        <button onclick="closeChargeModal()" style="position: absolute; top: 18px; right: 18px; background: none; border: none; font-size: 20px; color: var(--s-text-3); cursor: pointer; transition: color 0.15s;" onmouseover="this.style.color='var(--s-text)'" onmouseout="this.style.color='var(--s-text-3)'">✕</button>
        
        <h3 style="margin-bottom: 20px; font-weight: 900; font-size: 18px; color: var(--s-text); display: flex; align-items: center; gap: 8px;">
            <i class="las la-hand-holding-usd" style="color: var(--s-accent-dark); font-size: 24px;"></i> Registrar Cobro
        </h3>
        
        <div id="charge-info" style="background: var(--s-surface-2); padding: 16px; border: 1px solid var(--s-border); border-radius: 12px; margin-bottom: 20px; font-size: 13px; display: flex; flex-direction: column; gap: 8px;">
            <div style="display: flex; justify-content: space-between; color: var(--s-text-2);"><span>Nº Pedido:</span><b id="charge-order-no" style="color: var(--s-text);"></b></div>
            <div style="display: flex; justify-content: space-between; color: var(--s-text-2);"><span>Referencia / Mesa:</span><b id="charge-table" style="color: var(--s-text);"></b></div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--s-border); padding-top: 8px; margin-top: 4px; font-size: 14px;">
                <span style="font-weight: 700;">Total a Cobrar:</span>
                <b id="charge-total" style="font-size: 18px; color: var(--s-accent-dark); font-weight: 900;"></b>
            </div>
        </div>

        <!-- Cobro con comprobante -->
        <form method="POST" action="" id="charge-form" style="margin-bottom: 0;">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @if($invoiceTypes->count())
                <select class="s-input" name="series_id" id="charge-series" style="padding: 8px 12px; font-size: 12px; background: var(--s-surface-2);" onchange="onChargeSeriesChange()">
                    <option value="" data-code="NV">Nota de Venta (Clientes Varios - Por defecto)</option>
                    @foreach($invoiceTypes as $type)
                    @if(in_array($type->code, ['01', '03', 'NV']))
                    <optgroup label="{{ $type->code }} - {{ $type->name }}">
                        @foreach($type->series as $s)
                        <option value="{{ $s->id }}" data-code="{{ $type->code }}">{{ $s->series }} (Siguiente: {{ $s->nextNumber() }})</option>
                        @endforeach
                    </optgroup>
                    @endif
                    @endforeach
                </select>
                @else
                <input type="hidden" name="series_id" value="">
                @endif

                <div>
                    <label class="s-label" style="font-weight:700;">Detalle que verá el cliente</label>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:7px;">
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;cursor:pointer;"><input type="radio" name="detail_mode" value="detailed" checked onchange="toggleChargeConsumptionDescription()"> Por ítems</label>
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;cursor:pointer;"><input type="radio" name="detail_mode" value="consumption" onchange="toggleChargeConsumptionDescription()"> Por consumo</label>
                    </div>
                    <div id="charge-consumption-description-wrap" style="display:none;margin-top:10px;">
                        <input class="s-input" name="consumption_description" id="charge-consumption-description" maxlength="250" placeholder="Descripción para el comprobante. Ej.: Consumo en restaurante" style="height:42px;border-radius:10px;">
                    </div>
                </div>

                <div style="display: flex; gap: 6px;">
                    <select class="s-input" name="tipo_doc" id="charge-tipo-doc" style="width: 85px; padding: 6px; font-size: 12px; background: var(--s-surface-2);" onchange="clearChargeDocResult()">
                        <option value="1">DNI</option>
                        <option value="6">RUC</option>
                    </select>
                    <div style="flex: 1; position: relative;">
                        <input class="s-input" name="num_doc" id="charge-num-doc" placeholder="N° Documento cliente (opcional)" style="padding: 6px 12px; font-size: 12px; width: 100%; box-sizing: border-box; background: var(--s-surface-2);" autocomplete="off">
                        <div id="charge-doc-result" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--s-border); border-radius: 0 0 8px 8px; padding: 8px 12px; font-size: 12px; font-weight: 600; z-index: 10; display: none;"></div>
                    </div>
                </div>
                <div id="charge-client-badge" style="display:none; background:rgba(34,197,94,0.12); color:#15803d; border:1px solid rgba(34,197,94,0.3); border-radius:8px; padding:6px 10px; font-size:12px; font-weight:700; align-items:center; gap:6px; margin-top:4px;">
                    <i class="las la-user-check"></i>
                    <span id="charge-client-badge-name"></span>
                    <button type="button" onclick="clearChargeDocResult()" style="margin-left:auto; background:none; border:none; color:#15803d; cursor:pointer; font-size:14px; line-height:1;">✕</button>
                </div>
                
                <div style="background: var(--s-bg-light); border: 1px solid var(--s-border); border-radius: 12px; padding: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h4 style="margin: 0; font-size: 12px; text-transform: uppercase; font-weight: 800; color: var(--s-text-secondary); letter-spacing: 0.5px;">Métodos de Pago</h4>
                        <select id="add-payment-method-charge" class="s-input" style="width: 180px; height: 28px; padding: 2px 6px; font-size: 11px; border-radius: 6px; background: #fff;" onchange="showPaymentMethod('charge', this.value); this.value='';">
                            <option value="">+ Agregar método...</option>
                            <option value="cash">Efectivo</option>
                            <option value="card">POS / Tarjeta</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="transfer">Transferencia</option>
                            <option value="courtesy">Cortesía (Consumo Libre)</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; background: #fff; border: 1px dashed var(--s-border); border-radius: 10px; padding: 10px 12px; margin-bottom: 12px;">
                        <span style="font-size: 11px; font-weight: 800; color: var(--s-text-secondary); text-transform: uppercase;">Dividir cuenta</span>
                        <input type="number" min="2" max="50" value="2" id="charge-split-people" class="s-input" style="width: 64px; height: 30px; padding: 2px 8px; font-size: 12px; font-weight: 800; text-align: center;">
                        <span style="font-size: 12px; color: var(--s-text-muted);">partes</span>
                        <select id="charge-split-method" class="s-input" style="width: 125px; height: 30px; padding: 2px 6px; font-size: 11px; border-radius: 6px;">
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                        <button type="button" onclick="applyEqualSplit('charge')" class="s-btn s-btn-ghost s-btn-xs" style="height: 30px; border: 1px solid var(--s-border); font-weight: 800;">
                            Aplicar 1 parte
                        </button>
                        <span id="charge-split-preview" style="font-size: 12px; color: var(--s-text-muted); font-weight: 700;"></span>
                    </div>
                    <div style="background: #fff; border: 1px dashed var(--s-border); border-radius: 10px; padding: 10px 12px; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                            <span style="font-size: 11px; font-weight: 800; color: var(--s-text-secondary); text-transform: uppercase;">Por productos</span>
                            <input type="text" id="charge-product-person" class="s-input" value="Persona 1" style="width: 100px; height: 30px; padding: 2px 8px; font-size: 12px; font-weight: 700;">
                            <select id="charge-product-method" class="s-input" style="width: 125px; height: 30px; padding: 2px 6px; font-size: 11px; border-radius: 6px;">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                                <option value="transfer">Transferencia</option>
                            </select>
                            <button type="button" onclick="applyProductSplit('charge')" class="s-btn s-btn-ghost s-btn-xs" style="height: 30px; border: 1px solid var(--s-border); font-weight: 800;">
                                Aplicar consumo
                            </button>
                            <span id="charge-product-split-total" style="font-size: 12px; color: var(--s-primary); font-weight: 900;">S/ 0.00</span>
                        </div>
                        <div id="charge-product-split-items" style="display: flex; flex-direction: column; gap: 6px; max-height: 150px; overflow-y: auto;"></div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @php
                            $methods = [
                                'cash' => ['label' => 'Efectivo', 'icon' => '<i class="las la-money-bill-wave" style="color: #22c55e; font-size: 20px;"></i>'],
                                'card' => ['label' => 'POS / Tarjeta', 'icon' => '<i class="las la-credit-card" style="color: #f97316; font-size: 20px;"></i>'],
                                'yape' => ['label' => 'Yape', 'icon' => '<span style="background: #74226C; color: #fff; font-weight: 900; font-size: 9px; padding: 2px 6px; border-radius: 6px; text-transform: uppercase; box-shadow: 0 2px 4px rgba(116,34,108,0.3); letter-spacing: 0.5px; display: inline-block;">Yape</span>'],
                                'plin' => ['label' => 'Plin', 'icon' => '<span style="background: #00d2c4; color: #fff; font-weight: 900; font-size: 9px; padding: 2px 6px; border-radius: 6px; text-transform: uppercase; box-shadow: 0 2px 4px rgba(0,210,196,0.3); letter-spacing: 0.5px; display: inline-block;">Plin</span>'],
                                'transfer' => ['label' => 'Transferencia', 'icon' => '<i class="las la-university" style="color: #3b82f6; font-size: 20px;"></i>'],
                                'courtesy' => ['label' => 'Cortesía (Consumo Libre)', 'icon' => '<i class="las la-gift" style="color: #a855f7; font-size: 20px;"></i>'],
                            ];
                        @endphp
                        @foreach($methods as $name => $data)
                        <div id="payment-row-charge-{{ $name }}" style="display: {{ $name === 'cash' ? 'flex' : 'none' }}; flex-direction: column; gap: 6px; border-bottom: 1px dashed var(--s-border); padding-bottom: 10px; margin-bottom: 6px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                                    {!! $data['icon'] !!}
                                    <span style="font-weight: 700; font-size: 13px; color: var(--s-text-primary);">{{ $data['label'] }}</span>
                                    @if($name !== 'cash')
                                    <button type="button" onclick="hidePaymentMethod('charge', '{{ $name }}')" style="background: none; border: none; color: var(--s-danger); cursor: pointer; padding: 0 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-left: 4px;" title="Quitar">
                                        <i class="las la-trash-alt"></i>
                                    </button>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <button type="button" onclick="fillPayment('{{ $name }}', 'all')" style="padding: 2px 6px; font-size: 10px; font-weight: 800; background: var(--s-primary); color: #fff; border: none; border-radius: 4px; cursor: pointer; transition: opacity 0.15s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Todo</button>
                                    <button type="button" onclick="fillPayment('{{ $name }}', 'rest')" style="padding: 2px 6px; font-size: 10px; font-weight: 800; background: var(--s-info); color: #fff; border: none; border-radius: 4px; cursor: pointer; transition: opacity 0.15s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Resto</button>
                                    <span style="font-size: 12px; color: var(--s-text-muted);">S/</span>
                                    <input type="number" step="0.01" min="0" class="s-input split-charge-input" name="payments[{{ $name }}]" id="charge-input-{{ $name }}" data-method="{{ $name }}" value="0.00" style="width: 100px; height: 34px; text-align: right; padding: 4px 8px; border-radius: 8px; font-weight: 700;" oninput="updateSplitTotal('charge')">
                                </div>
                            </div>
                            
                            @if($name !== 'cash' && $name !== 'courtesy')
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; font-size: 11px;">
                                <span style="color: var(--s-text-secondary); font-weight: 600;">Destinar a:</span>
                                <select class="s-input" name="payment_accounts[{{ $name }}]" style="width: 220px; height: 28px; padding: 2px 6px; font-size: 11px; border-radius: 6px; background: #fff;">
                                    @php
                                        $typeMapping = [
                                            'yape' => 'wallet',
                                            'plin' => 'wallet',
                                            'card' => 'pos_card',
                                            'transfer' => 'bank'
                                        ];
                                        $targetType = $typeMapping[$name] ?? 'bank';
                                        $filteredAccounts = $bankAccounts->where('type', $targetType);
                                    @endphp
                                    <option value="">-- Cuenta Defectiva --</option>
                                    @foreach($filteredAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ strtoupper($acc->bank_name) }})</option>
                                    @endforeach
                                    @if($filteredAccounts->isEmpty())
                                    @foreach($bankAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ strtoupper($acc->bank_name) }})</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; border-top: 1px dashed var(--s-border); padding-top: 14px; margin-bottom: 14px;">
                    <span style="font-weight: 800; color: var(--s-text-primary);">Monto Ingresado:</span>
                    <span style="font-size: 16px; font-weight: 950; color: var(--s-primary);">S/ <span id="charge-entered-display">0.00</span></span>
                </div>
                <div id="charge-validation-alert" style="display:none; font-size:12px; color:var(--s-danger-text); background:var(--s-danger-bg); padding:8px 12px; border-radius:8px; margin-bottom:15px; font-weight:700;"></div>
                
                <button type="submit" class="s-btn s-btn-primary" id="charge-submit-btn" style="justify-content: center; gap: 8px; height: 46px; font-size: 14px; font-weight: 800;">
                    <i class="las la-file-invoice" style="font-size: 20px;"></i> Cobrar y Emitir Comprobante
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TRANSFER TABLE MODAL -->
@if($store->isRestaurant())
<div id="transfer-modal" class="s-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,25,35,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);" onclick="if(event.target===this)closeTransferModal()">
    <div style="background: var(--s-surface); width: 400px; padding: 24px; border-radius: 20px; border: 1px solid var(--s-border); position: relative; box-shadow: var(--s-shadow-lg); animation: sBoxIn .25s ease;">
        <button onclick="closeTransferModal()" style="position: absolute; top: 14px; right: 14px; background: none; border: none; font-size: 20px; color: var(--s-text-3); cursor: pointer;">✕</button>
        <h3 style="margin-bottom: 16px; font-weight: 800; font-size: 16px; color: var(--s-text); display: flex; align-items: center; gap: 8px;">
            <i class="las la-exchange-alt" style="color: var(--s-primary); font-size: 20px;"></i> Transferir Mesa
        </h3>
        <p style="font-size: 12px; color: var(--s-text-secondary); margin-bottom: 20px;">Mover la comanda activa de <strong id="transfer-from-name">Mesa</strong> a otra mesa libre:</p>
        
        <form method="POST" action="{{ route('seller.pos.table.transfer') }}">
            @csrf
            <input type="hidden" name="from_table_id" id="transfer-from-id">
            <div style="margin-bottom: 20px;">
                <label class="s-label">Mesa de Destino (Solo libres)</label>
                <select name="to_table_id" class="s-input" required style="background: var(--s-surface-2);">
                    <option value="">Selecciona mesa libre...</option>
                    @foreach($tables->where('status', 'free')->where('linked_to_table_id', null) as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} (Capacidad: {{ $t->capacity }} pers.)</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; height: 42px; font-weight: 700;">
                Confirmar Transferencia
            </button>
        </form>
    </div>
</div>

<!-- GROUP TABLES MODAL -->
<div id="group-modal" class="s-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15,25,35,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);" onclick="if(event.target===this)closeGroupModal()">
    <div style="background: var(--s-surface); width: 420px; max-height: 80vh; padding: 24px; border-radius: 20px; border: 1px solid var(--s-border); position: relative; box-shadow: var(--s-shadow-lg); animation: sBoxIn .25s ease; overflow-y: auto;">
        <button onclick="closeGroupModal()" style="position: absolute; top: 14px; right: 14px; background: none; border: none; font-size: 20px; color: var(--s-text-3); cursor: pointer;">✕</button>
        <h3 style="margin-bottom: 16px; font-weight: 800; font-size: 16px; color: var(--s-text); display: flex; align-items: center; gap: 8px;">
            <i class="las la-object-group" style="color: var(--s-primary); font-size: 20px;"></i> Agrupar Mesas
        </h3>
        <p style="font-size: 12px; color: var(--s-text-secondary); margin-bottom: 20px;">Selecciona las mesas libres para unir a <strong id="group-primary-name">Mesa</strong>:</p>
        
        <form method="POST" action="{{ route('seller.pos.table.group') }}">
            @csrf
            <input type="hidden" name="primary_table_id" id="group-primary-id">
            <div id="group-checkbox-list" style="margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px;">
                @php
                    $freeTables = $tables->where('status', 'free')->whereNull('linked_to_table_id');
                @endphp
                @forelse($freeTables as $t)
                <label style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1.5px solid var(--s-border); border-radius: 10px; cursor: pointer; transition: all .15s; font-size: 13px; font-weight: 600; color: var(--s-text-2);"
                       onmouseover="this.style.borderColor='var(--s-primary)'" onmouseout="this.style.borderColor='var(--s-border)'">
                    <input type="checkbox" name="secondary_table_ids[]" value="{{ $t->id }}" style="accent-color: var(--s-primary); width: 16px; height: 16px;">
                    <i class="las la-chair" style="font-size: 16px; color: var(--s-text-3);"></i>
                    {{ $t->name }} <span style="font-weight: 400; font-size: 11px; color: var(--s-text-3);">(Cap: {{ $t->capacity }})</span>
                </label>
                @empty
                <p style="font-size: 12px; color: var(--s-text-3); text-align: center; padding: 16px;">No hay mesas libres disponibles</p>
                @endforelse
            </div>
            <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; height: 42px; font-weight: 700;">
                <i class="las la-object-group"></i> Confirmar Agrupación
            </button>
        </form>
    </div>
</div>
@endif

<!-- DETALLES DE PRODUCTO MODAL OVERLAY -->
<div id="p-modal" style="position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; background: rgba(15,25,35,0.6); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);" onclick="if(event.target===this)pmClose()">
    <div style="position: relative; max-width: 420px; width: 100%; margin: 16px; z-index: 1; padding: 24px; background: var(--s-surface); border-radius: 20px; border: 1px solid var(--s-border); box-shadow: var(--s-shadow-lg); animation: sBoxIn .25s ease;">
        
        <button onclick="pmClose()" style="position: absolute; top: 14px; right: 14px; width: 30px; height: 30px; border: none; background: var(--s-surface-2); border-radius: 50%; cursor: pointer; font-size: 14px; color: var(--s-text-3); display: flex; align-items: center; justify-content: center; transition: background 0.15s;">✕</button>
        
        <h3 id="pm-name" style="font-size: 16px; font-weight: 800; margin: 0 0 4px; color: var(--s-text);"></h3>
        <p id="pm-price" style="font-size: 15px; font-weight: 800; color: var(--s-accent-dark); margin: 0 0 16px;"></p>
        
        <div id="pm-var-section" style="margin-bottom: 14px; display: none;">
            <label class="s-label" style="font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px; display: block; color: var(--s-text-2);">Variaciones</label>
            <div id="pm-var-list" style="display: flex; flex-direction: column; gap: 6px;"></div>
        </div>
        
        <div id="pm-addon-section" style="margin-bottom: 18px; display: none;">
            <label class="s-label" style="font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px; display: block; color: var(--s-text-2);">Adicionales / Extras</label>
            <div id="pm-addon-list" style="display: flex; flex-direction: column; gap: 6px;"></div>
        </div>
        
        <div style="margin-bottom: 14px; background: var(--s-surface-2); padding: 10px 14px; border-radius: var(--s-radius); border: 1.5px solid var(--s-border);">
            <label style="display: flex; align-items: center; gap: 10px; font-size: 12.5px; font-weight: 750; color: var(--s-text-2); cursor: pointer; user-select: none;">
                <input type="checkbox" id="pm-tupper" onchange="pmUpd()" style="width: 17px; height: 17px; accent-color: var(--s-primary); cursor: pointer;">
                <i class="las la-box" style="font-size: 18px; color: var(--s-primary);"></i>
                <span>Llevar en Tupper (+S/ 1.00)</span>
            </label>
        </div>

        <div style="margin-bottom: 18px;">
            <label class="s-label" style="font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px; display: block; color: var(--s-text-2);">Especificaciones / Nota del Plato</label>
            <input type="text" id="pm-note" class="s-input" placeholder="Ej: Sin picante, término medio, etc." style="padding: 8px 12px; font-size: 12px; background: var(--s-surface-2);">
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-top: 1.5px solid var(--s-border); font-size: 16px; font-weight: 800; margin-bottom: 12px; margin-top: 6px;">
            <span style="color: var(--s-text-2);">Total Plato:</span>
            <b id="pm-total" style="color: var(--s-accent-dark);">S/ 0.00</b>
        </div>
        
        <button onclick="pmConfirm()" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; gap: 6px;">
            <i class="las la-plus-circle"></i> Agregar a Comanda
        </button>
    </div>
</div>
@if($seller->cash_required && !$isCashOpen && !$store->isRestaurant())
<!-- Modal para abrir caja (bloqueante) -->
<div id="open-cash-modal" style="position: fixed; inset: 0; background: rgba(15,25,35,0.85); z-index: 99999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
    <div style="background: var(--s-surface); width: 400px; padding: 32px; border-radius: 24px; border: 1.5px solid var(--s-border); box-shadow: var(--s-shadow-lg); text-align: center;">
        <div style="width: 64px; height: 64px; background: var(--s-accent-light); color: var(--s-accent-dark); border-radius: 50%; display: grid; place-items: center; margin: 0 auto 20px; font-size: 32px;">
            <i class="las la-cash-register"></i>
        </div>
        <h3 style="font-size: 20px; font-weight: 900; color: var(--s-text); margin-bottom: 8px;">Caja Cerrada</h3>
        <p style="font-size: 13px; color: var(--s-text-3); margin-bottom: 24px; line-height: 1.5;">Debes abrir caja para poder registrar comandas y ventas en el punto de venta (POS).</p>
        
        <form method="POST" action="{{ route('seller.cash.open') }}" style="text-align: left;">
            @csrf
            <label class="s-label" style="font-weight: 700; font-size: 11px; margin-bottom: 6px; display: block; color: var(--s-text-2);">Monto Inicial de Apertura (S/)</label>
            <input type="number" step="0.01" min="0" name="opening_balance" class="s-input" placeholder="0.00" style="padding: 10px 14px; font-size: 14px; font-weight: 700; background: var(--s-surface-2); margin-bottom: 14px;" required autofocus>
            
            <label class="s-label" style="font-weight: 700; font-size: 11px; margin-bottom: 6px; display: block; color: var(--s-text-2);">Notas / Comentario (Opcional)</label>
            <textarea name="notes" class="s-input" placeholder="Ej. Caja chica turno mañana..." style="padding: 10px 14px; font-size: 12px; height: 60px; background: var(--s-surface-2); margin-bottom: 24px; resize: none;"></textarea>
            
            <button type="submit" class="s-btn s-btn-primary" style="width: 100%; justify-content: center; height: 46px; font-size: 14px; font-weight: 800; border-radius: 12px; gap: 8px;">
                <i class="las la-key"></i> Abrir Caja e Iniciar Turno
            </button>
        </form>
    </div>
</div>
@endif

<style>
/* CSS adicional premium para el POS */
.pos-table-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 10px 14px;
    border: 1.5px solid var(--s-border);
    border-radius: 14px;
    cursor: pointer;
    font-size: 11px;
    font-weight: 700;
    color: var(--s-text-2);
    background: var(--s-surface);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 76px;
    box-shadow: var(--s-shadow-sm);
}
.pos-table-btn:hover {
    border-color: var(--s-accent);
    color: var(--s-text);
    transform: translateY(-2px);
    box-shadow: var(--s-shadow);
}
.pos-table-btn.active {
    border-color: var(--s-accent);
    background: var(--s-accent-light);
    color: var(--s-accent-dark);
}
.pos-table-btn.free {
    border-color: #16a34a !important;
    background: #22c55e !important;
    color: #ffffff !important;
}
.pos-table-btn.free i {
    color: #ffffff !important;
}
.pos-table-btn.free:hover {
    border-color: #14532d !important;
    background: #15803d !important;
}
.pos-table-btn.free.active {
    border-color: #1e293b !important;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.5) !important;
}
.pos-table-btn.busy {
    border-color: #dc2626 !important;
    background: #ef4444 !important;
    color: #ffffff !important;
}
.pos-table-btn.busy i {
    color: #ffffff !important;
}
.pos-table-btn.busy:hover {
    border-color: #991b1b !important;
    background: #b91c1c !important;
}
.pos-table-btn.busy.active {
    border-color: #1e293b !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.5) !important;
}
.pos-table-btn i {
    font-size: 20px;
    color: var(--s-text-3);
}
.pos-table-btn.active i {
    color: var(--s-accent-dark);
}
.pos-table-btn.grouped {
    border-color: var(--s-primary);
    background: rgba(107, 91, 214, 0.06);
}
.pos-table-btn.grouped:hover {
    border-color: var(--s-primary);
}
.pos-table-btn.grouped i {
    color: var(--s-primary);
}
.pos-table-btn small {
    font-size: 8px;
    font-weight: 800;
    background: var(--s-warning);
    color: #fff;
    padding: 1px 5px;
    border-radius: 4px;
    margin-top: 3px;
    text-transform: uppercase;
}
.pos-table-btn.free small {
    background: #15803d !important;
    color: #ffffff !important;
}
.pos-table-btn.busy small {
    background: #991b1b !important;
    color: #ffffff !important;
}

.pos-type-btn {
    flex: 1;
    padding: 11px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
    color: var(--s-text-2);
    background: transparent;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.pos-type-btn:hover {
    background: var(--s-surface);
    color: var(--s-text);
}
.pos-type-btn.active {
    background: var(--s-accent);
    color: #fff;
    box-shadow: var(--s-shadow-accent);
}

.pos-prod {
    background: var(--s-surface); 
    border: 1.5px solid var(--s-border); 
    border-radius: var(--s-radius); 
    overflow: hidden; 
    cursor: pointer; 
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--s-shadow-sm);
}
.pos-prod:hover {
    border-color: var(--s-accent) !important;
    box-shadow: var(--s-shadow);
    transform: translateY(-4px);
}
.pos-prod-img {
    width: 100%; 
    height: 95px; 
    background: var(--s-surface-2); 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    overflow: hidden; 
    border-bottom: 1.5px solid var(--s-border);
    transition: background 0.2s;
}
.pos-prod-img img {
    width: 100%; 
    height: 100%; 
    object-fit: cover;
}
.pos-prod-img i {
    font-size: 32px; 
    color: var(--s-text-3);
}
.pos-prod-info {
    padding: 10px 12px;
}
.pos-prod-info b {
    display: block; 
    font-size: 12px; 
    font-weight: 700; 
    color: var(--s-text); 
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis;
}
.pos-prod-info span {
    font-size: 13px; 
    font-weight: 800; 
    color: var(--s-accent-dark); 
    display: block; 
    margin-top: 4px;
}

.order-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 6px;
    border-bottom: 1px dashed var(--s-border);
    font-size: 12px;
    color: var(--s-text);
}
.order-item-row:last-child {
    border-bottom: none;
}
.order-item-row b {
    color: var(--s-accent-dark);
}
.order-item-row span {
    font-weight: 700;
}
.order-item-row button {
    background: none;
    border: none;
    color: var(--s-danger);
    cursor: pointer;
    font-size: 15px;
    padding: 0 4px;
    line-height: 1;
    transition: transform 0.15s;
}
.order-item-row button:hover {
    transform: scale(1.2);
}

.p-var, .p-addon {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1.5px solid var(--s-border);
    border-radius: 10px;
    cursor: pointer;
    font-size: 12px;
    background: var(--s-surface-2);
    color: var(--s-text);
    font-weight: 600;
    transition: all 0.15s;
}
.p-var:hover, .p-addon:hover {
    border-color: var(--s-accent);
}
.p-var.selected, .p-addon.selected {
    border-color: var(--s-accent);
    background: var(--s-accent-light);
    color: var(--s-accent-dark);
}

.pending-payment-item {
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
    padding: 10px 12px; 
    background: var(--s-surface); 
    border: 1.5px solid var(--s-border); 
    border-radius: 10px; 
    font-size: 12px; 
    cursor: pointer; 
    transition: all 0.2s;
}
.pending-payment-item:hover {
    border-color: var(--s-warning);
    background: var(--s-warning-bg);
    transform: translateY(-1px);
}
.pending-payment-item .no {
    font-weight: 800; 
    color: var(--s-text);
}
.pending-payment-item .dest {
    color: var(--s-text-2); 
    margin-left: 6px;
}
.pending-payment-item .val {
    color: var(--s-accent-dark); 
    font-weight: 800;
}
.pay-method-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px 6px;
    border: 1.5px solid var(--s-border);
    border-radius: 12px;
    cursor: pointer;
    background: var(--s-surface);
    transition: all 0.2s;
    font-size: 11px;
    font-weight: 700;
    color: var(--s-text-2);
    min-height: 72px;
}
.pay-method-card:hover {
    border-color: var(--s-accent);
    background: var(--s-surface-2);
    color: var(--s-text);
}
.pay-method-card.active {
    border-color: var(--s-accent);
    background: var(--s-accent-light);
    color: var(--s-accent-dark);
    box-shadow: var(--s-shadow-sm);
}
.pos-layout-grid {
    display: grid;
    grid-template-columns: 1fr 390px;
    gap: 24px;
    align-items: start;
}
.pos-right-sticky {
    position: sticky;
    top: 90px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
@media (max-width: 991px) {
    .pos-layout-grid {
        grid-template-columns: 1fr;
    }
    .pos-right-sticky {
        position: fixed;
        top: 0;
        right: -420px;
        width: 390px;
        max-width: 100%;
        height: 100vh;
        z-index: 99999;
        background: var(--s-surface);
        box-shadow: -4px 0 24px rgba(0,0,0,0.15);
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 20px 24px;
        overflow-y: auto;
        display: flex;
    }
    .pos-right-sticky.open {
        right: 0;
    }
    #comanda-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15,25,35,0.45);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 99998;
    }
    #comanda-backdrop.open {
        display: block;
    }
    #floating-cart-btn {
        display: flex !important;
    }
    .comanda-close-btn {
        display: inline-flex !important;
    }
}
.pos-category-tabs-container {
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}
.pos-category-tabs-container::-webkit-scrollbar {
    height: 4px;
}
.pos-category-tabs-container::-webkit-scrollbar-thumb {
    background: var(--s-border);
    border-radius: 4px;
}
#tables-strip {
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}
@media (max-width: 991px) {
    #tables-strip {
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        padding-bottom: 8px;
    }
    #tables-strip::-webkit-scrollbar {
        height: 4px;
    }
    #tables-strip::-webkit-scrollbar-thumb {
        background: var(--s-border);
        border-radius: 4px;
    }
    #tables-strip .pos-table-btn {
        flex-shrink: 0;
    }
}
.pos-cat-tab {
    flex-shrink: 0;
    padding: 10px 18px;
    border: 1.5px solid var(--s-border);
    border-radius: 12px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    background: var(--s-surface);
    color: var(--s-text-2);
    transition: all 0.2s;
}
.pos-cat-tab:hover {
    border-color: var(--s-accent);
    background: var(--s-surface-2);
    color: var(--s-text);
}
.pos-cat-tab.active {
    border-color: var(--s-accent) !important;
    background: var(--s-accent-light) !important;
    color: var(--s-accent-dark) !important;
}
.pos-zone-tab {
    flex-shrink: 0;
    padding: 6px 14px;
    border: 1.5px solid var(--s-border);
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    background: var(--s-surface);
    color: var(--s-text-2);
    transition: all 0.2s;
}
.pos-zone-tab:hover {
    border-color: var(--s-accent);
    background: var(--s-surface-2);
    color: var(--s-text);
}
.pos-zone-tab.active {
    border-color: var(--s-primary) !important;
    background: var(--s-primary) !important;
    color: #fff !important;
    font-weight: 700;
}
</style>

@if(gs('google_maps_api'))
@push('script-lib')
<script>
function initDeliveryAutocomplete() {
    var a = document.getElementById('delivery-addr');
    if (!a || !window.google || !google.maps || !google.maps.places) return;
    new google.maps.places.Autocomplete(a, {componentRestrictions: {country: 'pe'}})
    .addListener('place_changed', function() {
        var p = this.getPlace();
        if (p && p.geometry) {
            dLat = p.geometry.location.lat();
            dLng = p.geometry.location.lng();
            document.getElementById('delivery-lat').value = dLat;
            document.getElementById('delivery-lng').value = dLng;
            fetch('/seller/pos/fee-estimate?lat=' + dLat + '&lng=' + dLng)
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    deliveryFee = d.delivery_fee || 0;
                    document.getElementById('fee-info').textContent = 'Envío: S/ ' + deliveryFee.toFixed(2) + ' — ' + (d.distance_km || 0).toFixed(1) + ' km';
                    updatePanel();
                });
        }
    });
    a.addEventListener('input', function() {
        dLat = null;
        dLng = null;
        document.getElementById('delivery-lat').value = '';
        document.getElementById('delivery-lng').value = '';
        document.getElementById('fee-info').textContent = '';
        deliveryFee = 0;
        updatePanel();
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places&callback=initDeliveryAutocomplete" async></script>
@endpush
@endif

@push('script')
<script>
var ALL_PRODUCTS = {!! $productsJson ?: '{}' !!};
var cart = [], orderType = '{{ $store->isRestaurant() ? 'dine_in' : 'takeaway' }}', selTable = null, deliveryFee = 0, dLat = null, dLng = null;
var activeOrderId = null, activeOrderItems = [];

// Auto-seleccionar mesa por URL param
(function(){
    var params = new URLSearchParams(window.location.search);
    var tid = params.get('table');
    if(tid){
        selTable = tid;
        setTimeout(function(){ selectTable(tid); }, 300);
    }
})();

function filterPosZone(zone, btn) {
    document.querySelectorAll('.pos-zone-tab').forEach(function(b) {
        b.classList.remove('active');
    });
    btn.classList.add('active');

    var tableBtns = document.querySelectorAll('#tables-strip .pos-table-btn:not(#table-direct)');
    tableBtns.forEach(function(el) {
        if (zone === 'all' || el.dataset.zone === zone) {
            el.style.display = '';
        } else {
            el.style.display = 'none';
        }
    });
}

function selectTable(id){
    selTable = id;
    activeOrderId = null;
    activeOrderItems = [];
    cart = [];
    
    document.querySelectorAll('.pos-table-btn').forEach(function(e){ e.classList.remove('active'); });
    var el = id ? document.getElementById('table-' + id) : document.getElementById('table-direct');
    if(el) el.classList.add('active');
    document.getElementById('table-label').textContent = id ? '— ' + (el.querySelector('span')?.textContent || 'Mesa ' + id) : '';

    var actionsContainer = document.getElementById('table-actions-container');
    if (actionsContainer) {
        if (id) {
            actionsContainer.style.display = 'flex';
            document.getElementById('active-table-name-action').textContent = el.querySelector('span')?.textContent || 'Mesa';

            var isGrouped = el.classList.contains('grouped');
            var btnGroup = document.getElementById('btn-ungroup-group');
            var btnUngroup = document.getElementById('btn-ungroup');

            if (isGrouped) {
                if (btnGroup) btnGroup.style.display = 'block';
                if (btnUngroup) btnUngroup.style.display = 'none';
            } else {
                if (btnGroup) btnGroup.style.display = 'none';
                if (btnUngroup) btnUngroup.style.display = 'none';
            }
        } else {
            actionsContainer.style.display = 'none';
        }
    }

    if (document.getElementById('cust-name')) document.getElementById('cust-name').value = '';
    if (document.getElementById('cust-phone')) document.getElementById('cust-phone').value = '';
    if (document.getElementById('sunat-numdoc')) document.getElementById('sunat-numdoc').value = '';
    if (document.getElementById('sunat-result')) document.getElementById('sunat-result').innerHTML = '';
    if (document.getElementById('kitchen-notes')) document.getElementById('kitchen-notes').value = '';
    if (document.getElementById('cust-staff-id')) document.getElementById('cust-staff-id').value = '';
    
    if(id && el && el.classList.contains('busy')){
        fetch('/seller/pos/table/' + id + '/active-order')
        .then(function(r){ return r.json(); })
        .then(function(data){
            if(data.status && data.order){
                activeOrderId = data.order.id;
                activeOrderItems = data.order.items || [];
                if (document.getElementById('cust-name')) document.getElementById('cust-name').value = data.order.customer_name || '';
                if (document.getElementById('cust-phone')) document.getElementById('cust-phone').value = data.order.customer_phone || '';
                if (document.getElementById('sunat-numdoc')) document.getElementById('sunat-numdoc').value = data.order.customer_doc || '';
                if (data.order.customer_doc && document.getElementById('sunat-tpdoc')) {
                    document.getElementById('sunat-tpdoc').value = data.order.customer_doc_type || '1';
                }
                if (document.getElementById('cust-staff-id') && data.order.pos_staff_id) {
                    document.getElementById('cust-staff-id').value = data.order.pos_staff_id;
                }
                renderItems();
            } else {
                renderItems();
            }
        }).catch(function() {
            renderItems();
        });
    } else {
        renderItems();
    }
}

function setOrderType(t){
    orderType = t;
    var allTypes = ['dine_in','takeaway','delivery','courtesy','daz','llama','rappi','pedidosya','lizto_delivery'];
    allTypes.forEach(function(x){
        var el = document.getElementById('type-' + x);
        if(el) el.classList.toggle('active', x === t);
    });
    var isExternal = ['daz','llama','rappi','pedidosya','lizto_delivery'].includes(t);
    if (document.getElementById('delivery-fields')) {
        document.getElementById('delivery-fields').style.display = (t === 'delivery' || isExternal) ? 'block' : 'none';
    }
    var ts = document.getElementById('tables-strip');
    if (ts) ts.style.display = (t === 'dine_in' || t === 'courtesy') ? 'flex' : 'none';
    updatePanel();
}

function addOrIncrementCartItem(item) {
    var existingIndex = -1;
    for (var i = 0; i < cart.length; i++) {
        if (cart[i].product_id === item.product_id && 
            cart[i].name === item.name && 
            (cart[i].notes || '') === (item.notes || '') && 
            cart[i].is_takeaway === item.is_takeaway &&
            (cart[i].is_courtesy || false) === (item.is_courtesy || false) &&
            (cart[i].has_tupper || false) === (item.has_tupper || false)) {
            existingIndex = i;
            break;
        }
    }
    if (existingIndex > -1) {
        cart[existingIndex].qty += item.qty || 1;
    } else {
        cart.push(item);
    }
    renderItems();
}

function addItem(pid){
    var p = ALL_PRODUCTS[pid];
    if(!p) return;
    if(!p.variations.length && !p.addons.length){
        addOrIncrementCartItem({product_id: pid, name: p.name, price: (orderType === 'courtesy') ? 0 : p.price, qty: 1, notes: '', is_takeaway: (orderType === 'takeaway'), is_courtesy: (orderType === 'courtesy'), has_tupper: false});
        return;
    }
    openModal(p);
}

function removeItem(i){
    cart.splice(i, 1);
    renderItems();
}

function updateQty(i, delta){
    if(cart[i]){
        cart[i].qty += delta;
        if(cart[i].qty <= 0){
            cart.splice(i, 1);
        }
        renderItems();
    }
}

function toggleCartItemTakeaway(i){
    cart[i].is_takeaway = !cart[i].is_takeaway;
    renderItems();
}

function toggleCartItemCourtesy(i){
    cart[i].is_courtesy = !cart[i].is_courtesy;
    if(cart[i].is_courtesy){
        cart[i].is_takeaway = false;
    }
    renderItems();
}

function toggleCartItemTupper(i){
    cart[i].has_tupper = !cart[i].has_tupper;
    renderItems();
}

function updateCartItemNote(i, val){
    cart[i].notes = val;
}

function renderItems(){
    var c = document.getElementById('order-items');
    if (!c) return;
    
    var newItemsCount = cart.reduce(function(t, item){ return t + item.qty; }, 0);
    var existingItemsCount = activeOrderId ? activeOrderItems.reduce(function(t, item){ return t + item.qty; }, 0) : 0;
    var totalCount = newItemsCount + existingItemsCount;
    if (document.getElementById('order-item-count')) {
        document.getElementById('order-item-count').textContent = totalCount + ' items';
    }
    if (document.getElementById('floating-cart-badge')) {
        document.getElementById('floating-cart-badge').textContent = totalCount;
    }
    
    var html = '';
    if(activeOrderId && activeOrderItems.length){
        html += '<div style="background:var(--s-surface-2); padding:6px 10px; border-radius:8px; margin-bottom:8px; border:1px solid var(--s-border); font-size:10px; color:var(--s-text-3); font-weight:700; display:flex; justify-content:space-between; align-items:center;">' +
                '<span>PRODUCTOS ENVIADOS A COCINA</span>' +
                '<span style="background:var(--s-warning); color:#fff; padding:1px 5px; border-radius:4px; font-size:9px; font-weight:800;">CONFIRMADO</span>' +
                '</div>';
        activeOrderItems.forEach(function(it){
            var statusLabels = {pending: 'Pendiente', preparing: 'En cocina', ready: 'Listo', done: 'Listo', delivered: 'Servido'};
            var statusClass = it.status === 'delivered' ? 's-badge-green' : (it.status === 'ready' || it.status === 'done' ? 's-badge-blue' : 's-badge-yellow');
            var takeawayBadge = it.is_takeaway ? ' <span style="font-size:8px; background:#f97316; color:#fff; padding:1px 4px; border-radius:3px; font-weight:800; margin-left:4px;"><i class="las la-shopping-bag"></i> LLEVAR</span>' : '';
            var noteHtml = it.notes ? '<div style="font-size:9.5px; color:var(--s-danger-text); margin-top:2px; font-style:italic;">Nota: ' + it.notes + '</div>' : '';
            html += '<div class="order-item-row" style="opacity: 0.75; background: var(--s-surface-2); padding: 6px 10px; border-radius: 6px; margin-bottom: 4px; border-bottom: none; display:block;">' +
                '<div style="display:flex; justify-content:space-between; align-items:center;">' +
                    '<div style="font-size:11px;"><b>' + it.qty + '×</b> ' + it.name + takeawayBadge + '</div>' +
                    '<div style="display:flex; align-items:center; gap:8px;">' +
                        '<span style="font-size:9px; font-weight:800; padding:1px 4px; border-radius:4px;" class="s-badge ' + statusClass + '">' + (statusLabels[it.status] || it.status) + '</span>' +
                        '<span style="font-weight:700;">S/ ' + (it.price * it.qty).toFixed(2) + '</span>' +
                    '</div>' +
                '</div>' +
                noteHtml +
                '</div>';
        });
        if(cart.length) {
            html += '<div style="background:var(--s-surface-2); padding:6px 10px; border-radius:8px; margin-top:12px; margin-bottom:8px; border:1px solid var(--s-border); font-size:10px; color:#e11d48; font-weight:800;">' +
                    'NUEVAS ADICIONES A AGREGAR' +
                    '</div>';
        }
    }
    
    if(!cart.length && !activeOrderItems.length){
        html = '<div style="text-align:center;color:var(--s-text-3);padding:48px 12px;font-size:12px">' +
            '<i class="las la-shopping-basket" style="font-size:32px;display:block;margin-bottom:8px;color:var(--s-border);"></i>' +
            'Selecciona productos de la lista' +
            '</div>';
    }else{
        cart.forEach(function(it, i){
            var takeawayBtnClass = it.is_takeaway ? 'background:#f97316; color:#fff; border-color:#f97316;' : 'background:var(--s-surface-2); border-color:var(--s-border); color:var(--s-text-2);';
            var courtesyBadge = it.is_courtesy ? ' <span style="font-size:9px;padding:1px 5px;border-radius:4px;font-weight:800;background:#a855f7;color:#fff;"><i class="las la-gift"></i> CORTESÍA</span>' : '';
            var itemPrice = it.is_courtesy ? 0 : ((it.price + (it.has_tupper ? 1.00 : 0)) * it.qty);
            html += '<div class="order-item-row" style="padding: 8px 6px; border-bottom:1px dashed var(--s-border); display:block;' + (it.is_courtesy ? 'background:rgba(168,85,247,0.04);' : '') + '">' +
                '<div style="display:flex; justify-content:space-between; align-items:center;">' +
                    '<div style="display:flex; align-items:center; gap:4px; flex-wrap:wrap; max-width:80%;">' +
                        '<button type="button" onclick="updateQty(' + i + ', -1)" style="width:24px; height:24px; border-radius:50%; border:1.5px solid var(--s-border); background:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:var(--s-text-2); padding:0; cursor:pointer; min-width:24px; min-height:24px; transition:all 0.15s;" onmouseover="this.style.borderColor=\'var(--s-accent)\'" onmouseout="this.style.borderColor=\'var(--s-border)\'">-</button>' +
                        '<span style="font-size:12px; font-weight:800; color:var(--s-text); min-width:14px; text-align:center;">' + it.qty + '</span>' +
                        '<button type="button" onclick="updateQty(' + i + ', 1)" style="width:24px; height:24px; border-radius:50%; border:1.5px solid var(--s-border); background:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:var(--s-text-2); padding:0; cursor:pointer; min-width:24px; min-height:24px; transition:all 0.15s; margin-right:4px;" onmouseover="this.style.borderColor=\'var(--s-accent)\'" onmouseout="this.style.borderColor=\'var(--s-border)\'">+</button>' +
                        '<span style="font-weight:600; color:var(--s-text);">' + it.name + '</span>' + courtesyBadge +
                        '<button onclick="toggleCartItemTakeaway(' + i + ')" style="padding: 1px 4px; font-size:9px; border-radius:4px; font-weight:800; cursor:pointer; margin-left:6px; border:1px solid; transition:0.15s; ' + takeawayBtnClass + '" title="Alternar Para Llevar">' +
                            '<i class="las la-shopping-bag"></i> Llevar' +
                        '</button>' +
                        '<button onclick="toggleCartItemCourtesy(' + i + ')" style="padding: 1px 4px; font-size:9px; border-radius:4px; font-weight:800; cursor:pointer; margin-left:2px; border:1px solid; transition:0.15s; ' + (it.is_courtesy ? 'background:#a855f7; color:#fff; border-color:#a855f7;' : 'background:var(--s-surface-2); border-color:var(--s-border); color:var(--s-text-2);') + '" title="Alternar Cortesía">' +
                            '<i class="las la-gift"></i> Cortesía' +
                        '</button>' +
                        '<button onclick="toggleCartItemTupper(' + i + ')" style="padding: 1px 4px; font-size:9px; border-radius:4px; font-weight:800; cursor:pointer; margin-left:2px; border:1px solid; transition:0.15s; ' + (it.has_tupper ? 'background:#10b981; color:#fff; border-color:#10b981;' : 'background:var(--s-surface-2); border-color:var(--s-border); color:var(--s-text-2);') + '" title="Alternar Tupper">' +
                            '<i class="las la-box"></i> Tupper' +
                        '</button>' +
                    '</div>' +
                    '<div style="display:flex; align-items:center; gap:6px;"><span style="' + (it.is_courtesy ? 'color:#a855f7;font-weight:700;' : '') + '">' + (it.is_courtesy ? 'S/ 0.00' : 'S/ ' + itemPrice.toFixed(2)) + '</span><button onclick="removeItem(' + i + ')" style="background:none; border:none; color:var(--s-danger); padding:4px; font-size:14px; cursor:pointer;">✕</button></div>' +
                '</div>' +
                '<div style="margin-top: 6px; display: flex; gap: 4px; align-items: center;">' +
                    '<input type="text" placeholder="Especificaciones / Nota del plato..." value="' + (it.notes || '') + '" oninput="updateCartItemNote(' + i + ', this.value)" style="flex: 1; font-size: 10.5px; padding: 4px 8px; border: 1.5px solid var(--s-border); border-radius: 8px; background: var(--s-surface-2); color: var(--s-text); font-style: italic;">' +
                '</div>' +
                '</div>';
        });
    }

    var isDeliveryType = ['delivery','daz','llama','rappi','pedidosya','lizto_delivery'].indexOf(orderType) !== -1;
    if (isDeliveryType && deliveryFee > 0) {
        html += '<div class="order-item-row" style="padding:8px 6px; border-bottom:none; display:block; background:var(--s-surface-2); border-radius:6px; margin-top:4px;">' +
            '<div style="display:flex; justify-content:space-between; align-items:center;">' +
                '<div style="display:flex; align-items:center; gap:4px; font-weight:600;">' +
                    '<i class="las la-motorcycle" style="color:var(--s-accent-dark);"></i> Costo de envío' +
                '</div>' +
                '<div><span style="font-weight:700;">S/ ' + deliveryFee.toFixed(2) + '</span></div>' +
            '</div>' +
        '</div>';
    }
    c.innerHTML = html;
    updatePanel();
}

function updatePanel(){
    var s = cart.reduce(function(t, i){ return t + (i.is_courtesy ? 0 : (i.price + (i.has_tupper ? 1.00 : 0)) * i.qty); }, 0);
    var existingTotal = activeOrderId ? activeOrderItems.reduce(function(t, i){ return t + (i.is_courtesy ? 0 : i.price * i.qty); }, 0) : 0;
    var isDeliveryType = ['delivery','daz','llama','rappi','pedidosya','lizto_delivery'].indexOf(orderType) !== -1;
    var t = s + existingTotal + (isDeliveryType ? deliveryFee : 0);
    if (document.getElementById('order-total')) {
        document.getElementById('order-total').textContent = 'S/ ' + t.toFixed(2);
    }
    var btn = document.getElementById('btn-submit');
    if(btn){
        if(activeOrderId){
            btn.innerHTML = '<i class="las la-plus-circle" style="font-size:16px;"></i> Agregar a Comanda';
            btn.style.background = '#f59e0b';
            btn.style.borderColor = '#f59e0b';
            btn.style.color = '#fff';
        } else {
            btn.innerHTML = '<i class="las la-paper-plane" style="font-size: 16px;"></i> Enviar Comanda';
            btn.style.background = '';
            btn.style.borderColor = '';
            btn.style.color = '';
        }
    }
}

function openModal(p){
    document.getElementById('pm-var-list').innerHTML = '';
    document.getElementById('pm-addon-list').innerHTML = '';
    if (document.getElementById('pm-note')) document.getElementById('pm-note').value = '';
    if (document.getElementById('pm-tupper')) document.getElementById('pm-tupper').checked = false;
    document.getElementById('p-modal').style.display = 'flex';
    document.getElementById('pm-name').textContent = p.name;
    document.getElementById('pm-price').textContent = 'S/ ' + p.price.toFixed(2);
    document.getElementById('pm-total').textContent = 'S/ ' + p.price.toFixed(2);
    window._pm = p;
    window._pmVar = null;
    window._pmAddons = [];
    var vl = document.getElementById('pm-var-list'), vc = document.getElementById('pm-var-section');
    if(p.variations.length){
        vc.style.display = 'block';
        p.variations.forEach(function(v){
            var lbl = document.createElement('label');
            lbl.className = 'p-var';
            lbl.innerHTML = '<span>' + v.name + '</span><span style="color:var(--s-accent-dark);font-weight:750;margin-left:auto">+S/ ' + v.price.toFixed(2) + '</span>';
            lbl.addEventListener('click', function(e){
                e.preventDefault();
                vl.querySelectorAll('.p-var').forEach(function(x){ x.classList.remove('selected'); });
                lbl.classList.add('selected');
                window._pmVar = {name: v.name, price: v.price};
                pmUpd();
            });
            vl.appendChild(lbl);
        });
    }else{
        vc.style.display = 'none';
        window._pmVar = null;
    }
    var al = document.getElementById('pm-addon-list'), ac = document.getElementById('pm-addon-section');
    if(p.addons.length){
        ac.style.display = 'block';
        p.addons.forEach(function(a){
            var lbl = document.createElement('label');
            lbl.className = 'p-addon';
            lbl.innerHTML = '<span>' + a.name + '</span><span style="color:var(--s-accent-dark);font-weight:750;margin-left:auto">+S/ ' + a.price.toFixed(2) + '</span>';
            lbl.addEventListener('click', function(e){
                e.preventDefault();
                lbl.classList.toggle('selected');
                if(lbl.classList.contains('selected')){
                    window._pmAddons.push({name: a.name, price: a.price});
                }else{
                    window._pmAddons = window._pmAddons.filter(function(x){ return x.name !== a.name; });
                }
                pmUpd();
            });
            al.appendChild(lbl);
        });
    }else{
        ac.style.display = 'none';
        window._pmAddons = [];
    }
}

function pmUpd(){
    var t = window._pm.price;
    if(window._pmVar) t += window._pmVar.price;
    window._pmAddons.forEach(function(a){ t += a.price; });
    if (document.getElementById('pm-tupper') && document.getElementById('pm-tupper').checked) {
        t += 1.00;
    }
    document.getElementById('pm-total').textContent = 'S/ ' + t.toFixed(2);
}

function pmConfirm(){
    var p = window._pm, t = p.price, n = p.name;
    if(window._pmVar){
        t += window._pmVar.price;
        n += ' (' + window._pmVar.name + ')';
    }
    window._pmAddons.forEach(function(a){
        t += a.price;
        n += ' +' + a.name;
    });
    var noteVal = document.getElementById('pm-note') ? document.getElementById('pm-note').value.trim() : '';
    var finalPrice = (orderType === 'courtesy') ? 0 : t;
    var hasTupper = document.getElementById('pm-tupper') ? document.getElementById('pm-tupper').checked : false;
    // Note: finalPrice has already included the Tupper fee if selected in the modal,
    // but we subtract it here because addOrIncrementCartItem expects the base price in 'price'
    // and calculates/renders the Tupper fee dynamically.
    if (hasTupper && orderType !== 'courtesy') {
        finalPrice -= 1.00;
    }
    addOrIncrementCartItem({
        product_id: p.id,
        name: n,
        price: finalPrice,
        qty: 1,
        notes: noteVal,
        is_takeaway: (orderType === 'takeaway'),
        is_courtesy: (orderType === 'courtesy'),
        has_tupper: hasTupper
    });
    pmClose();
}

function pmClose(){
    document.getElementById('p-modal').style.display = 'none';
}

function filterTables(area){
    document.querySelectorAll('.pos-table-btn[data-area]').forEach(function(t){
        t.style.display = (area === 'all' || t.dataset.area === area) ? '' : 'none';
    });
}

function switchCategory(catId){
    var searchInput = document.getElementById('product-search');
    if (searchInput) searchInput.value = '';
    filterProducts('');

    document.querySelectorAll('.pos-cat-tab').forEach(function(btn){
        btn.classList.remove('active');
    });
    var activeTab = (catId === 'all') ? document.getElementById('cat-tab-all') : document.getElementById('cat-tab-' + catId);
    if(activeTab) activeTab.classList.add('active');

    document.querySelectorAll('.category-section').forEach(function(section){
        section.style.display = (catId === 'all' || section.id === 'cat-section-' + catId) ? '' : 'none';
    });
}

function filterProducts(query){
    query = query.toLowerCase().trim();
    var clearBtn = document.getElementById('clear-search');
    clearBtn.style.display = query ? 'block' : 'none';

    if (query) {
        document.querySelectorAll('.pos-cat-tab').forEach(function(btn){
            btn.classList.remove('active');
        });
        var activeTab = document.getElementById('cat-tab-all');
        if(activeTab) activeTab.classList.add('active');
    }

    document.querySelectorAll('.pos-prod').forEach(function(prod) {
        var name = (prod.querySelector('b')?.textContent || '').toLowerCase();
        var price = (prod.querySelector('.pos-prod-info span')?.textContent || '').toLowerCase();
        var pid = prod.getAttribute('onclick')?.match(/\d+/)?.[0] || '';
        var p = ALL_PRODUCTS[pid];
        var barcode = p ? (p.barcode || '').toLowerCase() : '';
        var match = !query || name.includes(query) || price.includes(query) || barcode.includes(query);
        prod.style.display = match ? '' : 'none';
    });

    document.querySelectorAll('.category-section').forEach(function(section) {
        if (!query) { section.style.display = ''; return; }
        var visible = false;
        section.querySelectorAll('.pos-prod').forEach(function(p) { if (p.style.display !== 'none') visible = true; });
        section.style.display = visible ? '' : 'none';
    });
}

// Barcode scanner detection: rapid input + Enter
(function(){
    var searchInput = document.getElementById('product-search');
    if (!searchInput) return;
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            var val = searchInput.value.trim();
            if (!val) return;
            for (var pid in ALL_PRODUCTS) {
                if (ALL_PRODUCTS[pid].barcode && ALL_PRODUCTS[pid].barcode === val) {
                    addItem(parseInt(pid));
                    searchInput.value = '';
                    filterProducts('');
                    e.preventDefault();
                    return;
                }
            }
        }
    });
})();

function submitOrder(){
    if(!cart.length){ alert('Agrega productos'); return; }
    var btn = document.getElementById('btn-submit');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="las la-spinner la-spin"></i> Enviando...'; }

    var isCourtesy = orderType === 'courtesy';
    var mappedCart = cart.map(function(it) {
        var finalUnitPrice = it.is_courtesy ? 0 : (it.price + (it.has_tupper ? 1.00 : 0));
        var finalName = it.name;
        if (it.has_tupper) {
            finalName += ' (Con Tupper)';
        }
        return {
            product_id: it.product_id,
            name: finalName,
            price: finalUnitPrice,
            qty: it.qty,
            quantity: it.qty,
            notes: it.notes,
            is_takeaway: it.is_takeaway,
            is_courtesy: it.is_courtesy,
            has_tupper: it.has_tupper
        };
    });

    var body = {
        order_id: activeOrderId || null,
        pos_staff_id: document.getElementById('cust-staff-id')?.value || null,
        table_id: selTable || null,
        order_type: orderType,
        customer_name: document.getElementById('cust-name')?.value || null,
        customer_phone: document.getElementById('cust-phone')?.value || null,
        delivery_address: document.getElementById('delivery-addr')?.value || null,
        delivery_lat: dLat || null,
        delivery_lng: dLng || null,
        kitchen_notes: document.getElementById('kitchen-notes')?.value || null,
        courtesy: isCourtesy ? 1 : null,
        items: JSON.stringify(mappedCart)
    };

    fetch('{{ route("seller.pos.order.create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(body)
    })
    .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
    .then(function(res) {
        if (res.ok && res.data.success) {
            cart = [];
            renderItems();
            if (activeOrderId) { activeOrderId = null; }
            showToast(res.data.message || 'Pedido enviado', 'success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showToast(res.data.message || 'Error al crear pedido', 'error');
        }
    })
    .catch(function(e) {
        showToast('Error de red: ' + e.message, 'error');
    })
    .finally(function() {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="las la-paper-plane"></i> Enviar a Cocina'; }
    });
}

function showToast(msg, type) {
    var toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:14px 24px;border-radius:12px;font-size:14px;font-weight:600;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.2);animation:fadeIn .3s;max-width:400px;';
    toast.style.background = type === 'success' ? '#16a34a' : '#ef4444';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 4000);
}

function searchSunat(){
    var num = document.getElementById('sunat-numdoc').value.trim(), tp = document.getElementById('sunat-tpdoc').value;
    if(!num){ alert('Ingresa un número de documento'); return; }
    var r = document.getElementById('sunat-result');
    r.innerHTML = '<span style="color:var(--s-accent)"><i class="las la-spinner la-spin"></i> Buscando...</span>';
    fetch('/seller/sunat-lookup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({numdoc: num, tpdoc: tp})
    })
    .then(function(x){ return x.json(); })
    .then(function(d){
        if(d.status && d.nombre){
            document.getElementById('cust-name').value = d.nombre;
            document.getElementById('cust-phone').focus();
            r.innerHTML = '<span style="color:var(--s-success)">✓ ' + d.nombre + '</span>';
        }else{
            r.innerHTML = '<span style="color:var(--s-danger)">' + (d.result || 'No encontrado') + '</span>';
        }
    })
    .catch(function(){ r.innerHTML = '<span style="color:var(--s-danger)">Error en la búsqueda</span>'; });
}

var currentOrderTotal = 0;
var currentOrderItems = [];
var productSplitStarted = { charge: false };
@php
$paymentData = $pendingPayment->mapWithKeys(function($order) {
    return [$order->id => $order->items->map(function($item) {
        $quantity = max(1, (int) $item->quantity);
        return [
            'id' => $item->id,
            'name' => $item->product_name ?: ($item->product?->name ?? 'Producto'),
            'quantity' => $quantity,
            'unit_price' => round(((float) $item->total_price) / $quantity, 2),
        ];
    })->values()];
});
@endphp
var orderPaymentItems = @json($paymentData);

function fillPayment(method, fillType) {
    let inputs = document.querySelectorAll('.split-charge-input');
    
    if (fillType === 'all') {
        inputs.forEach(input => {
            if (input.getAttribute('data-method') === method) {
                input.value = currentOrderTotal.toFixed(2);
            } else {
                input.value = '0.00';
            }
        });
    } else if (fillType === 'rest') {
        let sumOfOthers = 0;
        inputs.forEach(input => {
            if (input.getAttribute('data-method') !== method) {
                let val = parseFloat(input.value);
                if (!isNaN(val) && val > 0) {
                    sumOfOthers += val;
                }
            }
        });
        let rest = Math.max(0, currentOrderTotal - sumOfOthers);
        inputs.forEach(input => {
            if (input.getAttribute('data-method') === method) {
                input.value = rest.toFixed(2);
            }
        });
    }
    updateSplitTotal('charge');
}

function applyEqualSplit(type) {
    let peopleInput = document.getElementById(type + '-split-people');
    let methodSelect = document.getElementById(type + '-split-method');
    let preview = document.getElementById(type + '-split-preview');
    if (!peopleInput || !methodSelect) return;

    let people = Math.max(2, parseInt(peopleInput.value || '2', 10));
    let method = methodSelect.value || 'cash';
    let perPerson = Math.floor((currentOrderTotal / people) * 100) / 100;
    let remainder = +(currentOrderTotal - (perPerson * people)).toFixed(2);

    showPaymentMethod(type, method);
    let input = document.getElementById(type + '-input-' + method);
    if (input) {
        input.value = perPerson.toFixed(2);
    }
    if (preview) {
        preview.textContent = 'S/ ' + perPerson.toFixed(2) + ' por parte' + (remainder > 0 ? ' (+ S/ ' + remainder.toFixed(2) + ' de ajuste)' : '');
    }
    updateSplitTotal(type);
}

function renderProductSplit(type) {
    let container = document.getElementById(type + '-product-split-items');
    if (!container) return;
    if (!currentOrderItems.length) {
        container.innerHTML = '<div style="font-size:12px;color:var(--s-text-muted);padding:6px 0;">Esta orden no tiene productos detallados para dividir.</div>';
        return;
    }

    container.innerHTML = currentOrderItems.map(item => {
        let max = parseInt(item.quantity || 1, 10);
        let unit = parseFloat(item.unit_price || 0);
        return '<div style="display:flex;align-items:center;gap:8px;justify-content:space-between;border-bottom:1px solid var(--s-border);padding-bottom:6px;">'
            + '<div style="min-width:0;flex:1;"><div style="font-size:12px;font-weight:800;color:var(--s-text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(item.name) + '</div>'
            + '<div style="font-size:11px;color:var(--s-text-muted);">S/ ' + unit.toFixed(2) + ' c/u · disponible: ' + max + '</div></div>'
            + '<input type="number" min="0" max="' + max + '" step="1" value="0" data-unit="' + unit.toFixed(2) + '" class="s-input product-split-' + type + '-qty" style="width:64px;height:30px;text-align:center;font-size:12px;font-weight:800;">'
            + '</div>';
    }).join('');
    container.querySelectorAll('.product-split-' + type + '-qty').forEach(input => {
        input.addEventListener('input', function() { updateProductSplitTotal(type); });
    });
    updateProductSplitTotal(type);
}

function updateProductSplitTotal(type) {
    let inputs = document.querySelectorAll('.product-split-' + type + '-qty');
    let total = 0;
    inputs.forEach(input => {
        let qty = Math.max(0, parseInt(input.value || '0', 10));
        let max = Math.max(0, parseInt(input.max || '0', 10));
        if (qty > max) {
            qty = max;
            input.value = max;
        }
        total += qty * parseFloat(input.dataset.unit || '0');
    });
    let label = document.getElementById(type + '-product-split-total');
    if (label) label.textContent = 'S/ ' + total.toFixed(2);
    return total;
}

function applyProductSplit(type) {
    let methodSelect = document.getElementById(type + '-product-method');
    let method = methodSelect ? methodSelect.value : 'cash';
    let total = updateProductSplitTotal(type);
    if (total <= 0) return;

    if (!productSplitStarted[type]) {
        document.querySelectorAll('.split-' + type + '-input').forEach(input => { input.value = '0.00'; });
        productSplitStarted[type] = true;
    }

    showPaymentMethod(type, method);
    let input = document.getElementById(type + '-input-' + method);
    if (input) {
        let existing = parseFloat(input.value || '0');
        input.value = ((isNaN(existing) ? 0 : existing) + total).toFixed(2);
    }
    document.querySelectorAll('.product-split-' + type + '-qty').forEach(input => { input.value = '0'; });
    updateProductSplitTotal(type);
    updateSplitTotal(type);
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(char) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
}

function showPaymentMethod(type, method) {
    let row = document.getElementById('payment-row-' + type + '-' + method);
    if (!row) return;
    row.style.display = 'flex';
    
    // Auto fill remaining balance if not cash and it's being shown
    if (method !== 'cash') {
        let inputs = document.querySelectorAll('.split-' + type + '-input');
        let sumOfOthers = 0;
        inputs.forEach(input => {
            if (input.getAttribute('data-method') !== method) {
                let val = parseFloat(input.value);
                if (!isNaN(val) && val > 0) {
                    sumOfOthers += val;
                }
            }
        });
        let rest = Math.max(0, currentOrderTotal - sumOfOthers);
        let input = document.getElementById(type + '-input-' + method);
        if (input) {
            input.value = rest.toFixed(2);
        }
    }
    
    updatePaymentDropdown(type);
    updateSplitTotal(type);
}

function hidePaymentMethod(type, method) {
    let row = document.getElementById('payment-row-' + type + '-' + method);
    if (!row) return;
    row.style.display = 'none';
    
    let input = document.getElementById(type + '-input-' + method);
    if (input) {
        input.value = '0.00';
    }
    
    updatePaymentDropdown(type);
    updateSplitTotal(type);
}

function updatePaymentDropdown(type) {
    let select = document.getElementById('add-payment-method-' + type);
    if (!select) return;
    
    for (let option of select.options) {
        if (!option.value) continue;
        let row = document.getElementById('payment-row-' + type + '-' + option.value);
        if (row && row.style.display !== 'none') {
            option.disabled = true;
            option.style.display = 'none';
        } else {
            option.disabled = false;
            option.style.display = 'block';
        }
    }
}

function openChargeModal(id, orderNo, total, customer, table){
    currentOrderTotal = parseFloat(total);
    currentOrderItems = orderPaymentItems[id] || [];
    productSplitStarted.charge = false;
    document.getElementById('charge-order-no').textContent = '#' + orderNo;
    document.getElementById('charge-table').textContent = table || customer || '—';
    document.getElementById('charge-total').textContent = 'S/ ' + total.toFixed(2);
    document.getElementById('charge-form').action = '{{ route('seller.pos.order.pay', '__ID__') }}'.replace('__ID__', id);
    
    if(orderType === 'courtesy'){
        let inputs = document.querySelectorAll('.split-charge-input');
        inputs.forEach(input => { input.value = '0.00'; });
        showPaymentMethod('charge', 'courtesy');
        ['cash', 'card', 'yape', 'plin', 'transfer'].forEach(m => hidePaymentMethod('charge', m));
    }else{
        let inputs = document.querySelectorAll('.split-charge-input');
        inputs.forEach(input => {
            if(input.getAttribute('data-method') === 'cash') {
                input.value = currentOrderTotal.toFixed(2);
            } else {
                input.value = '0.00';
            }
        });
        showPaymentMethod('charge', 'cash');
        ['card', 'yape', 'plin', 'transfer', 'courtesy'].forEach(m => hidePaymentMethod('charge', m));
    }
    
    updateSplitTotal('charge');
    renderProductSplit('charge');
    const detailedOption = document.querySelector('input[name="detail_mode"][value="detailed"]');
    if (detailedOption) detailedOption.checked = true;
    toggleChargeConsumptionDescription();
    onChargeSeriesChange();
    document.getElementById('charge-modal').style.display = 'flex';
}

function onChargeSeriesChange() {
    const seriesSelect = document.getElementById('charge-series');
    if (!seriesSelect) return;
    const selectedOption = seriesSelect.options[seriesSelect.selectedIndex];
    const docCode = selectedOption ? (selectedOption.getAttribute('data-code') || '') : '';
    const tipoDocSelect = document.getElementById('charge-tipo-doc');
    const numDocInput = document.getElementById('charge-num-doc');
    if (!tipoDocSelect || !numDocInput) return;

    if (docCode === '01') {
        tipoDocSelect.value = '6';
        numDocInput.placeholder = 'N° RUC cliente (11 dígitos - obligatorio)';
        numDocInput.required = true;
    } else if (docCode === '03') {
        tipoDocSelect.value = '1';
        numDocInput.placeholder = 'N° DNI cliente (8 dígitos - opcional)';
        numDocInput.required = false;
    } else {
        tipoDocSelect.value = '1';
        numDocInput.placeholder = 'N° Documento cliente (opcional)';
        numDocInput.required = false;
    }
    clearChargeDocResult();
}

function closeChargeModal(){
    document.getElementById('charge-modal').style.display = 'none';
    clearChargeDocResult();
}

function toggleChargeConsumptionDescription() {
    const mode = document.querySelector('input[name="detail_mode"]:checked')?.value;
    const wrap = document.getElementById('charge-consumption-description-wrap');
    const input = document.getElementById('charge-consumption-description');
    if (!wrap || !input) return;
    const isConsumption = mode === 'consumption';
    wrap.style.display = isConsumption ? 'block' : 'none';
    input.required = isConsumption;
    if (isConsumption && !input.value) input.value = 'Consumo';
}

// ── Charge modal: document auto-search ──
let chargeDocSearchTimer = null;

function clearChargeDocResult() {
    var r = document.getElementById('charge-doc-result');
    if (r) { r.style.display = 'none'; r.innerHTML = ''; }
    var b = document.getElementById('charge-client-badge');
    if (b) b.style.display = 'none';
}

function selectChargeClient(name) {
    document.getElementById('charge-doc-result').style.display = 'none';
    document.getElementById('charge-doc-result').innerHTML = '';
    var b = document.getElementById('charge-client-badge');
    document.getElementById('charge-client-badge-name').textContent = name;
    b.style.display = 'flex';
}

function searchChargeDoc() {
    var tipo = document.getElementById('charge-tipo-doc').value;
    var num = document.getElementById('charge-num-doc').value.trim();
    var r = document.getElementById('charge-doc-result');

    var minLen = tipo === '1' ? 8 : 11;
    if (num.length < minLen) { r.style.display = 'none'; return; }

    r.style.display = 'block';
    r.innerHTML = '<span style="color:var(--s-text-muted);">Buscando...</span>';

    fetch('{{ route("seller.sunat") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ numdoc: num, tpdoc: tipo })
    })
    .then(function(x) { return x.json(); })
    .then(function(d) {
        if (d.status && d.nombre) {
            r.innerHTML = '<div onclick="selectChargeClient(\'' + d.nombre.replace(/'/g, "\\'") + '\')" onmouseover="this.style.background=\'rgba(34,197,94,0.2)\'" onmouseout="this.style.background=\'rgba(34,197,94,0.1)\'" style="cursor:pointer; background:rgba(34,197,94,0.1); color:#15803d; border:1px solid rgba(34,197,94,0.3); border-radius:6px; padding:8px 12px; font-weight:700; display:flex; justify-content:space-between; align-items:center; transition:background 0.2s;"><span>✓ ' + d.nombre + '</span><span style="font-size:10px; background:#22c55e; color:#fff; padding:2px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px;">Seleccionar</span></div>';
        } else {
            r.innerHTML = '<div style="color:var(--s-danger-text); padding:4px 6px;">✗ ' + (d.result || 'No encontrado') + '</div>';
        }
    })
    .catch(function() {
        r.innerHTML = '<div style="color:var(--s-danger-text); padding:4px 6px;">✗ Error de conexión</div>';
    });
}

function updateSplitTotal(type) {
    let inputs = document.querySelectorAll('.split-' + type + '-input');
    let sum = 0;
    inputs.forEach(input => {
        let val = parseFloat(input.value);
        if(!isNaN(val) && val > 0) {
            sum += val;
        }
    });
    
    document.getElementById(type + '-entered-display').innerText = sum.toFixed(2);
    
    let alertDiv = document.getElementById(type + '-validation-alert');
    let submitBtn = document.getElementById(type + '-submit-btn');
    
    let diff = Math.abs(sum - currentOrderTotal);
    if(diff > 0.01) {
        alertDiv.style.display = 'block';
        alertDiv.innerText = 'El monto total ingresado (S/ ' + sum.toFixed(2) + ') debe coincidir exactamente con el total de la cuenta (S/ ' + currentOrderTotal.toFixed(2) + ')';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
    } else {
        alertDiv.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
    }
}

function openTransferModal() {
    if (!selTable) return;
    var el = document.getElementById('table-' + selTable);
    var tableName = el ? el.querySelector('span')?.textContent : 'Mesa';
    document.getElementById('transfer-from-id').value = selTable;
    document.getElementById('transfer-from-name').textContent = tableName;
    document.getElementById('transfer-modal').style.display = 'flex';
}
function closeTransferModal() {
    document.getElementById('transfer-modal').style.display = 'none';
}

function openGroupModal() {
    if (!selTable) return;
    var el = document.getElementById('table-' + selTable);
    var tableName = el ? el.querySelector('span')?.textContent : 'Mesa';
    document.getElementById('group-primary-id').value = selTable;
    document.getElementById('group-primary-name').textContent = tableName;

    document.querySelectorAll('#group-checkbox-list label').forEach(function(label){
        var cb = label.querySelector('input[type=checkbox]');
        if (cb && cb.value == selTable) {
            label.style.display = 'none';
            cb.checked = false;
        } else {
            label.style.display = 'flex';
        }
    });

    document.getElementById('group-modal').style.display = 'flex';
}
function closeGroupModal() {
    document.getElementById('group-modal').style.display = 'none';
}

function submitUngroup() {
    if (!selTable) return;
    if (confirm('¿Desagrupar esta mesa del grupo?')) {
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = '{{ route('seller.pos.table.ungroup') }}';
        f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
            '<input type="hidden" name="table_id" value="' + selTable + '">';
        document.body.appendChild(f);
        f.submit();
    }
}

function submitUngroupAll() {
    if (!selTable) return;
    var el = document.getElementById('table-' + selTable);
    var tableName = el ? el.querySelector('span')?.textContent : 'Mesa';
    if (confirm('¿Desagrupar todo el grupo de ' + tableName + '? Todas las mesas quedarán libres.')) {
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = '{{ route('seller.pos.table.ungroup-all') }}';
        f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
            '<input type="hidden" name="table_id" value="' + selTable + '">';
        document.body.appendChild(f);
        f.submit();
    }
}
function toggleComandaDrawer() {
    var drawer = document.querySelector('.pos-right-sticky');
    var backdrop = document.getElementById('comanda-backdrop');
    if (drawer) drawer.classList.toggle('open');
    if (backdrop) backdrop.classList.toggle('open');
}

// ── Charge modal: doc search on input ──
document.addEventListener('DOMContentLoaded', function() {
    var chargeNumInput = document.getElementById('charge-num-doc');
    if (chargeNumInput) {
        chargeNumInput.addEventListener('input', function() {
            clearTimeout(chargeDocSearchTimer);
            chargeDocSearchTimer = setTimeout(searchChargeDoc, 400);
        });
    }

    // Attach Autocomplete to POS delivery-addr
    var delInput = document.getElementById('delivery-addr');
    if (delInput && window.google && google.maps && google.maps.places) {
        var posAc = new google.maps.places.Autocomplete(delInput, {
            componentRestrictions: { country: 'pe' },
            fields: ['formatted_address', 'geometry', 'name']
        });
        posAc.addListener('place_changed', function() {
            var place = posAc.getPlace();
            if (place.geometry && place.geometry.location) {
                document.getElementById('delivery-lat').value = place.geometry.location.lat();
                document.getElementById('delivery-lng').value = place.geometry.location.lng();
                if (typeof calculateDeliveryFee === 'function') calculateDeliveryFee();
            }
        });
    }
});

// ════════════════════════════════
//  POS MAP SELECTOR MODAL
// ════════════════════════════════
var posMap = null;
var posMarker = null;
var posSearchAutocomplete = null;
var posTempLat = null;
var posTempLng = null;
var posTempAddr = '';

window.openPosMapModal = function() {
    var modal = document.getElementById('pos-map-modal');
    if (!modal) return;
    modal.style.display = 'flex';

    var centerLat = parseFloat(document.getElementById('delivery-lat').value) || {{ $store->latitude ?? -12.046374 }};
    var centerLng = parseFloat(document.getElementById('delivery-lng').value) || {{ $store->longitude ?? -77.042793 }};

    setTimeout(function() {
        if (!posMap && window.google && google.maps) {
            var mapEl = document.getElementById('pos-selector-map');
            posMap = new google.maps.Map(mapEl, {
                center: { lat: centerLat, lng: centerLng },
                zoom: 16,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false
            });

            posMarker = new google.maps.Marker({
                position: { lat: centerLat, lng: centerLng },
                map: posMap,
                draggable: true,
                title: 'Arrastra el marcador al punto de entrega'
            });

            posTempLat = centerLat;
            posTempLng = centerLng;

            posMap.addListener('click', function(e) {
                posMarker.setPosition(e.latLng);
                posTempLat = e.latLng.lat();
                posTempLng = e.latLng.lng();
                reverseGeocodePos(posTempLat, posTempLng);
            });

            posMarker.addListener('dragend', function() {
                var pos = posMarker.getPosition();
                posTempLat = pos.lat();
                posTempLng = pos.lng();
                reverseGeocodePos(posTempLat, posTempLng);
            });

            // SearchBox inside POS Map Modal
            var searchInput = document.getElementById('pos-modal-search-input');
            if (searchInput && google.maps.places) {
                posSearchAutocomplete = new google.maps.places.Autocomplete(searchInput, {
                    componentRestrictions: { country: 'pe' },
                    fields: ['formatted_address', 'geometry', 'name']
                });

                posSearchAutocomplete.addListener('place_changed', function() {
                    var place = posSearchAutocomplete.getPlace();
                    if (place.geometry && place.geometry.location) {
                        var loc = place.geometry.location;
                        posMap.setCenter(loc);
                        posMap.setZoom(17);
                        posMarker.setPosition(loc);
                        posTempLat = loc.lat();
                        posTempLng = loc.lng();
                        posTempAddr = place.formatted_address || place.name || searchInput.value;
                    }
                });
            }
        } else if (posMap) {
            posMap.setCenter({ lat: centerLat, lng: centerLng });
            posMarker.setPosition({ lat: centerLat, lng: centerLng });
            google.maps.event.trigger(posMap, 'resize');
        }
    }, 200);
};

function reverseGeocodePos(lat, lng) {
    if (!window.google || !google.maps || !google.maps.Geocoder) return;
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
        if (status === 'OK' && results[0]) {
            posTempAddr = results[0].formatted_address;
            var searchInput = document.getElementById('pos-modal-search-input');
            if (searchInput) searchInput.value = posTempAddr;
        }
    });
}

window.closePosMapModal = function() {
    var modal = document.getElementById('pos-map-modal');
    if (modal) modal.style.display = 'none';
};

window.confirmPosMapLocation = function() {
    if (posTempLat && posTempLng) {
        document.getElementById('delivery-lat').value = posTempLat;
        document.getElementById('delivery-lng').value = posTempLng;
        if (posTempAddr) {
            document.getElementById('delivery-addr').value = posTempAddr;
        }
        if (typeof calculateDeliveryFee === 'function') {
            calculateDeliveryFee();
        }
    }
    closePosMapModal();
};
</script>
@endpush

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

<!-- POS Map Selector Modal -->
<div id="pos-map-modal" class="map-selector-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.85);backdrop-filter:blur(8px);z-index:999999;align-items:center;justify-content:center;">
    <div class="map-selector-content" style="background:#1e293b;border-radius:20px;padding:24px;max-width:650px;width:92%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);color:#fff;">
        <h4 style="margin-top:0; color:#fff; font-weight:800; font-size:16px; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
            <i class="las la-map-marked-alt" style="color:#22c55e; font-size:20px;"></i> Seleccionar Ubicación de Entrega
        </h4>
        <p style="color:#94a3b8; font-size:12px; margin-bottom:12px; line-height:1.4;">Escribe la dirección en el buscador o arrastra el marcador para fijar el destino exacto.</p>
        
        <!-- Address Search Bar inside Modal -->
        <div style="margin-bottom:12px;position:relative;">
            <input type="text" id="pos-modal-search-input" class="s-input" placeholder="🔍 Buscar calle, avenida, referencia..." style="width:100%;background:#0f172a !important;color:#fff !important;border:1.5px solid rgba(255,255,255,0.2) !important;border-radius:12px !important;padding:10px 14px !important;font-size:13px !important;" autocomplete="off">
        </div>

        <div id="pos-selector-map" style="width:100%; height:360px; border-radius:12px; margin-bottom:16px; border:1px solid rgba(255,255,255,0.1); background:#334155;"></div>
        
        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <button type="button" class="s-btn s-btn-secondary" onclick="closePosMapModal()" style="background:#334155; border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:10px; padding:8px 16px;">Cancelar</button>
            <button type="button" class="s-btn s-btn-primary" onclick="confirmPosMapLocation()" style="background:#22c55e; border:none; color:#fff; border-radius:10px; padding:8px 16px; font-weight:700;">Confirmar Ubicación</button>
        </div>
    </div>
</div>
@endsection
