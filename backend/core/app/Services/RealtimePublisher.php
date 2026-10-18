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

    /**
     * Notifica un evento de negocio de un ride a los participantes.
     *
     * Topics destino:
     *   - ride.{rideId}  -> tanto driver como pasajero suscritos al ride
     *   - driver.{driverId} -> el taxista (para NEW_RIDE, NEW_BID, BID_ACCEPT, etc.)
     *   - user.{userId}  -> el pasajero (para actualización de pantalla en vivo)
     */
    public static function rideEvent(
        int $rideId,
        string $eventType,
        array $payload = [],
        ?int $driverId = null,
        ?int $userId = null,
    ): bool {
        $envelope = array_merge([
            'type'       => $eventType,
            'ride_id'    => $rideId,
            'event_type' => $eventType,
            'ts'         => (int) (microtime(true) * 1000),
        ], $payload);

        $ok = true;

        // 1. Al ride (ambos lados lo escuchan).
        $ok = self::publish("ride.{$rideId}", $envelope) && $ok;

        // 2. Al taxista asignado (si hay).
        if ($driverId) {
            $ok = self::publish("driver.{$driverId}", $envelope) && $ok;
        }

        // 3. Al pasajero (para actualizar pantallas en vivo).
        if ($userId) {
            $ok = self::publish("user.{$userId}", $envelope) && $ok;
        }

        return $ok;
    }

    /**
     * Publica la posición de un driver para la vista de "taxis cercanos" del pasajero.
     *
     * El pasajero se suscribe al topic "nearby.{serviceType}" (p. ej. "nearby.ride")
     * para ver moverse en vivo a los taxis disponibles antes de pedir.
     */
    public static function nearbyDriver(
        int $driverId,
        float $lat,
        float $lng,
        ?float $bearing = null,
        ?float $speed = null,
        ?string $serviceType = 'ride',
    ): bool {
        $serviceType = $serviceType ?: 'ride';
        return self::publish("nearby.{$serviceType}", [
            'type'         => 'driver_location',
            'driver_id'    => $driverId,
            'lat'          => $lat,
            'lng'          => $lng,
            'bearing'      => $bearing,
            'speed'        => $speed,
            'service_type' => $serviceType,
            'ts'           => (int) (microtime(true) * 1000),
        ]);
    }
}
