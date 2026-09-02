@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-boxes"></i></span> Productos
@endsection

@section('topbar-actions')
<button class="s-btn s-btn-primary" onclick="openProdModal()">
    <i class="las la-plus"></i> Nuevo Producto
</button>
@endsection

@section('seller-content')
<style>
.catalog-head{min-height:148px;margin-bottom:16px;padding:24px 26px;border-radius:14px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:20px;background:linear-gradient(90deg,rgba(112,45,10,.92),rgba(161,71,19,.72),rgba(108,48,13,.42)),url('{{ asset('assets/images/banner-cover.png') }}') center/cover no-repeat}.catalog-head .crumb{font-size:9px;color:rgba(255,255,255,.75);margin-bottom:8px}.catalog-head h2{font:800 24px 'Plus Jakarta Sans','Inter',sans-serif;margin:0 0 4px;letter-spacing:-.5px}.catalog-head p{font-size:10px;color:rgba(255,255,255,.78);margin:0}.catalog-head-actions{display:flex;gap:8px;flex-wrap:wrap}.catalog-head-actions .s-btn{height:36px;border-radius:9px;background:rgba(255,255,255,.13);color:#fff;border:1px solid rgba(255,255,255,.28);backdrop-filter:blur(8px)}.catalog-head-actions .primary{background:#fff;color:#a64c15;border-color:#fff}.catalog-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}.catalog-kpi{min-height:82px;background:#fff;border:1px solid var(--s-border);border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:var(--s-shadow-sm)}.catalog-kpi i{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;color:#fff;font-size:18px;box-shadow:0 6px 14px rgba(15,23,42,.1)}.catalog-kpi b{display:block;font-size:19px;color:#172033}.catalog-kpi small{display:block;font-size:9px;color:#94a3b8}.catalog-filter-row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.catalog-pills{display:flex;gap:7px;overflow:auto;padding-bottom:2px}.catalog-pill{height:34px;padding:0 13px;border:1px solid var(--s-border);border-radius:9px;background:#fff;color:#64748b;font-size:10px;font-weight:700;white-space:nowrap;cursor:pointer}.catalog-pill.active{background:#f97316;color:#fff;border-color:#f97316;box-shadow:0 5px 12px rgba(249,115,22,.2)}.catalog-toolbar{display:flex;align-items:center;gap:8px;margin:0}.catalog-search{position:relative}.catalog-search i{position:absolute;left:13px;top:11px;color:#94a3b8}.catalog-search input{width:220px;height:36px;padding:0 12px 0 37px;border:1px solid var(--s-border);border-radius:10px;background:#fff;outline:0;font-size:10px}.catalog-toolbar select{height:36px;border:1px solid var(--s-border);border-radius:10px;padding:0 10px;background:#fff;color:#64748b;font-size:10px}.catalog-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.catalog-product-card{min-width:0;border:1px solid var(--s-border);border-radius:14px;background:#fff;overflow:hidden;box-shadow:0 3px 10px rgba(15,23,42,.045);transition:.2s}.catalog-product-card:hover{border-color:#fed7aa;transform:translateY(-2px);box-shadow:0 9px 20px rgba(15,23,42,.08)}.catalog-product-image{height:154px;position:relative;background:#edf1f5;overflow:hidden}.catalog-product-image img{width:100%;height:100%;object-fit:cover}.catalog-product-image:after{content:'';position:absolute;inset:auto 0 0;height:42%;background:linear-gradient(transparent,rgba(0,0,0,.55))}.catalog-product-price{position:absolute;right:12px;bottom:10px;z-index:2;color:#fff;font-size:15px;font-weight:800}.catalog-product-status{position:absolute;left:10px;top:10px;z-index:2;padding:4px 8px;border-radius:7px;color:#fff;background:#10b981;font-size:8px;font-weight:800}.catalog-product-status.off{background:#ef4444}.catalog-product-body{padding:13px}.catalog-product-name{font-size:12px;font-weight:800;color:#273244;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.catalog-product-desc{height:32px;margin:5px 0 8px;color:#94a3b8;font-size:9px;line-height:1.55;overflow:hidden}.catalog-product-meta{display:flex;justify-content:space-between;align-items:center;gap:8px}.catalog-product-actions{display:flex;align-items:center;gap:3px}.catalog-product-actions form{margin:0}.catalog-product-actions .s-btn{width:27px;height:27px;padding:0;justify-content:center}.catalog-category-tag{padding:3px 7px;border-radius:7px;background:#fff7ed;color:#ea580c;font-size:8px;font-weight:700}@media(max-width:1199px){.catalog-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:900px){.catalog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.catalog-kpis{grid-template-columns:repeat(2,1fr)}.catalog-filter-row{align-items:stretch;flex-direction:column}.catalog-toolbar,.catalog-search,.catalog-search input{width:100%}}@media(max-width:600px){.catalog-head{align-items:flex-start;flex-direction:column}.catalog-head-actions{width:100%}.catalog-grid{grid-template-columns:1fr}.catalog-kpis{grid-template-columns:1fr}}
.catalog-pill{display:inline-flex;align-items:center;text-decoration:none}.catalog-pill:hover{color:#ea580c;border-color:#fed7aa}.catalog-pill.active:hover{color:#fff}
@media(max-width:900px){.catalog-toolbar{flex-wrap:wrap}.catalog-search{flex:1 1 220px}.catalog-toolbar select{flex:1 1 140px}}
@media(max-width:600px){.catalog-toolbar .s-btn{flex:0 0 36px}.catalog-search{flex-basis:100%}}
</style>
<div class="s-content">
<section class="catalog-head">
    <div><div class="crumb"><i class="las la-home"></i> Seller &nbsp;/&nbsp; Restaurante &nbsp;/&nbsp; Menú</div><h2><i class="las la-clipboard-list"></i> Gestión del menú</h2><p>Administra productos, categorías y precios de <b>{{ $store->name }}</b>.</p></div>
    <div class="catalog-head-actions"><button class="s-btn primary" onclick="openProdModal()"><i class="las la-plus"></i> Añadir producto</button><a class="s-btn" href="{{ route('seller.categories') }}"><i class="las la-folder-plus"></i> Nueva categoría</a></div>
</section>
<div class="catalog-kpis">
    <div class="catalog-kpi"><i class="las la-clipboard-list" style="background:linear-gradient(135deg,#fbbf24,#f59e0b)"></i><div><b>{{ $totalProducts }}</b><small>Total productos</small></div></div>
    <div class="catalog-kpi"><i class="las la-check-circle" style="background:linear-gradient(135deg,#34d399,#10b981)"></i><div><b style="color:#10b981">{{ $activeProducts }}</b><small>Disponibles</small></div></div>
    <div class="catalog-kpi"><i class="las la-times-circle" style="background:linear-gradient(135deg,#fb7185,#ef4444)"></i><div><b style="color:#ef4444">{{ $inactiveProducts }}</b><small>Agotados / inactivos</small></div></div>
    <div class="catalog-kpi"><i class="las la-layer-group" style="background:linear-gradient(135deg,#a78bfa,#7c3aed)"></i><div><b>{{ $cats->count() }}</b><small>Categorías</small></div></div>
</div>
<div class="catalog-filter-row">
    <div class="catalog-pills"><a href="{{ route('seller.products', array_filter(['search'=>request('search'),'status'=>request('status'),'tax_type'=>request('tax_type')])) }}" class="catalog-pill {{ request('category') ? '' : 'active' }}">Todos ({{ $totalProducts }})</a>@foreach($cats as $cat)<a href="{{ route('seller.products', array_filter(['category'=>$cat->id,'search'=>request('search'),'status'=>request('status'),'tax_type'=>request('tax_type')])) }}" class="catalog-pill {{ (string)request('category') === (string)$cat->id ? 'active' : '' }}">{{ $cat->name }} ({{ $categoryCounts[$cat->id] ?? 0 }})</a>@endforeach</div>
    <form class="catalog-toolbar" method="GET" action="{{ route('seller.products') }}">@if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif<label class="catalog-search"><i class="las la-search"></i><input name="search" value="{{ request('search') }}" type="search" placeholder="Buscar productos..."></label><select name="status" aria-label="Filtrar por estado" onchange="this.form.submit()"><option value="">Todos los estados</option><option value="active" @selected(request('status')==='active')>Disponibles</option><option value="inactive" @selected(request('status')==='inactive')>Inactivos</option></select><select name="tax_type" aria-label="Filtrar por IGV" onchange="this.form.submit()"><option value="">Todas las opciones de IGV</option>@foreach($taxTypes as $value => $label)<option value="{{ $value }}" @selected(request('tax_type') === $value)>{{ $label }}</option>@endforeach</select><button class="s-btn s-btn-primary s-btn-sm" type="submit" aria-label="Aplicar filtros"><i class="las la-search"></i></button></form>
</div>

@if($cats->isEmpty())
<div class="s-card">
    <div class="s-empty">
        <i class="las la-tags"></i>
        <p>Primero crea una <a href="{{ route('seller.categories') }}" style="color:var(--s-accent-dark);font-weight:700">categoría</a> para empezar a agregar productos.</p>
    </div>
</div>
@endif

<div class="catalog-grid">
        @forelse($allProducts as $p)
        <article class="catalog-product-card" data-category="{{ $p->store_category_id }}" data-status="{{ $p->status ? 'active' : 'inactive' }}" style="{{ $p->status ? '' : 'opacity:.68' }}">
            <div class="catalog-product-image">
                @if($p->image)
                    <img src="{{ asset('storage/'.$p->image) }}" loading="lazy" alt="{{ $p->name }}">
                @else
                    <div style="width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(135deg,#fff7ed,#fed7aa)"><i class="las la-hamburger" style="font-size:48px;color:#f97316"></i></div>
                @endif
                <span class="catalog-product-status {{ $p->status ? '' : 'off' }}">{{ $p->status ? 'Disponible' : 'Agotado' }}</span><span class="catalog-product-price">S/ {{ number_format($p->price,2) }}</span>
            </div>
            <div class="catalog-product-body"><div class="catalog-product-name">{{ $p->name }}</div><div class="catalog-product-desc">{{ $p->description ?: 'Sin descripción. Añade detalles para presentar mejor este producto.' }}</div><div class="catalog-product-meta"><span class="catalog-category-tag">{{ $p->category?->name ?? 'Sin categoría' }}</span><div class="catalog-product-actions">
                @php $invItem = $p->invProductItems->first()?->item; @endphp
                <button class="s-btn s-btn-ghost s-btn-xs" onclick="editProduct({{ $p->id }},'{{ addslashes($p->name) }}',{{ $p->price }},{{ $p->discount_price??0 }},'{{ addslashes($p->description) }}',{{ $p->store_category_id }},{{ $p->sort_order }},{{ $p->status }},'{{ $p->stock_type??'packaged' }}','{{ $p->barcode }}',{{ $p->variations->toJson() }},{{ $p->addons->toJson() }},'{{ $p->tax_type ?? 'gravado' }}','{{ $p->sunat_code }}', '{{ $invItem?->unit ?? 'NIU' }}', {{ $invItem?->cost ?? 0 }}, {{ $invItem?->stock ?? 0 }}, {{ $invItem?->min_stock ?? 5 }})" title="Editar">
                    <i class="las la-edit"></i>
                </button>
                @if($p->stock_type === 'packaged')
                <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-warning)" onclick="openStockAdjModal({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $invItem?->stock ?? 0 }}, '{{ $invItem?->unit ?? 'NIU' }}')" title="Ajustar Stock (Kardex)">
                    <i class="las la-boxes"></i>
                </button>
                @endif
                <form method="POST" action="{{ route('seller.products.delete', $p->id) }}" onsubmit="return confirm('¿Eliminar {{ addslashes($p->name) }}?')">
                    @csrf
                    <button class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)" title="Eliminar">
                        <i class="las la-trash"></i>
                    </button>
                </form>
            </div></div></div>
        </article>
        @empty
        <div style="grid-column:1/-1;padding:20px;text-align:center;color:var(--s-text-3);font-size:13px">
            <i class="las la-box" style="font-size:28px;display:block;margin-bottom:6px;color:var(--s-border)"></i>
            Sin productos en esta categoría
        </div>
        @endforelse
