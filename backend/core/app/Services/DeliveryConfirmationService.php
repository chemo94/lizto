<?php

namespace App\Services;

use App\Models\CourierProof;
use App\Models\DeliveryPin;
use App\Models\DeliveryOrder;
use App\Models\Favor;
use App\Models\Driver;
use App\Events\FavorStatusUpdated;
use App\Events\DeliveryOrderStatusUpdated;

class DeliveryConfirmationService
{
    /**
     * Verify PIN code for a delivery.
     * Returns true if PIN matches, false otherwise.
     */
    public static function verifyPin(Driver $courier, int $jobId, string $type, string $pinCode): bool
    {
        $pin = $type === 'favor'
            ? DeliveryPin::where('favor_id', $jobId)->where('status', 'active')->first()
            : DeliveryPin::where('order_id', $jobId)->where('status', 'active')->first();

        if (!$pin) {
            return false;
        }

        if ($pin->pin_code !== $pinCode) {
            return false;
        }

        $pin->update(['status' => 'verified', 'verified_at' => now()]);
        return true;
    }

    /**
     * Check if a delivery has photo proof uploaded.
     */
    public static function hasPhotoProof(int $jobId, string $type): bool
    {
        return CourierProof::where('job_id', $jobId)
            ->where('job_type', $type)
            ->where('courier_id', auth()->id())
            ->exists();
    }

    /**
     * Check if PIN verification is required for this delivery.
     * PIN is required when:
     * - payer_type is 'recipient' (COD payment), OR
     * - favor has a user_id (customer-created, not seller-created)
     */
    public static function isPinRequired($job, string $type): bool
    {
        if ($type === 'favor') {
            // PIN required for COD or customer-created favors
            return $job->payer_type === 'recipient' || !is_null($job->user_id);
        }

        // For delivery orders, PIN is required when payment is not prepaid
        return !$job->payment_status;
    }

    /**
     * Check if photo proof is required for this delivery.
     * Photo is always required as proof of delivery.
     */
    public static function isPhotoRequired(int $jobId, string $type): bool
    {
        return true; // Always require photo proof
    }

    /**
     * Get the delivery confirmation requirements for a job.
     */
    public static function getRequirements($job, string $type): array
    {
        $pinRequired = self::isPinRequired($job, $type);
        $photoRequired = self::isPhotoRequired($job->id, $type);
        $hasPhoto = self::hasPhotoProof($job->id, $type);

        $pinVerified = false;
        if ($pinRequired) {
            $pin = $type === 'favor'
                ? DeliveryPin::where('favor_id', $job->id)->where('status', 'verified')->exists()
                : DeliveryPin::where('order_id', $job->id)->where('status', 'verified')->exists();
            $pinVerified = $pin;
        }

        return [
            'pin_required'    => $pinRequired,
            'pin_verified'    => $pinVerified,
            'photo_required'  => $photoRequired,
            'photo_uploaded'  => $hasPhoto,
            'can_deliver'     => (!$pinRequired || $pinVerified) && (!$photoRequired || $hasPhoto),
            'missing'         => array_filter([
                $pinRequired && !$pinVerified ? 'pin' : null,
                $photoRequired && !$hasPhoto ? 'photo' : null,
            ]),
        ];
    }

    /**
     * Attempt to mark a job as delivered.
     * Returns ['success' => bool, 'message' => string, 'requirements' => array]
     */
    public static function attemptDeliver(Driver $courier, int $jobId, string $type, ?string $pinCode = null): array
    {
        $job = $type === 'favor'
            ? Favor::where('courier_id', $courier->id)->findOrFail($jobId)
            : DeliveryOrder::where('driver_id', $courier->id)->findOrFail($jobId);

        $requirements = self::getRequirements($job, $type);

        // Verify PIN if required
        if ($requirements['pin_required'] && !$requirements['pin_verified']) {
            if (!$pinCode) {
                return [
                    'success' => false,
                    'message' => 'Se requiere el código PIN de verificación del cliente.',
                    'requirements' => $requirements,
                ];
            }

            if (!self::verifyPin($courier, $jobId, $type, $pinCode)) {
                return [
                    'success' => false,
                    'message' => 'El código PIN es incorrecto. Solicita el PIN al cliente.',
                    'requirements' => $requirements,
                ];
            }

            // Refresh requirements after PIN verification
            $requirements = self::getRequirements($job, $type);
        }

        // Check photo proof
        if ($requirements['photo_required'] && !$requirements['photo_uploaded']) {
            return [
                'success' => false,
                'message' => 'Se requiere subir una foto como comprobante de entrega.',
                'requirements' => $requirements,
            ];
        }

        // All requirements met — mark as delivered
        $job->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        if ($type === 'favor') {
            event(new FavorStatusUpdated($job));
        } else {
            event(new DeliveryOrderStatusUpdated($job->fresh('store', 'user')));
        }

        return [
            'success' => true,
            'message' => 'Entrega completada exitosamente.',
            'requirements' => $requirements,
        ];
    }

    /**
     * Get the PIN code for a job (for seller to share with courier if needed).
     */
    public static function getPinCode(int $jobId, string $type): ?string
    {
        $pin = $type === 'favor'
            ? DeliveryPin::where('favor_id', $jobId)->first()
            : DeliveryPin::where('order_id', $jobId)->first();

        return $pin?->pin_code;
    }
}
