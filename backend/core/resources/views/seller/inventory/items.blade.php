@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-box"></i></span> Insumos y Productos
@endsection

@section('seller-content')
<div class="s-content">
@php $inventoryValue = $items->sum(fn($item) => (float)$item->stock * (float)$item->cost); @endphp
<section class="module-hero inventory"><div><div class="module-crumb"><i class="las la-home"></i> Inicio &nbsp;/&nbsp; Inventario &nbsp;/&nbsp; Insumos</div><h2><i class="las la-boxes"></i> Insumos y Existencias</h2><p>Controla materias primas, costos, niveles de stock y reposición.</p></div><div class="module-hero-stats"><div><b>{{ $items->count() }}</b><small>Insumos</small></div><div><b>{{ $lowStock->count() }}</b><small>Stock crítico</small></div><div><b>S/ {{ number_format($inventoryValue,0) }}</b><small>Valor estimado</small></div></div></section>

{{-- Alertas de stock bajo ─────────────────────────────────────────────────── --}}
@if($lowStock->count())
<div style="background:var(--s-warning-bg);border:1px solid var(--s-warning);border-left:4px solid var(--s-warning);border-radius:14px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
    <i class="las la-exclamation-triangle" style="font-size:22px;color:var(--s-warning);flex-shrink:0"></i>
    <div>
        <b style="font-size:13px;color:var(--s-warning-text)">Alerta de stock bajo</b>
        <p style="margin:2px 0 0;font-size:12px;color:var(--s-warning-text)">
            {{ $lowStock->count() }} item(s) con stock bajo o agotado requieren reposición.
        </p>
    </div>
</div>
@endif

{{-- Tabs ─────────────────────────────────────────────────────────────────── --}}
<div style="display:flex;gap:8px;border-bottom:2px solid var(--s-border);padding-bottom:0;margin-bottom:20px;">
    <button id="itab-all"     class="s-tab-btn s-tab-active" onclick="filterItems('all')">
        <i class="las la-th-list"></i> Todos
        <span class="s-badge s-badge-gray" style="font-size:10px;">{{ $items->count() }}</span>
    </button>
    <button id="itab-cocina"  class="s-tab-btn" onclick="filterItems('cocina')">
        <i class="las la-utensils"></i> Cocina
        <span class="s-badge s-badge-green" style="font-size:10px;">{{ $items->where('is_bar_item',false)->count() }}</span>
    </button>
    @if($items->where('is_bar_item',true)->count())
    <button id="itab-bar"     class="s-tab-btn" onclick="filterItems('bar')">
        <i class="las la-wine-glass-alt"></i> Barra
        <span class="s-badge s-badge-orange" style="font-size:10px;">{{ $items->where('is_bar_item',true)->count() }}</span>
    </button>
    @endif
</div>

