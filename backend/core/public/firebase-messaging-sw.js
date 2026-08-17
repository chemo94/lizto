importScripts('https://www.gstatic.com/firebasejs/9.18.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.18.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyDOipHPuaDCXlZ_fUJlXENEpzacFPS9n5g",
    authDomain: "services-c8c8d.firebaseapp.com",
    databaseURL: "https://services-c8c8d-default-rtdb.firebaseio.com",
    projectId: "services-c8c8d",
    storageBucket: "services-c8c8d.firebasestorage.app",
    messagingSenderId: "714316853778",
    appId: "1:714316853778:web:fd2a5d4d59870e0f885ecb",
    measurementId: "G-CNG1LJZ46T"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    console.log('[SW Background] Notificación recibida: ', payload);
    
    const notificationTitle = (payload.notification && payload.notification.title) || (payload.data && payload.data.title) || "Nuevo Pedido";
    const notificationBody = (payload.notification && payload.notification.body) || (payload.data && payload.data.body) || "Tienes un nuevo pedido pendiente.";
    
    const notificationOptions = {
        body: notificationBody,
        icon: (payload.notification && payload.notification.icon) || '/assets/images/logo.png',
        badge: '/assets/images/badge.png',
        data: payload.data
    };
    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Manejar clicks en las notificaciones
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    const url = '/seller/orders';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.includes('/seller') && 'focus' in client) {
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        })
    );
});
