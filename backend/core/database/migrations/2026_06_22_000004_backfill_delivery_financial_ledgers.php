<?php

use App\Models\DeliveryCommission;
use App\Models\DeliveryOrder;
use App\Models\Favor;
use App\Models\WalletTransaction;
use App\Services\DeliveryFinancialLedger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $commission = DeliveryCommission::where('status', 1)->first();

        DeliveryOrder::with(['driver', 'store.seller.wallet'])
            ->where('status', 'delivered')
            ->chunkById(100, function ($orders) use ($commission) {
                foreach ($orders as $order) {
                    $channel = DeliveryFinancialLedger::paymentChannel($order);

                    if ($channel === 'cash' && $order->driver) {
                        DeliveryFinancialLedger::recordCashCollection($order->driver, (float) $order->total, $order);
                    }

                    $seller = $order->store?->seller;
                    if (!$seller || !in_array($channel, ['cash', 'mercadopago', 'yape', 'plin'], true)) continue;

                    $oldCredit = $seller->wallet
                        ? WalletTransaction::where('wallet_id', $seller->wallet->id)
                            ->where('trx_type', '+')->where('remark', 'earning')
                            ->where('ref_type', DeliveryOrder::class)->where('ref_id', $order->id)->first()
                        : null;

                    $sellerNet = $oldCredit ? (float) $oldCredit->amount : $this->sellerNet($order, $commission);
                    $this->reverseOldWalletCredit($seller, $order, $oldCredit);

                    DeliveryFinancialLedger::recordSellerReceivable($seller, $sellerNet, $channel, $order);
                }
            });

        Favor::with('courier')->where('status', 'delivered')->chunkById(100, function ($favors) {
            foreach ($favors as $favor) {
                if ($favor->courier && DeliveryFinancialLedger::paymentChannel($favor) === 'cash') {
                    DeliveryFinancialLedger::recordCashCollection($favor->courier, (float) $favor->total, $favor);
                }
            }
        });
    }

    public function down(): void
    {
        WalletTransaction::where('remark', 'receivable_reclass')->orderByDesc('id')->each(function ($transaction) {
            DB::transaction(function () use ($transaction) {
                $wallet = DB::table('wallets')->where('id', $transaction->wallet_id)->lockForUpdate()->first();
                if ($wallet) {
                    DB::table('wallets')->where('id', $wallet->id)->update([
                        'balance' => (float) $wallet->balance + (float) $transaction->amount,
                        'updated_at' => now(),
                    ]);
                }
                $transaction->delete();
            });
        });
    }

    private function sellerNet(DeliveryOrder $order, ?DeliveryCommission $commission): float
    {
        $subtotal = (float) $order->subtotal;
        if (($commission?->store_commission_type ?? 'percent') === 'percent') {
            return max(0, $subtotal - ($subtotal * (float) ($commission?->store_commission_percent ?? 5) / 100));
        }
        return max(0, $subtotal - (float) ($commission?->store_fixed_amount ?? 0));
    }

    private function reverseOldWalletCredit($seller, DeliveryOrder $order, ?WalletTransaction $credit): void
    {
        if (!$credit || !$seller->wallet) return;
        $trx = 'RCL-DEL-' . $order->id;
        if (WalletTransaction::where('trx', $trx)->exists()) return;

        DB::transaction(function () use ($seller, $order, $credit, $trx) {
            $wallet = DB::table('wallets')->where('id', $seller->wallet->id)->lockForUpdate()->first();
            $newBalance = (float) $wallet->balance - (float) $credit->amount;
            DB::table('wallets')->where('id', $wallet->id)->update(['balance' => $newBalance, 'updated_at' => now()]);
            DB::table('wallet_transactions')->insert([
                'wallet_id' => $wallet->id,
                'trx' => $trx,
                'amount' => $credit->amount,
                'post_balance' => $newBalance,
                'charge' => 0,
                'trx_type' => '-',
                'details' => 'Reclasificación contable de venta delivery',
                'remark' => 'receivable_reclass',
                'ref_type' => DeliveryOrder::class,
                'ref_id' => $order->id,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
};
