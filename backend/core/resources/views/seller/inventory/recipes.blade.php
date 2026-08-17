@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-book-open"></i></span>
Recetas
@endsection

@section('seller-content')
<div class="s-content">

{{-- Tabs ────────────────────────────────────────────────────────────────── --}}
<div style="display:flex;gap:8px;margin-bottom:20px;border-bottom:2px solid var(--s-border);padding-bottom:0;">
    <button id="tab-kitchen" class="s-tab-btn s-tab-active" onclick="switchTab('kitchen')">
        <i class="las la-utensils"></i> Cocina
        <span class="s-badge s-badge-blue" style="font-size:10px;margin-left:4px;">{{ $kitchenRecipes->count() }}</span>
    </button>
    <button id="tab-bar" class="s-tab-btn" onclick="switchTab('bar')">
        <i class="las la-cocktail"></i> Barra / Licores
        <span class="s-badge s-badge-orange" style="font-size:10px;margin-left:4px;">{{ $barRecipes->count() }}</span>
    </button>
    <button id="tab-production" class="s-tab-btn" onclick="switchTab('production')">
        <i class="las la-industry"></i> Producciones
    </button>
</div>

{{-- ── TAB COCINA ────────────────────────────────────────────────────────── --}}
<div id="pane-kitchen">
    <div class="s-card" style="margin-bottom:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <h3 class="s-card-title"><i class="las la-utensils" style="color:var(--s-accent)"></i> Recetas de Cocina</h3>
            <button class="s-btn s-btn-primary" onclick="openRecipeModal('kitchen')">
                <i class="las la-plus"></i> Nueva Receta
            </button>
        </div>
    </div>

    @forelse($kitchenRecipes as $recipe)
    <div class="s-card" style="margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <b style="font-size:15px;">{{ $recipe->name }}</b>
                    @if($recipe->product)
                        <span class="s-badge s-badge-blue" style="font-size:10px;">↔ {{ $recipe->product->name }}</span>
                    @endif
                </div>
                <div style="font-size:12px;color:var(--s-text-3);">
                    Produce <b>{{ $recipe->portions }}</b> {{ $recipe->unit_produced }}
                    @if($recipe->notes) · {{ $recipe->notes }} @endif
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <button class="s-btn s-btn-primary s-btn-sm"
                    onclick="openProduceModal({{ $recipe->id }}, '{{ addslashes($recipe->name) }}', {{ $recipe->portions }}, '{{ $recipe->unit_produced }}')"
                    title="Registrar producción">
                    <i class="las la-play-circle"></i> Producir
                </button>
                <button class="s-btn s-btn-ghost s-btn-sm" onclick="editRecipe({{ $recipe->id }})" title="Editar">
                    <i class="las la-edit"></i>
                </button>
                <form method="POST" action="{{ route('seller.inventory.recipes.delete', $recipe->id) }}"
                    onsubmit="return confirm('¿Eliminar receta {{ addslashes($recipe->name) }}?')" style="display:inline;">
                    @csrf
                    <button class="s-btn s-btn-ghost s-btn-sm" style="color:var(--s-danger)">
                        <i class="las la-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Cost / Margin Summary --}}
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin:12px 0 8px;padding:10px 14px;background:var(--s-bg-2);border-radius:10px;font-size:12px;">
            <div>
                <span style="color:var(--s-text-3);">Costo Total:</span>
                <b style="color:var(--s-accent);">S/ {{ number_format($recipe->total_cost, 2) }}</b>
            </div>
            <div>
                <span style="color:var(--s-text-3);">Costo/Porción:</span>
                <b style="color:var(--s-accent-dark);">S/ {{ number_format($recipe->cost_per_portion, 2) }}</b>
            </div>
            @if($recipe->product_price > 0)
            <div>
                <span style="color:var(--s-text-3);">Precio Venta:</span>
                <b>S/ {{ number_format($recipe->product_price, 2) }}</b>
            </div>
            <div>
                <span style="color:var(--s-text-3);">Margen:</span>
                @php $m = $recipe->margin; @endphp
                <b style="color:{{ $m >= 30 ? '#16a34a' : ($m >= 15 ? '#f59e0b' : '#ef4444') }}">
                    {{ $m }}%
                </b>
            </div>
            @endif
            <div>
                <span style="color:var(--s-text-3);">Precio Sugerido:</span>
                <b style="color:#16a34a;">S/ {{ number_format($recipe->suggested_price, 2) }}</b>
            </div>
        </div>

        {{-- Ingredientes --}}
        <div style="margin-top:12px;overflow-x:auto;">
            <table class="s-table" style="font-size:12px;">
                <thead><tr>
                    <th>Insumo</th><th>Cant. Bruta</th><th>% Merma</th><th>Cant. Neta</th><th>Unidad</th>
                </tr></thead>
                <tbody>
                @foreach($recipe->items as $ri)
                <tr>
                    <td><b>{{ $ri->item->name ?? '—' }}</b></td>
                    <td>{{ number_format($ri->quantity_gross, 3) }}</td>
                    <td>
                        @if($ri->waste_pct > 0)
                            <span class="s-badge s-badge-orange">{{ $ri->waste_pct }}%</span>
                        @else
                            <span style="color:var(--s-text-3)">0%</span>
                        @endif
                    </td>
                    <td><b style="color:var(--s-accent-dark)">{{ number_format($ri->quantity_net, 3) }}</b></td>
                    <td>{{ $ri->unit }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="s-card" style="text-align:center;padding:40px;color:var(--s-text-3);">
        <i class="las la-utensils" style="font-size:40px;margin-bottom:8px;display:block;"></i>
        <b>Sin recetas de cocina</b><br>
        <span style="font-size:13px;">Crea tu primera receta para controlar el consumo de insumos.</span>
    </div>
    @endforelse
</div>

{{-- ── TAB BARRA ─────────────────────────────────────────────────────────── --}}
<div id="pane-bar" style="display:none;">
    <div class="s-card" style="margin-bottom:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <h3 class="s-card-title"><i class="las la-wine-glass-alt" style="color:#f59e0b"></i> Recetas de Barra</h3>
            <button class="s-btn s-btn-primary" onclick="openRecipeModal('bar')">
                <i class="las la-plus"></i> Nueva Receta de Barra
            </button>
        </div>
    </div>

    @forelse($barRecipes as $recipe)
    <div class="s-card" style="margin-bottom:12px;border-left:4px solid #f59e0b;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <i class="las la-cocktail" style="color:#f59e0b"></i>
                    <b style="font-size:15px;">{{ $recipe->name }}</b>
                    @if($recipe->product)
                        <span class="s-badge s-badge-orange" style="font-size:10px;">{{ $recipe->product->name }}</span>
                    @endif
                </div>
                <div style="font-size:12px;color:var(--s-text-3);">
                    Rinde <b>{{ $recipe->portions }}</b> {{ $recipe->unit_produced }}
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <button class="s-btn s-btn-primary s-btn-sm"
                    onclick="openProduceModal({{ $recipe->id }}, '{{ addslashes($recipe->name) }}', {{ $recipe->portions }}, '{{ $recipe->unit_produced }}')">
                    <i class="las la-play-circle"></i> Preparar
                </button>
                <form method="POST" action="{{ route('seller.inventory.recipes.delete', $recipe->id) }}"
                    onsubmit="return confirm('¿Eliminar receta?')" style="display:inline;">
                    @csrf
                    <button class="s-btn s-btn-ghost s-btn-sm" style="color:var(--s-danger)"><i class="las la-trash"></i></button>
                </form>
            </div>
        </div>

        {{-- Cost / Margin Summary --}}
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin:12px 0 8px;padding:10px 14px;background:var(--s-bg-2);border-radius:10px;font-size:12px;">
            <div>
                <span style="color:var(--s-text-3);">Costo Total:</span>
                <b style="color:#f59e0b;">S/ {{ number_format($recipe->total_cost, 2) }}</b>
            </div>
            <div>
                <span style="color:var(--s-text-3);">Costo/Copa:</span>
                <b style="color:#f59e0b;">S/ {{ number_format($recipe->cost_per_portion, 2) }}</b>
            </div>
            @if($recipe->product_price > 0)
            <div>
                <span style="color:var(--s-text-3);">Precio Venta:</span>
                <b>S/ {{ number_format($recipe->product_price, 2) }}</b>
            </div>
            <div>
                <span style="color:var(--s-text-3);">Margen:</span>
                @php $m = $recipe->margin; @endphp
                <b style="color:{{ $m >= 30 ? '#16a34a' : ($m >= 15 ? '#f59e0b' : '#ef4444') }}">
                    {{ $m }}%
                </b>
            </div>
            @endif
            <div>
                <span style="color:var(--s-text-3);">Precio Sugerido:</span>
                <b style="color:#16a34a;">S/ {{ number_format($recipe->suggested_price, 2) }}</b>
            </div>
        </div>

        <div style="margin-top:10px;overflow-x:auto;">
            <table class="s-table" style="font-size:12px;">
                <thead><tr><th>Ingrediente</th><th>Cantidad Bruta</th><th>% Merma</th><th>Cantidad Neta</th><th>Unidad</th></tr></thead>
                <tbody>
                @foreach($recipe->items as $ri)
                <tr>
                    <td>
                        @if($ri->item->is_bar_item)
                            <i class="las la-wine-bottle" style="color:#f59e0b;font-size:11px;"></i>
                        @endif
                        {{ $ri->item->name ?? '—' }}
                        @if($ri->item->bar_category)
                            <span class="s-badge s-badge-gray" style="font-size:9px;text-transform:uppercase;">{{ $ri->item->bar_category }}</span>
                        @endif
                    </td>
                    <td>{{ number_format($ri->quantity_gross, 3) }}</td>
                    <td>{{ $ri->waste_pct > 0 ? $ri->waste_pct . '%' : '—' }}</td>
                    <td><b>{{ number_format($ri->quantity_net, 3) }}</b></td>
                    <td>{{ $ri->unit }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="s-card" style="text-align:center;padding:40px;color:var(--s-text-3);">
        <i class="las la-cocktail" style="font-size:40px;margin-bottom:8px;display:block;color:#f59e0b;"></i>
        <b>Sin recetas de barra</b><br>
        <span style="font-size:13px;">Agrega recetas para controlar el consumo de licores y mezclas.</span>
    </div>
    @endforelse
</div>

{{-- ── TAB PRODUCCIONES ─────────────────────────────────────────────────── --}}
<div id="pane-production" style="display:none;">
    <div class="s-card">
        <h3 class="s-card-title"><i class="las la-industry"></i> Últimas Producciones</h3>
        <div style="overflow-x:auto;">
        <table class="s-table">
            <thead><tr>
                <th>#</th><th>Receta</th><th>Tipo</th><th>Porciones</th><th>Fecha</th><th>Estado</th><th></th>
            </tr></thead>
            <tbody>
            @forelse($productions as $prod)
            <tr>
                <td style="color:var(--s-text-3);font-size:12px;">#{{ $prod->id }}</td>
                <td><b>{{ $prod->recipe->name ?? '—' }}</b></td>
                <td>
                    @if($prod->recipe->recipe_type === 'bar')
                        <span class="s-badge s-badge-orange" style="font-size:10px;"><i class="las la-cocktail"></i> Bar</span>
                    @else
                        <span class="s-badge s-badge-blue" style="font-size:10px;"><i class="las la-utensils"></i> Cocina</span>
                    @endif
                </td>
                <td><b>{{ $prod->portions_produced }}</b></td>
                <td style="font-size:12px;color:var(--s-text-2);">{{ $prod->produced_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>
                    <span class="s-badge {{ $prod->status === 'completed' ? 's-badge-green' : 's-badge-red' }}">
                        {{ $prod->status === 'completed' ? 'Completado' : 'Anulado' }}
                    </span>
                </td>
                <td>
                    @if($prod->status === 'completed')
                    <form method="POST" action="{{ route('seller.inventory.productions.void', $prod->id) }}"
                        onsubmit="return confirm('¿Anular producción #{{ $prod->id }}? Se restaurará el stock de insumos.')" style="display:inline;">
                        @csrf
                        <button class="s-btn s-btn-ghost s-btn-sm" style="color:var(--s-danger)" title="Anular producción">
                            <i class="las la-undo"></i>
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:var(--s-text-3);padding:30px;">Sin producciones registradas</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

</div>

{{-- ════ Modal: Nueva Receta ════════════════════════════════════════════════ --}}
<div id="recipe-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(10,20,35,.65);align-items:center;justify-content:center;backdrop-filter:blur(4px);">
<div class="s-card" style="width:100%;max-width:680px;max-height:90vh;overflow-y:auto;padding:24px;border-radius:18px;" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3 style="margin:0;font-weight:900;" id="recipe-modal-title"><i class="las la-book-open" style="color:var(--s-accent)"></i> Nueva Receta</h3>
        <button class="s-btn s-btn-ghost" onclick="closeRecipeModal()" style="font-size:20px;">✕</button>
    </div>
    <form id="recipe-form" method="POST" action="{{ route('seller.inventory.recipes.store') }}">
        @csrf
        <input type="hidden" name="recipe_id" id="recipe-id-field" value="">
        <input type="hidden" name="recipe_type" id="recipe-type-field" value="kitchen">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div class="s-input-group" style="grid-column:1/-1;">
                <label class="s-input-label">Nombre de la Receta *</label>
                <input class="s-input" name="name" id="recipe-name" placeholder="Ej: Ceviche mixto, Pisco Sour..." required>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Porciones que produce *</label>
                <input class="s-input" type="number" name="portions" id="recipe-portions" step="0.5" min="0.5" value="1" required>
            </div>
            <div class="s-input-group">
                <label class="s-input-label">Unidad producida</label>
                <select class="s-input" name="unit_produced" id="recipe-unit">
                    <option value="porcion">Porción</option>
                    <option value="plato">Plato</option>
                    <option value="copa">Copa</option>
                    <option value="vaso">Vaso</option>
                    <option value="trago">Trago</option>
                    <option value="botella">Botella</option>
                    <option value="unidad">Unidad</option>
                </select>
            </div>
            <div class="s-input-group" style="grid-column:1/-1;">
                <label class="s-input-label">Producto del menú (opcional)</label>
                <select class="s-input" name="product_id">
                    <option value="">— Sin vincular —</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="s-input-group" style="grid-column:1/-1;">
                <label class="s-input-label">Notas</label>
                <input class="s-input" name="notes" placeholder="Observaciones...">
            </div>
        </div>

        {{-- Ingredientes dinámicos --}}
        <div style="border-top:1px solid var(--s-border);padding-top:16px;margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <b style="font-size:13px;"><i class="las la-list"></i> Ingredientes</b>
                <button type="button" class="s-btn s-btn-ghost s-btn-sm" onclick="addIngredient()">
                    <i class="las la-plus"></i> Agregar
                </button>
            </div>
            <div id="ingredients-container">
                {{-- Líneas añadidas por JS --}}
            </div>
            <div id="no-ingredients-msg" style="text-align:center;color:var(--s-text-3);font-size:12px;padding:16px 0;">
                Haz clic en <b>+ Agregar</b> para añadir ingredientes
            </div>
        </div>
        <input type="hidden" name="ingredients" id="ingredients-json">

        <button type="submit" class="s-btn s-btn-primary" style="width:100%;justify-content:center;"
            onclick="buildIngredientsJson(event)">
            <i class="las la-save"></i> Guardar Receta
        </button>
    </form>
</div>
</div>

{{-- ════ Modal: Producción ══════════════════════════════════════════════════ --}}
<div id="produce-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(10,20,35,.65);align-items:center;justify-content:center;backdrop-filter:blur(4px);">
<div class="s-card" style="width:100%;max-width:400px;padding:24px;border-radius:18px;" onclick="event.stopPropagation()">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h3 style="margin:0;font-weight:900;"><i class="las la-play-circle" style="color:var(--s-accent)"></i> Registrar Producción</h3>
        <button class="s-btn s-btn-ghost" onclick="closeProduceModal()">✕</button>
    </div>
    <form method="POST" action="{{ route('seller.inventory.productions.store') }}">
        @csrf
        <input type="hidden" name="recipe_id" id="produce-recipe-id">
        <p style="font-size:14px;color:var(--s-text-2);margin-bottom:16px;">
            Receta: <b id="produce-recipe-name" style="color:var(--s-accent)"></b><br>
            <small style="color:var(--s-text-3);">Rinde <span id="produce-base-portions"></span> <span id="produce-base-unit"></span> por producción</small>
        </p>
        <div class="s-input-group" style="margin-bottom:16px;">
            <label class="s-input-label">Porciones a producir *</label>
            <input class="s-input" type="number" name="portions_produced" min="1" value="1" required
                style="font-size:22px;text-align:center;font-weight:900;">
        </div>
        <div class="s-input-group" style="margin-bottom:16px;">
            <label class="s-input-label">Notas</label>
            <input class="s-input" name="notes" placeholder="Observaciones...">
        </div>
        <button type="submit" class="s-btn s-btn-primary" style="width:100%;justify-content:center;">
            <i class="las la-check"></i> Confirmar Producción
        </button>
    </form>
</div>
</div>

@push('style')
<style>
.s-tab-btn {
    background: none;
    border: none;
    padding: 10px 18px;
    font-size: 13px;
    font-weight: 600;
    color: var(--s-text-3);
    cursor: pointer;
    border-bottom: 3px solid transparent;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all .2s;
}
.s-tab-active {
    color: var(--s-accent);
    border-bottom-color: var(--s-accent);
}
.ingredient-row {
    display: grid;
    grid-template-columns: 1fr 90px 80px 70px 80px 32px;
    gap: 6px;
    align-items: center;
    margin-bottom: 8px;
    background: var(--s-bg-2);
    padding: 8px 10px;
    border-radius: 10px;
}
@media(max-width:600px) {
    .ingredient-row { grid-template-columns: 1fr 1fr 1fr; }
}
</style>
@endpush

@push('script')
<script>
// ── Tab switching ─────────────────────────────────────────────────────────
function switchTab(tab) {
    ['kitchen','bar','production'].forEach(t => {
        document.getElementById('pane-' + t).style.display = t === tab ? 'block' : 'none';
        document.getElementById('tab-' + t).classList.toggle('s-tab-active', t === tab);
    });
}

// ── Recipe modal ─────────────────────────────────────────────────────────
let ingredients = [];
const invItems = @json($items->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit]));

function openRecipeModal(type) {
    ingredients = [];
    document.getElementById('recipe-type-field').value = type;
    document.getElementById('recipe-modal-title').innerHTML =
        '<i class="las la-book-open" style="color:var(--s-accent)"></i> ' +
        (type === 'bar' ? 'Nueva Receta de Barra' : 'Nueva Receta de Cocina');
    renderIngredients();
    document.getElementById('recipe-form').reset();
    document.getElementById('recipe-id-field').value = '';
    document.getElementById('recipe-modal').style.display = 'flex';
}
function closeRecipeModal() {
    document.getElementById('recipe-modal').style.display = 'none';
}

function addIngredient() {
    ingredients.push({ item_id: '', quantity_gross: 1, waste_pct: 0, unit: 'UNIDAD' });
    renderIngredients();
}

function removeIngredient(idx) {
    ingredients.splice(idx, 1);
    renderIngredients();
}

function renderIngredients() {
    const container = document.getElementById('ingredients-container');
    const noMsg = document.getElementById('no-ingredients-msg');
    noMsg.style.display = ingredients.length ? 'none' : 'block';

    const opts = invItems.map(i => `<option value="${i.id}">${i.name} (${i.unit})</option>`).join('');

    container.innerHTML = ingredients.map((ing, idx) => `
        <div class="ingredient-row">
            <select class="s-input" style="font-size:12px;" onchange="ingredients[${idx}].item_id=this.value">
                <option value="">— Insumo —</option>${opts}
            </select>
            <input class="s-input" type="number" step="0.001" min="0" value="${ing.quantity_gross}"
                placeholder="Cant. bruta" style="font-size:12px;"
                onchange="ingredients[${idx}].quantity_gross=parseFloat(this.value)||0;updateNet(${idx})">
            <input class="s-input" type="number" step="0.1" min="0" max="100" value="${ing.waste_pct}"
                placeholder="% merma" style="font-size:12px;"
                onchange="ingredients[${idx}].waste_pct=parseFloat(this.value)||0;updateNet(${idx})">
            <span id="net-${idx}" style="font-size:11px;color:var(--s-accent-dark);font-weight:700;text-align:center;">
                ${calcNet(ing).toFixed(3)}
            </span>
            <select class="s-input" style="font-size:12px;" onchange="ingredients[${idx}].unit=this.value">
                ${[
                    {code:'NIU',label:'UND'},{code:'KGM',label:'KG'},{code:'GRM',label:'GR'},
                    {code:'LTR',label:'LT'},{code:'MLT',label:'ML'},{code:'ONZ',label:'OZ'},
                    {code:'BO',label:'BOT'},{code:'CA',label:'LT'},{code:'BX',label:'CAJ'},
                    {code:'DZN',label:'DOC'},{code:'C62',label:'PZ'},{code:'ZZ',label:'SERV'}
                ].map(u =>
                    `<option value="${u.code}" ${ing.unit===u.code?'selected':''}>${u.label}</option>`).join('')}
            </select>
            <button type="button" class="s-btn s-btn-ghost" style="color:var(--s-danger);padding:4px;"
                onclick="removeIngredient(${idx})"><i class="las la-times"></i></button>
        </div>
    `).join('');

    // Restore selected values
    ingredients.forEach((ing, idx) => {
        const sel = container.querySelectorAll('select')[idx * 3];
        if (sel && ing.item_id) sel.value = ing.item_id;
    });
}

function calcNet(ing) {
    return parseFloat(ing.quantity_gross) * (1 - parseFloat(ing.waste_pct) / 100);
}
function updateNet(idx) {
    const el = document.getElementById('net-' + idx);
    if (el) el.textContent = calcNet(ingredients[idx]).toFixed(3);
}

function buildIngredientsJson(e) {
    if (!ingredients.length) {
        e.preventDefault();
        alert('Agrega al menos un ingrediente a la receta');
        return;
    }
    // Collect current select/input values from DOM
    const rows = document.querySelectorAll('.ingredient-row');
    rows.forEach((row, idx) => {
        const selects = row.querySelectorAll('select');
        const inputs  = row.querySelectorAll('input');
        if (selects[0]) ingredients[idx].item_id       = selects[0].value;
        if (inputs[0])  ingredients[idx].quantity_gross = parseFloat(inputs[0].value) || 0;
        if (inputs[1])  ingredients[idx].waste_pct      = parseFloat(inputs[1].value) || 0;
        if (selects[1]) ingredients[idx].unit           = selects[1].value;
    });
    document.getElementById('ingredients-json').value = JSON.stringify(ingredients);
}

// ── Produce modal ─────────────────────────────────────────────────────────
function openProduceModal(recipeId, recipeName, portions, unit) {
    document.getElementById('produce-recipe-id').value   = recipeId;
    document.getElementById('produce-recipe-name').textContent = recipeName;
    document.getElementById('produce-base-portions').textContent = portions;
    document.getElementById('produce-base-unit').textContent = unit;
    document.getElementById('produce-modal').style.display = 'flex';
}
function closeProduceModal() {
    document.getElementById('produce-modal').style.display = 'none';
}

document.getElementById('recipe-modal').addEventListener('click', closeRecipeModal);
document.getElementById('produce-modal').addEventListener('click', closeProduceModal);
</script>
@endpush
@endsection
