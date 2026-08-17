<?php

namespace App\Http\Controllers\Api\User;

use App\Models\Ride;
use App\Models\Zone;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\SosAlert;
use App\Constants\Status;
use App\Events\Ride as EventsRide;
use Illuminate\Http\Request;
use App\Models\GatewayCurrency;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Bid;
use App\Models\Deposit;
use App\Models\RideQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\Gateway;
use App\Http\Controllers\Gateway\PaymentController;


class RideController extends Controller
{

    public function findFareAndDistance(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'pickup_latitude'       => 'required|numeric',
            'pickup_longitude'      => 'required|numeric',
            'destination_latitude'  => 'required|numeric',
            'destination_longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }


        $zoneData = $this->getZone($request);

        if (@$zoneData['status'] == 'error') {
            $notify[] = $zoneData['message'];
            return apiResponse('not_found', 'error', $notify);
        }

        $googleMapData = $this->getGoogleMapData($request);

        if (@$googleMapData['status'] == 'error') {
            $notify[] = $googleMapData['message'];
            return apiResponse('api_error', 'error', $notify);
        }

        $pickUpZone      = $zoneData['pickup_zone'];
        $destinationZone = $zoneData['destination_zone'];
        $distance        = $googleMapData['distance'];

        $services = Service::active()->orderBy('name')->get();
        $data = [];

        $serviceData['ride_type']        = Status::CITY_RIDE;



        if ($pickUpZone->id == $destinationZone->id) {
            $minColumName = "city_min_fare";
            $maxColumName = "city_max_fare";
            $recColumName = "city_recommend_fare";
            $baseFareCol  = "city_base_fare";
            $rateCol      = "city_rate_per_km";
            $minTripCol   = "city_min_trip_fare";
            $rideType     = Status::CITY_RIDE;
        } else {
            $minColumName = "intercity_min_fare";
            $maxColumName = "intercity_max_fare";
            $recColumName = "intercity_recommend_fare";
            $baseFareCol  = "intercity_base_fare";
            $rateCol      = "intercity_rate_per_km";
            $minTripCol   = "intercity_min_trip_fare";
            $rideType     = Status::INTER_CITY_RIDE;
        }

        foreach ($services as $service) {
            $serviceData = $service->toArray();

            $baseFare = $service->$baseFareCol;
            $minTrip  = $service->$minTripCol;
            $rate     = $service->$rateCol > 0 ? $service->$rateCol : $service->$recColumName;

            $minAmount       = max($baseFare + ($service->$minColumName * $distance), $minTrip);
            $maxAmount       = max($baseFare + ($service->$maxColumName * $distance), $minTrip);
            $recommendAmount = max($baseFare + ($rate * $distance), $minTrip);

            $serviceData['min_amount']       = getFareAmount($minAmount);
            $serviceData['max_amount']       = getFareAmount($maxAmount);
            $serviceData['recommend_amount'] = getFareAmount($recommendAmount);
            $data[]                          = $serviceData;
        }

        return apiResponse("ride_data", 'success',  ['ride_data'],  [
            'distance' => $distance,
            'ride_type' => $rideType,
            'services' => $data,
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id'            => 'required|integer',
            'pickup_latitude'       => 'required|numeric',
            'pickup_longitude'      => 'required|numeric',
            'pickup_location'       => 'required|string',
            'destination_latitude'  => 'required|numeric',
            'destination_longitude' => 'required|numeric',
            'destination_location'  => 'required|string',
            'note'                  => 'nullable',
            'number_of_passenger'   => 'required|integer',
            'offer_amount'          => 'required|numeric',
            'payment_type'          => ['required', Rule::in(Status::PAYMENT_TYPE_GATEWAY, Status::PAYMENT_TYPE_CASH)],
            'gateway_currency_id'   => $request->payment_type == Status::PAYMENT_TYPE_GATEWAY ? 'required|exists:gateway_currencies,id' : 'nullable',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $existsRide = Ride::where('user_id', auth()->id())->whereIn('status', [Status::RIDE_ACTIVE])->exists();

        if ($existsRide) {
            $notify[] = 'Puedes crear un viaje después de finalizar el viaje en curso.';
            return apiResponse("not_found", 'error', $notify);
        }

        $service = Service::active()->find($request->service_id);


        if (!$service) {
            $notify[] = 'Este servicio no está disponible actualmente';
            return apiResponse("not_found", 'error', $notify);
        }

        $zoneData = $this->getZone($request);

        if (@$zoneData['status'] == 'error') {
            $notify[] = $zoneData['message'];
            return apiResponse('not_found', 'error', $notify);
        }

        $googleMapData = $this->getGoogleMapData($request);

        if (@$googleMapData['status'] == 'error') {
            $notify[] = $googleMapData['message'];
            return apiResponse('api_error', 'error', $notify);
        }

        $data            = $googleMapData;
        $pickUpZone      = $zoneData['pickup_zone'];
        $destinationZone = $zoneData['destination_zone'];
        $distance        = $googleMapData['distance'];
        $user            = auth()->user();

        if ($pickUpZone->country !=  $destinationZone->country) {
            $notify[] = "La zona de recogida y la zona de destino deben estar dentro del mismo país.";
            return apiResponse('zone_error', 'error', $notify);
        }

        if ($pickUpZone->id == $destinationZone->id) {
            $baseFare = $service->city_base_fare;
            $minTrip  = $service->city_min_trip_fare;
            $rate     = $service->city_rate_per_km > 0 ? $service->city_rate_per_km : $service->city_recommend_fare;

            $data['min_amount']            = getFareAmount(max($baseFare + (getRideMiniumAmount($service->city_min_fare) * $distance), $minTrip));
            $data['max_amount']            = getFareAmount(max($baseFare + ($service->city_max_fare * $distance), $minTrip));
            $data['recommend_amount']      = getFareAmount(max($baseFare + ($rate * $distance), $minTrip));
            $data['ride_type']             = Status::CITY_RIDE;
            $data['commission_percentage'] = getAmount($service->city_fare_commission);
        } else {
            $baseFare = $service->intercity_base_fare;
            $minTrip  = $service->intercity_min_trip_fare;
            $rate     = $service->intercity_rate_per_km > 0 ? $service->intercity_rate_per_km : $service->intercity_recommend_fare;

            $data['min_amount']            = getFareAmount(max($baseFare + (getRideMiniumAmount($service->intercity_min_fare) * $distance), $minTrip));
            $data['max_amount']            = getFareAmount(max($baseFare + ($service->intercity_max_fare * $distance), $minTrip));
            $data['recommend_amount']      = getFareAmount(max($baseFare + ($rate * $distance), $minTrip));
            $data['ride_type']             = Status::INTER_CITY_RIDE;
            $data['commission_percentage'] = getAmount($service->intercity_fare_commission);
        }

        if ($distance < gs('min_distance')) {
            $notify[] = 'La distancia mínima debe ser ' . getAmount(gs('min_distance')) . ' ' . gs('distance_unit');
            return apiResponse('limit_error', 'error', $notify);
        }

        if ($request->offer_amount < $data['min_amount'] || $request->offer_amount > $data['max_amount']) {
            $notify[] = 'El monto ofrecido debe ser un mínimo de ' . showAmount($data['min_amount']) . ' y un máximo de ' . showAmount($data['max_amount']);
            return apiResponse('limit_error', 'error', $notify);
        }

        $penaltyAmount = $user->penalty_amount ?? 0;
        $finalAmount = $request->offer_amount + $penaltyAmount;

        $ride                        = new Ride();
        $ride->uid                   = getTrx(10);
        $ride->user_id               = $user->id;
        $ride->service_id            = $request->service_id;
        $ride->pickup_location       = $request->pickup_location;
        $ride->pickup_latitude       = $request->pickup_latitude;
        $ride->pickup_longitude      = $request->pickup_longitude;
        $ride->destination           = $request->destination_location;
        $ride->destination_latitude  = $request->destination_latitude;
        $ride->destination_longitude = $request->destination_longitude;
        $ride->ride_type             = $data['ride_type'];
        $ride->note                  = $request->note;
        $ride->number_of_passenger   = $request->number_of_passenger;
        $ride->distance              = $distance;
        $ride->duration              = $data['duration'];
        $ride->pickup_zone_id        = $pickUpZone->id;
        $ride->destination_zone_id   = $destinationZone->id;
        $ride->recommend_amount      = $data['recommend_amount'] + $penaltyAmount;
        $ride->min_amount            = $data['min_amount'] + $penaltyAmount;
        $ride->max_amount            = $data['max_amount'] + $penaltyAmount;
        $ride->amount                = $finalAmount;
        $ride->payment_type          = $request->payment_type;
        $ride->commission_percentage = $data['commission_percentage'];
        $ride->gateway_currency_id   = $request->payment_type == Status::PAYMENT_TYPE_GATEWAY ? $request->gateway_currency_id : 0;
        $ride->save();

        // Save user's pickup location for emergency/audit purposes
        $user->update([
            'latitude'  => $request->pickup_latitude,
            'longitude' => $request->pickup_longitude,
        ]);

        $ride->load('user', 'service', 'driver', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year');

        if ($penaltyAmount > 0) {
            $user->penalty_amount = 0;
            $user->save();
        }

        //create a ride queue
        $rideQueue              = new RideQueue();
        $rideQueue->ride_id     = $ride->id;
        $rideQueue->action_type = "new_driver_notification";
        $rideQueue->ordering    = time();
        $rideQueue->save();

        // Envío inmediato
        $rideQueue->dispatch_count = 1;
        $rideQueue->save();
        (new \App\Lib\ManageRideQueue())->initQueue($rideQueue);

        $notify[] = 'Viaje creado exitosamente';

        return apiResponse('ride_create_success', 'success', $notify, [
            'ride' => $ride
        ]);
    }

    public function details($id)
    {

        $ride = Ride::with(['bids', 'userReview', 'driverReview', 'driver', 'service', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year'])
            ->where('user_id', auth()->id())
            ->find($id);

        if (!$ride) {
            $notify[] = 'Viaje inválido';
            return apiResponse('not_found', 'error', $notify);
        }

        $driverRideCount = Ride::where('driver_id', $ride->driver_id)->where('id', '!=', $ride->id)->where('status', Status::RIDE_COMPLETED)->count();

        $notify[]        = 'Detalles del viaje';

        return apiResponse('ride_details', 'success', $notify, [
            'ride'               => $ride,
            'service_image_path' => getFilePath('service'),
            'brand_image_path'   => getFilePath('brand'),
            'user_image_path'    => getFilePath('user'),
            'driver_image_path'  => getFilePath('driver'),
            'driver_total_ride'  => $driverRideCount,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $user = auth()->user();

        $ride = Ride::whereIn('status', [Status::RIDE_PENDING, Status::RIDE_ACTIVE])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$ride) {
            return apiResponse("not_found", 'error', ['Viaje no encontrado']);
        }

        $cancelRideCount = Ride::where('user_id', $user->id)
            ->where('canceled_user_type', Status::USER)
            ->count();

        $penaltyApplied = $cancelRideCount >= gs('user_cancellation_limit');

        if ($penaltyApplied) {
            $penalty = gs('user_cancellation_penalty');
            $user->penalty_amount += $penalty;
            $user->save();
        }

        $ride->cancel_reason      = $request->cancel_reason;
        $ride->canceled_user_type = Status::USER;
        $ride->status             = Status::RIDE_CANCELED;
        $ride->cancelled_at       = now();
        $ride->save();

        if ($ride->driver_id) {

            event(new EventsRide("rider-driver-$ride->driver_id", "CANCEL_RIDE", [
                'ride' => $ride
            ]));

            notify($ride->driver, 'CANCEL_RIDE', [
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
        }

        $message = $penaltyApplied
            ? 'Se te ha cobrado ' . showAmount(gs('user_cancellation_penalty')) . ' por la cancelación del viaje'
            : 'Viaje cancelado exitosamente';

        return apiResponse("canceled_ride", 'success', [$message]);
    }

    public function sos(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'message'   => 'nullable',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $ride = Ride::running()->where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = 'El viaje no fue encontrado';
            return apiResponse('invalid_ride', 'error', $notify);
        }

        $sosAlert            = new SosAlert();
        $sosAlert->ride_id   = $id;
        $sosAlert->latitude  = $request->latitude;
        $sosAlert->longitude = $request->longitude;
        $sosAlert->message   = $request->message;
        $sosAlert->status    = Status::ENABLE;
        $sosAlert->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $ride->user->id;
        $adminNotification->title     = 'Se ha creado una nueva alerta SOS, por favor tome acción';
        $adminNotification->click_url = urlPath('admin.rides.detail', $ride->id);
        $adminNotification->save();

        $notify[] = 'Solicitud SOS enviada exitosamente';
        return apiResponse("sos_request", "success", $notify);
    }

    public function list()
    {
        $rides = Ride::with(['driver', 'user', 'service', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year'])
            ->withCount(['bids' => function ($q) {
                $q->whereIn('status', [Status::BID_PENDING, Status::BID_ACCEPTED]);
            }])
            ->filter(['ride_type', 'status'])
            ->where('user_id', auth()->id())
            ->orderByRaw('FIELD(status, ' . Status::RIDE_END . ', ' . Status::RIDE_RUNNING . ', ' . Status::RIDE_ACTIVE . ', ' . Status::RIDE_PENDING . ', ' . Status::RIDE_COMPLETED . ', ' . Status::RIDE_CANCELED . ')')
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        $notify[]      = "Obtener la lista de viajes";
        $data['rides'] = $rides;

        return apiResponse("ride_list", 'success', $notify, $data);
    }

    private function getZone($request)
    {
        $zones           = Zone::active()->get();
        $pickupAddress   = ['lat' => $request->pickup_latitude, 'long' => $request->pickup_longitude];
        $pickupZone      = null;
        $destinationZone = null;

        foreach ($zones as $zone) {
            $pickupZone = insideZone($pickupAddress, $zone);
            if ($pickupZone) {
                $pickupZone = $zone;
                break;
            }
        }

        if (!$pickupZone) {
            return [
                'status'  => 'error',
                'message' => 'La ubicación de recogida no está dentro de ninguna de nuestras zonas'
            ];
        }

        $destinationAddress = ['lat' => $request->destination_latitude, 'long' => $request->destination_longitude];

        foreach ($zones as $zone) {
            $destinationZone = insideZone($destinationAddress, $zone);

            if ($destinationZone) {
                $destinationZone = $zone;
                break;
            }
        }

        if (!$destinationZone) {
            return [
                'status'  => 'error',
                'message' => 'La ubicación de destino no está dentro de ninguna de nuestras zonas'
            ];
        }

        return [
            'pickup_zone'      => $pickupZone,
            'destination_zone' => $destinationZone,
            'status'           => 'success'
        ];
    }

    private function getGoogleMapData($request)
    {
        $apiKey        = gs('google_maps_api');
        $url           = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$request->pickup_latitude},{$request->pickup_longitude}&destinations={$request->destination_latitude},{$request->destination_longitude}&units=driving&key={$apiKey}";
        $response      = file_get_contents($url);
        $googleMapData = json_decode($response);

        if ($googleMapData->status != 'OK') {
            return [
                'status'  => 'error',
                'message' => '¡Algo salió mal!'
            ];
        }

        if ($googleMapData->rows[0]->elements[0]->status == 'ZERO_RESULTS') {
            return [
                'status'  => 'error',
                'message' => 'Dirección no encontrada'
            ];
        }

        $distance = gs('distance_unit') == Status::MILE_UNIT ? ($googleMapData->rows[0]->elements[0]->distance->value / 1000) * 0.621371 : $googleMapData->rows[0]->elements[0]->distance->value / 1000;

        $duration = $googleMapData->rows[0]->elements[0]->duration->text;

        return [
            'distance'            => $distance,
            'duration'            => $duration,
            'origin_address'      => $googleMapData->origin_addresses[0],
            'destination_address' => $googleMapData->destination_addresses[0],
        ];
    }

    public function bids($id)
    {
        $ride = Ride::where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = 'El viaje no fue encontrado';
            return apiResponse('not_found', 'error', $notify);
        }

        $bids = Bid::with(['driver', 'driver.service', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year'])
            ->where('ride_id', $ride->id)
            ->where('status', Status::BID_PENDING)
            ->get();

        $notify[] = 'Todas las ofertas';

        return apiResponse("bids", "success", $notify, [
            'bids'              => $bids,
            'ride'              => $ride,
            'driver_image_path' => getFilePath('driver'),
            'user_image_path'   => getFilePath('user'),
        ]);
    }

    public function accept($bidId)
    {
        $bid = Bid::pending()->with('ride', 'driver')->whereHas('ride', function ($q) {
            return $q->pending()->where('user_id', auth()->id());
        })->find($bidId);

        if (!$bid) {
            $notify[] = 'Oferta inválida';
            return apiResponse('not_found', 'error', $notify);
        }

        $activeRide = Ride::where('user_id', auth()->id())->active()->exists();

        if ($activeRide) {
            $notify[] = 'Tienes un viaje activo';
            return apiResponse('active_ride', 'error', $notify);
        }

        $runningRide = Ride::where('user_id', auth()->id())->running()->exists();

        if ($runningRide) {
            $notify[] = 'Tienes un viaje en curso';
            return apiResponse('running_ride', 'error', $notify);
        }

        $bid->status      = Status::BID_ACCEPTED;
        $bid->accepted_at = now();
        $bid->save();

        //all the bid rejected after the one accept this bid
        Bid::where('id', '!=', $bid->id)->where('ride_id', $bid->ride_id)->update(['status' => Status::BID_REJECTED]);

        $ride            = $bid->ride;
        $ride->status    = Status::RIDE_ACTIVE;
        $ride->driver_id = $bid->driver_id;
        $ride->otp       = getNumber(4);
        $ride->amount    = $bid->bid_amount;
        if ($bid->driver && $bid->driver->fleet_id) {
            $ride->fleet_id = $bid->driver->fleet_id;
        }
        $ride->save();

        $ride->load('driver', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year', 'service', 'user');
        $driverRideCount = Ride::where('driver_id', $ride->driver_id)->where('id', '!=', $ride->id)->where('status', Status::RIDE_COMPLETED)->count();

        event(new EventsRide("rider-driver-$ride->driver_id", "BID_ACCEPT", [
            'ride'              => $ride,
            'driver_total_ride' => $driverRideCount
        ]));

        event(new EventsRide("rider-user-$ride->user_id", "BID_ACCEPT", [
            'ride'              => $ride,
            'driver_total_ride' => $driverRideCount
        ]));

        notify($ride->driver, 'ACCEPT_RIDE', [
            'ride_id'         => $ride->uid,
            'amount'          => showAmount($ride->amount),
            'rider'           => $ride->user->username,
            'service'         => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination'     => $ride->destination,
            'duration'        => $ride->duration,
            'distance'        => $ride->distance,
            'pickup_time'     => showDateTime(now()),
        ]);

        $notify[] = 'Oferta aceptada exitosamente';
        return apiResponse('accepted', 'success', $notify, [
            'ride' => $ride
        ]);
    }

    public function reject($id)
    {
        $bid = Bid::pending()->with('ride', 'driver')->find($id);

        if (!$bid) {
            $notify[] = 'Oferta inválida';
            return apiResponse('not_found', 'error', $notify);
        }

        $ride = $bid->ride;
        if ($ride->user_id != auth()->id()) {
            $notify[] = 'Este viaje no pertenece a este usuario';
            return apiResponse('unauthenticated', 'error', $notify);
        }

        $bid->status = Status::BID_REJECTED;
        $bid->save();

        event(new EventsRide("rider-driver-$bid->driver_id", 'BID_REJECT', [
            'ride' => $ride
        ]));

        notify($ride->driver, 'BID_REJECT', [
            'ride_id'         => $ride->uid,
            'amount'          => showAmount($bid->bid_amount),
            'service'         => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination'     => $ride->destination,
            'duration'        => $ride->duration,
            'distance'        => $ride->distance,
            'pickup_time'     => showDateTime(now()),
        ]);

        $notify[] = 'Oferta rechazada exitosamente';
        return apiResponse('rejected_bid', 'success', $notify);
    }

    public function payment($id)
    {
        $ride = Ride::where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = 'El viaje no fue encontrado';
            return apiResponse('not_found', 'error', $notify);
        }

        $ride->load('driver', 'driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year', 'service', 'user', 'coupon');

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->active();
        })->with('method')->orderby('method_code')->get();

        $notify[] = "Pagos del viaje";
        return apiResponse('payment', 'success', $notify, [
            'gateways'          => $gatewayCurrency,
            'image_path'        => getFilePath('gateway'),
            'ride'              => $ride,
            'coupons'           => Coupon::orderBy('id', 'desc')->active()->get(),
            'driver_image_path' => getFilePath('driver'),
        ]);
    }

    public function paymentSave(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_type' => ['required', Rule::in(Status::PAYMENT_TYPE_GATEWAY, Status::PAYMENT_TYPE_CASH)],
            'method_code'  => 'required_if:payment_type,1',
            'currency'     => 'required_if:payment_type,1',
            'tips_amount'  => 'required|numeric|gte:0'
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $ride  = Ride::where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = 'El viaje no fue encontrado';
            return apiResponse('not_found', 'error', $notify);
        }

        if ($ride->status == Status::RIDE_COMPLETED) {
            $notify[] = 'El viaje ya fue completado';
            return apiResponse('not_found', 'error', $notify);
        }

        $ride->tips_amount = $request->tips_amount;
        $ride->save();

        if ($request->payment_type == Status::PAYMENT_TYPE_GATEWAY) {
            return $this->paymentViaGateway($request, $ride);
        } else {

            $ride->payment_status = Status::WAITING_FOR_CASH_PAYMENT;
            $ride->save();

            $ride->load('driver.vehicle', 'driver.vehicle.model', 'driver.vehicle.color', 'driver.vehicle.year', 'user', 'service');

            event(new EventsRide("rider-driver-$ride->driver_id", 'CASH_PAYMENT_REQUEST', [
                'ride' => $ride
            ]));

            event(new EventsRide("rider-user-$ride->user_id", 'CASH_PAYMENT_REQUEST', [
                'ride' => $ride
            ]));

            notify($ride->driver, 'CASH_PAYMENT_REQUEST', [
                'ride_id'         => $ride->uid,
                'amount'          => showAmount($ride->amount, currencyFormat: false),
                'service'         => $ride->service->name,
                'pickup_location' => $ride->pickup_location,
                'destination'     => $ride->destination,
                'duration'        => $ride->duration,
                'distance'        => $ride->distance,
            ]);

            $notify[] = "Por favor, entregue al conductor " . showAmount($ride->amount) . " en efectivo.";
            return apiResponse('cash_payment', 'success', $notify, [
                'ride' => $ride
            ]);
        }
    }

    private function paymentViaGateway($request, $ride)
    {
        $amount = $ride->amount - $ride->discount_amount + $ride->tips_amount;

        $gateway = GatewayCurrency::whereHas('method', function ($gateway) {
            $gateway->active();
        })->where('method_code', $request->method_code)->where('currency', $request->currency)->first();

        if (!$gateway) {
            $notify[] = "Pasarela de pago inválida seleccionada";
            return apiResponse('not_found', 'error', $notify);
        }

        // ── MercadoPago → Checkout API (no redirect) ──
        $isMercadoPago = stripos($gateway->method->alias ?? '', 'mercadopago') !== false
                      || stripos($gateway->method->name  ?? '', 'mercadopago') !== false;

        if ($isMercadoPago) {
            $mpGateway = Gateway::where('alias', 'MercadoPago')->active()->first();
            if ($mpGateway) {
                $param     = json_decode($mpGateway->gateway_parameters);
                $publicKey = $param->public_key->value ?? ($param->public_key ?? '');
                if ($publicKey) {
                    $user  = auth()->user();
                    $trx   = getTrx();
                    // Create deposit so IPN can also confirm later
                    $deposit                  = new Deposit();
                    $deposit->from_api        = 1;
                    $deposit->user_id         = $user->id;
                    $deposit->method_code     = $gateway->method_code;
                    $deposit->method_currency = strtoupper($gateway->currency);
                    $deposit->amount          = $amount;
                    $deposit->charge          = 0;
                    $deposit->rate            = $gateway->rate;
                    $deposit->final_amount    = $amount * $gateway->rate;
                    $deposit->ride_id         = $ride->id;
                    $deposit->btc_amount      = 0;
                    $deposit->btc_wallet      = '';
                    $deposit->success_url     = urlPath('user.deposit.history');
                    $deposit->failed_url      = urlPath('user.deposit.history');
                    $deposit->trx             = $trx;
                    $deposit->save();

                    return apiResponse('mp_checkout_api', 'success', ['Checkout API MercadoPago'], [
                        'checkout_api'   => true,
                        'public_key'     => $publicKey,
                        'trx'            => $trx,
                        'deposit_id'     => $deposit->id,
                        'amount'         => round((float) $deposit->final_amount, 2),
                        'currency'       => $gateway->currency,
                        'payer_email'    => $user->email ?? '',
                        'description'    => 'Viaje #' . $ride->uid,
                    ]);
                }
            }
        }

        if ($gateway->min_amount > $amount) {
            $notify[] = 'El límite mínimo para esta pasarela es ' . showAmount($gateway->min_amount);
            return apiResponse('limit_exists', 'error', $notify);
        }
        if ($gateway->max_amount < $amount) {
            $notify[] = 'El límite máximo para esta pasarela es ' . showAmount($gateway->max_amount);
            return apiResponse('limit_exists', 'error', $notify);
        }

        $charge      = 0;
        $payable     = $amount + $charge;
        $finalAmount = $payable * $gateway->rate;
        $user        = auth()->user();

        $data                  = new Deposit();
        $data->from_api        = 1;
        $data->user_id         = $user->id;
        $data->method_code     = $gateway->method_code;
        $data->method_currency = strtoupper($gateway->currency);
        $data->amount          = $amount;
        $data->charge          = $charge;
        $data->rate            = $gateway->rate;
        $data->final_amount    = $finalAmount;
        $data->ride_id         = $ride->id;
        $data->btc_amount      = 0;
        $data->btc_wallet      = '';
        $data->success_url     = urlPath('user.deposit.history');
        $data->failed_url      = urlPath('user.deposit.history');
        $data->trx             = getTrx();
        $data->save();

        $notify[] = "Pago en línea";

        return apiResponse('gateway_payment', 'success', $notify, [
            'deposit'      => $data,
            'redirect_url' => route('deposit.app.confirm', encrypt($data->id))
        ]);
    }

    /**
     * Process MercadoPago Checkout API card token for a ride payment.
     * POST /api/ride/payment/{id}/mp-process
     */
    public function mpCheckoutProcess(Request $request, $id)
    {
        $request->validate([
            'deposit_id'        => 'required|integer',
            'card_token'        => 'required|string',
            'installments'      => 'required|integer|min:1',
            'payment_method_id' => 'required|string',
            'issuer_id'         => 'nullable',
            'payer_email'       => 'required|email',
            'payer_doc_type'    => 'nullable|string',
            'payer_doc_num'     => 'nullable|string',
        ]);

        $user    = auth()->user();
        $ride    = Ride::where('user_id', $user->id)->findOrFail($id);
        $deposit = Deposit::where('user_id', $user->id)
            ->where('id', $request->deposit_id)
            ->where('status', Status::PAYMENT_INITIATE)
            ->firstOrFail();

        $mpGateway = Gateway::where('alias', 'MercadoPago')->active()->first();
        if (!$mpGateway) {
            return apiResponse('gateway_error', 'error', ['Gateway MercadoPago no encontrado']);
        }

        $param       = json_decode($mpGateway->gateway_parameters);
        $accessToken = $param->access_token->value ?? ($param->access_token ?? '');

        if (!$accessToken) {
            return apiResponse('gateway_error', 'error', ['Access Token de MercadoPago no configurado']);
        }

        $paymentData = [
            'transaction_amount' => round((float) $deposit->final_amount, 2),
            'token'              => $request->card_token,
            'description'        => 'Viaje #' . $ride->uid,
            'installments'       => (int) $request->installments,
            'payment_method_id'  => $request->payment_method_id,
            'issuer_id'          => $request->issuer_id ?: null,
            'payer'              => [
                'email'          => $request->payer_email,
                'identification' => [
                    'type'   => $request->payer_doc_type ?: 'DNI',
                    'number' => $request->payer_doc_num  ?: '',
                ],
            ],
            'external_reference'  => $deposit->trx,
            'notification_url'    => url('/ipn/MercadoPago'),
            'metadata'            => ['trx' => $deposit->trx, 'ride_id' => $ride->id],
        ];

        $ch = curl_init('https://api.mercadopago.com/v1/payments');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($paymentData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'X-Idempotency-Key: ride-' . $deposit->trx,
            ],
        ]);
        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return apiResponse('connection_error', 'error', ['Error de conexión con MercadoPago. Intenta de nuevo.']);
        }

        $result   = json_decode($response, true);
        $mpStatus = $result['status'] ?? null;

        if ($mpStatus === 'approved') {
            PaymentController::userDataUpdate($deposit);
            return apiResponse('payment_approved', 'success', ['¡Pago aprobado!'], [
                'mp_status'     => 'approved',
                'mp_payment_id' => $result['id'] ?? null,
                'ride_id'       => $ride->id,
            ]);
        }

        if ($mpStatus === 'in_process' || $mpStatus === 'pending') {
            $deposit->status = Status::PAYMENT_PENDING;
            $deposit->save();
            return apiResponse('payment_pending', 'success', ['Tu pago está en revisión.'], [
                'mp_status'     => 'pending',
                'mp_payment_id' => $result['id'] ?? null,
            ]);
        }

        $detail = $result['status_detail'] ?? ($result['message'] ?? 'Pago rechazado');
        $friendlyMessages = [
            'cc_rejected_insufficient_amount'    => 'Fondos insuficientes en la tarjeta.',
            'cc_rejected_bad_filled_cvv'         => 'CVV incorrecto.',
            'cc_rejected_bad_filled_date'        => 'Fecha de vencimiento incorrecta.',
            'cc_rejected_bad_filled_card_number' => 'Número de tarjeta incorrecto.',
            'cc_rejected_high_risk'              => 'Pago rechazado por seguridad. Contacta a tu banco.',
            'cc_rejected_call_for_authorize'     => 'Debes autorizar el pago con tu banco.',
            'cc_rejected_card_disabled'          => 'La tarjeta está desactivada.',
        ];
        $message = $friendlyMessages[$detail] ?? 'Pago rechazado: ' . str_replace('_', ' ', $detail);

        return apiResponse('payment_rejected', 'error', [$message], [
            'mp_status'        => $mpStatus,
            'mp_status_detail' => $detail,
        ], 422);
    }

    public function receipt($id)
    {

        $ride = Ride::with(['user', 'driver'])->where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = "El viaje no está disponible";
            return apiResponse('not_exists', 'error', $notify);
        }

        if ($ride->status != Status::RIDE_COMPLETED) {
            $notify[] = "El recibo del viaje no está disponible en este momento";
            return apiResponse('not_exists', 'error', $notify);
        }

        $finalAmount      = $ride->amount + $ride->tips_amount - $ride->discount_amount;
        $pdfGeneratedTime = now();

        $type     = "user";
        $pdf      = Pdf::loadView('admin.rides.pdf', compact('ride', 'type', 'finalAmount', 'pdfGeneratedTime'));
        $fileName = 'ride.pdf';

        return $pdf->stream($fileName);
    }
}
