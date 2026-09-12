@if($invoice->tipo_doc === '01')
@php
    // An older controller may render this partial during an incomplete deployment.
    // Missing validation must not enable submission or break the invoice detail.
    if (!array_key_exists('raEligibilityError', get_defined_vars())) {
        $raEligibilityError = 'La validación de baja no está disponible. Actualiza SellerPosController y limpia la caché del servidor.';
    }
@endphp
<div id="modal-baja" style="display:none; position:fixed; inset:0; background:rgba(15,25,35,.6); align-items:center; justify-content:center; z-index:9999; padding:16px;">
    <div class="s-card" role="dialog" aria-modal="true" aria-labelledby="baja-title" style="width:100%; max-width:520px; padding:24px; max-height:90vh; overflow:auto;">
        <h3 id="baja-title">Solicitar comunicación de baja (RA)</h3>
        <p>Factura <strong>{{ $invoice->serie }}-{{ $invoice->correlativo }}</strong><br>Emisor: {{ $invoice->company?->business_name }}</p>
        <p>Esta opción da de baja una factura no otorgada al cliente. Si ya fue entregada o puesta a su disposición, utiliza la nota de crédito FC.</p>
        @if($raEligibilityError)
        <p role="alert">{{ $raEligibilityError }}</p>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('seller.invoice.baja'))
        <form method="POST" action="{{ route('seller.invoice.baja', $invoice->id) }}">
            @csrf
            <label class="s-input-label" for="baja-reason">Motivo de baja *</label>
            <textarea id="baja-reason" class="s-input" name="reason" maxlength="100" rows="3" required>{{ old('reason') }}</textarea>
            <label style="display:flex; gap:10px; margin:18px 0;">
                <input type="checkbox" name="not_granted" value="1" required>
                <span>Declaro que esta factura no fue entregada ni puesta a disposición del cliente.</span>
            </label>
            <p style="font-size:12px;">Plazo: siete días calendario desde el día siguiente a la generación. El envío obtiene un ticket; la factura solo se dará de baja cuando SUNAT acepte la solicitud.</p>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="s-btn s-btn-ghost" onclick="toggleModal('modal-baja')">Cerrar</button>
                <button type="submit" class="s-btn s-btn-danger" @disabled($raEligibilityError)>Solicitar baja RA</button>
            </div>
        </form>
        @else
        <p role="alert">La ruta de baja no está disponible. Actualiza routes/web.php y limpia la caché de rutas del servidor.</p>
        <button type="button" class="s-btn s-btn-ghost" onclick="toggleModal('modal-baja')">Cerrar</button>
        @endif
    </div>
</div>
@endif