{{-- ── Formulario agregar ────────────────────────────────────────────────── --}}
<div class="s-card seller-work-card" style="margin-bottom:20px;">
    <h3 class="s-card-title"><i class="las la-plus-circle"></i> Agregar Nuevo Insumo</h3>
    
    <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-left: 5px solid #4f46e5; border-radius: 12px; padding: 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="flex: 1; min-width: 250px; display: flex; gap: 12px; align-items: flex-start;">
            <i class="las la-exclamation-circle" style="font-size: 24px; color: #4f46e5; margin-top: 2px; flex-shrink: 0;"></i>
            <div>
                <b style="font-size: 14px; color: #1e1b4b; display: block; margin-bottom: 4px;">⚠️ ¡Importante! Módulo Exclusivo para Insumos</b>
                <span style="font-size: 13px; color: #312e81; line-height: 1.5; display: block;">Este formulario es **únicamente** para registrar materias primas o insumos de cocina y barra (que no se venden de forma directa). Si deseas registrar un producto para venderlo en el mostrador o en mesa, debes hacerlo en el módulo de productos de venta.</span>
            </div>
        </div>
        <a href="{{ route('seller.products') }}" class="s-btn" style="font-size: 12px; padding: 10px 16px; gap: 8px; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);" onmouseenter="this.style.background='#4338ca'" onmouseleave="this.style.background='#4f46e5'">
            <i class="las la-shopping-bag" style="font-size: 16px;"></i> Registrar Productos de Venta
        </a>
    </div>

    <form method="POST" action="{{ route('seller.inventory.items.store') }}">
        @csrf
        <input type="hidden" name="item_type" value="insumo">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;align-items:flex-end;margin-bottom:10px;">
            <div class="s-input-group">
                <label class="s-input-label">Nombre *</label>
                <input class="s-input" name="name" id="new-item-name" placeholder="Ej: Harina, Ron Cartavio..." required oninput="checkDuplicateName(this.value)">
                <div id="duplicate-warning" style="display:none; color:var(--s-warning); font-size:11px; margin-top:4px;">
                    <i class="las la-exclamation-triangle"></i> ¡Ya existe un insumo con este nombre!
                </div>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Categoría</label>
                <input class="s-input" name="category" placeholder="Ej: Lácteos">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Unidad (SUNAT)</label>
                <select class="s-input" name="unit">
                    <optgroup label="Unidades comunes">
                        <option value="NIU">Unidad (UND)</option>
                        <option value="DZN">Docena (DOC)</option>
                        <option value="HD">Media docena (1/2 DOC)</option>
                        <option value="QD">Cuarto de docena (1/4 DOC)</option>
                        <option value="C62">Piezas (PZ)</option>
                        <option value="PR">Par (PAR)</option>
                        <option value="SET">Juego (JGO)</option>
                        <option value="KT">Kit (KIT)</option>
                    </optgroup>
                    <optgroup label="Peso">
                        <option value="KGM">Kilogramo (KG)</option>
                        <option value="GRM">Gramos (GR)</option>
                        <option value="TNE">Toneladas (TNL)</option>
                        <option value="LBR">Libras (LB)</option>
                        <option value="ONZ">Onzas (ONZ)</option>
                    </optgroup>
                    <optgroup label="Volumen">
                        <option value="LTR">Litro (LT)</option>
                        <option value="MLT">Mililitro (ML)</option>
                        <option value="GLL">Galon (GL)</option>
                    </optgroup>
                    <optgroup label="Envases">
                        <option value="BO">Botellas (BOT)</option>
                        <option value="CA">Latas (LT)</option>
                        <option value="BX">Caja (CAJ)</option>
                        <option value="PK">Paquete (PQT)</option>
                        <option value="BG">Bolsa (BOLS)</option>
                        <option value="JR">Frasco (FCO)</option>
                        <option value="BLL">Barril (BRL)</option>
                    </optgroup>
                    <optgroup label="Otros">
                        <option value="ZZ">Servicio (SERV)</option>
                        <option value="HUR">Hora (HR)</option>
                        <option value="U2">Tableta/blister (BLIST)</option>
                        <option value="LEF">Hoja (HOJA)</option>
                        <option value="RM">Resma (RESM)</option>
                    </optgroup>
                </select>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Tipo tributario</label>
                <select class="s-input" name="tax_type">
                    <option value="gravado">Gravado (IGV 18%)</option>
                    <option value="exonerado">Exonerado</option>
                    <option value="inafecto">Inafecto</option>
                </select>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Stock mínimo</label>
                <input class="s-input" type="number" name="min_stock" step="0.01" value="0">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Stock inicial</label>
                <input class="s-input" type="number" name="initial_stock" step="0.01" value="0" placeholder="0.00">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Costo unit.</label>
                <input class="s-input" type="number" name="cost" step="0.01" value="0" placeholder="S/ 0.00">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Código SUNAT (UNSPSC)</label>
                <input class="s-input" name="sunat_code" id="new-sunat-code" list="inv-sunat-catalog" placeholder="Buscar código...">
                <datalist id="inv-sunat-catalog">
                    <option value="50151600">Bebidas alcohólicas</option>
                    <option value="50151601">Cerveza</option>
                    <option value="50151602">Vino</option>
                    <option value="50151603">Whisky</option>
                    <option value="50151604">Pisco</option>
                    <option value="50151605">Ron</option>
                    <option value="50151606">Tequila</option>
                    <option value="50151607">Gin</option>
                    <option value="50151608">Vodka</option>
                    <option value="50151500">Bebidas no alcohólicas</option>
                    <option value="50151501">Gaseosa</option>
                    <option value="50151502">Agua mineral</option>
                    <option value="50151503">Jugo natural</option>
                    <option value="50151504">Jugo en polvo</option>
                    <option value="50151505">Energizante</option>
                    <option value="50151506">Té</option>
                    <option value="50151507">Café</option>
                    <option value="50180000">Carnes y aves</option>
                    <option value="50180100">Carne de res</option>
                    <option value="50180101">Lomo de res</option>
                    <option value="50180102">Bistec</option>
                    <option value="50180103">Carne molida</option>
                    <option value="50180104">Costillas de res</option>
                    <option value="50180200">Carne de cerdo</option>
                    <option value="50180300">Aves</option>
                    <option value="50180301">Pechuga de pollo</option>
                    <option value="50180302">Muslo de pollo</option>
                    <option value="50180303">Alita de pollo</option>
                    <option value="50180304">Pollo entero</option>
                    <option value="50180400">Mariscos</option>
                    <option value="50180401">Camarón</option>
                    <option value="50180402">Pescado fresco</option>
                    <option value="50180403">Calamar</option>
                    <option value="50180404">Pulpo</option>
                    <option value="50190000">Lácteos y huevos</option>
                    <option value="50190100">Queso</option>
                    <option value="50190101">Queso fresco</option>
                    <option value="50190102">Queso parmesano</option>
                    <option value="50190103">Queso mozzarella</option>
                    <option value="50190200">Leche</option>
                    <option value="50190300">Mantequilla</option>
                    <option value="50190400">Huevos</option>
                    <option value="50200000">Frutas y verduras</option>
                    <option value="50200100">Papas y tubérculos</option>
                    <option value="50200101">Papa</option>
                    <option value="50200102">Camote</option>
                    <option value="50200200">Verduras de hoja</option>
                    <option value="50200201">Lechuga</option>
                    <option value="50200202">Espinaca</option>
                    <option value="50200203">Cebolla</option>
                    <option value="50200204">Ajo</option>
                    <option value="50200300">Verduras de fruto</option>
                    <option value="50200301">Tomate</option>
                    <option value="50200302">Pimiento</option>
                    <option value="50200303">Ají</option>
                    <option value="50200304">Zapallo</option>
                    <option value="50200305">Pepino</option>
                    <option value="50200400">Frutas frescas</option>
                    <option value="50200401">Limón</option>
                    <option value="50200402">Naranja</option>
                    <option value="50200403">Plátano</option>
                    <option value="50200404">Manzana</option>
                    <option value="50200405">Palta</option>
                    <option value="50210000">Cereales y panadería</option>
                    <option value="50210100">Arroz</option>
                    <option value="50210200">Harina</option>
                    <option value="50210300">Pasta</option>
                    <option value="50210400">Pan</option>
                    <option value="50220000">Aceites y grasas</option>
                    <option value="50220100">Aceite de oliva</option>
                    <option value="50220200">Aceite vegetal</option>
                    <option value="50220300">Aceite de soya</option>
                    <option value="50230000">Condimentos y especias</option>
                    <option value="50230100">Sal</option>
                    <option value="50230200">Pimienta</option>
                    <option value="50230300">Comino</option>
                    <option value="50230400">Orégano</option>
                    <option value="50230700">Salsa de soya</option>
                    <option value="50230800">Vinagre</option>
                    <option value="50230900">Mayonesa</option>
                    <option value="50231000">Ketchup</option>
                    <option value="50231100">Mostaza</option>
                    <option value="50250000">Platos preparados</option>
                    <option value="50250100">Entradas</option>
                    <option value="50250200">Platos de fondo</option>
                    <option value="50250300">Pastas</option>
                    <option value="50250400">Pizzas</option>
                    <option value="50250500">Ensaladas</option>
                    <option value="50250600">Sopas</option>
                    <option value="50250700">Postres</option>
                    <option value="50250800">Helados</option>
                    <option value="50260000">Servicios restaurante</option>
                    <option value="30121500">Envases de bebidas</option>
                    <option value="30121501">Lata de aluminio</option>
                    <option value="30121502">Botella de vidrio</option>
                    <option value="30121503">Botella de plástico</option>
                    <option value="25111500">Papel higiénico</option>
                    <option value="25111501">Servilletas</option>
                    <option value="25171500">Bolsas plásticas</option>
                    <option value="30131500">Detergentes</option>
                </datalist>
                <script>
                (function(){
                    var storeCategories = @json($storeCategories ?? []);
                    var categorySunatMap = {
                        'Restaurantes':   ['5025','5018','5020','5019','5021','5022','5023','5015'],
                        'Licorerías':     ['501516','501515','301215'],
                        'Super/Mini Markets': ['5018','5019','5020','5021','5022','5023','5015','2511','2517','3013'],
                        'Farmacia':       ['5026','9310'],
                        'Mascotas':       ['5026','9310'],
                    };
                    
                    var defaultCodes = {
                        'Restaurantes': '50250000', // Platos preparados
                        'Licorerías': '50151600',   // Bebidas alcohólicas
                        'Super/Mini Markets': '50151500', // Bebidas no alcohólicas (Abarrotes/Gaseosas)
                        'Farmacia': '50260000',     // Servicios / Medicinas
                        'Mascotas': '50260000'
                    };

                    var dl = document.getElementById('inv-sunat-catalog');
                    if (!dl) return;
                    var opts = Array.from(dl.options);
                    var matchedPrefixes = [];
                    storeCategories.forEach(function(cat){
                        if (categorySunatMap[cat]) {
                            matchedPrefixes = matchedPrefixes.concat(categorySunatMap[cat]);
                        }
                    });

                    // Auto-fill default code for new item
                    var defaultCode = '';
                    for (var i = 0; i < storeCategories.length; i++) {
                        var cat = storeCategories[i];
                        if (defaultCodes[cat]) {
                            defaultCode = defaultCodes[cat];
                            break;
                        }
                    }
                    if (defaultCode) {
                        var newField = document.getElementById('new-sunat-code');
                        if (newField && !newField.value) {
                            newField.value = defaultCode;
                        }
                    }

                    if (matchedPrefixes.length === 0) return;
                    opts.sort(function(a, b){
                        var aMatch = matchedPrefixes.some(function(p){ return b.value.indexOf(p) === 0; });
                        var bMatch = matchedPrefixes.some(function(p){ return a.value.indexOf(p) === 0; });
                        if (aMatch && !bMatch) return 1;
                        if (!aMatch && bMatch) return -1;
                        return 0;
                    });
                    dl.innerHTML = '';
                    opts.forEach(function(o){ dl.appendChild(o); });
                })();
                </script>
            </div>
        </div>
        {{-- Campo barra (oculto por defecto) --}}
        <div id="new-bar-group" style="display:none;background:var(--s-bg-2);border-radius:10px;padding:12px;margin-bottom:10px;">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="is_bar_item" value="1" id="new-is-bar">
                    <i class="las la-wine-glass-alt" style="color:#f59e0b;"></i>
                    <b>Ítem de barra</b>
                </label>
                <div class="s-input-group" style="margin:0;">
                    <select class="s-input" name="bar_category" style="width:150px;">
                        <option value="">— Cat. barra —</option>
                        <option value="licor">Licor</option>
                        <option value="mixer">Mixer</option>
                        <option value="garnish">Garnish</option>
                        <option value="preparado">Preparado</option>
                        <option value="otros">Otros</option>
                    </select>
                </div>
            </div>
        </div>
        <button class="s-btn s-btn-primary"><i class="las la-plus"></i> Agregar</button>
    </form>
