@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-bell"></i></span> Notificaciones Push
@endsection

@section('seller-content')
<div class="s-content">
    <div style="max-width:640px">

        <!-- INFO CARD -->
        <div style="background:linear-gradient(135deg,#0f1923,#162130);border:1px solid rgba(34,197,94,.2);border-radius:20px;padding:28px;margin-bottom:20px;position:relative;overflow:hidden">
            <div style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;border-radius:50%;background:rgba(34,197,94,.06);pointer-events:none"></div>
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px">
                <div style="width:52px;height:52px;border-radius:14px;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.25);display:grid;place-items:center;font-size:26px;flex-shrink:0">🔔</div>
                <div>
                    <h3 style="color:#fff;font-size:18px;font-weight:800;margin:0 0 4px">Notificaciones Push</h3>
                    <p style="color:rgba(255,255,255,.45);font-size:13px;margin:0">{{ $userCount }} usuarios recibirán tu mensaje en tiempo real</p>
                </div>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 16px;text-align:center;flex:1;min-width:100px">
                    <div style="font-size:22px;font-weight:900;color:#22c55e">{{ $userCount }}</div>
                    <small style="color:rgba(255,255,255,.4);font-size:10px;font-weight:600">SUSCRIPTORES</small>
                </div>
                <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 16px;text-align:center;flex:1;min-width:100px">
                    <div style="font-size:22px;font-weight:900;color:#3b82f6">📱</div>
                    <small style="color:rgba(255,255,255,.4);font-size:10px;font-weight:600">MULTICANAL</small>
                </div>
                <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 16px;text-align:center;flex:1;min-width:100px">
                    <div style="font-size:22px;font-weight:900;color:#f59e0b">⚡</div>
                    <small style="color:rgba(255,255,255,.4);font-size:10px;font-weight:600">TIEMPO REAL</small>
                </div>
            </div>
        </div>

        <!-- FORM -->
        <div class="s-card">
            <h3 class="s-card-title"><i class="las la-paper-plane"></i> Enviar Notificación</h3>

            <form method="POST" action="{{ route('seller.notifications.send') }}">
                @csrf
                <div class="s-form-grid">
                    <div class="s-input-group">
                        <label class="s-input-label">Título *</label>
                        <input class="s-input" name="title" placeholder="Ej: 🎉 ¡Promoción especial hoy!" maxlength="100" required id="notif-title">
                        <span style="font-size:11px;color:var(--s-text-3);margin-top:4px" id="title-count">0 / 100</span>
                    </div>
                    <div class="s-input-group">
                        <label class="s-input-label">Mensaje *</label>
                        <textarea class="s-input" name="body" rows="4" placeholder="Escribe aquí el mensaje que recibirán todos los usuarios de tu tienda..." maxlength="255" required id="notif-body"></textarea>
                        <span style="font-size:11px;color:var(--s-text-3);margin-top:4px" id="body-count">0 / 255</span>
                    </div>

                    <!-- PREVIEW -->
                    <div style="background:var(--s-surface-2);border:1px solid var(--s-border);border-radius:14px;padding:16px">
                        <label class="s-input-label" style="margin-bottom:10px;display:block">Vista previa en dispositivo</label>
                        <div style="background:#1c1c1e;border-radius:14px;padding:14px;max-width:300px">
                            <div style="display:flex;align-items:flex-start;gap:10px">
                                <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#22c55e,#16a34a);display:grid;place-items:center;font-size:18px;flex-shrink:0">🏪</div>
                                <div>
                                    <div style="color:#fff;font-size:12px;font-weight:700;margin-bottom:2px" id="prev-title">Título de la notificación</div>
                                    <div style="color:rgba(255,255,255,.55);font-size:11px;line-height:1.4" id="prev-body">Aquí aparece tu mensaje...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="s-btn s-btn-primary s-btn-lg" style="justify-content:center">
                        <i class="las la-paper-plane"></i> Enviar a {{ $userCount }} usuarios
                    </button>
                </div>
            </form>
        </div>

        <!-- TIPS -->
        <div style="margin-top:16px;padding:16px;background:var(--s-info-bg);border:1px solid var(--s-info);border-radius:14px;border-left:3px solid var(--s-info)">
            <div style="font-size:12px;font-weight:700;color:var(--s-info-text);margin-bottom:8px"><i class="las la-lightbulb"></i> Tips para mejores notificaciones</div>
            <ul style="margin:0;padding:0 0 0 16px;font-size:12px;color:var(--s-info-text);line-height:1.7">
                <li>Usa emojis en el título para mayor visibilidad</li>
                <li>Mensajes cortos y directos tienen más impacto</li>
                <li>Evita enviar más de 2 notificaciones por día</li>
            </ul>
        </div>
    </div>
</div>

@push('script')
<script>
var titleInput = document.getElementById('notif-title');
var bodyInput = document.getElementById('notif-body');

function updatePreview() {
    document.getElementById('prev-title').textContent = titleInput.value || 'Título de la notificación';
    document.getElementById('prev-body').textContent = bodyInput.value || 'Aquí aparece tu mensaje...';
    document.getElementById('title-count').textContent = titleInput.value.length + ' / 100';
    document.getElementById('body-count').textContent = bodyInput.value.length + ' / 255';
}

titleInput.addEventListener('input', updatePreview);
bodyInput.addEventListener('input', updatePreview);
</script>
@endpush
@endsection