</div>
@if($allProducts->hasPages())
<div style="margin-top:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><small style="color:#94a3b8">Mostrando {{ $allProducts->firstItem() }}–{{ $allProducts->lastItem() }} de {{ $allProducts->total() }} productos</small><div>{{ $allProducts->links() }}</div></div>
@endif
</div>

<!-- PRODUCT MODAL -->
<div id="prod-modal" class="s-modal">
    <div class="s-modal-bg" onclick="this.parentElement.classList.remove('open')"></div>
    <div class="s-modal-box">
        <div class="s-modal-head">
            <h3 class="s-modal-title" id="prod-modal-title">Nuevo Producto</h3>
            <button class="s-modal-close" onclick="this.closest('.s-modal').classList.remove('open')">✕</button>
        </div>
        <div class="s-modal-body">
            <form id="prod-form" method="POST" action="{{ route('seller.products.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="prod-method" value="POST">
                <div class="s-form-grid">
                    <div class="s-input-group">
                        <label class="s-input-label">Nombre del producto *</label>
                        <input class="s-input" name="name" id="prod-name" placeholder="Ej: Pizza Margherita" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="s-input-group">
                            <label class="s-input-label">Precio *</label>
                            <input class="s-input" type="number" name="price" id="prod-price" step="0.01" placeholder="0.00" required>
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Precio tachado</label>
                            <input class="s-input" type="number" name="discount_price" id="prod-discount" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Categoría *</label>
                        <select class="s-input" name="store_category_id" id="prod-cat" required onchange="autoFillSunatCode()">
                            <option value="">Seleccionar categoría...</option>
                            @foreach($cats as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Descripción</label>
                        <textarea class="s-input" name="description" id="prod-desc" rows="2" placeholder="Descripción del producto..."></textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="s-input-group">
                            <label class="s-input-label">Código de barras</label>
                            <input class="s-input" name="barcode" id="prod-barcode" placeholder="Ej: 7751234567890">
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Orden de visualización</label>
                            <input class="s-input" type="number" name="sort_order" id="prod-sort" value="0">
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Imagen del producto</label>
                            <input type="file" name="image" class="s-input" accept="image/*">
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--s-surface-2);border-radius:10px;border:1px solid var(--s-border)">
                        <input type="checkbox" name="status" id="prod-status" checked style="width:16px;height:16px;accent-color:var(--s-accent)">
                        <label for="prod-status" style="font-size:13px;font-weight:600;cursor:pointer;margin:0">Producto activo (visible en el menú)</label>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Tipo de inventario</label>
                        <select class="s-input" name="stock_type" id="prod-stock-type" onchange="toggleStockFields()">
                            @foreach(\App\Models\Product::stockTypes() as $k => $v)
                            <option value="{{ $k }}" {{ $k==='packaged'?'selected':'' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="s-input-group">
                        <label class="s-input-label">Código Producto SUNAT (UNSPSC) <span style="color:var(--s-text-3);font-weight:400">— 8 dígitos</span></label>
                        <input class="s-input" name="sunat_code" id="prod-sunat-code" list="sunat-catalog" placeholder="Buscar código..." maxlength="8" value="50190000">
                        <small style="color:var(--s-text-3);font-size:10px">Escribe para buscar por nombre o código. Ej: 50180101 (Lomo de res)</small>
                    </div>

                    <!-- Campos de inventario (solo para productos packaged) -->
                    <div id="stock-fields" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:14px;background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:10px">
                        <div class="s-input-group">
                            <label class="s-input-label">Unidad de medida (SUNAT) *</label>
                            <select class="s-input" name="unit" id="prod-unit">
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
                                    <option value="MGM">Miligramos (MG)</option>
                                </optgroup>
                                <optgroup label="Volumen">
                                    <option value="LTR">Litro (LT)</option>
                                    <option value="MLT">Mililitro (ML)</option>
                                    <option value="GLL">Galon (GL)</option>
                                    <option value="GLI">Galon ingles (GL)</option>
                                </optgroup>
                                <optgroup label="Longitud">
                                    <option value="MTR">Metro (M)</option>
                                    <option value="CMT">Centimetro (CM)</option>
                                    <option value="MMT">Milimetro (ML)</option>
                                    <option value="FOT">Pies (PIE)</option>
                                    <option value="INH">Pulgadas (INCH)</option>
                                    <option value="YRD">Yarda (YD)</option>
                                    <option value="KTM">Kilometro (KM)</option>
                                </optgroup>
                                <optgroup label="Area / Volumen espacial">
                                    <option value="MTK">Metro cuadrado (M2)</option>
                                    <option value="MTQ">Metro cubico (M3)</option>
                                    <option value="FTK">Pies cuadrados (PIE2)</option>
                                    <option value="FTQ">Pies cubicos (PIE3)</option>
                                    <option value="CMK">Centimetro cuadrado (CM2)</option>
                                    <option value="CMQ">Centimetro cubico (CM3)</option>
                                    <option value="MMK">Milimetro cuadrado (ML2)</option>
                                    <option value="MMQ">Milimetro cubico (ML3)</option>
                                </optgroup>
                                <optgroup label="Envases / Embalajes">
                                    <option value="BO">Botellas (BOT)</option>
                                    <option value="CA">Latas (LT)</option>
                                    <option value="BX">Caja (CAJ)</option>
                                    <option value="PK">Paquete (PQT)</option>
                                    <option value="BG">Bolsa (BOLS)</option>
                                    <option value="BE">Fardo (FARD)</option>
                                    <option value="SA">Saco (SCO)</option>
                                    <option value="CH">Envase (ENV)</option>
                                    <option value="JR">Frasco (FCO)</option>
                                    <option value="BLL">Barril (BRL)</option>
                                    <option value="CY">Cilindro (CIL)</option>
                                    <option value="BJ">Balde (BALD)</option>
                                    <option value="JG">Jarra (JARR)</option>
                                    <option value="CT">Carton (CTON)</option>
                                    <option value="ST">Pliego (PLGO)</option>
                                    <option value="TU">Tubos (TB)</option>
                                    <option value="RL">Carrete (CRR)</option>
                                </optgroup>
                                <optgroup label="Otros">
                                    <option value="ZZ">Servicio (SERV)</option>
                                    <option value="HUR">Hora (HR)</option>
                                    <option value="SEC">Segundo (SEG)</option>
                                    <option value="HT">Media hora (1/2 H)</option>
                                    <option value="U2">Tableta o blister (BLIST)</option>
                                    <option value="AV">Capsula (CAPS)</option>
                                    <option value="PF">Paletas (PAL)</option>
                                    <option value="PG">Placas (PLAC)</option>
                                    <option value="RD">Varilla (VAR)</option>
                                    <option value="LEF">Hoja (HOJA)</option>
                                    <option value="RM">Resma (RESM)</option>
                                    <option value="BT">Tornillo (TORN)</option>
                                    <option value="UM">Millon (MILL)</option>
                                    <option value="MIL">Millar (MIL)</option>
                                    <option value="CEN">Centenar (CTO)</option>
                                    <option value="KWH">Kilovatio hora (KWxH)</option>
                                    <option value="MWH">Megavatio hora (MWxH)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Tipo tributario</label>
                            <select class="s-input" name="tax_type" id="prod-tax-type">
                                <option value="gravado">Gravado (IGV 18%)</option>
                                <option value="exonerado">Exonerado</option>
                                <option value="inafecto">Inafecto</option>
                            </select>
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Costo unitario (S/)</label>
                            <input class="s-input" type="number" name="cost" id="prod-cost" step="0.01" placeholder="0.00">
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Stock actual / inicial</label>
                            <input class="s-input" type="number" name="initial_stock" id="prod-initial-stock" step="0.01" placeholder="0.00" value="0">
                        </div>
                        <div class="s-input-group">
                            <label class="s-input-label">Stock mínimo (alerta)</label>
                            <input class="s-input" type="number" name="min_stock" id="prod-min-stock" step="1" placeholder="5" value="5">
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;padding-top:20px;grid-column: 1 / -1;">
                            <i class="las la-info-circle" style="color:var(--s-text-3)"></i>
                            <span style="font-size:11px;color:var(--s-text-3)">Se crea automáticamente el insumo en inventario</span>
                        </div>
                        <datalist id="sunat-catalog">
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
                                <option value="50151508">Néctar</option>
                                <option value="50180000">Carnes y aves</option>
                                <option value="50180100">Carne de res</option>
                                <option value="50180101">Lomo de res</option>
                                <option value="50180102">Bistec</option>
                                <option value="50180103">Carne molida</option>
                                <option value="50180104">Costillas de res</option>
                                <option value="50180105">Punta de ternera</option>
                                <option value="50180200">Carne de cerdo</option>
                                <option value="50180201">Chuleta de cerdo</option>
                                <option value="50180202">Lomo de cerdo</option>
                                <option value="50180203">Panceta</option>
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
                                <option value="50180405">Concha de abanico</option>
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
                                <option value="50200406">Piña</option>
                                <option value="50200407">Sandía</option>
                                <option value="50200500">Frutas congeladas</option>
                                <option value="50210000">Cereales y panadería</option>
                                <option value="50210100">Arroz</option>
                                <option value="50210200">Harina</option>
                                <option value="50210300">Pasta</option>
                                <option value="50210400">Pan</option>
                                <option value="50210500">Tortillas</option>
                                <option value="50210600">Cereal</option>
                                <option value="50220000">Aceites y grasas</option>
                                <option value="50220100">Aceite de oliva</option>
                                <option value="50220200">Aceite vegetal</option>
                                <option value="50220300">Aceite de soya</option>
                                <option value="50220400">Manteca</option>
                                <option value="50230000">Condimentos y especias</option>
                                <option value="50230100">Sal</option>
                                <option value="50230200">Pimienta</option>
                                <option value="50230300">Comino</option>
                                <option value="50230400">Orégano</option>
                                <option value="50230500">Perejil</option>
                                <option value="50230600">Cilantro</option>
                                <option value="50230700">Salsa de soya</option>
                                <option value="50230800">Vinagre</option>
                                <option value="50230900">Mayonesa</option>
                                <option value="50231000">Ketchup</option>
                                <option value="50231100">Mostaza</option>
                                <option value="50231200">Salsa de tomate</option>
                                <option value="50240000">Bebidas preparadas</option>
                                <option value="50240100">Cócteles</option>
                                <option value="50240200">Jugos preparados</option>
                                <option value="50240300">Café preparado</option>
                                <option value="50250000">Platos preparados</option>
                                <option value="50250100">Entradas</option>
                                <option value="50250200">Platos de fondo</option>
                                <option value="50250300">Pastas</option>
                                <option value="50250400">Pizzas</option>
                                <option value="50250500">Ensaladas</option>
                                <option value="50250600">Sopas</option>
                                <option value="50250700">Postres</option>
                                <option value="50250800">Helados</option>
                                <option value="50260000">Servicios de restaurante</option>
                                <option value="50260100">Servicio de mesa</option>
                                <option value="50260200">Servicio de delivery</option>
                                <option value="50260300">Servicio de catering</option>
                                <option value="50260400">Servicio de buffete</option>
                                <option value="93101500">Servicios de alimentos</option>
                                <option value="93101501">Servicio de comidas preparadas</option>
                                <option value="93101502">Servicio de cafeteria</option>
                                <option value="93101503">Servicio de bar</option>
                                <option value="30121500">Envases de bebidas</option>
                                <option value="30121501">Lata de aluminio</option>
                                <option value="30121502">Botella de vidrio</option>
                                <option value="30121503">Botella de plástico</option>
                                <option value="30121504">Tetra Pak</option>
                                <option value="25111500">Papel higiénico</option>
                                <option value="25111501">Servilletas</option>
                                <option value="25111502">Toallas de papel</option>
                                <option value="25171500">Bolsas plásticas</option>
                                <option value="25171501">Bolsas para llevar</option>
                                <option value="25171502">Bolsas de basura</option>
                                <option value="30131500">Detergentes</option>
                                <option value="30131501">Jabón líquido</option>
                                <option value="30131502">Lavavajillas</option>
                                <option value="30131503">Desinfectante</option>
                            </datalist>
                    </div>
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
                        var dl = document.getElementById('sunat-catalog');
                        if (!dl) return;
                        var opts = Array.from(dl.options);
                        var matchedPrefixes = [];
                        storeCategories.forEach(function(cat){
                            if (categorySunatMap[cat]) {
                                matchedPrefixes = matchedPrefixes.concat(categorySunatMap[cat]);
                            }
                        });
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

                    <hr class="s-divider">
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                            <label class="s-input-label" style="font-size:12px">Variaciones <span style="color:var(--s-text-3)">(talla, sabor, etc.)</span></label>
                            <button type="button" class="s-btn s-btn-outline s-btn-xs" onclick="addVarRow()">
                                <i class="las la-plus"></i> Agregar
                            </button>
                        </div>
                        <div id="var-list" style="display:flex;flex-direction:column;gap:6px"></div>
                    </div>
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                            <label class="s-input-label" style="font-size:12px">Extras / Add-ons</label>
                            <button type="button" class="s-btn s-btn-outline s-btn-xs" onclick="addAddonRow()">
                                <i class="las la-plus"></i> Agregar
                            </button>
                        </div>
                        <div id="addon-list" style="display:flex;flex-direction:column;gap:6px"></div>
                    </div>
                    <button type="submit" class="s-btn s-btn-primary" style="justify-content:center;margin-top:4px">
                        <i class="las la-save"></i> Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- STOCK ADJUSTMENT MODAL -->
<div id="stock-adj-modal" class="s-modal">
    <div class="s-modal-bg" onclick="this.parentElement.classList.remove('open')"></div>
    <div class="s-modal-box">
        <div class="s-modal-head">
            <h3 class="s-modal-title">Ajustar Stock (Kardex)</h3>
            <button class="s-modal-close" onclick="this.closest('.s-modal').classList.remove('open')">✕</button>
        </div>
        <div class="s-modal-body">
            <form id="stock-adj-form" method="POST" action="">
                @csrf
                <div style="background:var(--s-surface-2);padding:12px;border-radius:8px;border:1px solid var(--s-border);margin-bottom:14px">
                    <span style="font-size:13px;font-weight:700;color:var(--s-text);display:block" id="adj-product-name">Producto: </span>
                    <span style="font-size:12px;color:var(--s-text-3);display:block;margin-top:3px" id="adj-product-stock">Stock actual: 0.00</span>
                </div>
                <div class="s-form-grid">
                    <div class="s-input-group">
                        <label class="s-input-label">Tipo de Movimiento *</label>
                        <select class="s-input" name="adjust_type" required>
                            <option value="in">Ingreso (+) (Entrada)</option>
                            <option value="out">Egreso (-) (Salida)</option>
                        </select>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Cantidad a ajustar *</label>
                        <input class="s-input" type="number" name="adjust_qty" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    <div class="s-input-group" style="grid-column: 1 / -1;">
                        <label class="s-input-label">Almacén de Ajuste *</label>
                        <select class="s-input" name="adjust_warehouse_id" required>
                            @foreach($warehouses ?? [] as $wh)
                                <option value="{{ $wh->id }}" {{ $wh->is_default ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="s-input-group" style="grid-column: 1 / -1;">
                        <label class="s-input-label">Motivo / Nota del ajuste *</label>
                        <input class="s-input" name="adjust_reason" placeholder="Ej: Conteo físico, Merma por daño, etc." required>
                    </div>
                    <button type="submit" class="s-btn s-btn-primary" style="justify-content:center;margin-top:8px;grid-column: 1 / -1;">
                        <i class="las la-save"></i> Guardar Ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
function filterCatalogProducts() {
    var query = (document.getElementById('catalogSearch')?.value || '').trim().toLowerCase();
    var status = document.getElementById('catalogStatus')?.value || 'all';
    var category = document.querySelector('.catalog-pill.active')?.dataset.category || 'all';
    document.querySelectorAll('.catalog-product-card').forEach(function(card) {
        var matches = (!query || card.textContent.toLowerCase().includes(query))
            && (status === 'all' || card.dataset.status === status)
            && (category === 'all' || card.dataset.category === category);
        card.style.display = matches ? 'block' : 'none';
    });
}
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('catalogSearch')?.addEventListener('input', filterCatalogProducts);
    document.getElementById('catalogStatus')?.addEventListener('change', filterCatalogProducts);
    document.querySelectorAll('.catalog-pill').forEach(function(pill) {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.catalog-pill').forEach(function(item) { item.classList.remove('active'); });
            this.classList.add('active');
            filterCatalogProducts();
        });
    });
});