</div>

{{-- ── Tabla de items ────────────────────────────────────────────────────── --}}
@foreach($categories as $cat)
@php $catItems = $items->where('category', $cat); @endphp
<div class="s-card item-group-card" style="margin-bottom:14px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 class="s-card-title" style="margin:0;">
            <span style="width:8px;height:8px;border-radius:50%;background:var(--s-accent);display:inline-block;"></span>
            {{ $cat ?: 'Sin categoría' }}
            <span style="font-size:12px;color:var(--s-text-3);font-weight:500;">({{ $catItems->count() }})</span>
        </h3>
    </div>
    <div style="overflow-x:auto;">
    <table class="s-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo item</th>
                <th>Tributario</th>
                <th>Unidad</th>
                <th>Stock</th>
                <th>Stock Mín.</th>
                <th>Costo</th>
                <th>P. Venta</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($catItems as $item)
        @php $isLow = $item->isLowStock(); @endphp
        <tr data-item-type="{{ $item->item_type }}" data-is-bar="{{ $item->is_bar_item ? '1' : '0' }}"
            style="{{ $isLow ? 'background:rgba(239,68,68,.04)' : '' }}">
            <td>
                <b style="color:var(--s-text)">{{ $item->name }}</b>
                @if($item->is_bar_item)
                    <span class="s-badge s-badge-orange" style="font-size:9px;margin-left:4px;">
                        <i class="las la-wine-glass-alt"></i> {{ $item->bar_category }}
                    </span>
                @endif
                @if($item->notes ?? false)<div style="font-size:11px;color:var(--s-text-3);">{{ $item->notes }}</div>@endif
            </td>
            <td>
                @if($item->item_type === 'producto')
                    <span class="s-badge s-badge-blue" style="font-size:10px;"><i class="las la-shopping-bag"></i> Producto</span>
                @else
                    <span class="s-badge s-badge-green" style="font-size:10px;"><i class="las la-seedling"></i> Insumo</span>
                @endif
            </td>
            <td>
                @php
                    $taxColors = ['gravado'=>'s-badge-orange','exonerado'=>'s-badge-blue','inafecto'=>'s-badge-gray'];
                    $taxLabels = ['gravado'=>'Gravado','exonerado'=>'Exonerado','inafecto'=>'Inafecto'];
                @endphp
                <span class="s-badge {{ $taxColors[$item->tax_type ?? 'gravado'] ?? 's-badge-gray' }}" style="font-size:10px;">
                    {{ $taxLabels[$item->tax_type ?? 'gravado'] ?? $item->tax_type }}
                </span>
            </td>
            <td><span class="s-badge s-badge-gray" style="font-size:10px;">{{ $item->unit }}</span></td>
            <td>
                <b style="color:{{ $isLow ? 'var(--s-danger)' : 'var(--s-accent-dark)' }};font-size:15px;">
                    {{ number_format($item->stock, 2) }}
                </b>
            </td>
            <td style="color:var(--s-text-3);">{{ $item->min_stock ?: '—' }}</td>
            <td style="color:var(--s-text-2);">S/ {{ number_format($item->cost, 2) }}</td>
            <td style="color:var(--s-success);">
                @if($item->item_type === 'producto' || $item->sale_price > 0)
                    S/ {{ number_format($item->sale_price, 2) }}
                @else
                    <span style="color:var(--s-text-3);">—</span>
                @endif
            </td>
            <td>
                @if($isLow)
                    <span class="s-badge s-badge-red"><i class="las la-exclamation-triangle"></i> Stock bajo</span>
                @else
                    <span class="s-badge s-badge-green"><i class="las la-check-circle"></i> OK</span>
                @endif
            </td>
            <td>
                <button class="s-btn s-btn-ghost s-btn-xs"
                    onclick="openEditModal({{ $item->id }},'{{ addslashes($item->name) }}','{{ $item->category }}','{{ $item->unit }}',{{ $item->min_stock }},{{ $item->cost }},{{ $item->sale_price }},{{ $item->stock }},'{{ $item->item_type }}','{{ $item->tax_type }}',{{ $item->is_bar_item ? 1 : 0 }},'{{ $item->bar_category }}','{{ $item->sunat_code }}')"
                    title="Editar">
                    <i class="las la-edit" style="color:var(--s-info)"></i>
                </button>
                <button class="s-btn s-btn-ghost s-btn-xs"
                    onclick="adjustStock({{ $item->id }},'{{ addslashes($item->name) }}',{{ $item->stock }})"
                    title="Ajustar stock">
                    <i class="las la-balance-scale" style="color:var(--s-text-3)"></i>
                </button>
                <a href="{{ route('seller.inventory.kardex', $item->id) }}" class="s-btn s-btn-ghost s-btn-xs" title="Kardex">
                    <i class="las la-history" style="color:var(--s-text-3)"></i>
                </a>
                <form method="POST" action="{{ route('seller.inventory.items.delete', $item->id) }}"
                    onsubmit="return confirm('¿Eliminar {{ addslashes($item->name) }}?')" style="display:inline">
                    @csrf
                    <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" title="Eliminar">
                        <i class="las la-trash"></i>
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endforeach

