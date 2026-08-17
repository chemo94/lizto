# Checklist de validación: tiempo real y notificaciones

Ejecutar en dispositivos físicos Android e iOS, con un usuario distinto por
rol. Registrar `event_id`, tiempo de emisión y tiempo de recepción.

## Casos críticos

1. Cliente crea un pedido: Seller, Admin y repartidores conectados reciben un
   solo aviso; Seller abre el pedido correcto.
2. Repartidor acepta y cambia estados: cliente, seguimiento, repartidor y
   Seller se actualizan sin recargar manualmente.
3. Nuevo favor y devolución: solo repartidores elegibles lo ven; el cliente y
   la tienda reciben el cambio correspondiente.
4. Con la app en primer plano se muestra una sola alerta; en segundo plano se
   recibe FCM; con la app terminada se procesa `getInitialMessage`.
5. Desactivar y recuperar Wi-Fi/datos: la app se reconecta y vuelve a
   suscribirse sin duplicar listeners.
6. Cerrar sesión e iniciar con otra cuenta en el mismo teléfono: el token FCM
   se reasigna y no entrega avisos de la cuenta anterior.

## Criterios de aceptación

- Ningún evento se procesa más de una vez por `event_id`.
- La actualización llega en menos de 5 s en red normal.
- No se muestran tokens, cabeceras Authorization ni datos sensibles en logs.
- Los tokens inválidos son eliminados después de la respuesta de FCM.