function openProdModal() {
    document.getElementById('prod-modal-title').textContent = 'Nuevo Producto';
    document.getElementById('prod-form').action = '{{ route("seller.products.store") }}';
    document.getElementById('prod-method').value = 'POST';
    ['prod-name','prod-price','prod-discount','prod-desc','prod-barcode'].forEach(function(id){ document.getElementById(id).value=''; });
    document.getElementById('prod-sort').value = 0;
    document.getElementById('prod-status').checked = true;
    document.getElementById('prod-stock-type').value = 'packaged';
    document.getElementById('prod-tax-type').value = 'gravado';
    document.getElementById('prod-sunat-code').value = '50190000';
    document.getElementById('prod-unit').value = 'NIU';
    document.getElementById('prod-cost').value = '0';
    
    // Configuración para nuevo producto: stock editable
    var stockField = document.getElementById('prod-initial-stock');
    stockField.value = '0';
    stockField.removeAttribute('readonly');
    stockField.style.backgroundColor = '';

    document.getElementById('prod-min-stock').value = '5';
    document.getElementById('var-list').innerHTML = '';
    document.getElementById('addon-list').innerHTML = '';
    vi = 0; ai = 0;
    toggleStockFields();
    document.getElementById('prod-modal').classList.add('open');
}

function editProduct(id, name, price, discount, desc, cat, sort, status, stockType, barcode, vars, addons, taxType, sunatCode, unit, cost, stock, minStock) {
    document.getElementById('prod-modal-title').textContent = 'Editar Producto';
    document.getElementById('prod-form').action = '{{ route("seller.products.update", ":id") }}'.replace(':id', id);
    document.getElementById('prod-method').value = 'POST';
    document.getElementById('prod-name').value = name;
    document.getElementById('prod-price').value = price;
    document.getElementById('prod-discount').value = discount || '';
    document.getElementById('prod-desc').value = desc || '';
    document.getElementById('prod-cat').value = cat;
    document.getElementById('prod-sort').value = sort;
    document.getElementById('prod-barcode').value = barcode || '';
    document.getElementById('prod-status').checked = status == 1;
    document.getElementById('prod-stock-type').value = stockType || 'packaged';
    document.getElementById('prod-tax-type').value = taxType || 'gravado';
    document.getElementById('prod-sunat-code').value = sunatCode || '';
    document.getElementById('prod-unit').value = unit || 'NIU';
    document.getElementById('prod-cost').value = cost || '0';
    
    // Configuración para editar producto: stock bloqueado (sólo lectura)
    var stockField = document.getElementById('prod-initial-stock');
    stockField.value = stock || '0';
    stockField.setAttribute('readonly', 'readonly');
    stockField.style.backgroundColor = 'var(--s-surface-3)';

    document.getElementById('prod-min-stock').value = minStock || '5';
    document.getElementById('var-list').innerHTML = (vars || []).map((v,i) => varRow(i, v.name, v.price)).join('');
    document.getElementById('addon-list').innerHTML = (addons || []).map((a,i) => addonRow(i, a.name, a.price)).join('');
    vi = (vars || []).length; ai = (addons || []).length;
    toggleStockFields();
    document.getElementById('prod-modal').classList.add('open');
}

