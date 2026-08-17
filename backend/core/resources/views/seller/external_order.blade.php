@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-globe"></i></span> Pedido Externo
@endsection

@section('seller-content')
<div class="s-content">
    <form method="POST" action="{{ route('seller.external.order') }}" id="external-order-form" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
        @csrf

        <div>
            <div class="s-card">
                <h3 class="s-card-title"><i class="las la-tag"></i> Origen del Pedido</h3>

                <div class="s-form-group">
                    <label class="s-label">Plataforma</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:4px">
                        @foreach(['daz'=>'DAZ','llama'=>'LLAMA','rappi'=>'RAPPI','pedidosya'=>'PEDIDOSYA','delivery'=>'Delivery','lizto_delivery'=>'Lizto App'] as $val=>$label)
                        @php $colors = ['daz'=>'#e11d48','llama'=>'#f59e0b','rappi'=>'#8b5cf6','pedidosya'=>'#0891b2','delivery'=>'#64748b','lizto_delivery'=>'#16a34a']; @endphp
                        <label class="s-radio-card" style="border:2px solid var(--s-border);border-radius:10px;padding:10px;text-align:center;cursor:pointer;transition:.15s;display:flex;flex-direction:column;align-items:center;gap:4px;{{ old('order_type') === $val ? 'border-color:'.$colors[$val].';background:'.$colors[$val].'10' : '' }}" onclick="this.closest('.s-radio-card').querySelector('input').checked=true;this.closest('.s-radio-card').style.borderColor='{{ $colors[$val] }}';document.querySelectorAll('.s-radio-card').forEach(e=>e.style.borderColor='var(--s-border)');this.style.borderColor='{{ $colors[$val] }}'">
                            <input type="radio" name="order_type" value="{{ $val }}" {{ old('order_type') === $val || $val === 'daz' ? 'checked' : '' }} style="display:none">
                            <span style="font-weight:900;font-size:10px;padding:2px 8px;border-radius:4px;color:#fff;background:{{ $colors[$val] }}">{{ $label }}</span>
                            <span style="font-size:10px;color:var(--s-text-3)">Externo</span>
                        </label>
                        @endforeach
                    </div>
                    @error('order_type')<span class="s-field-error">{{ $message }}</span>@enderror
                </div>

                <div class="s-form-group">
                    <label class="s-label">Buscar Cliente (SUNAT)</label>
                    @include('seller.partials.sunat_lookup', [
                        'prefix'      => 'ext',
                        'defaultType' => '6',
                        'nameTarget'  => 'ext-customer-name',
                        'phoneTarget' => 'ext-customer-phone',
                        'tpdocName'   => 'customer_doc_type',
                        'numdocName'  => 'customer_doc',
                    ])
                </div>

                <div class="s-form-group">
                    <label class="s-label">Nombre del Cliente</label>
                    <input type="text" name="customer_name" id="ext-customer-name" class="s-input" value="{{ old('customer_name') }}" placeholder="Cliente">
                </div>

                <div class="s-form-group">
                    <label class="s-label">Teléfono</label>
                    <input type="text" name="customer_phone" id="ext-customer-phone" class="s-input" value="{{ old('customer_phone') }}" placeholder="999 999 999">
                </div>

                <div class="s-form-group">
                    <label class="s-label">Dirección de Entrega</label>
                    <textarea name="delivery_address" id="ext-delivery-addr" class="s-input" rows="2" placeholder="Dirección...">{{ old('delivery_address') }}</textarea>
                    <input type="hidden" name="delivery_lat" id="ext-delivery-lat" value="{{ old('delivery_lat') }}">
                    <input type="hidden" name="delivery_lng" id="ext-delivery-lng" value="{{ old('delivery_lng') }}">
                    @include('seller.partials.google_address', [
                        'addressId' => 'ext-delivery-addr',
                        'latId'     => 'ext-delivery-lat',
                        'lngId'     => 'ext-delivery-lng',
                        'callback'  => 'initExtAddress',
                    ])
                </div>

                <div class="s-form-group">
                    <label class="s-label">Notas</label>
                    <textarea name="notes" class="s-input" rows="2" placeholder="Notas del pedido...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div>
            <div class="s-card">
                <h3 class="s-card-title"><i class="las la-shopping-cart"></i> Productos</h3>

                <div id="product-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;max-height:360px;overflow-y:auto">
                    <div class="product-row" style="display:flex;gap:8px;align-items:center;background:var(--s-surface-2);border-radius:8px;padding:8px">
                        <select name="items[0][product_id]" class="s-input product-select" style="flex:1;min-width:0" onchange="updateProductRow(this)">
                            <option value="">Seleccionar...</option>
                            @foreach($categories as $cat)
                            <optgroup label="{{ $cat->name }}">
                                @foreach($products->where('store_category_id',$cat->id) as $p)
                                <option value="{{ $p->id }}" data-price="{{ $p->finalPrice() }}">{{ $p->name }} — S/{{ number_format($p->finalPrice(),2) }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        <input type="number" name="items[0][quantity]" class="s-input" value="1" min="1" style="width:60px;text-align:center" placeholder="Cant">
                        <input type="number" name="items[0][price]" class="s-input item-price" value="" min="0" step="0.01" style="width:90px" placeholder="Precio">
                        <button type="button" class="s-btn s-btn-danger s-btn-xs" onclick="this.closest('.product-row').remove()" style="flex-shrink:0"><i class="las la-trash"></i></button>
                    </div>
                </div>

                <button type="button" class="s-btn s-btn-outline s-btn-sm" onclick="addProductRow()" style="width:100%;justify-content:center;margin-bottom:12px">
                    <i class="las la-plus"></i> Agregar Producto
                </button>

                <div style="display:flex;gap:8px;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid var(--s-border)">
                    <span style="font-weight:800;font-size:18px;color:var(--s-text)">S/ <span id="external-total">0.00</span></span>
                    <button type="submit" class="s-btn s-btn-primary" style="gap:6px">
                        <i class="las la-check-circle"></i> Registrar Pedido
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('script')
@php $optsHtml = ''; foreach($categories as $cat) { $optsHtml .= '<optgroup label="'.e($cat->name).'">'; foreach($products->where('store_category_id',$cat->id) as $p) { $optsHtml .= '<option value="'.$p->id.'" data-price="'.$p->finalPrice().'">'.e($p->name).' — S/'.number_format($p->finalPrice(),2).'</option>'; } $optsHtml .= '</optgroup>'; } @endphp
<script>
var rowIndex = 1;
var PRODUCT_OPTIONS = '{!! $optsHtml !!}';
function addProductRow() {
    var html = '<div class="product-row" style="display:flex;gap:8px;align-items:center;background:var(--s-surface-2);border-radius:8px;padding:8px">' +
        '<select name="items['+rowIndex+'][product_id]" class="s-input product-select" style="flex:1;min-width:0" onchange="updateProductRow(this)">' +
        '<option value="">Seleccionar...</option>' + PRODUCT_OPTIONS + '</select>' +
        '<input type="number" name="items['+rowIndex+'][quantity]" class="s-input" value="1" min="1" style="width:60px;text-align:center" placeholder="Cant">' +
        '<input type="number" name="items['+rowIndex+'][price]" class="s-input item-price" value="" min="0" step="0.01" style="width:90px" placeholder="Precio">' +
        '<button type="button" class="s-btn s-btn-danger s-btn-xs" onclick="this.closest(\'.product-row\').remove()" style="flex-shrink:0"><i class="las la-trash"></i></button>' +
        '</div>';
    document.getElementById('product-list').insertAdjacentHTML('beforeend', html);
    rowIndex++;
}
function updateProductRow(sel) {
    var opt = sel.options[sel.selectedIndex];
    var price = opt.getAttribute('data-price') || '';
    var priceInput = sel.closest('.product-row').querySelector('.item-price');
    if (price && !priceInput.value) priceInput.value = price;
    calcTotal();
}
function calcTotal() {
    var total = 0;
    document.querySelectorAll('.product-row').forEach(function(row) {
        var qty = parseFloat(row.querySelector('[name*="[quantity]"]').value) || 0;
        var price = parseFloat(row.querySelector('.item-price').value) || 0;
        total += qty * price;
    });
    document.getElementById('external-total').textContent = total.toFixed(2);
}
document.addEventListener('input', function(e) {
    if (e.target.closest('.product-row')) calcTotal();
});
document.querySelectorAll('.product-row .product-select').forEach(function(sel) {
    updateProductRow(sel);
});
</script>
@endpush
@endsection