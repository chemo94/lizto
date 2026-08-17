<div id="premium-cta" class="p-modal-mask" style="{{ session('premium_required') ? 'display:flex' : 'display:none' }}">
    <div class="bg" onclick="this.parentElement.style.display='none'"></div>
    <div class="box" style="text-align:center">
        <button class="close" onclick="this.closest('.p-modal-mask').style.display='none'">✕</button>
        <div style="font-size:64px;margin-bottom:12px">⭐</div>
        <h3 style="font-size:22px;margin-bottom:8px">Funcionalidad Premium</h3>
        <p style="color:var(--pmt);margin:0 0 16px;font-size:14px">
            Esta funcionalidad requiere un <strong>plan Premium o Destacado</strong>.<br>
            Suscríbete a uno de nuestros paquetes empresariales para desbloquearla.
        </p>
        <div style="display:flex;gap:8px;justify-content:center">
            <a href="{{ route('seller.dashboard') }}" class="p-btn p-btn-outline">Ahora no</a>
            <a href="https://wa.me/51997428341/?text=Hola%2C%20quiero%20adquirir%20un%20plan%20empresarial%20Lizto Delivery" target="_blank" class="p-btn p-btn-primary">
                <i class="lab la-whatsapp"></i> Suscribirme
            </a>
        </div>
    </div>
</div>
