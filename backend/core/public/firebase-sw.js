// Firebase Service Worker para manejo de notificaciones
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging.js');

// Inicializar Firebase en el Service Worker
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', () => {
    self.clients.matchAll().then(clients => {
        clients.forEach(client => client.navigate(client.url));
    });
});

// Escuchar mensajes de fondo
self.addEventListener('push', function(event) {
    console.log('Push notification received:', event);
    
    if (event.data) {
        try {
            const data = event.data.json();
            const title = data.notification?.title || 'Lizto Notification';
            const options = {
                body: data.notification?.body || '',
                icon: data.notification?.icon || '/img/icon.png',
                badge: data.notification?.badge || '/img/icon.png',
                tag: data.notification?.tag || 'notification',
                data: data.data || {}
            };

            event.waitUntil(
                self.registration.showNotification(title, options)
            );
        } catch (e) {
            console.error('Error processing push notification:', e);
        }
    }
});

// Manejar clicks en notificaciones
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    const data = event.notification.data;
    const url = data.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientList => {
            // Buscar si hay una ventana abierta
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            // Si no hay ventana, abrir una nueva
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
