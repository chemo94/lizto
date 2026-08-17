<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>🍳 Pantalla Cocina — {{ gs('site_name') }}</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">

    {{-- Pusher JS --}}
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0a0a0f;
            --bg2: #111118;
            --bg3: #1a1a24;
            --border: rgba(255,255,255,0.08);
            --text: #f0f0f8;
            --muted: #6b7280;
            --accent: #6366f1;

            --urgent-bg: rgba(239,68,68,0.12);
            --urgent-border: rgba(239,68,68,0.5);
            --urgent-glow: 0 0 20px rgba(239,68,68,0.3);
            --urgent-badge: #ef4444;

            --normal-bg: rgba(245,158,11,0.10);
            --normal-border: rgba(245,158,11,0.4);
            --normal-badge: #f59e0b;

            --new-bg: rgba(16,185,129,0.08);
            --new-border: rgba(16,185,129,0.35);
            --new-badge: #10b981;
        }

        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }

        /* ── TOPBAR ── */
        .topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 60px;
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 16px;
            z-index: 100;
        }

        .topbar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 800;
            color: var(--text);
            text-decoration: none;
        }

        .topbar-logo i {
            font-size: 24px;
            color: var(--accent);
        }

        .topbar-stats {
            display: flex;
            gap: 12px;
            margin-left: auto;
            align-items: center;
        }

        .stat-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--border);
            background: var(--bg3);
        }

        .stat-pill.urgent { border-color: var(--urgent-border); color: var(--urgent-badge); }
        .stat-pill.normal { border-color: var(--normal-border); color: var(--normal-badge); }
        .stat-pill.new    { border-color: var(--new-border);    color: var(--new-badge); }

        .topbar-time {
            font-size: 22px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: var(--text);
            min-width: 75px;
            text-align: right;
        }

        /* Sound Toggle */
        .sound-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg3);
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .sound-btn:hover { border-color: var(--accent); color: var(--accent); }
        .sound-btn.muted { opacity: .5; }

        /* Fullscreen btn */
        .fullscreen-btn {
            display: flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg3);
            color: var(--muted);
            font-size: 16px;
            cursor: pointer;
            transition: all .2s;
        }
        .fullscreen-btn:hover { color: var(--text); border-color: rgba(255,255,255,0.2); }

        /* Connection badge */
        .ws-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }
        .ws-badge .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--muted);
        }
        .ws-badge.connected .dot { background: #10b981; box-shadow: 0 0 6px #10b981; }
        .ws-badge.error .dot { background: #ef4444; }

        /* ── GRID ── */
        .kitchen-wrap {
            position: fixed;
            top: 60px; left: 0; right: 0; bottom: 0;
            overflow-y: auto;
            padding: 20px;
        }

        .kitchen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        /* Empty state */
        .empty-state {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: calc(100vh - 140px);
            gap: 16px;
            color: var(--muted);
        }
        .empty-state i { font-size: 72px; opacity: .3; }
        .empty-state h2 { font-size: 22px; font-weight: 700; opacity: .5; }
        .empty-state p  { font-size: 14px; opacity: .4; }

        /* ── ORDER CARD ── */
        .order-card {
            border-radius: 16px;
            border: 1.5px solid var(--border);
            background: var(--bg2);
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            animation: slideIn .4s cubic-bezier(.34,1.56,.64,1);
            transition: box-shadow .3s, border-color .3s;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .order-card.priority-urgent {
            background: var(--urgent-bg);
            border-color: var(--urgent-border);
            box-shadow: var(--urgent-glow);
        }
        .order-card.priority-normal {
            background: var(--normal-bg);
            border-color: var(--normal-border);
        }
        .order-card.priority-new {
            background: var(--new-bg);
            border-color: var(--new-border);
        }

        /* Pulsing top bar for urgent */
        .order-card.priority-urgent::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--urgent-badge);
            animation: urgentPulse 1.4s ease-in-out infinite;
        }
        @keyframes urgentPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .3; }
        }

        /* Card header */
        .card-header-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .card-type-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .card-meta { flex: 1; min-width: 0; }

        .card-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            padding: 2px 7px;
            border-radius: 5px;
            margin-bottom: 4px;
        }

        .card-order-no {
            font-size: 15px;
            font-weight: 800;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-customer {
            font-size: 13px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* Priority badge */
        .priority-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 9px;
            border-radius: 20px;
            white-space: nowrap;
        }
        .priority-badge.urgent { background: rgba(239,68,68,.2); color: #ef4444; }
        .priority-badge.normal { background: rgba(245,158,11,.2); color: #f59e0b; }
        .priority-badge.new    { background: rgba(16,185,129,.2); color: #10b981; }

        /* Card body */
        .card-address {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 13px;
            color: var(--text);
            line-height: 1.4;
        }
        .card-address i { color: var(--muted); margin-top: 1px; flex-shrink: 0; }

        .card-items {
            font-size: 12px;
            color: var(--muted);
            background: rgba(255,255,255,0.04);
            border-radius: 8px;
            padding: 8px 10px;
            line-height: 1.5;
        }

        /* Footer row */
        .card-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .card-total {
            font-size: 20px;
            font-weight: 800;
        }

        .card-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(255,255,255,0.07);
        }

        .card-timer {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .card-timer.warn { color: #f59e0b; }
        .card-timer.danger { color: #ef4444; animation: timerPulse 1s ease-in-out infinite; }
        @keyframes timerPulse {
            0%,100%{opacity:1} 50%{opacity:.5}
        }

        .card-detail-btn {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            background: rgba(99,102,241,0.15);
            color: #818cf8;
            border: 1px solid rgba(99,102,241,0.3);
            transition: all .2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .card-detail-btn:hover {
            background: rgba(99,102,241,0.3);
            color: #c7d2fe;
            text-decoration: none;
        }

        /* NEW flash animation for ws events */
        .card-new-flash {
            animation: newFlash .8s ease-out;
        }
        @keyframes newFlash {
            0%  { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
            40% { box-shadow: 0 0 30px 8px rgba(16,185,129,0.5); }
            100%{ box-shadow: 0 0 0 0 rgba(16,185,129,0); }
        }

        /* Scrollbar */
        .kitchen-wrap::-webkit-scrollbar { width: 5px; }
        .kitchen-wrap::-webkit-scrollbar-track { background: transparent; }
        .kitchen-wrap::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }

        @media (max-width: 600px) {
            .kitchen-grid { grid-template-columns: 1fr; }
            .topbar-stats .stat-pill span { display: none; }
        }
    </style>
</head>
<body>

<!-- ── TOPBAR ── -->
<header class="topbar">
    <a href="{{ route('admin.kitchen.index') }}" class="topbar-logo" target="_blank">
        <i class="las la-concierge-bell"></i>
        <span>Pantalla Cocina</span>
    </a>

    <div id="ws-badge" class="ws-badge">
        <span class="dot"></span>
        <span id="ws-label">Conectando...</span>
    </div>

    <div class="topbar-stats">
        <div class="stat-pill urgent">
            <i class="las la-fire"></i>
            <span>URGENTE</span>
            <strong id="cnt-urgent">0</strong>
        </div>
        <div class="stat-pill normal">
            <i class="las la-clock"></i>
            <span>NORMAL</span>
            <strong id="cnt-normal">0</strong>
        </div>
        <div class="stat-pill new">
            <i class="las la-star"></i>
            <span>NUEVO</span>
            <strong id="cnt-new">0</strong>
        </div>
    </div>

    <button class="sound-btn" id="sound-toggle" title="Silenciar / Activar sonido">
        <i class="las la-volume-up" id="sound-icon"></i>
        <span id="sound-label">Sonido ON</span>
    </button>

    <button class="fullscreen-btn" id="fullscreen-btn" title="Pantalla completa">
        <i class="las la-expand"></i>
    </button>

    <div class="topbar-time" id="clock">--:--</div>
</header>

<!-- ── KITCHEN GRID ── -->
<div class="kitchen-wrap">
    <div class="kitchen-grid" id="kitchen-grid">
        <div class="empty-state" id="empty-state">
            <i class="las la-concierge-bell"></i>
            <h2>Sin pedidos activos</h2>
            <p>Los nuevos pedidos aparecerán aquí automáticamente</p>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // ── Config ──
    var PUSHER_KEY     = "{{ $pusherKey ?? 'f8e4c1e963c412b65574' }}";
    var PUSHER_CLUSTER = "{{ $pusherCluster ?? 'sa1' }}";
    var POLL_INTERVAL  = 10000; // 10s fallback polling
    var CSRF_TOKEN     = document.querySelector('meta[name="csrf-token"]').content;
    var AUTH_URL       = "{{ route('admin.delivery.broadcasting.auth') }}";

    // ── State ──
    var knownUids   = new Set();
    var soundMuted  = localStorage.getItem('kitchen_muted') === '1';
    var pollTimer   = null;
    var audioCtx    = null;
    var wsConnected = false;

    // ── DOM ──
    var grid        = document.getElementById('kitchen-grid');
    var emptyState  = document.getElementById('empty-state');
    var soundBtn    = document.getElementById('sound-toggle');
    var soundIcon   = document.getElementById('sound-icon');
    var soundLabel  = document.getElementById('sound-label');
    var wsBadge     = document.getElementById('ws-badge');
    var wsLabel     = document.getElementById('ws-label');
    var clock       = document.getElementById('clock');
    var cntUrgent   = document.getElementById('cnt-urgent');
    var cntNormal   = document.getElementById('cnt-normal');
    var cntNew      = document.getElementById('cnt-new');

    // ── Clock ──
    function updateClock() {
        var now = new Date();
        var h = String(now.getHours()).padStart(2,'0');
        var m = String(now.getMinutes()).padStart(2,'0');
        var s = String(now.getSeconds()).padStart(2,'0');
        clock.textContent = h + ':' + m + ':' + s;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ── Sound ──
    function ensureAudioCtx() {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        return audioCtx;
    }

    function playBeep(type) {
        if (soundMuted) return;
        try {
            var ctx = ensureAudioCtx();
            var freqs  = type === 'urgent' ? [880, 660, 880] : type === 'normal' ? [660, 880] : [880];
            var time = ctx.currentTime;
            freqs.forEach(function(freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0, time + i * 0.18);
                gain.gain.linearRampToValueAtTime(0.4, time + i * 0.18 + 0.04);
                gain.gain.exponentialRampToValueAtTime(0.001, time + i * 0.18 + 0.3);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(time + i * 0.18);
                osc.stop(time + i * 0.18 + 0.3);
            });
        } catch(e) { console.warn('Audio error:', e); }
    }

    // ── Sound Toggle ──
    function updateSoundUI() {
        if (soundMuted) {
            soundBtn.classList.add('muted');
            soundIcon.className = 'las la-volume-mute';
            soundLabel.textContent = 'Silenciado';
        } else {
            soundBtn.classList.remove('muted');
            soundIcon.className = 'las la-volume-up';
            soundLabel.textContent = 'Sonido ON';
        }
    }
    soundBtn.addEventListener('click', function() {
        soundMuted = !soundMuted;
        localStorage.setItem('kitchen_muted', soundMuted ? '1' : '0');
        updateSoundUI();
        if (!soundMuted) playBeep('new');
    });
    updateSoundUI();

    // ── Fullscreen ──
    document.getElementById('fullscreen-btn').addEventListener('click', function() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(function(){});
        } else {
            document.exitFullscreen().catch(function(){});
        }
    });

    // ── Priority Helpers ──
    function priorityLabel(p) {
        if (p === 'urgent') return '<i class="las la-fire"></i> URGENTE';
        if (p === 'normal') return '<i class="las la-clock"></i> NORMAL';
        return '<i class="las la-star"></i> NUEVO';
    }

    function timerClass(minutes) {
        if (minutes >= 15) return 'danger';
        if (minutes >= 5)  return 'warn';
        return '';
    }

    function minutesLabel(minutes) {
        if (minutes < 1) return 'Ahora mismo';
        if (minutes === 1) return '1 min';
        return minutes + ' min';
    }

    // ── Render card ──
    function buildCard(order) {
        var card = document.createElement('div');
        card.className = 'order-card priority-' + order.priority;
        card.id = 'card-' + order.uid;
        card.dataset.createdAt = order.created_at_raw;

        var typeBgRgb = hexToRgb(order.type_color);
        var typeBg = typeBgRgb ? 'rgba(' + typeBgRgb.r + ',' + typeBgRgb.g + ',' + typeBgRgb.b + ',0.15)' : 'rgba(99,102,241,0.15)';

        var detailBtn = order.detail_url
            ? '<a href="' + order.detail_url + '" target="_blank" class="card-detail-btn"><i class="las la-external-link-alt"></i> Ver</a>'
            : '';

        var expressTag = order.is_express
            ? '<span style="background:rgba(239,68,68,.2);color:#ef4444;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:700;margin-left:6px;">⚡ EXPRESS</span>'
            : '';

        card.innerHTML = [
            '<div class="card-header-row">',
                '<div class="card-type-icon" style="background:' + typeBg + ';color:' + order.type_color + '">',
                    '<i class="las ' + order.type_icon + '"></i>',
                '</div>',
                '<div class="card-meta">',
                    '<div class="card-type-badge" style="background:' + typeBg + ';color:' + order.type_color + '">',
                        '<i class="las ' + order.type_icon + '"></i> ' + escHtml(order.type_label),
                    '</div>',
                    '<div class="card-order-no">' + escHtml(order.order_no) + expressTag + '</div>',
                    '<div class="card-customer"><i class="las la-user"></i> ' + escHtml(order.subtitle) + ' &nbsp;·&nbsp; ' + escHtml(order.created_at) + '</div>',
                '</div>',
                '<div class="priority-badge ' + order.priority + '">' + priorityLabel(order.priority) + '</div>',
            '</div>',
            order.address && order.address !== '—' ? [
                '<div class="card-address">',
                    '<i class="las la-map-marker-alt"></i>',
                    '<span>' + escHtml(order.address) + '</span>',
                '</div>'
            ].join('') : '',
            order.items_summary && order.items_summary !== '—' ? [
                '<div class="card-items">',
                    '<i class="las la-list"></i> ',
                    escHtml(order.items_summary),
                    order.items_count > 3 ? ' <em>+más</em>' : '',
                '</div>'
            ].join('') : '',
            '<div class="card-footer-row">',
                '<div class="card-total" style="color:' + order.type_color + '">',
                    escHtml(order.currency) + ' ' + escHtml(order.total),
                '</div>',
                '<div class="card-timer ' + timerClass(order.minutes_waiting) + '">',
                    '<i class="las la-hourglass-half"></i> ' + minutesLabel(order.minutes_waiting),
                '</div>',
                '<div class="card-status">',
                    '<i class="las la-dot-circle"></i> ' + escHtml(order.status_label),
                '</div>',
                detailBtn,
            '</div>',
        ].join('');

        return card;
    }

    function hexToRgb(hex) {
        var r = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return r ? { r: parseInt(r[1],16), g: parseInt(r[2],16), b: parseInt(r[3],16) } : null;
    }

    function escHtml(str) {
        if (!str && str !== 0) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Inject or update cards ──
    function injectOrders(orders, isNew) {
        var counts = { urgent: 0, normal: 0, new: 0 };

        orders.forEach(function(order) {
            counts[order.priority] = (counts[order.priority] || 0) + 1;

            if (knownUids.has(order.uid)) {
                // Update existing card priority class (timer advances)
                var existing = document.getElementById('card-' + order.uid);
                if (existing) {
                    existing.className = 'order-card priority-' + order.priority;
                }
                return;
            }

            // New card
            knownUids.add(order.uid);
            var card = buildCard(order);

            if (isNew) {
                card.classList.add('card-new-flash');
                playBeep(order.priority);
            }

            // Insert sorted by created_at_raw desc
            var inserted = false;
            var existing = grid.querySelectorAll('.order-card');
            for (var i = 0; i < existing.length; i++) {
                if (parseInt(existing[i].dataset.createdAt) < order.created_at_raw) {
                    grid.insertBefore(card, existing[i]);
                    inserted = true;
                    break;
                }
            }
            if (!inserted) grid.appendChild(card);
        });

        // Remove cards no longer in the list (completed/cancelled)
        if (!isNew) {
            var activeUids = new Set(orders.map(function(o){ return o.uid; }));
            var cards = grid.querySelectorAll('.order-card');
            cards.forEach(function(c) {
                var uid = c.id.replace('card-', '');
                if (!activeUids.has(uid)) {
                    c.style.transition = 'opacity .4s, transform .4s';
                    c.style.opacity = '0';
                    c.style.transform = 'scale(0.9)';
                    setTimeout(function() { c.remove(); }, 400);
                    knownUids.delete(uid);
                }
            });
        }

        // Update counters
        cntUrgent.textContent = counts.urgent || 0;
        cntNormal.textContent = counts.normal || 0;
        cntNew.textContent    = counts['new'] || 0;

        // Empty state
        emptyState.style.display = knownUids.size === 0 ? 'flex' : 'none';
    }

    // ── Polling ──
    function fetchOrders() {
        fetch("{{ route('admin.kitchen.orders') }}", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                injectOrders(data.orders, false);
            }
        })
        .catch(function(e) { console.warn('Poll error:', e); });
    }

    // Initial load
    fetchOrders();
    pollTimer = setInterval(fetchOrders, POLL_INTERVAL);

    // Timer updater (updates waiting time on existing cards every 60s)
    setInterval(function() {
        grid.querySelectorAll('.order-card').forEach(function(card) {
            var ts = parseInt(card.dataset.createdAt);
            if (!ts) return;
            var minutes = Math.floor((Date.now()/1000 - ts) / 60);
            var timerEl = card.querySelector('.card-timer');
            if (timerEl) {
                timerEl.className = 'card-timer ' + timerClass(minutes);
                timerEl.innerHTML = '<i class="las la-hourglass-half"></i> ' + minutesLabel(minutes);
            }
            // Re-classify priority
            var newPriority = minutes >= 15 ? 'urgent' : minutes >= 5 ? 'normal' : 'new';
            card.className = 'order-card priority-' + newPriority;
            var pb = card.querySelector('.priority-badge');
            if (pb) {
                pb.className = 'priority-badge ' + newPriority;
                pb.innerHTML = priorityLabel(newPriority);
            }
        });
    }, 60000);

    // ── WebSocket / Pusher ──
    function initPusher() {
        if (!window.Pusher || !PUSHER_KEY) {
            setWsStatus('error', 'Sin WS');
            return;
        }

        var pusher = new Pusher(PUSHER_KEY, {
            cluster: PUSHER_CLUSTER,
            forceTLS: true,
            authEndpoint: AUTH_URL,
            auth: { headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } }
        });

        pusher.connection.bind('connected', function() {
            wsConnected = true;
            setWsStatus('connected', 'En vivo');
        });
        pusher.connection.bind('disconnected', function() {
            wsConnected = false;
            setWsStatus('error', 'Desconectado');
        });
        pusher.connection.bind('error', function() {
            setWsStatus('error', 'Error WS');
        });

        var channel = pusher.subscribe('private-admin-notifications');

        // New delivery order (DeliveryOrder)
        channel.bind('new_delivery_order', function(data) {
            console.log('WS: new_delivery_order', data);
            // Trigger full refresh to get complete formatted data
            fetchOrdersWithNew(data);
        });

        // New favor request
        channel.bind('store_favor_requested', function(data) {
            console.log('WS: store_favor_requested', data);
            fetchOrdersWithNew(data);
        });

        // Listen for any new order on the channel
        channel.bind('new_pos_order', function(data) {
            console.log('WS: new_pos_order', data);
            fetchOrdersWithNew(data);
        });
    }

    function fetchOrdersWithNew(wsData) {
        // Fetch full list; newly added items will be detected by knownUids difference
        fetch("{{ route('admin.kitchen.orders') }}", {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                // Mark all that weren't known yet as "new" for sound
                var incoming = data.orders.filter(function(o) { return !knownUids.has(o.uid); });
                if (incoming.length > 0) {
                    injectOrders(data.orders, true);
                } else {
                    injectOrders(data.orders, false);
                }
            }
        });
    }

    function setWsStatus(state, label) {
        wsBadge.className = 'ws-badge ' + state;
        wsLabel.textContent = label;
    }

    // Initialize Pusher after page fully loads
    window.addEventListener('load', function() {
        try { initPusher(); }
        catch(e) { console.warn('Pusher init error:', e); setWsStatus('error', 'Sin WS'); }
    });

})();
</script>
</body>
</html>
