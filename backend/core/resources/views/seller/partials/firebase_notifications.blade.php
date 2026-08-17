<script src="https://www.gstatic.com/firebasejs/9.18.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.18.0/firebase-messaging-compat.js"></script>

<style>
#notif-permission-banner {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%) translateY(120px);
    z-index: 99999;
    background: #0f1923;
    color: #fff;
    border: 1px solid rgba(34,197,94,0.3);
    border-radius: 16px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.35);
    max-width: 420px;
    width: calc(100vw - 48px);
    transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s;
    opacity: 0;
    font-family: inherit;
}
#notif-permission-banner.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}
#notif-permission-banner .npb-icon {
    width: 42px; height: 42px;
    background: rgba(34,197,94,0.15);
    border-radius: 12px;
    display: grid; place-items: center;
    flex-shrink: 0;
    font-size: 22px;
}
#notif-permission-banner .npb-body { flex: 1; min-width: 0; }
#notif-permission-banner .npb-title {
    font-size: 13px; font-weight: 800;
    color: #fff; margin: 0 0 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#notif-permission-banner .npb-text {
    font-size: 11.5px; color: rgba(255,255,255,0.6);
    margin: 0; line-height: 1.4;
}
#notif-permission-banner .npb-actions { display: flex; gap: 8px; flex-shrink: 0; }
#notif-permission-banner .npb-allow {
    background: linear-gradient(135deg,#22c55e,#16a34a);
    color: #fff; border: none; border-radius: 9px;
    padding: 8px 14px; font-size: 12px; font-weight: 700;
    cursor: pointer; transition: all .18s; white-space: nowrap;
    font-family: inherit;
}
#notif-permission-banner .npb-allow:hover { transform: scale(1.04); }
#notif-permission-banner .npb-dismiss {
    background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.5);
    border: 1px solid rgba(255,255,255,0.1); border-radius: 9px;
    padding: 8px 12px; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: all .18s; font-family: inherit;
}
#notif-permission-banner .npb-dismiss:hover { color: #fff; background: rgba(255,255,255,0.15); }
</style>

<!-- Notification Permission Banner -->
<div id="notif-permission-banner" role="alertdialog" aria-label="Solicitud de permiso de notificaciones">
    <div class="npb-icon">🔔</div>
    <div class="npb-body">
        <p class="npb-title">Activar notificaciones</p>
        <p class="npb-text">Recibe alertas de nuevos pedidos en tiempo real.</p>
    </div>
    <div class="npb-actions">
        <button class="npb-allow" id="npb-allow-btn" onclick="grantNotificationPermission()">Activar</button>
        <button class="npb-dismiss" onclick="dismissNotifBanner(true)">Ahora no</button>
    </div>
</div>

<script>
(function() {
    /* ── Firebase init ── */
    const firebaseConfig = {
        apiKey: "AIzaSyDOipHPuaDCXlZ_fUJlXENEpzacFPS9n5g",
        authDomain: "services-c8c8d.firebaseapp.com",
        databaseURL: "https://services-c8c8d-default-rtdb.firebaseio.com",
        projectId: "services-c8c8d",
        storageBucket: "services-c8c8d.firebasestorage.app",
        messagingSenderId: "714316853778",
        appId: "1:714316853778:web:fd2a5d4d59870e0f885ecb",
        measurementId: "G-CNG1LJZ46T"
    };

    let messaging = null;
    const vapidKey = "BNB2LLOnzR7ahnCCKeYu11qxiDDLNpMhrVrfNdgNRhgYkvpmzO7W53jFyqpxkCtcVoaeC2PoXswTLR6q29Dbxrg";
    const SW_DISMISSED_KEY = 'seller_notif_banner_dismissed';

    /* ── Init Firebase & Service Worker ── */
    function initFirebase() {
        try {
            firebase.initializeApp(firebaseConfig);
            messaging = firebase.messaging();
            messaging.onMessage((payload) => {
                if (typeof showSellerNotificationModal === 'function') {
                    showSellerNotificationModal(payload.data, payload.notification?.title || 'Notificación');
                }
            });
        } catch (e) {
            console.warn('[FCM] Firebase init error:', e);
        }
    }

    function registerSW() {
        if (!('serviceWorker' in navigator)) return Promise.reject('no-sw');
        return navigator.serviceWorker.register('/firebase-messaging-sw.js')
            .then(reg => {
                console.log('✅ Seller SW registrado:', reg.scope);
                return reg;
            });
    }

    /* ── Banner logic ── */
    function showBanner() {
        const banner = document.getElementById('notif-permission-banner');
        if (!banner) return;
        setTimeout(() => banner.classList.add('show'), 1200);
    }

    window.dismissNotifBanner = function(snooze) {
        const banner = document.getElementById('notif-permission-banner');
        if (banner) {
            banner.classList.remove('show');
            setTimeout(() => banner.style.display = 'none', 400);
        }
        if (snooze) {
            // Snooze por 24 horas
            localStorage.setItem(SW_DISMISSED_KEY, Date.now().toString());
        }
    };

    window.grantNotificationPermission = function() {
        const btn = document.getElementById('npb-allow-btn');
        if (btn) { btn.textContent = '⏳'; btn.disabled = true; }

        Notification.requestPermission().then((permission) => {
            dismissNotifBanner(false);
            if (permission === 'granted') {
                localStorage.removeItem(SW_DISMISSED_KEY);
                registerSW().then(() => getAndSendToken()).catch(console.warn);
            } else {
                localStorage.setItem(SW_DISMISSED_KEY, Date.now().toString());
            }
        }).catch(err => {
            console.warn('[FCM] Permission error:', err);
            if (btn) { btn.textContent = 'Activar'; btn.disabled = false; }
        });
    };

    function getAndSendToken() {
        if (!messaging) return;
        messaging.getToken({ vapidKey: vapidKey })
            .then(token => {
                if (token) sendTokenToServer(token);
            })
            .catch(err => console.warn('[FCM] Token error:', err));
    }

    function sendTokenToServer(token) {
        fetch('{{ route("seller.save-token") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ token: token, type: 'web' })
        })
        .then(r => {
            if (!r.ok) return null;
            return r.json();
        })
        .then(d => { if (d) console.log('📡 Seller FCM token registrado:', d); })
        .catch(err => console.warn('[FCM] sendToken error:', err));
    }

    /* ── Startup ── */
    function startup() {
        initFirebase();

        const currentPermission = ('Notification' in window) ? Notification.permission : 'denied';

        if (currentPermission === 'granted') {
            // Ya tiene permiso — solo registrar SW y obtener token silenciosamente
            registerSW().then(() => getAndSendToken()).catch(console.warn);
            return;
        }

        if (currentPermission === 'denied') {
            // El usuario lo bloqueó explícitamente — no mostrar banner
            return;
        }

        // currentPermission === 'default' — mostrar banner si no fue ignorado recientemente
        const dismissed = localStorage.getItem(SW_DISMISSED_KEY);
        const oneDayMs = 24 * 60 * 60 * 1000;
        if (dismissed && (Date.now() - parseInt(dismissed)) < oneDayMs) {
            return; // Está en período de snooze
        }

        showBanner();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startup);
    } else {
        startup();
    }
})();
</script>
