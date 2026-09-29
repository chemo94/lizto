<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * Publica eventos de tiempo real al servidor WebSocket nativo vía Redis Pub/Sub.
 *
 * El servidor (App\WebSocket\RealtimeServer) escucha los canales "realtime.*"
 * y reenvía los mensajes a los clientes Flutter suscritos al topic correspondiente.
 *
 * Topics (los que el cliente Flutter usa al hacer "subscribe"):
 *   - "driver.{driverId}"  -> posición en vivo del taxi/repartidor
 *   - "ride.{rideId}"      -> posición en vivo dentro de un viaje de taxi
 *   - "order.{orderId}"    -> estado del pedido de delivery
 *   - "favor.{favorId}"    -> estado del favor/mandado
 */
class RealtimePublisher
{
    public const PREFIX = 'realtime.';

    /**
     * Publica un payload a un topic.
     */
    public static function publish(string $topic, array $payload): bool
    {
        try {
            Redis::publish(self::PREFIX . $topic, json_encode($payload, JSON_UNESCAPED_UNICODE));
            return true;
        } catch (\Throwable $e) {
            // El WS es un bonus: nunca debe romper la request HTTP.
            \Log::warning('[realtime] no se pudo publicar al topic ' . $topic . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Publica la posición en vivo de un conductor/repartidor.
     */
    public static function driverLocation(
        int $driverId,
        float $lat,
        float $lng,
        ?float $bearing = null,
        ?float $speed = null,
        ?int $rideId = null,
    ): bool {
        $payload = [
            'type'      => 'driver_location',
            'driver_id' => $driverId,
            'lat'       => $lat,
            'lng'       => $lng,
            'bearing'   => $bearing,
            'speed'     => $speed,
            'ts'        => (int) (microtime(true) * 1000),
        ];

        if ($rideId) {
            $payload['ride_id'] = $rideId;
            // Publicar también en el canal del ride para los pasajeros.
            self::publish("ride.{$rideId}", $payload);
        }

        return self::publish("driver.{$driverId}", $payload);
    }
}
