<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\Driver;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverEconomicPolicyService
{
    // Estados económicos del repartidor
    public const STATE_PROMOTIONAL_BALANCE   = 'PROMOTIONAL_BALANCE';
    public const STATE_ACTIVE_BALANCE        = 'ACTIVE_BALANCE';
    public const STATE_INSUFFICIENT_BALANCE  = 'INSUFFICIENT_BALANCE';
    public const STATE_BLOCKED_FROM_ORDERS   = 'BLOCKED_FROM_ORDERS';

    // Tipos de transacción del Wallet
    public const TRX_PROMOTIONAL_CREDIT = 'PROMOTIONAL_CREDIT';
    public const TRX_RECHARGE           = 'RECHARGE';
    public const TRX_CONSUMPTION        = 'CONSUMPTION';
    public const TRX_REFUND             = 'REFUND';
    public const TRX_ADMIN_ADJUSTMENT   = 'ADMIN_ADJUSTMENT';

    /**
     * Monto mínimo de recarga configurable (por defecto S/ 8.00).
     */
    public static function getMinRechargeAmount(): float
    {
        return (float) (gs('min_driver_recharge') ?? 8.00);
    }

    /**
     * Saldo promocional de bienvenida configurable (por defecto S/ 15.00).
     */
    public static function getInitialPromotionalCredit(): float
    {
        return (float) (gs('initial_promotional_credit') ?? 15.00);
    }

    /**
     * Asegura que el conductor tenga su modelo Wallet inicializado.
     */
    public static function ensureWallet(Driver $driver): Wallet
    {
        $wallet = $driver->wallet;
        if (!$wallet) {
            $wallet = Wallet::firstOrCreate(
                ['holder_type' => Driver::class, 'holder_id' => $driver->id],
                [
                    'balance'             => 0,
                    'promotional_balance' => 0,
                    'recharge_balance'    => 0,
                    'blocked_balance'     => 0,
                    'economic_state'      => self::STATE_INSUFFICIENT_BALANCE,
                    'status'              => 1,
                ]
            );
            if ($driver->wallet_id !== $wallet->id) {
                $driver->wallet_id = $wallet->id;
                $driver->save();
            }
            $driver->setRelation('wallet', $wallet);
        }
        return $wallet;
    }

    /**
     * Otorga automáticamente los S/ 15.00 promocionales de bienvenida al repartidor.
     * Registra la transacción inmutable como PROMOTIONAL_CREDIT.
     */
    public static function grantInitialPromotionalCredit(Driver $driver): ?WalletTransaction
    {
        return DB::transaction(function () use ($driver) {
            $driver = Driver::lockForUpdate()->find($driver->id);
            $wallet = self::ensureWallet($driver);

            // Verificar si ya se otorgó el crédito promocional
            $alreadyGranted = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('remark', self::TRX_PROMOTIONAL_CREDIT)
                ->exists();

            if ($alreadyGranted || (float) $wallet->promotional_balance > 0) {
                return null;
            }

            $creditAmount = self::getInitialPromotionalCredit();
            $newPromotional = (float) $wallet->promotional_balance + $creditAmount;
            $newTotalBalance = (float) $wallet->balance + $creditAmount;

            $trxCode = getTrx();

            // 1. Registrar WalletTransaction
            $walletTrx = $wallet->transactions()->create([
                'trx'          => $trxCode,
                'amount'       => $creditAmount,
                'post_balance' => $newTotalBalance,
                'charge'       => 0,
                'trx_type'     => '+',
                'remark'       => self::TRX_PROMOTIONAL_CREDIT,
                'details'      => 'Recarga promocional de bienvenida',
                'ref_type'     => Driver::class,
                'ref_id'       => $driver->id,
                'status'       => 1,
            ]);

            // 2. Actualizar balances del Wallet
            $wallet->update([
                'promotional_balance' => $newPromotional,
                'balance'             => $newTotalBalance,
                'economic_state'      => self::STATE_PROMOTIONAL_BALANCE,
            ]);

            // 3. Sincronizar balance legacy del Driver
            $driver->balance = $newTotalBalance;
            $driver->save();

            // 4. Sincronizar tabla legacy Transaction
            $trx = new Transaction();
            $trx->driver_id    = $driver->id;
            $trx->user_id      = 0;
            $trx->amount       = $creditAmount;
            $trx->post_balance = $newTotalBalance;
            $trx->charge       = 0;
            $trx->trx_type     = '+';
            $trx->trx          = $trxCode;
            $trx->remark       = self::TRX_PROMOTIONAL_CREDIT;
            $trx->details      = 'Recarga promocional de bienvenida';
            $trx->save();

            return $walletTrx;
        });
    }

    /**
     * Determina el estado económico actual del repartidor.
     */
    public static function getDriverEconomicState(Driver $driver): string
    {
        if ($driver->status == Status::USER_BAN || $driver->is_deleted) {
            return self::STATE_BLOCKED_FROM_ORDERS;
        }

        $wallet = self::ensureWallet($driver);
        $promoBalance    = (float) ($wallet->promotional_balance ?? 0);
        $rechargeBalance = (float) ($wallet->recharge_balance ?? 0);
        $totalBalance    = (float) ($wallet->balance ?? 0);

        if ($promoBalance > 0.0001) {
            return self::STATE_PROMOTIONAL_BALANCE;
        }

        if ($rechargeBalance > 0.0001 || $totalBalance > 0.0001) {
            return self::STATE_ACTIVE_BALANCE;
        }

        return self::STATE_INSUFFICIENT_BALANCE;
    }

    /**
     * REGLA CENTRAL DE LIZTO:
     * Comprueba de forma estricta y centralizada si un repartidor está apto para recibir ofertas/pedidos.
     *
     * @return array [
     *   'allowed'           => bool,
     *   'reason'            => ?string,
     *   'balance'           => float,
     *   'balance_type'      => 'promotional'|'recharge'|'none',
     *   'economic_state'    => string,
     *   'promotional_balance' => float,
     *   'recharge_balance'  => float,
     *   'min_recharge'      => float
     * ]
     */
    public static function canDriverReceiveOrders(Driver $driver): array
    {
        $minRecharge = self::getMinRechargeAmount();

        // 1. Verificación de estado de cuenta
        if ($driver->status == Status::USER_BAN || $driver->is_deleted) {
            return [
                'allowed'             => false,
                'reason'              => 'Tu cuenta está suspendida o inhabilitada.',
                'balance'             => (float) $driver->balance,
                'balance_type'        => 'none',
                'economic_state'      => self::STATE_BLOCKED_FROM_ORDERS,
                'promotional_balance' => 0.0,
                'recharge_balance'    => 0.0,
                'min_recharge'        => $minRecharge,
            ];
        }

        // 2. Verificación de verificación de documentos (KYC y vehículo si aplica)
        if ($driver->dv != Status::VERIFIED) {
            return [
                'allowed'             => false,
                'reason'              => 'Tus documentos de repartidor están pendientes de verificación.',
                'balance'             => (float) $driver->balance,
                'balance_type'        => 'none',
                'economic_state'      => self::STATE_BLOCKED_FROM_ORDERS,
                'promotional_balance' => 0.0,
                'recharge_balance'    => 0.0,
                'min_recharge'        => $minRecharge,
            ];
        }

        // 3. Verificación de estado Online
        if (!$driver->online_status) {
            return [
                'allowed'             => false,
                'reason'              => 'Debes conectarte en línea para recibir pedidos.',
                'balance'             => (float) $driver->balance,
                'balance_type'        => 'none',
                'economic_state'      => self::getDriverEconomicState($driver),
                'promotional_balance' => (float) ($driver->wallet?->promotional_balance ?? 0),
                'recharge_balance'    => (float) ($driver->wallet?->recharge_balance ?? 0),
                'min_recharge'        => $minRecharge,
            ];
        }

        // 4. Verificación económica según la política de Lizto
        $wallet = self::ensureWallet($driver);
        $promoBalance    = round((float) ($wallet->promotional_balance ?? 0), 2);
        $rechargeBalance = round((float) ($wallet->recharge_balance ?? 0), 2);
        $totalBalance    = round((float) ($wallet->balance ?? 0), 2);

        // Caso A: Tiene saldo promocional vigente (S/ 15 iniciales o restante)
        if ($promoBalance > 0.0001) {
            return [
                'allowed'             => true,
                'reason'              => null,
                'balance'             => $promoBalance,
                'balance_type'        => 'promotional',
                'economic_state'      => self::STATE_PROMOTIONAL_BALANCE,
                'promotional_balance' => $promoBalance,
                'recharge_balance'    => $rechargeBalance,
                'min_recharge'        => $minRecharge,
            ];
        }

        // Caso B: Saldo promocional consumido, pero tiene saldo recargado activo
        if ($rechargeBalance > 0.0001 || $totalBalance > 0.0001) {
            return [
                'allowed'             => true,
                'reason'              => null,
                'balance'             => max($rechargeBalance, $totalBalance),
                'balance_type'        => 'recharge',
                'economic_state'      => self::STATE_ACTIVE_BALANCE,
                'promotional_balance' => 0.0,
                'recharge_balance'    => max($rechargeBalance, $totalBalance),
                'min_recharge'        => $minRecharge,
            ];
        }

        // Caso C: Saldo agotado -> Bloqueado de recibir ofertas hasta recargar mínimo S/ 8.00
        return [
            'allowed'             => false,
            'reason'              => 'Tu saldo de operación se agotó. Realiza una recarga mínima de S/' . number_format($minRecharge, 2) . ' para continuar.',
            'balance'             => 0.0,
            'balance_type'        => 'insufficient',
            'economic_state'      => self::STATE_INSUFFICIENT_BALANCE,
            'promotional_balance' => 0.0,
            'recharge_balance'    => 0.0,
            'min_recharge'        => $minRecharge,
        ];
    }

    /**
     * Valida el monto de recarga posterior según la configuración mínima (S/ 8.00).
     */
    public static function validateRechargeAmount(float $amount): void
    {
        $min = self::getMinRechargeAmount();
        if ($amount < $min) {
            throw ValidationException::withMessages([
                'amount' => 'El monto mínimo de recarga es de S/ ' . number_format($min, 2) . '.',
            ]);
        }
    }

    /**
     * Procesa una recarga exitosa para el repartidor.
     * Acredita en recharge_balance y balance, con tipo RECHARGE y auditoría inmutable.
     */
    public static function processRecharge(
        Driver $driver,
        float $amount,
        ?Model $deposit = null,
        string $methodName = 'Depósito'
    ): WalletTransaction {
        self::validateRechargeAmount($amount);

        return DB::transaction(function () use ($driver, $amount, $deposit, $methodName) {
            $driver = Driver::lockForUpdate()->find($driver->id);
            $wallet = self::ensureWallet($driver);

            $newRechargeBalance = (float) $wallet->recharge_balance + $amount;
            $newTotalBalance    = (float) $wallet->balance + $amount;
            $trxCode = $deposit?->trx ?? getTrx();

            // 1. Crear WalletTransaction
            $walletTrx = $wallet->transactions()->create([
                'trx'          => $trxCode,
                'amount'       => $amount,
                'post_balance' => $newTotalBalance,
                'charge'       => $deposit?->charge ?? 0,
                'trx_type'     => '+',
                'remark'       => self::TRX_RECHARGE,
                'details'      => 'Recarga de saldo vía ' . $methodName,
                'ref_type'     => $deposit ? get_class($deposit) : null,
                'ref_id'       => $deposit?->id ?? null,
                'status'       => 1,
            ]);

            // 2. Actualizar balances de la Billetera
            $wallet->update([
                'recharge_balance' => $newRechargeBalance,
                'balance'          => $newTotalBalance,
                'economic_state'   => self::STATE_ACTIVE_BALANCE,
            ]);

            // 3. Sincronizar conductor
            $driver->balance = $newTotalBalance;
            $driver->save();

            // 4. Sincronizar tabla legacy Transaction
            $trx = new Transaction();
            $trx->driver_id    = $driver->id;
            $trx->user_id      = 0;
            $trx->amount       = $amount;
            $trx->post_balance = $newTotalBalance;
            $trx->charge       = $deposit?->charge ?? 0;
            $trx->trx_type     = '+';
            $trx->trx          = $trxCode;
            $trx->remark       = self::TRX_RECHARGE;
            $trx->details      = 'Recarga de saldo vía ' . $methodName;
            $trx->save();

            return $walletTrx;
        });
    }

    /**
     * Registra un consumo del saldo del repartidor garantizando la regla:
     * - Si el repartidor tiene saldo promocional vigente, conserva el 100% de la ganancia
     *   y NO se le cobra una comisión tradicional por pedido.
     * - Toda operación de descuento debita primero del saldo promocional y luego del saldo recargado.
     * - Genera siempre WalletTransaction con tipo CONSUMPTION.
     */
    public static function consumeBalance(
        Driver $driver,
        float $amount,
        string $details,
        ?Model $ref = null
    ): ?WalletTransaction {
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($driver, $amount, $details, $ref) {
            $driver = Driver::lockForUpdate()->find($driver->id);
            $wallet = self::ensureWallet($driver);

            $promoBalance    = (float) $wallet->promotional_balance;
            $rechargeBalance = (float) $wallet->recharge_balance;
            $totalBalance    = (float) $wallet->balance;

            // Descontar primero del saldo promocional si existiera
            if ($promoBalance >= $amount) {
                $newPromo = $promoBalance - $amount;
                $newRecharge = $rechargeBalance;
            } else {
                $rem = $amount - $promoBalance;
                $newPromo = 0.0;
                $newRecharge = max(0.0, $rechargeBalance - $rem);
            }

            $newTotal = max(0.0, $totalBalance - $amount);
            $trxCode = getTrx();

            $walletTrx = $wallet->transactions()->create([
                'trx'          => $trxCode,
                'amount'       => $amount,
                'post_balance' => $newTotal,
                'charge'       => 0,
                'trx_type'     => '-',
                'remark'       => self::TRX_CONSUMPTION,
                'details'      => $details,
                'ref_type'     => $ref ? get_class($ref) : null,
                'ref_id'       => $ref?->id ?? null,
                'status'       => 1,
            ]);

            $newState = $newPromo > 0 ? self::STATE_PROMOTIONAL_BALANCE : ($newTotal > 0 ? self::STATE_ACTIVE_BALANCE : self::STATE_INSUFFICIENT_BALANCE);

            $wallet->update([
                'promotional_balance' => $newPromo,
                'recharge_balance'    => $newRecharge,
                'balance'             => $newTotal,
                'economic_state'      => $newState,
            ]);

            $driver->balance = $newTotal;
            $driver->save();

            $trx = new Transaction();
            $trx->driver_id    = $driver->id;
            $trx->user_id      = 0;
            $trx->amount       = $amount;
            $trx->post_balance = $newTotal;
            $trx->charge       = 0;
            $trx->trx_type     = '-';
            $trx->trx          = $trxCode;
            $trx->remark       = self::TRX_CONSUMPTION;
            $trx->details      = $details;
            $trx->save();

            return $walletTrx;
        });
    }

    /**
     * Ajuste administrativo de saldo con auditoría estricta.
     */
    public static function adminAdjustment(
        Driver $driver,
        float $amount,
        string $trxType, // '+' o '-'
        string $details,
        ?int $adminId = null
    ): WalletTransaction {
        return DB::transaction(function () use ($driver, $amount, $trxType, $details, $adminId) {
            $driver = Driver::lockForUpdate()->find($driver->id);
            $wallet = self::ensureWallet($driver);

            $postBalance = $trxType === '+'
                ? (float) $wallet->balance + $amount
                : max(0.0, (float) $wallet->balance - $amount);

            $postRecharge = $trxType === '+'
                ? (float) $wallet->recharge_balance + $amount
                : max(0.0, (float) $wallet->recharge_balance - $amount);

            $trxCode = getTrx();

            $walletTrx = $wallet->transactions()->create([
                'trx'          => $trxCode,
                'amount'       => $amount,
                'post_balance' => $postBalance,
                'charge'       => 0,
                'trx_type'     => $trxType,
                'remark'       => self::TRX_ADMIN_ADJUSTMENT,
                'details'      => $details . ($adminId ? ' (Admin #' . $adminId . ')' : ''),
                'status'       => 1,
            ]);

            $wallet->update([
                'recharge_balance' => $postRecharge,
                'balance'          => $postBalance,
                'economic_state'   => self::getDriverEconomicState($driver),
            ]);

            $driver->balance = $postBalance;
            $driver->save();

            $trx = new Transaction();
            $trx->driver_id    = $driver->id;
            $trx->user_id      = 0;
            $trx->amount       = $amount;
            $trx->post_balance = $postBalance;
            $trx->charge       = 0;
            $trx->trx_type     = $trxType;
            $trx->trx          = $trxCode;
            $trx->remark       = self::TRX_ADMIN_ADJUSTMENT;
            $trx->details      = $details;
            $trx->save();

            return $walletTrx;
        });
    }

    /**
     * Resumen económico completo para la app del repartidor y el panel administrativo.
     */
    public static function getEconomicSummary(Driver $driver): array
    {
        $wallet = self::ensureWallet($driver);
        $check = self::canDriverReceiveOrders($driver);

        $recentTransactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id'           => $t->id,
                'trx'          => $t->trx,
                'amount'       => (float) $t->amount,
                'post_balance' => (float) $t->post_balance,
                'trx_type'     => $t->trx_type,
                'remark'       => $t->remark,
                'details'      => $t->details,
                'date'         => $t->created_at?->format('d/m/Y H:i'),
            ]);

        return array_merge($check, [
            'total_balance'       => (float) $wallet->balance,
            'promotional_balance' => (float) $wallet->promotional_balance,
            'recharge_balance'    => (float) $wallet->recharge_balance,
            'min_recharge'        => self::getMinRechargeAmount(),
            'recent_transactions' => $recentTransactions,
        ]);
    }
}
