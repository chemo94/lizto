@php
    use App\Models\DeliveryCommission;
    $pusherKey   = env('PUSHER_APP_KEY', env('REVERB_APP_KEY', ''));
    $pusherHost  = env('REVERB_HOST', env('PUSHER_HOST', 'localhost'));
    $pusherPort  = env('REVERB_PORT', env('PUSHER_PORT', 8080));
    $pusherSSL   = env('REVERB_SCHEME', env('PUSHER_SCHEME', 'http')) === 'https';
    $pusherCluster = env('PUSHER_APP_CLUSTER', '');
@endphp

@if($pusherKey)
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
(function() {
    var pusher = new Pusher('{{ $pusherKey }}', {
        wsHost: '{{ $pusherHost }}',
        wsPort: {{ $pusherPort }},
        wssPort: {{ $pusherPort }},
        forceTLS: {{ $pusherSSL ? 'true' : 'false' }},
        cluster: '{{ $pusherCluster ?: "mt1" }}',
        enabledTransports: ['ws', 'wss'],
        authorizer: function(channel) {
            return {
                authorize: function(socketId, callback) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ url('api/broadcasting/auth') }}', true);
                    xhr.withCredentials = true;
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                callback(null, JSON.parse(xhr.responseText));
                            } else {
                                callback(new Error('Auth failed'), null);
                            }
                        }
                    };
                    xhr.send(JSON.stringify({ socket_id: socketId, channel_name: channel.name }));
                }
            };
        }
    });

    var channel = pusher.subscribe('private-admin-notifications');

    // Request Notification Permission on Page Load
    if (window.Notification && Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }

    function showNotificationModal(data, title) {
        try {
            var audio = new Audio('data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//uQxAAAAAAAAAAAAAAAAAAAAAAAASW5mbwAAAA8AAAACAAABhgC7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7//////////////////////////////////////////////////////////////////8AAAAATGF2YzU4LjEzAAAAAAAAAAAAAAAAJAAAAAAAAAAAAYYQu77yEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
            audio.play().catch(function(){});
        } catch(e) {}

        // Native Browser Notification
        if (window.Notification && Notification.permission === "granted") {
            var bodyText = (data.order_no ? 'Pedido: ' + data.order_no + '\n' : '') +
                           (data.customer_name ? 'Cliente: ' + data.customer_name + '\n' : '') +
                           (data.total ? 'Total: S/ ' + data.total : '');
            var notification = new Notification(title, {
                body: bodyText,
                icon: '{{ asset("assets/images/logoIcon/logo.png") }}'
            });
            notification.onclick = function() {
                window.focus();
                window.location.href = '/admin/delivery/' + (data.type === 'delivery' ? 'orders' : 'favors') + '/' + data.id;
            };
        }

        var modalHtml = '<div id="newOrderModal" style="position:fixed;top:20px;right:20px;z-index:99999;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.2);width:380px;overflow:hidden;animation:slideIn 0.4s ease-out">' +
            '<div style="background:linear-gradient(135deg,#6C63FF,#4834d4);padding:20px;color:white">' +
                '<div style="display:flex;justify-content:space-between;align-items:center">' +
                    '<span style="font-weight:bold;font-size:18px">\u{1F514} ' + title + '</span>' +
                    '<button onclick="document.getElementById(\'newOrderModal\').remove()" style="background:none;border:none;color:white;font-size:22px;cursor:pointer">&times;</button>' +
                '</div>' +
                '<div style="font-size:28px;font-weight:bold;margin-top:8px">' + (data.order_no || '') + '</div>' +
            '</div>' +
            '<div style="padding:16px">' +
                (data.seller_name ? '<p style="margin:0 0 8px;font-size:14px"><strong>Tienda:</strong> ' + data.seller_name + '</p>' : '') +
                (data.store_name ? '<p style="margin:0 0 8px;font-size:14px"><strong>Tienda:</strong> ' + data.store_name + '</p>' : '') +
                (data.customer_name ? '<p style="margin:0 0 8px;font-size:14px"><strong>Cliente:</strong> ' + data.customer_name + '</p>' : '') +
                (data.items_count ? '<p style="margin:0 0 8px;font-size:14px"><strong>Productos:</strong> ' + data.items_count + ' items</p>' : '') +
                (data.description ? '<p style="margin:0 0 8px;font-size:14px"><strong>Descripción:</strong> ' + data.description.substring(0,100) + '</p>' : '') +
                '<p style="margin:0 0 8px;font-size:14px"><strong>Total:</strong> S/ ' + (data.total || '0') + '</p>' +
                '<p style="margin:0;font-size:12px;color:#888">' + (data.created_at || '') + '</p>' +
                '<div style="margin-top:12px;display:flex;gap:8px">' +
                    '<a href="/admin/delivery/' + (data.type === 'delivery' ? 'orders' : 'favors') + '/' + data.id + '" style="flex:1;text-align:center;background:#6C63FF;color:white;padding:10px;border-radius:8px;text-decoration:none;font-weight:600">Ver</a>' +
                    '<button onclick="document.getElementById(\'newOrderModal\').remove()" style="flex:1;background:#eee;border:none;padding:10px;border-radius:8px;cursor:pointer;font-weight:600">Cerrar</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        var existingModal = document.getElementById('newOrderModal');
        if (existingModal) existingModal.remove();

        var div = document.createElement('div');
        div.innerHTML = modalHtml;
        document.body.appendChild(div.firstChild);

        setTimeout(function() {
            var modal = document.getElementById('newOrderModal');
            if (modal) modal.remove();
        }, 15000);
    }

    channel.bind('new_delivery_order', function(data) {
        showNotificationModal(data, data.type === 'favor' ? 'Nuevo Favor' : 'Nuevo Pedido');
    });

    channel.bind('store_favor_requested', function(data) {
        showNotificationModal(data, 'Tienda Solicita Repartidor');
    });

    // Log connection
    pusher.connection.bind('connected', function() {
        console.log('📡 Admin notifications: Conectado a Pusher');
    });

    pusher.connection.bind('error', function(err) {
        console.error('📡 Pusher error:', err);
    });
})();
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>
@endif
