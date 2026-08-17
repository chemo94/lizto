<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
        ]);
        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $seller = auth()->user();
        $store = Store::where('seller_id', $seller->id)->findOrFail($request->store_id);

        $schedules = $store->schedules()->orderBy('day')->orderBy('open_time')->get()
            ->groupBy(fn($s) => $s->day)
            ->map(fn($items, $day) => [
                'day'       => (int) $day,
                'day_name'  => StoreSchedule::days()[$day] ?? '',
                'slots'     => $items->map(fn($s) => [
                    'id'         => $s->id,
                    'open_time'  => $s->open_time,
                    'close_time' => $s->close_time,
                ])->values(),
            ])->values();

        return apiResponse('store_schedules', 'success', ['Horarios de ' . $store->name], [
            'store_id'  => $store->id,
            'schedules' => $schedules,
            'days'      => StoreSchedule::days(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id'   => 'required|exists:stores,id',
            'day'        => 'required|integer|between:0,6',
            'open_time'  => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
        ]);
        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $seller = auth()->user();
        $store = Store::where('seller_id', $seller->id)->findOrFail($request->store_id);

        // Check overlap
        $overlap = StoreSchedule::where('store_id', $store->id)
            ->where('day', $request->day)
            ->where('open_time', '<', $request->close_time)
            ->where('close_time', '>', $request->open_time)
            ->exists();

        if ($overlap) {
            return apiResponse('overlap', 'error', ['El horario se solapa con uno existente']);
        }

        $schedule = StoreSchedule::create([
            'store_id'   => $store->id,
            'day'        => $request->day,
            'open_time'  => $request->open_time,
            'close_time' => $request->close_time,
        ]);

        return apiResponse('schedule_created', 'success', ['Horario agregado'], [
            'schedule' => [
                'id'         => $schedule->id,
                'day'        => $schedule->day,
                'day_name'   => $schedule->dayName(),
                'open_time'  => $schedule->open_time,
                'close_time' => $schedule->close_time,
            ],
        ]);
    }

    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id'  => 'required|exists:stores,id',
            'schedules' => 'required|array',
            'schedules.*.day'        => 'required|integer|between:0,6',
            'schedules.*.open_time'  => 'required|date_format:H:i',
            'schedules.*.close_time' => 'required|date_format:H:i|after:schedules.*.open_time',
        ]);
        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $seller = auth()->user();
        $store = Store::where('seller_id', $seller->id)->findOrFail($request->store_id);

        // Delete existing schedules and replace
        $store->schedules()->delete();

        $created = [];
        foreach ($request->schedules as $slot) {
            $created[] = StoreSchedule::create([
                'store_id'   => $store->id,
                'day'        => $slot['day'],
                'open_time'  => $slot['open_time'],
                'close_time' => $slot['close_time'],
            ]);
        }

        return apiResponse('schedules_saved', 'success', [count($created) . ' horarios guardados'], [
            'count' => count($created),
        ]);
    }

    public function destroy($id)
    {
        $seller = auth()->user();
        $schedule = StoreSchedule::whereHas('store', function ($q) use ($seller) {
            $q->where('seller_id', $seller->id);
        })->findOrFail($id);

        $schedule->delete();

        return apiResponse('schedule_deleted', 'success', ['Horario eliminado']);
    }

    public function clearDay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
            'day'      => 'required|integer|between:0,6',
        ]);
        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $seller = auth()->user();
        $store = Store::where('seller_id', $seller->id)->findOrFail($request->store_id);
        $count = $store->schedules()->where('day', $request->day)->delete();

        return apiResponse('day_cleared', 'success', [$count . ' horarios eliminados']);
    }
}