@php $noCat = $items->whereNull('category')->merge($items->where('category','')); @endphp
@if($noCat->count())
<div class="s-card item-group-card">
    <h3 class="s-card-title"><i class="las la-question-circle"></i> Sin categoría</h3>
    <div style="overflow-x:auto;"><table class="s-table">
        <thead><tr><th>Nombre</th><th>Tipo</th><th>Tributario</th><th>Unidad</th><th>Stock</th><th>Costo</th><th></th></tr></thead>
        <tbody>
        @foreach($noCat as $item)
        <tr data-item-type="{{ $item->item_type }}" data-is-bar="{{ $item->is_bar_item ? '1' : '0' }}">
            <td><b>{{ $item->name }}</b></td>
            <td><span class="s-badge {{ $item->item_type === 'producto' ? 's-badge-blue' : 's-badge-green' }}" style="font-size:10px;">{{ $item->item_type }}</span></td>
            <td><span class="s-badge s-badge-gray" style="font-size:10px;">{{ $item->tax_type ?? 'gravado' }}</span></td>
            <td>{{ $item->unit }}</td>
            <td><b>{{ $item->stock }}</b></td>
            <td>S/ {{ $item->cost }}</td>
            <td>
                <button class="s-btn s-btn-ghost s-btn-xs" onclick="adjustStock({{ $item->id }},'{{ addslashes($item->name) }}',{{ $item->stock }})" title="Ajustar stock">
                    <i class="las la-balance-scale" style="color:var(--s-info)"></i>
                </button>
                <a href="{{ route('seller.inventory.kardex', $item->id) }}" class="s-btn s-btn-ghost s-btn-xs"><i class="las la-history"></i></a>
                <form method="POST" action="{{ route('seller.inventory.items.delete', $item->id) }}" onsubmit="return confirm('¿Eliminar?')" style="display:inline">
                    @csrf<button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)"><i class="las la-trash"></i></button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@endif

