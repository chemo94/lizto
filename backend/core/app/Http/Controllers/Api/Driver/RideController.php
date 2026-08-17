<?php

namespace App\Http\Controllers\Api\Driver;

use App\Models\Ride;
use App\Constants\Status;
use App\Events\Ride as EventsRide;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\RidePaymentManager;
use App\Models\Zone;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;

class RideController extends Controller
{
    public function details($id)
    {
        $ride = Ride::with(['bids', 'user', 'driver', 'userReview', 'driverReview', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year', 'service'])->find($id);

        if (!$ride) {
            $notify[] = 'Este viaje no está disponible';
            return apiResponse("not_found", 'error', $notify);
        }

        $driver = auth()->user();
        if ($ride->status == Status::RIDE_PENDING) {
            event(new \App\Events\Ride("rider-user-{$ride->user_id}", 'DRIVER_VIEWING', [
                'driver' => [
                    'id' => (string)$driver->id,
                    'firstname' => $driver->firstname,
                    'lastname' => $driver->lastname,
                    'image' => $driver->avatar,
                ]
            ]));
        }

        $notify[] = 'Detalles del viaje';
        return apiResponse("ride_details", 'success', $notify, [
            'ride'            => $ride,
            'user_image_path' => getFilePath('user'),
        ]);
    }

    public function start(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:4'
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $driver = auth()->user();
        $ride   = Ride::where('status', Status::RIDE_ACTIVE)->where('driver_id', $driver->id)->find($id);

        if (!$ride) {
            $notify[] = 'El viaje no fue encontrado o aún no es elegible para iniciar';
            return apiResponse('not_found', 'error', $notify);
        }

        $hasRunningRide = Ride::running()->where('driver_id', $driver->id)->first();

        if ($hasRunningRide) {
            $notify[] = 'Tienes otro viaje en curso. Debes completar ese viaje primero.';
            return apiResponse('complete', 'error', $notify);
        }

        if ($ride->otp != $request->otp) {
            $notify[] = 'El código OTP es inválido';
            return apiResponse('invalid', 'error', $notify);
        }

        $commission              = $ride->amount / 100 * $ride->commission_percentage;
        $ride->start_time        = now();
        $ride->status            = Status::RIDE_RUNNING;
        $ride->commission_amount = $commission;
        $ride->save();

        $ride->load('driver', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year', 'service', 'user');

        event(new EventsRide("rider-user-$ride->user_id", 'PICK_UP', [
            'ride' => $ride
        ]));

        notify($ride->user, 'START_RIDE', [
            'ride_id'         => $ride->uid,
            'amount'          => showAmount($ride->amount, currencyFormat: false),
            'rider'           => $ride->user->username,
            'service'         => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination'     => $ride->destination,
            'duration'        => $ride->duration,
            'distance'        => $ride->distance,
            'pickup_time'     => showDateTime(now())
        ]);

        $notify[] = 'El viaje ha sido iniciado';
        return apiResponse("ride_start", "success", $notify);
    }

    public function end(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $driver = auth()->user();
        $ride   = Ride::running()
            ->where('driver_id', $driver->id)
            ->find($id);

        if (!$ride) {
            $notify[] = 'El viaje no fue encontrado';
            return apiResponse('not_found', 'error', $notify);
        }

        // update the driver current zone
        if ($ride->ride_type == Status::INTER_CITY_RIDE) {
            $zones       = Zone::active()->get();
            $address     = ['lat' => $request->latitude, 'long' => $request->longitude];
            $currentZone = null;

            foreach ($zones as $zone) {
                $findZone = insideZone($address, $zone);
                if ($findZone) {
                    $currentZone = $zone;
                    break;
                }
            }
            if ($currentZone) {
                $driver->zone_id = $currentZone->id;
                $driver->save();
            }
        }

        $ride->payment_status = Status::PAYMENT_PENDING;
        $ride->status         = Status::RIDE_END;
        $ride->end_time       = now();
        $ride->duration       = getAmount(now()->parse($ride->start_time)->diffinMinutes(now())) . " Min";
        $ride->save();

        $ride->load('driver', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year', 'service', 'user');

        event(new EventsRide("rider-user-$ride->user_id", 'RIDE_END', [
            'ride' => $ride
        ]));

        notify($ride->user, 'RIDE_END', [
            'ride_id'         => $ride->uid,
            'amount'          => showAmount($ride->amount, currencyFormat: false),
            'service'         => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination'     => $ride->destination,
            'duration'        => $ride->duration,
            'distance'        => $ride->distance,
        ]);

        $notify[] = 'El viaje ahora está disponible para el pago';
        return apiResponse("ride_complete", 'success', $notify);
    }

    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $driver = auth()->user();

        $ride = Ride::whereIn('status', [Status::RIDE_ACTIVE, Status::RIDE_RUNNING])
            ->where('driver_id', $driver->id)
            ->find($id);

        if (!$ride) {
            return apiResponse("not_found", 'error', ['Viaje no encontrado']);
        }

        $cancelRideCount = Ride::where('driver_id', $driver->id)
            ->where('canceled_user_type', Status::DRIVER)
            ->count();

        $penaltyApplied = $cancelRideCount >= gs('driver_cancellation_limit');

        if ($penaltyApplied) {
            $penalty = gs('driver_cancellation_penalty');
            $driver->balance -= $penalty;
            $driver->save();
        }

        $ride->cancel_reason      = $request->cancel_reason;
        $ride->canceled_user_type = Status::DRIVER;
        $ride->status             = Status::RIDE_CANCELED;
        $ride->cancelled_at       = now();
        $ride->save();

        event(new EventsRide("rider-user-$ride->user_id", "CANCEL_RIDE", [
            'ride' => $ride
        ]));

        notify($ride->user, 'CANCEL_RIDE', [
            'ride_id'         => $ride->uid,
            'reason'          => $ride->cancel_reason,
            'amount'          => showAmount($ride->amount, currencyFormat: false),
            'service'         => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination'     => $ride->destination,
            'duration'        => $ride->duration,
            'distance'        => $ride->distance,
            'pickup_time'     => showDateTime(now())
        ]);

        $message = $penaltyApplied
            ? 'Viaje cancelado y ' . showAmount(gs('driver_cancellation_penalty')) . ' deducidos como penalización'
            : 'Viaje cancelado exitosamente';

        $type   = $penaltyApplied ? 'limit_exceeded' : 'canceled_ride';

        return apiResponse($type, 'success', [$message]);
    }



    public function list()
    {
        $driver = auth()->user();
        $query  = Ride::with('user')->where('service_id', @$driver->service_id);

        if (request()->status == 'accept') {
            $query->pending()
                ->whereHas('bids', function ($q) use ($driver) {
                    $q->where('status', Status::BID_PENDING)->where("driver_id", $driver->id);
                })
                ->filter(['ride_type']);
        } elseif (request()->status == 'all') {
            $query->where('driver_id', auth()->id())
                ->orWhereHas('bids', function ($q) use ($driver) {
                    $q->where('status', Status::BID_PENDING)->where("driver_id", $driver->id);
                })
                ->filter(['ride_type']);
        } else {
            $query->where('driver_id', auth()->id())->filter(['status', 'ride_type']);
        }

        $rides = $query->with("service")->orderByRaw('FIELD(status, ' . Status::RIDE_END . ', ' . Status::RIDE_RUNNING . ', ' . Status::RIDE_ACTIVE . ', ' . Status::RIDE_PENDING . ', ' . Status::RIDE_COMPLETED . ', ' . Status::RIDE_CANCELED . ')')->orderBy('id', 'desc')->paginate(getPaginate());

        $notify[] = 'Lista de viajes';
        if (request()->status == 'new' && $driver->online_status != Status::YES) {
            $rides = null;
        }

        return apiResponse('ride_list', 'success', $notify, [
            'rides'           => $rides,
            'user_image_path' => getFilePath('user'),
        ]);
    }

    public function receivedCashPayment($id)
    {
        $driver = auth()->user();
        $ride   = Ride::where('status', Status::RIDE_END)->where('driver_id', $driver->id)->find($id);

        if (!$ride) {
            $notify[] = 'El viaje no fue encontrado';
            return apiResponse('not_found', 'error', $notify);
        }

        if ($ride->payment_status != Status::WAITING_FOR_CASH_PAYMENT) {
            $notify[] = "El pago en efectivo no está pendiente para este viaje";
            return apiResponse('not_found', 'error', $notify);
        }

        (new RidePaymentManager())->payment($ride, Status::PAYMENT_TYPE_CASH);

        $ride->load('bids', 'user', 'driver', 'service', 'userReview', 'driverReview', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year');

        event(new EventsRide("rider-user-$ride->user_id", 'CASH_PAYMENT_RECEIVED', [
            'ride' => $ride
        ]));

        notify($ride->user, 'CASH_PAYMENT_RECEIVED', [
            'ride_id'         => $ride->uid,
            'amount'          => showAmount($ride->amount, currencyFormat: false),
            'service'         => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination'     => $ride->destination,
            'duration'        => $ride->duration,
            'distance'        => $ride->distance,
        ]);

        $notify[] = 'Pago recibido exitosamente';
        return apiResponse('payment_received', 'success', $notify, [
            'ride' => $ride
        ]);
    }



    public function receipt($id)
{
    $ride = Ride::with(['user', 'driver'])
        ->where('driver_id', auth()->id())
        ->where('id', $id)
        ->first();

    if (!$ride) {
        return apiResponse('not_exists', 'error', ["El viaje no está disponible"]);
    }

    if ($ride->status != Status::RIDE_COMPLETED) {
        return apiResponse('not_available', 'error', ["El viaje no ha sido completado"]);
    }

    $finalAmount = $ride->amount + $ride->tips_amount - $ride->discount_amount;
    $pdfGeneratedTime = now();
    $type = "driver";

    $pdf = Pdf::loadView('admin.rides.pdf', compact(
        'ride',
        'type',
        'finalAmount',
        'pdfGeneratedTime'
    ));

    return response()->streamDownload(
        fn () => print($pdf->output()),
        'ride.pdf',
        ['Content-Type' => 'application/pdf']
    );
}
}