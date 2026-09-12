@php
    $adjustmentLines = app(\App\Services\CreditNoteAdjustment::class)->lines((string)$invoice->xml_content);
@endphp
<div id="credit-adjustments">
    <p id="credit-effect" role="status" style="padding:12px; background:var(--s-warning-bg); border-radius:8px;"></p>
    <div data-motives="02" hidden>
        <label class="s-input-label">RUC correcto para la nueva factura *</label>
        <input class="s-input" name="adjustment[correct_ruc]" maxlength="11" pattern="[0-9]{11}" inputmode="numeric" data-required disabled>
        <small>La nota conserva el receptor original. Después se debe emitir otra factura al RUC correcto.</small>
    </div>
    <div data-motives="04" hidden>
        <label class="s-input-label">Descuento global sin impuestos ({{ $invoice->moneda }}) *</label>
        <input class="s-input" type="number" name="adjustment[global_amount]" min="0.01" step="0.01" data-required disabled>
        <small>Se distribuirá entre los ítems y se calcularán los impuestos según el XML original.</small>
    </div>
    <div data-motives="03,05,07,08,09,10,11,12" hidden style="overflow-x:auto; margin:14px 0;">
        <p>Modifica solo los ítems afectados. Los importes son <strong>sin impuestos</strong>; las cantidades corresponden a la unidad original.</p>
        @forelse($adjustmentLines as $line)
        <div class="s-card" data-original-net="{{ $line['net'] }}" data-original-tax="{{ $line['tax'] }}" data-original-quantity="{{ $line['quantity'] }}" style="padding:12px; margin-bottom:8px;">
            <strong>{{ $line['id'] }}. {{ $line['description'] }}</strong>
            <p style="font-size:12px;">Cantidad: {{ $line['quantity'] }} · Valor sin impuestos: {{ $invoice->moneda }} {{ number_format($line['net'],2) }}</p>
            <div data-motives="03" hidden>
                <label class="s-input-label">Descripción corregida (vacío = sin corrección)</label>
                <input class="s-input" name="adjustment[lines][{{ $line['id'] }}][description]" maxlength="500" disabled>
            </div>
            <div data-motives="07" hidden>
                <label class="s-input-label">Cantidad a devolver</label>
                <input class="s-input" type="number" name="adjustment[lines][{{ $line['id'] }}][quantity]" min="0" max="{{ $line['quantity'] }}" step="0.000001" value="0" disabled>
            </div>
            <div data-motives="05,08,09,10,11,12" hidden>
                <label class="s-input-label">Importe a reducir sin impuestos</label>
                <input class="s-input" type="number" name="adjustment[lines][{{ $line['id'] }}][amount]" min="0" max="{{ $line['net'] }}" step="0.01" value="0" disabled>
            </div>
        </div>
        @empty
        <p>No hay detalle XML disponible para calcular ajustes por ítem.</p>
        @endforelse
    </div>
    <div data-motives="13" hidden>
        <label class="s-input-label">Monto neto pendiente de pago *</label>
        <input class="s-input" type="number" name="adjustment[pending_amount]" min="0.01" max="{{ $invoice->total }}" step="0.01" data-required disabled>
        <p>Ingresa el cronograma completo corregido. La suma debe coincidir con el monto pendiente.</p>
        <div id="credit-installments"></div>
        <button type="button" class="s-btn s-btn-outline" id="add-credit-installment">Agregar cuota</button>
        <template id="credit-installment-template">
            <div style="display:flex; gap:8px; margin:8px 0;">
                <input class="s-input" type="date" aria-label="Vencimiento de cuota" min="{{ now('America/Lima')->format('Y-m-d') }}" data-field="date" data-required>
                <input class="s-input" type="number" aria-label="Importe de cuota" min="0.01" step="0.01" data-field="amount" data-required>
                <button class="s-btn s-btn-ghost" type="button" data-remove>Quitar</button>
            </div>
        </template>
    </div>
    <p id="credit-estimate" style="font-weight:700;" role="status"></p>