function openStockAdjModal(productId, productName, currentStock, unit) {
    document.getElementById('adj-product-name').textContent = 'Producto: ' + productName;
    document.getElementById('adj-product-stock').textContent = 'Stock actual: ' + parseFloat(currentStock).toFixed(2) + ' (' + unit + ')';
    
    var form = document.getElementById('stock-adj-form');
    form.action = '{{ route("seller.products.adjust-stock", ":id") }}'.replace(':id', productId);
    
    // Reset form fields
    form.reset();
    
    document.getElementById('stock-adj-modal').classList.add('open');
}

function toggleStockFields() {
    var type = document.getElementById('prod-stock-type').value;
    document.getElementById('stock-fields').style.display = type === 'packaged' ? 'grid' : 'none';
}

var vi = 0, ai = 0;

document.addEventListener('DOMContentLoaded', function() {
    toggleStockFields();
});

function varRow(i, name, price) {
    return '<div style="display:flex;gap:6px;align-items:center"><input class="s-input" name="variations['+i+'][name]" value="'+(name||'')+'" placeholder="Nombre variación" style="flex:1"><input class="s-input" name="variations['+i+'][price]" type="number" step="0.01" value="'+(price||'')+'" placeholder="Precio" style="width:90px"><button type="button" onclick="this.parentElement.remove()" class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)">✕</button></div>';
}
function addonRow(i, name, price) {
    return '<div style="display:flex;gap:6px;align-items:center"><input class="s-input" name="addons['+i+'][name]" value="'+(name||'')+'" placeholder="Nombre extra" style="flex:1"><input class="s-input" name="addons['+i+'][price]" type="number" step="0.01" value="'+(price||'')+'" placeholder="Precio" style="width:90px"><button type="button" onclick="this.parentElement.remove()" class="s-btn s-btn-ghost s-btn-xs" style="color:var(--s-danger)">✕</button></div>';
}

