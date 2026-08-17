<?php

namespace App\Lib;

use App\Constants\Status;
use App\Models\RidePayment;
use App\Models\Transaction;
use App\Models\Wallet;

class RidePaymentManager
{
    public function payment($ride, $paymentType)
    {
        $amount = $ride->amount - $ride->discount_amount;
        $driver = $ride->driver;
        $user   = $ride->user;

        if ($paymentType ==  Status::PAYMENT_TYPE_GATEWAY) {

            $user->balance -= $amount;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '-';
            $transaction->trx          = $ride->uid;
            $transaction->remark       = 'payment';
            $transaction->details      = 'Ride payment ' . showAmount($amount) . ' and ride uid ' . $ride->uid . '';
            $transaction->save();
        }

        $this->ridePayment($ride, $paymentType);

        if ($paymentType ==  Status::PAYMENT_TYPE_GATEWAY) {

            $driver->balance += $amount;
            $driver->save();

            $transaction               = new Transaction();
            $transaction->driver_id    = $driver->id;
            $transaction->amount       = $amount;
            $transaction->post_balance = $driver->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->trx          = $ride->uid;
            $transaction->remark       = 'payment_received';
            $transaction->details      = 'Ride payment received ' . showAmount($amount) . ' and ride uid ' . $ride->uid . '';
            $transaction->save();
        }

        $driverWallet = Wallet::firstOrCreate(
            ['holder_type' => get_class($driver), 'holder_id' => $driver->id]
        );

        if ($driver->fleet_id && $driver->fleet) {
            $fleet = $driver->fleet;
            // 1. Calculate driver commission to Fleet Owner
            $fleetCommission = $ride->amount / 100 * $fleet->driver_commission_rate;
            $driverWallet->debit($fleetCommission, 'commission', 'Comisión de viaje #' . $ride->uid . ' a la Flota', $ride);

            // 2. Credit Fleet Owner wallet
            if ($fleet->owner_id) {
                $fleetOwnerWallet = Wallet::firstOrCreate(
                    ['holder_type' => \App\Models\FleetOwner::class, 'holder_id' => $fleet->owner_id]
                );
                $fleetOwnerWallet->credit($fleetCommission, 'earning', 'Comisión ganada por viaje #' . $ride->uid . ' del conductor ' . $driver->username, $ride);

                // 3. Debit Lizto tech license fee from Fleet Owner wallet (only if not subscription-based)
                if ($fleet->commission_type !== 'subscription') {
                    $liztoCommission = $ride->amount / 100 * $fleet->lizto_commission_rate;
                    if ($liztoCommission > 0) {
                        $fleetOwnerWallet->debit($liztoCommission, 'commission', 'Comisión de Lizto por viaje #' . $ride->uid, $ride);
                    }
                }
            }
            $commissionAmount = $fleetCommission;
        } else {
            $commissionAmount  = $ride->commission_amount;
            $driverWallet->debit($commissionAmount, 'commission', 'Comisión de viaje #' . $ride->uid, $ride);
        }

        $transaction               = new Transaction();
        $transaction->driver_id    = $driver->id;
        $transaction->amount       = $commissionAmount;
        $transaction->post_balance = $driver->balance;
        $transaction->charge       = 0;
        $transaction->trx_type     = '-';
        $transaction->trx          = $ride->uid;
        $transaction->remark       = 'ride_commission';
        $transaction->details      = 'Subtract ride commission amount ' . showAmount($commissionAmount) . ' and ride uid ' . $ride->uid . '';
        $transaction->save();

        if ($paymentType ==  Status::PAYMENT_TYPE_GATEWAY) {
            notify($ride->driver, "RIDE_PAYMENT_COMPLETE", [
                'trx'          => $transaction->trx,
                'ride_uid'     => $ride->uid,
                'amount'       => showAmount($amount, currencyFormat: false),
                'post_balance' => showAmount($driver->balance)
            ]);
        }
    }

    public function ridePayment($ride, $paymentType)
    {
        $payment               = new RidePayment();
        $payment->ride_id      = $ride->id;
        $payment->rider_id     = $ride->user_id;
        $payment->driver_id    = $ride->driver_id;
        $payment->amount       = $ride->amount - $ride->discount_amount;
        $payment->payment_type = $paymentType;
        $payment->save();

        $ride->payment_status = Status::PAID;
        $ride->status         = Status::RIDE_COMPLETED;
        $ride->payment_type   = $paymentType;
        $ride->save();
    }
}