</div>
@push('script')
<script>
(() => {
    const root = document.getElementById('credit-adjustments');
    const select = document.getElementById('credit-note-reason');
    if (!root || !select) return;
    const effects = {
        '01':'Anula la operación completa. Al aceptarse se aplican la anulación y devolución correspondientes.',
        '02':'Anula el documento por RUC incorrecto. Conserva la venta, sus pagos y el inventario para emitir el comprobante correcto.',
        '03':'Corrige descripciones. No devuelve dinero ni productos y no anula la venta.',
        '04':'Aplica un descuento global proporcional. Reduce el importe y no mueve inventario.',
        '05':'Descuenta únicamente los importes de los ítems elegidos. No mueve inventario.',
        '06':'Devuelve la operación completa. Al aceptarse se anula la venta y se concilian pagos e inventario.',
        '07':'Devuelve solo las cantidades indicadas y sus importes. La venta original continúa vigente.',
        '08':'Aplica una bonificación monetaria sobre los ítems elegidos. No registra una entrega adicional de productos.',
        '09':'Reduce el valor de los ítems elegidos sin devolver productos.',
        '10':'Reduce importes por otro concepto. Explica en el sustento la causa que justifica la nota.',
        '11':'Ajusta importes de exportación. El servidor verifica que los ítems sean de exportación.',
        '12':'Ajusta importes afectos al IVAP. Conserva la tasa del XML y verifica el tributo original.',
        '13':'Corrige el saldo y cronograma de una factura al crédito. No anula la venta ni genera devolución.'
    };
    let next = 0;
    function refresh() {
        root.querySelectorAll('[data-motives]').forEach(section => {
            const active = section.dataset.motives.split(',').includes(select.value);
            section.hidden = !active;
        });
        root.querySelectorAll('input').forEach(input => {
            const active = !input.closest('[hidden]');
            input.disabled = !active;
            input.required = active && input.hasAttribute('data-required');
        });
        document.getElementById('credit-effect').textContent = effects[select.value] || '';
        let total = 0;
        if (['01','02','06'].includes(select.value)) total = Number(@json($invoice->total));
        if (select.value === '04') {
            const net = @json(array_sum(array_column($adjustmentLines, 'net')));
            const tax = @json(array_sum(array_column($adjustmentLines, 'tax')));
            total = Number(root.querySelector('[name="adjustment[global_amount]"]').value || 0) * (net ? 1+tax/net : 1);
        }
        if (['05','07','08','09','10','11','12'].includes(select.value)) root.querySelectorAll('[data-original-net]').forEach(row => {
            const net=Number(row.dataset.originalNet), tax=Number(row.dataset.originalTax), qty=Number(row.dataset.originalQuantity);
            const input=row.querySelector('input:not(:disabled)'); const value=Number(input?.value || 0);
            total += select.value === '07' ? (qty ? (net+tax)*value/qty : 0) : value*(net ? 1+tax/net : 1);
        });
        document.getElementById('credit-estimate').textContent = 'Importe estimado de la nota: '+@json($invoice->moneda)+' '+total.toFixed(2)+'. El servidor valida impuestos y saldos antes de emitir.';
    }
    document.getElementById('add-credit-installment').addEventListener('click', () => {
        const fragment=document.getElementById('credit-installment-template').content.cloneNode(true);
        const row=fragment.firstElementChild;
        row.querySelectorAll('[data-field]').forEach(input=>input.name=`adjustment[installments][${next}][${input.dataset.field}]`);
        next++;
        row.querySelector('[data-remove]').addEventListener('click',()=>{row.remove();refresh();});
        document.getElementById('credit-installments').appendChild(fragment);refresh();
    });
    select.addEventListener('change',refresh);root.addEventListener('input',refresh);refresh();
})();
</script>
@endpush