function addVarRow() {
    document.getElementById('var-list').insertAdjacentHTML('beforeend', varRow(vi++));
}
function addAddonRow() {
    document.getElementById('addon-list').insertAdjacentHTML('beforeend', addonRow(ai++));
}

function autoFillSunatCode() {
    var select = document.getElementById('prod-cat');
    var selectedOption = select.options[select.selectedIndex];
    if (!selectedOption) return;
    
    var catName = selectedOption.text.toLowerCase();
    var sunatField = document.getElementById('prod-sunat-code');
    
    // Only auto-fill if the field is currently empty
    if (sunatField.value.trim() !== '') return;
    
    if (catName.includes('bebida') || catName.includes('gaseosa') || catName.includes('agua') || catName.includes('jugo') || catName.includes('refresco')) {
        sunatField.value = '50151500'; // Bebidas no alcohólicas
    } else if (catName.includes('cerveza') || catName.includes('vino') || catName.includes('licor') || catName.includes('trago') || catName.includes('alcohol') || catName.includes('coctel') || catName.includes('cóctel')) {
        sunatField.value = '50151600'; // Bebidas alcohólicas
    } else if (catName.includes('comida') || catName.includes('plato') || catName.includes('entrada') || catName.includes('postre') || catName.includes('cocina') || catName.includes('menú') || catName.includes('menu') || catName.includes('carta')) {
        sunatField.value = '50250000'; // Platos preparados
    } else {
        sunatField.value = '50190000'; // Lácteos / Alimentos generales default
    }
}
</script>
@endpush
@endsection
