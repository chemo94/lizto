# Contrato de eventos en tiempo real

Todos los eventos de pedido incluyen `event_id` (cuando aplique), `order_id` o
`job_id`, `type`, `message` y una versión de payload. Los clientes deben
considerar idempotente un evento repetido y refrescar la entidad desde API.

| Evento | Canal | Consumidores |
| --- | --- | --- |
| `new_delivery_order` | `private-seller.{sellerId}` | Seller |
| `new_delivery_order` | `private-nearby-couriers` | Repartidor/Delivery courier |
| `new_job_available` | `private-nearby-couriers` o `private-courier.{driverId}` | Repartidor/Delivery courier |
| `delivery_order_status_updated` | `private-delivery-order.{userId}` | Pasajero/Delivery customer |
| `delivery_order_status_updated` | `private-tracking.{orderId}` | Seguimiento del cliente |
| `delivery_order_status_updated` | `private-courier.{driverId}` | Repartidor/Delivery courier |
| `delivery_order_status_updated` | `private-seller.{sellerId}` | Seller |

Las notificaciones FCM deben usar los mismos identificadores y un `type`
estable. WebSocket actualiza la interfaz en primer plano; FCM despierta o dirige
la aplicación en segundo plano/terminada. Nunca se debe realizar dos veces una
acción de negocio por recibir ambos medios.