</div>{{-- end s-content --}}

{{-- ══ Modal: Editar item ══════════════════════════════════════════════════ --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(10,20,35,.65);align-items:center;justify-content:center;backdrop-filter:blur(4px);">
<div class="s-card" style="width:100%;max-width:560px;max-height:90vh;overflow-y:auto;padding:24px;border-radius:18px;" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3 style="margin:0;font-weight:900;"><i class="las la-edit" style="color:var(--s-accent)"></i> Editar Insumo/Producto</h3>
        <button class="s-btn s-btn-ghost" onclick="closeEditModal()">✕</button>
    </div>
    <form id="edit-form" method="POST">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div class="s-input-group" style="grid-column:1/-1;">
                <label class="s-input-label">Nombre *</label>
                <input class="s-input" name="name" id="edit-name" required>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Tipo de item</label>
                <select class="s-input" name="item_type" id="edit-item-type" onchange="toggleEditBarField(this.value)">
                    <option value="insumo">Insumo / Materia prima</option>
                    <option value="producto">Producto para venta directa</option>
                </select>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Categoría</label>
                <input class="s-input" name="category" id="edit-category">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Unidad (SUNAT)</label>
                <select class="s-input" name="unit" id="edit-unit">
                    <optgroup label="Unidades comunes">
                        <option value="NIU">Unidad (UND)</option>
                        <option value="DZN">Docena (DOC)</option>
                        <option value="HD">Media docena (1/2 DOC)</option>
                        <option value="QD">Cuarto de docena (1/4 DOC)</option>
                        <option value="C62">Piezas (PZ)</option>
                        <option value="PR">Par (PAR)</option>
                        <option value="SET">Juego (JGO)</option>
                        <option value="KT">Kit (KIT)</option>
                    </optgroup>
                    <optgroup label="Peso">
                        <option value="KGM">Kilogramo (KG)</option>
                        <option value="GRM">Gramos (GR)</option>
                        <option value="TNE">Toneladas (TNL)</option>
                        <option value="LBR">Libras (LB)</option>
                        <option value="ONZ">Onzas (ONZ)</option>
                    </optgroup>
                    <optgroup label="Volumen">
                        <option value="LTR">Litro (LT)</option>
                        <option value="MLT">Mililitro (ML)</option>
                        <option value="GLL">Galon (GL)</option>
                    </optgroup>
                    <optgroup label="Envases">
                        <option value="BO">Botellas (BOT)</option>
                        <option value="CA">Latas (LT)</option>
                        <option value="BX">Caja (CAJ)</option>
                        <option value="PK">Paquete (PQT)</option>
                        <option value="BG">Bolsa (BOLS)</option>
                        <option value="JR">Frasco (FCO)</option>
                        <option value="BLL">Barril (BRL)</option>
                    </optgroup>
                    <optgroup label="Otros">
                        <option value="ZZ">Servicio (SERV)</option>
                        <option value="HUR">Hora (HR)</option>
                        <option value="U2">Tableta/blister (BLIST)</option>
                        <option value="LEF">Hoja (HOJA)</option>
                        <option value="RM">Resma (RESM)</option>
                    </optgroup>
                </select>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Tipo tributario</label>
                <select class="s-input" name="tax_type" id="edit-tax-type">
                    <option value="gravado">Gravado (IGV 18%)</option>
                    <option value="exonerado">Exonerado</option>
                    <option value="inafecto">Inafecto</option>
                </select>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Stock mínimo</label>
                <input class="s-input" type="number" name="min_stock" id="edit-min-stock" step="0.01">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Costo unit.</label>
                <input class="s-input" type="number" name="cost" id="edit-cost" step="0.01">
            </div>
            <div class="s-input-group" id="edit-sale-price-group">
                <label class="s-input-label">Precio venta</label>
                <input class="s-input" type="number" name="sale_price" id="edit-sale-price" step="0.01">
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Código SUNAT (UNSPSC)</label>
                <input class="s-input" name="sunat_code" id="edit-sunat-code" list="inv-sunat-catalog" placeholder="Buscar código...">
            </div>
        </div>
        {{-- Barra --}}
        <div id="edit-bar-group" style="background:var(--s-bg-2);border-radius:10px;padding:12px;margin-bottom:14px;">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="is_bar_item" value="1" id="edit-is-bar">
                    <i class="las la-wine-glass-alt" style="color:#f59e0b;"></i> <b>Ítem de barra</b>
                </label>
                <select class="s-input" name="bar_category" id="edit-bar-category" style="width:150px;">
                    <option value="">— Cat. barra —</option>
                    <option value="licor">Licor</option>
                    <option value="mixer">Mixer</option>
                    <option value="garnish">Garnish</option>
                    <option value="preparado">Preparado</option>
                    <option value="otros">Otros</option>
                </select>
            </div>
        </div>
        <div style="background:var(--s-bg-2);border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;color:var(--s-text-3);">
            <i class="las la-info-circle"></i>
            Stock actual: <b id="edit-current-stock" style="color:var(--s-accent)">0</b> unidades
            — para ajustar usa el botón <i class="las la-balance-scale"></i>
        </div>
        <button type="submit" class="s-btn s-btn-primary" style="width:100%;justify-content:center;">
            <i class="las la-save"></i> Guardar Cambios
        </button>
    </form>
</div>
</div>

{{-- ══ Modal: Ajustar stock ════════════════════════════════════════════════ --}}
<div id="stock-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,25,35,0.6);align-items:center;justify-content:center;backdrop-filter:blur(4px);">
<div class="s-card" style="width:100%;max-width:400px;padding:24px;border-radius:16px;" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3 style="margin:0;font-weight:900;"><i class="las la-balance-scale" style="color:var(--s-info)"></i> Ajustar Stock</h3>
        <button class="s-btn s-btn-ghost" onclick="closeModal()" style="font-size:20px;color:var(--s-text-3)">✕</button>
    </div>
    <form method="POST" action="{{ route('seller.inventory.stock-adjust') }}">
        @csrf
        <input type="hidden" name="item_id" id="adj-item-id">
        <p style="font-size:13px;color:var(--s-text-2);margin-bottom:16px;">
            <b id="adj-item-name"></b> — Stock actual: <b id="adj-current-stock">0</b>
        </p>
        <div style="display:flex;gap:10px;margin-bottom:16px;">
            <select name="type" class="s-input" style="width:110px;">
                <option value="entrada">+ Entrada</option>
                <option value="salida">− Salida</option>
            </select>
            <input type="number" name="quantity" step="0.01" min="0" value="0" required class="s-input"
                style="flex:1;text-align:center;font-weight:700;">
        </div>
        <div class="s-input-group" style="margin-bottom:16px;">
            <label class="s-input-label">Motivo del ajuste</label>
            <input class="s-input" name="description" placeholder="Ej: Merma, error de conteo..." required>
        </div>
        <button type="submit" class="s-btn s-btn-primary" style="justify-content:center;width:100%;">
            <i class="las la-save"></i> Guardar Ajuste
        </button>
    </form>
</div>
</div>

@push('style')
<style>
.s-tab-btn {
    background:none;border:none;padding:10px 16px;font-size:13px;font-weight:600;
    color:var(--s-text-3);cursor:pointer;border-bottom:3px solid transparent;
    display:flex;align-items:center;gap:6px;transition:all .2s;
}
.s-tab-active { color:var(--s-accent);border-bottom-color:var(--s-accent); }
</style>
@endpush

@push('script')
<script>
// ── Tab filtering ─────────────────────────────────────────────────────────
function filterItems(type) {
    ['all','cocina','bar'].forEach(t => {
        const btn = document.getElementById('itab-' + t);
        if (btn) btn.classList.toggle('s-tab-active', t === type);
    });
    document.querySelectorAll('.item-group-card').forEach(card => {
        const rows = card.querySelectorAll('tbody tr');
        let visible = 0;
        rows.forEach(row => {
            const bar = row.dataset.isBar;
            let show = false;
            if (type === 'all')        show = true;
            else if (type === 'bar')   show = bar === '1';
            else if (type === 'cocina') show = bar !== '1';
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        card.style.display = visible ? '' : 'none';
    });
}

// ── Edit modal ────────────────────────────────────────────────────────────
function openEditModal(id, name, cat, unit, minStock, cost, salePrice, stock, itemType, taxType, isBar, barCat, sunatCode) {
    document.getElementById('edit-form').action = `/seller/inventory/items/${id}/update`;
    document.getElementById('edit-name').value        = name;
    document.getElementById('edit-category').value    = cat || '';
    document.getElementById('edit-unit').value        = unit;
    document.getElementById('edit-min-stock').value   = minStock;
    document.getElementById('edit-cost').value        = cost;
    document.getElementById('edit-sale-price').value  = salePrice;
    document.getElementById('edit-current-stock').textContent = stock;
    document.getElementById('edit-item-type').value   = itemType || 'insumo';
    document.getElementById('edit-tax-type').value    = taxType || 'gravado';
    document.getElementById('edit-is-bar').checked    = isBar == 1;
    document.getElementById('edit-bar-category').value = barCat || '';
    document.getElementById('edit-sunat-code').value  = sunatCode || '';
    toggleEditBarField(itemType || 'insumo');
    document.getElementById('edit-modal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}
function toggleEditBarField(type) {
    const barGrp   = document.getElementById('edit-bar-group');
    const priceGrp = document.getElementById('edit-sale-price-group');
    if (barGrp)   barGrp.style.display   = type === 'insumo' ? 'block' : 'none';
    if (priceGrp) priceGrp.style.display = type === 'producto' ? 'block' : 'flex';
}
function toggleBarField(type) {
    const barGrp      = document.getElementById('new-bar-group');
    if (barGrp)   barGrp.style.display   = type === 'insumo' ? 'block' : 'none';
}

var existingNames = @json($items->pluck('name')->unique()->values()->toArray());
function checkDuplicateName(val) {
    var warning = document.getElementById('duplicate-warning');
    if (!val || val.trim().length === 0) {
        warning.style.display = 'none';
        return;
    }
    var cleanVal = val.trim().toLowerCase();
    var match = existingNames.some(function(name) {
        return name.trim().toLowerCase() === cleanVal;
    });
    if (match) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

// ── Stock adjust modal ────────────────────────────────────────────────────
function adjustStock(id, name, stock) {
    document.getElementById('adj-item-id').value = id;
    document.getElementById('adj-item-name').textContent = name;
    document.getElementById('adj-current-stock').textContent = stock;
    document.getElementById('stock-modal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('stock-modal').style.display = 'none';
}

document.getElementById('stock-modal').addEventListener('click', closeModal);
document.getElementById('edit-modal').addEventListener('click', closeEditModal);
</script>
@endpush
@endsection
