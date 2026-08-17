<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\DriverCashTransaction;
use App\Models\DriverEarningTransaction;
use App\Models\Favor;
use App\Models\Gateway;
use App\Models\Seller;
use App\Models\SellerReceivableTransaction;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryFinancialLedger
{
    public static function getDriverDynamicCommissionPercent($driverId, float $basePercent = 20.0): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek   = now()->endOfWeek();

        $completedDeliveries = DeliveryOrder::where('driver_id', $driverId)
            ->where('status', 'delivered')
            ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
            ->count();

        $completedFavors = Favor::where('courier_id', $driverId)
            ->where('status', 'delivered')
            ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
            ->count();

        $totalWeeklyJobs = $completedDeliveries + $completedFavors;

        if ($totalWeeklyJobs > 30) {
            $effectivePercent = max(5.0, $basePercent - 8.0);
            $tierName = 'Diamante';
            $tierBadge = '💎';
            $nextTierNeeded = 0;
            $nextTierName = 'Máximo Nivel';
        } elseif ($totalWeeklyJobs >= 16) {
            $effectivePercent = max(5.0, $basePercent - 5.0);
            $tierName = 'Oro';
            $tierBadge = '🥇';
            $nextTierNeeded = 31 - $totalWeeklyJobs;
            $nextTierName = 'Diamante';
        } elseif ($totalWeeklyJobs >= 6) {
            $effectivePercent = max(5.0, $basePercent - 3.0);
            $tierName = 'Plata';
            $tierBadge = '🥈';
            $nextTierNeeded = 16 - $totalWeeklyJobs;
            $nextTierName = 'Oro';
        } else {
            $effectivePercent = $basePercent;
            $tierName = 'Bronce';
            $tierBadge = '🥉';
            $nextTierNeeded = 6 - $totalWeeklyJobs;
            $nextTierName = 'Plata';
        }

        return [
            'effective_percent' => $effectivePercent,
            'total_weekly_jobs' => $totalWeeklyJobs,
            'tier_name'         => $tierName,
            'tier_badge'        => $tierBadge,
            'next_tier_needed'  => $nextTierNeeded,
            'next_tier_name'    => $nextTierName,
        ];
    }

    public static function paymentChannel(Model $job): string
    {
        $name = strtolower(trim((string) ($job->payment_method_name ?? '')));
        $code = (string) ($job->payment_method_code ?? '0');

        if ($code === '' || (float) $code === 0.0 || str_contains($name, 'efectivo')) return 'cash';
        if (str_contains($name, 'mercadopago') || str_contains($name, 'mercado pago')) return 'mercadopago';
        if (str_contains($name, 'yape')) return 'yape';
        if (str_contains($name, 'plin')) return 'plin';

        $gateway = Gateway::where('code', $code)->first();
        $alias = strtolower((string) ($gateway?->alias ?? $gateway?->name ?? ''));
        if (str_contains($alias, 'mercadopago') || str_contains($alias, 'mercado pago')) return 'mercadopago';
        if (str_contains($alias, 'yape')) return 'yape';
        if (str_contains($alias, 'plin')) return 'plin';

        return 'other';
    }

    public static function recordCashCollection(Driver $driver, float $amount, Model $job): ?DriverCashTransaction
    {
        if ($amount <= 0) return null;

        return DB::transaction(function () use ($driver, $amount, $job) {
            $existing = DriverCashTransaction::where('type', 'collection')
                ->where('ref_type', $job::class)->where('ref_id', $job->id)->first();
            if ($existing) return $existing;

            $locked = Driver::lockForUpdate()->findOrFail($driver->id);
            $locked->cash_in_hand = (float) $locked->cash_in_hand + $amount;
            $locked->save();

            return DriverCashTransaction::create([
                'driver_id' => $locked->id,
                'type' => 'collection',
                'trx_type' => '+',
                'amount' => $amount,
                'post_balance' => $locked->cash_in_hand,
                'ref_type' => $job::class,
                'ref_id' => $job->id,
                'reference' => $job->order_no ?? null,
                'notes' => 'Efectivo cobrado al cliente',
            ]);
        });
    }

    public static function recordDriverEarning(Driver $driver, float $amount, Model $earning): ?DriverEarningTransaction
    {
        if ($amount <= 0) return null;

        return DB::transaction(function () use ($driver, $amount, $earning) {
            $existing = DriverEarningTransaction::where('type', 'earning')
                ->where('ref_type', $earning::class)->where('ref_id', $earning->id)->first();
            if ($existing) return $existing;

            $locked = Driver::lockForUpdate()->findOrFail($driver->id);
            $locked->earning_balance = (float) $locked->earning_balance + $amount;
            $locked->save();

            return DriverEarningTransaction::create([
                'driver_id' => $locked->id,
                'type' => 'earning',
                'trx_type' => '+',
                'amount' => $amount,
                'post_balance' => $locked->earning_balance,
                'ref_type' => $earning::class,
                'ref_id' => $earning->id,
                'reference' => method_exists($earning, 'job') ? ($earning->job?->order_no ?? null) : null,
                'notes' => 'Ganancia generada por servicio completado',
            ]);
        });
    }

    public static function recordSellerReceivable(Seller $seller, float $amount, string $channel, Model $job): ?SellerReceivableTransaction
    {
        if ($amount <= 0 || !in_array($channel, ['cash', 'mercadopago'], true)) return null;

        return DB::transaction(function () use ($seller, $amount, $channel, $job) {
            $existing = SellerReceivableTransaction::where('type', 'earning')
                ->where('ref_type', $job::class)->where('ref_id', $job->id)->first();
            if ($existing) return $existing;

            $locked = Seller::lockForUpdate()->findOrFail($seller->id);
            $locked->receivable_balance = (float) $locked->receivable_balance + $amount;
            $locked->save();

            return SellerReceivableTransaction::create([
                'seller_id' => $locked->id,
                'type' => 'earning',
                'trx_type' => '+',
                'amount' => $amount,
                'post_balance' => $locked->receivable_balance,
                'payment_channel' => $channel,
                'ref_type' => $job::class,
                'ref_id' => $job->id,
                'reference' => $job->order_no ?? null,
                'notes' => $channel === 'cash' ? 'Venta cobrada en efectivo por el repartidor' : 'Venta cobrada por MercadoPago',
            ]);
        });
    }

    public static function remitDriverCash(Driver $driver, float $amount, ?int $adminId, ?string $reference, ?string $notes): DriverCashTransaction
    {
        return DB::transaction(function () use ($driver, $amount, $adminId, $reference, $notes) {
            $locked = Driver::lockForUpdate()->findOrFail($driver->id);
            if ($amount <= 0 || $amount > (float) $locked->cash_in_hand + 0.00001) {
                throw ValidationException::withMessages(['amount' => 'El monto supera el efectivo pendiente del repartidor.']);
            }
            $locked->cash_in_hand = (float) $locked->cash_in_hand - $amount;
            $locked->save();

            return DriverCashTransaction::create([
                'driver_id' => $locked->id,
                'type' => 'remittance',
                'trx_type' => '-',
                'amount' => $amount,
                'post_balance' => $locked->cash_in_hand,
                'admin_id' => $adminId,
                'reference' => $reference,
                'notes' => $notes ?: 'Efectivo entregado a administración',
            ]);
        });
    }

    public static function settleDriverEarning(Driver $driver, float $amount, string $method, ?int $adminId, ?string $reference, ?string $notes): DriverEarningTransaction
    {
        return DB::transaction(function () use ($driver, $amount, $method, $adminId, $reference, $notes) {
            $locked = Driver::lockForUpdate()->findOrFail($driver->id);
            if ($amount <= 0 || $amount > (float) $locked->earning_balance + 0.00001) {
                throw ValidationException::withMessages(['amount' => 'El monto supera la ganancia pendiente del repartidor.']);
            }

            if ($method === 'balance') {
                $wallet = Wallet::firstOrCreate(
                    ['holder_type' => Driver::class, 'holder_id' => $locked->id],
                    ['balance' => 0, 'blocked_balance' => 0, 'status' => 1]
                );
                $wallet->credit($amount, 'earning_settlement', 'Depósito de ganancia del repartidor', null);
                if (!$locked->wallet_id) $locked->wallet_id = $wallet->id;
                $locked->balance = $wallet->balance;

                Transaction::create([
                    'driver_id'    => $locked->id,
                    'amount'       => $amount,
                    'post_balance' => $locked->balance,
                    'charge'       => 0,
                    'trx'          => getTrx(),
                    'trx_type'     => '+',
                    'remark'       => 'earning_settlement',
                    'details'      => 'Depósito de ganancia del repartidor',
                ]);
            }

            $locked->earning_balance = (float) $locked->earning_balance - $amount;
            $locked->save();

            return DriverEarningTransaction::create([
                'driver_id' => $locked->id,
                'type' => 'settlement',
                'trx_type' => '-',
                'amount' => $amount,
                'post_balance' => $locked->earning_balance,
                'settlement_method' => $method,
                'admin_id' => $adminId,
                'reference' => $reference,
                'notes' => $notes ?: 'Depósito de ganancia registrado por administración',
            ]);
        });
    }

    public static function settleSeller(Seller $seller, float $amount, string $method, ?int $adminId, ?string $reference, ?string $notes): SellerReceivableTransaction
    {
        return DB::transaction(function () use ($seller, $amount, $method, $adminId, $reference, $notes) {
            $locked = Seller::lockForUpdate()->findOrFail($seller->id);
            if ($amount <= 0 || $amount > (float) $locked->receivable_balance + 0.00001) {
                throw ValidationException::withMessages(['amount' => 'El monto supera la cuenta por cobrar del seller.']);
            }

            if ($method === 'balance') {
                $wallet = Wallet::firstOrCreate(
                    ['holder_type' => Seller::class, 'holder_id' => $locked->id],
                    ['balance' => 0, 'blocked_balance' => 0, 'status' => 1]
                );
                $wallet->credit($amount, 'receivable_settlement', 'Liquidación de cuenta por cobrar', null);
                if (!$locked->wallet_id) $locked->wallet_id = $wallet->id;
            }

            $locked->receivable_balance = (float) $locked->receivable_balance - $amount;
            $locked->save();

            return SellerReceivableTransaction::create([
                'seller_id' => $locked->id,
                'type' => 'settlement',
                'trx_type' => '-',
                'amount' => $amount,
                'post_balance' => $locked->receivable_balance,
                'settlement_method' => $method,
                'admin_id' => $adminId,
                'reference' => $reference,
                'notes' => $notes ?: 'Liquidación registrada por administración',
            ]);
        });
    }
}
