<?php

namespace App\Services;

use App\Models\Favor;
use App\Models\Driver;
use App\Models\DeliveryRefund;
use App\Events\FavorStatusUpdated;
use App\Services\FcmService;

class ReturnHandlingService
{
    /**
     * Valid return reasons.
     */
    const RETURN_REASONS = [
        'recipient_not_found'  => 'Destinatario no encontrado',
        'wrong_address'        => 'Dirección incorrecta',
        'recipient_refused'    => 'Destinatario rechazó el paquete',
        'damaged_in_transit'   => 'Dañado en tránsito',
        ' incomplete_order'    => 'Pedido incompleto',
        'other'                => 'Otro motivo',
    ];

    /**
     * Request a return for a delivered favor.
     * Only the seller or customer can request a return.
     */
    public static function requestReturn(Favor $favor, string $reasonCode, ?string $notes = null): array
    {
        if ($favor->status !== 'delivered') {
            return [
                'success' => false,
                'message' => 'Solo se pueden devolver envíos entregados.',
            ];
        }

        if ($favor->return_status === 'return_requested' || $favor->return_status === 'return_in_transit') {
            return [
                'success' => false,
                'message' => 'Ya existe una solicitud de devolución activa.',
            ];
        }

        if (!isset(self::RETURN_REASONS[$reasonCode])) {
            $reasonCode = 'other';
        }

        $favor->update([
            'return_status'    => 'return_requested',
            'return_reason'    => $reasonCode,
            'return_notes'     => $notes,
            'return_requested_at' => now(),
        ]);

        // Notify couriers about return pickup
        if (gs('pn') && gs('firebase_config')) {
            FcmService::sendToAllCouriers(
                'Devolución solicitada',
                'Se requiere recoger el envío #' . $favor->order_no . ' para devolución.',
                [
                    'type'       => 'return_requested',
                    'favor_id'   => (string) $favor->id,
                    'return_reason' => $reasonCode,
                    'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );
        }

        event(new FavorStatusUpdated($favor));

        return [
            'success' => true,
            'message' => 'Solicitud de devolución registrada.',
            'return_reason' => self::RETURN_REASONS[$reasonCode],
        ];
    }

    /**
     * Courier accepts the return pickup.
     */
    public static function acceptReturn(Driver $courier, Favor $favor): array
    {
        if ($favor->return_status !== 'return_requested') {
            return [
                'success' => false,
                'message' => 'Esta devolución no está disponible para aceptar.',
            ];
        }

        $favor->update([
            'courier_id'         => $courier->id,
            'courier_assigned_at' => now(),
            'return_status'      => 'return_assigned',
        ]);

        event(new FavorStatusUpdated($favor));

        return [
            'success' => true,
            'message' => 'Devolución asignada.',
        ];
    }

    /**
     * Courier picks up the return item.
     */
    public static function pickupReturn(Favor $favor): array
    {
        if (!in_array($favor->return_status, ['return_requested', 'return_assigned'])) {
            return [
                'success' => false,
                'message' => 'Estado de devolución no válido para recogida.',
            ];
        }

        $favor->update([
            'return_status'      => 'return_in_transit',
            'return_picked_up_at' => now(),
        ]);

        event(new FavorStatusUpdated($favor));

        return [
            'success' => true,
            'message' => 'Devolución en tránsito.',
        ];
    }

    /**
     * Courier delivers the return item back to the store/seller.
     */
    public static function completeReturn(Favor $favor): array
    {
        if ($favor->return_status !== 'return_in_transit') {
            return [
                'success' => false,
                'message' => 'La devolución no está en tránsito.',
            ];
        }

        $favor->update([
            'return_status'       => 'return_completed',
            'return_completed_at' => now(),
        ]);

        // Create refund record
        DeliveryRefund::create([
            'favor_id'    => $favor->id,
            'amount'      => $favor->total ?? $favor->delivery_fee ?? 0,
            'reason'      => $favor->return_reason,
            'status'      => 'pending',
            'description' => $favor->return_notes,
        ]);

        event(new FavorStatusUpdated($favor));

        return [
            'success' => true,
            'message' => 'Devolución completada. Reembolso pendiente de procesamiento.',
        ];
    }

    /**
     * Get the valid return reasons for display.
     */
    public static function getReturnReasons(): array
    {
        return self::RETURN_REASONS;
    }

    /**
     * Check if a favor is eligible for return.
     */
    public static function isEligibleForReturn(Favor $favor): bool
    {
        if ($favor->status !== 'delivered') {
            return false;
        }

        if (in_array($favor->return_status, ['return_requested', 'return_in_transit'])) {
            return false;
        }

        // 24-hour window for returns
        if ($favor->delivered_at && $favor->delivered_at->diffInHours(now()) > 24) {
            return false;
        }

        return true;
    }
}
