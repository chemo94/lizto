<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\CourierEarning;
use App\Models\Driver;
use App\Models\Ride;
use App\Services\DeliveryFinancialLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverEarningController extends Controller
{
    public function driverEarning(Request $request, $id)
    {
        $driver = Driver::with('wallet')->findOrFail($id);
        $serviceType = in_array($request->service_type, ['ride', 'delivery'], true)
            ? $request->service_type
            : 'all';

        $rideSummary = $this->rideSummary($driver->id);
        $deliverySummary = $this->deliverySummary($driver->id);

        $widget = [
            'ride' => $rideSummary,
            'delivery' => $deliverySummary,
            'total_earning' => $rideSummary['earning'] + $deliverySummary['earning'],
            'pending_earning' => (float) $driver->earning_balance,
            'total_commission' => $rideSummary['commission'] + $deliverySummary['commission'],
            'total_tips' => $rideSummary['tips'],
            'total_services' => $rideSummary['count'] + $deliverySummary['count'],
            'average_earning' => ($rideSummary['count'] + $deliverySummary['count']) > 0
                ? ($rideSummary['earning'] + $deliverySummary['earning']) / ($rideSummary['count'] + $deliverySummary['count'])
                : 0,
            'today_earning' => $this->periodNet($driver->id, Carbon::today(), now()),
            'this_month_earning' => $this->periodNet($driver->id, now()->startOfMonth(), now()),
        ];

        $latestLegacyTransaction = $driver->transactions()->latest('id')->first();
        $balance = [
            // drivers.balance is the balance consumed by the driver application.
            'available' => (float) $driver->balance,
            'wallet' => $driver->wallet ? (float) $driver->wallet->balance : null,
            'transaction' => $latestLegacyTransaction ? (float) $latestLegacyTransaction->post_balance : null,
        ];
        $balance['has_mismatch'] = ($balance['wallet'] !== null && abs($balance['available'] - $balance['wallet']) > 0.009)
            || ($balance['transaction'] !== null && abs($balance['available'] - $balance['transaction']) > 0.009);

        $earnings = $this->earningRows($driver->id, $serviceType)
            ->orderByDesc('earned_at')
            ->paginate(getPaginate())
            ->withQueryString();
        $cashTransactions = $driver->cashTransactions()->with('admin')->latest('id')->limit(20)->get();
        $earningTransactions = $driver->earningTransactions()->with('admin')->latest('id')->limit(20)->get();

        $pageTitle = 'Ganancias del conductor - ' . $driver->username;

        return view('admin.driver.earning.details', compact(
            'pageTitle',
            'widget',
            'driver',
            'earnings',
            'serviceType',
            'balance',
            'cashTransactions',
            'earningTransactions'
        ));
    }

    public function remitCash(Request $request, $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $driver = Driver::findOrFail($id);
        DeliveryFinancialLedger::remitDriverCash(
            $driver,
            (float) $data['amount'],
            auth('admin')->id(),
            $data['reference'] ?? null,
            $data['notes'] ?? null
        );

        return back()->withNotify([['success', 'Rendición de efectivo registrada correctamente']]);
    }

    public function settleEarning(Request $request, $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'settlement_method' => 'required|in:balance,bank,yape,plin',
            'reference' => 'nullable|required_unless:settlement_method,balance|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $driver = Driver::findOrFail($id);
        DeliveryFinancialLedger::settleDriverEarning(
            $driver,
            (float) $data['amount'],
            $data['settlement_method'],
            auth('admin')->id(),
            $data['reference'] ?? null,
            $data['notes'] ?? null
        );

        return back()->withNotify([['success', 'DepÃ³sito de ganancia registrado correctamente']]);
    }

    public function chart(Request $request, $id)
    {
        Driver::findOrFail($id);
        $serviceType = in_array($request->service_type, ['ride', 'delivery'], true)
            ? $request->service_type
            : 'all';

        [$start, $end] = $this->chartRange($request);
        $rows = $this->earningRows($id, $serviceType)
            ->whereBetween('earned_at', [$start, $end])
            ->orderBy('earned_at')
            ->get();

        $result = [];
        for ($day = $start->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
            $result[$day->format('Y-m-d')] = [
                'ride_amount' => 0,
                'delivery_amount' => 0,
                'total_amount' => 0,
            ];
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->earned_at)->format('Y-m-d');
            if (!isset($result[$key])) {
                continue;
            }
            $field = $row->service_type === 'ride' ? 'ride_amount' : 'delivery_amount';
            $result[$key][$field] += (float) $row->earning_amount;
            $result[$key]['total_amount'] += (float) $row->earning_amount;
        }

        return response()->json($result);
    }

    private function rideSummary(int $driverId): array
    {
        $row = Ride::where('driver_id', $driverId)
            ->completed()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(amount - discount_amount), 0) as gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as commission')
            ->selectRaw('COALESCE(SUM(tips_amount), 0) as tips')
            ->selectRaw('COALESCE(SUM(amount - discount_amount + tips_amount), 0) as earning')
            ->first();

        return [
            'count' => (int) $row->total_count,
            'gross' => (float) $row->gross,
            'commission' => (float) $row->commission,
            'tips' => (float) $row->tips,
            'earning' => (float) $row->earning,
        ];
    }

    private function deliverySummary(int $driverId): array
    {
        $row = CourierEarning::where('courier_id', $driverId)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(amount), 0) as gross')
            ->selectRaw('COALESCE(SUM(commission), 0) as commission')
            ->selectRaw('COALESCE(SUM(amount), 0) as earning')
            ->first();

        return [
            'count' => (int) $row->total_count,
            'gross' => (float) $row->gross,
            'commission' => (float) $row->commission,
            'tips' => 0.0,
            'earning' => (float) $row->earning,
        ];
    }

    private function periodNet(int $driverId, Carbon $start, Carbon $end): float
    {
        $rideNet = Ride::where('driver_id', $driverId)
            ->completed()
            ->whereBetween(DB::raw('COALESCE(end_time, updated_at, created_at)'), [$start, $end])
            ->selectRaw('COALESCE(SUM(amount - discount_amount + tips_amount), 0) as earning')
            ->value('earning');

        $deliveryNet = CourierEarning::where('courier_id', $driverId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        return (float) $rideNet + (float) $deliveryNet;
    }

    private function earningRows(int $driverId, string $serviceType)
    {
        $rideRows = DB::table('rides')
            ->where('driver_id', $driverId)
            ->where('status', Status::RIDE_COMPLETED)
            ->selectRaw("CAST('ride' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as service_type")
            ->selectRaw('id as source_id')
            ->selectRaw('CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as job_type')
            ->selectRaw('CAST(uid AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as reference')
            ->selectRaw("CAST(CONCAT('Viaje #', uid) AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as description")
            ->selectRaw('(amount - discount_amount) as gross_amount')
            ->selectRaw('commission_amount')
            ->selectRaw('tips_amount')
            ->selectRaw('(amount - discount_amount + tips_amount) as earning_amount')
            ->selectRaw('COALESCE(end_time, updated_at, created_at) as earned_at');

        $deliveryRows = DB::table('courier_earnings')
            ->leftJoin('favors', 'favors.id', '=', 'courier_earnings.job_id')
            ->where('courier_earnings.courier_id', $driverId)
            ->selectRaw("CAST('delivery' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as service_type")
            ->selectRaw('courier_earnings.job_id as source_id')
            ->selectRaw('CAST(courier_earnings.job_type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as job_type')
            ->selectRaw(
                "CAST(CASE WHEN courier_earnings.job_type = ? THEN COALESCE(favors.order_no, CONCAT('FAVOR-', courier_earnings.job_id)) ELSE CONCAT('DEL-', courier_earnings.job_id) END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as reference",
                [\App\Models\Favor::class]
            )
            ->selectRaw("CAST(COALESCE(courier_earnings.description, CONCAT('Delivery #', courier_earnings.job_id)) AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as description")
            ->selectRaw('courier_earnings.amount as gross_amount')
            ->selectRaw('courier_earnings.commission as commission_amount')
            ->selectRaw('0 as tips_amount')
            ->selectRaw('courier_earnings.amount as earning_amount')
            ->selectRaw('courier_earnings.created_at as earned_at');

        if ($serviceType === 'ride') {
            return DB::query()->fromSub($rideRows, 'driver_earnings');
        }
        if ($serviceType === 'delivery') {
            return DB::query()->fromSub($deliveryRows, 'driver_earnings');
        }

        return DB::query()->fromSub($rideRows->unionAll($deliveryRows), 'driver_earnings');
    }

    private function chartRange(Request $request): array
    {
        if ($request->time_period === 'date_range' && $request->filled('date')) {
            $parts = preg_split('/\s+to\s+/', $request->date);
            try {
                $start = Carbon::parse($parts[0])->startOfDay();
                $end = Carbon::parse($parts[1] ?? $parts[0])->endOfDay();
                if ($start->diffInDays($end) <= 92) {
                    return [$start, $end];
                }
            } catch (\Throwable $e) {
                // Fall back to the default range below.
            }
        }

        return [now()->subDays(6)->startOfDay(), now()->endOfDay()];
    }
}
