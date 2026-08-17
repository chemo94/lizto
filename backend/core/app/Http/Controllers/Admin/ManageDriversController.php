<?php
namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\UserNotificationSender;
use App\Models\Deposit;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\Ride;
use App\Models\RidePayment;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\UserLogin;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Zone;
use App\Rules\FileTypeValidate;
use App\Services\FcmService;
use Illuminate\Http\Request;

class ManageDriversController extends Controller {
    public function allDrivers() {
        $pageTitle = __('All Drivers');
        extract($this->driverData());
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());

        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function activeDrivers() {
        $pageTitle = __('Active Drivers');
        extract($this->driverData('active'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function bannedDrivers() {
        $pageTitle = __('Banned Drivers');
        extract($this->driverData('banned'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function emailUnverifiedDrivers() {
        $pageTitle = __('Email Unverified Drivers');
        extract($this->driverData('emailUnverified'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function unverifiedDrivers() {
        $pageTitle = __('Document Unverified Drivers');
        extract($this->driverData('documentUnverified'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function verifyPendingDrivers() {
        $pageTitle = __('Driver Verification Pending');
        extract($this->driverData('documentVerifyPending'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function vehicleUnverifiedDrivers() {
        $pageTitle = __('Vehicle Unverified Drivers');
        extract($this->driverData("vehicleUnverified"));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function vehiclePendingDrivers() {
        $pageTitle = __('Vehicle Verification Pending');
        extract($this->driverData("vehicleVerifyPending"));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function emailVerifiedDrivers() {
        $pageTitle = __('Email Verified Drivers');
        extract($this->driverData());
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function mobileUnverifiedDrivers() {
        $pageTitle = __('Mobile Unverified Drivers');
        extract($this->driverData('mobileUnverified'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    public function mobileVerifiedDrivers() {
        $pageTitle = __('Mobile Verified Drivers');
        extract($this->driverData());
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());
        return view('admin.driver.list', compact('pageTitle', 'drivers', 'widget'));
    }

    protected function driverData($scope = "query") {
        $baseQuery  = Driver::$scope()->with('ride')->searchable(['email', 'username', 'firstname', 'lastname'])->dateFilter()->filter(['status'])->orderBy('id', getOrderBy());
        $countQuery = Driver::query();

        $widget['all']   = (clone $countQuery)->count();
        $widget['today'] = (clone $countQuery)->whereDate('created_at', now())->count();
        $widget['week']  = (clone $countQuery)->whereDate('created_at', ">=", now()->subDays(7))->count();
        $widget['month'] = (clone $countQuery)->whereDate('created_at', ">=", now()->subDays(30))->count();

        return [
            'baseQuery' => $baseQuery,
            'widget'    => $widget,
        ];
    }

    public function detail($id) {
        $driver    = Driver::with('vehicle', 'zone')->findOrFail($id);
        $pageTitle = 'Driver Detail - ' . $driver->username;
        $loginLogs = UserLogin::where('driver_id', $driver->id)->take(6)->get();
        $services  = Service::get();
        $zones     = Zone::active()->orderBy('name')->get();

        $widget['total_deposit']  = Deposit::where('driver_id', $driver->id)->successful()->sum('amount');
        $widget['total_payment']  = RidePayment::where('driver_id', $driver->id)->sum('amount');
        $widget['total_withdraw'] = Withdrawal::where('driver_id', $driver->id)->approved()->sum('amount');
        $countries                = json_decode(file_get_contents(resource_path('views/partials/country.json')));

        $rideQuery = Ride::where('driver_id', $driver->id);

        $widget['total_ride']     = (clone $rideQuery)->count();
        $widget['completed_ride'] = (clone $rideQuery)->completed()->count();
        $widget['canceled_ride']  = (clone $rideQuery)->canceled()->count();
        $widget['running_ride']   = (clone $rideQuery)->running()->count();

        return view('admin.driver.detail', compact('pageTitle', 'driver', 'widget', 'countries', 'loginLogs', 'services', 'zones'));
    }

    public function changeZone(Request $request, $id) {
        $request->validate([
            'zone_id' => 'required|exists:zones,id',
        ]);

        $driver          = Driver::findOrFail($id);
        $driver->zone_id = $request->zone_id;
        $driver->save();

        $notify[] = ['success', __('Driver zone updated successfully')];
        return back()->withNotify($notify);
    }
    public function location($id) {
        $driver    = Driver::findOrFail($id);
        $pageTitle = __('Driver Location');

        return view('admin.driver.location', compact('pageTitle', 'driver'));
    }

    public function liveLocation($id) {
        $driver = Driver::find($id);
        if (!$driver) {
            return response()->json(['error' => 'Driver not found'], 404);
        }
        return response()->json([
            'success' => true,
            'latitude' => $driver->current_lat,
            'longitude' => $driver->current_lot,
            'last_location_fetch_at' => $driver->last_location_fetch_at ? showDateTime($driver->last_location_fetch_at) : null
        ]);
    }

    public function verificationApprove($id) {
        $driver     = Driver::findOrFail($id);
        $driver->dv = Status::VERIFIED;
        $driver->save();

        notify($driver, 'DRIVER_DOCUMENT_APPROVE', []);
        $notify[] = ['success', __('Driver verification approved successfully')];
        return back()->withNotify($notify);
    }

    public function verificationReject($id) {
        $driver     = Driver::findOrFail($id);
        $driver->dv = Status::UNVERIFIED;
        $driver->save();

        notify($driver, 'DRIVER_DOCUMENT_REJECT', []);

        $notify[] = ['success', __('Driver information has been rejected')];
        return back()->withNotify($notify);
    }

    public function vehicleApprove($id) {
        $driver     = Driver::findOrFail($id);
        $driver->vv = Status::VERIFIED;
        $driver->save();

        notify($driver, 'VEHICLE_VERIFY_APPROVE', []);
        $notify[] = ['success', __('Vehicle information approved successfully')];
        return back()->withNotify($notify);
    }

    public function vehicleReject($id) {
        $driver     = Driver::find($id);
        $driver->vv = Status::UNVERIFIED;
        $driver->save();

        notify($driver, 'VEHICLE_VERIFY_REJECT', []);

        $notify[] = ['success', __('Vehicle information has been rejected')];
        return back()->withNotify($notify);
    }

    public function rideRules($id) {
        $pageTitle = __('Ride Rules');
        $driver    = Driver::findOrFail($id);
        return view('admin.driver.rules_detail', compact('pageTitle', 'driver', 'widget'));
    }

    public function update(Request $request, $id) {
        $driver       = Driver::findOrFail($id);
        $countryData  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryArray = (array) $countryData;
        $countries    = implode(',', array_keys($countryArray));

        $countryCode = $request->country;
        $country     = $countryData->$countryCode->country;
        $dialCode    = $countryData->$countryCode->dial_code;

        $request->validate([
            'firstname' => 'required|string|max:40',
            'lastname'  => 'required|string|max:40',
            'email'     => 'required|email|string|max:40|unique:drivers,email,' . $driver->id,
            'mobile'    => 'required|string|max:40',
            'service'   => 'nullable|integer|exists:drivers,id',
            'service_type' => 'nullable|string|in:ride,delivery,both',
            'country'   => 'required|in:' . $countries,
        ]);

        $exists = Driver::where('mobile', $request->mobile)->where('dial_code', $dialCode)->where('id', '!=', $driver->id)->exists();

        if ($exists) {
            $notify[] = ['error', __('The mobile number already exists.')];
            return back()->withNotify($notify);
        }

        $driver->mobile    = $request->mobile;
        $driver->firstname = $request->firstname;
        $driver->lastname  = $request->lastname;
        $driver->email     = $request->email;

        $driver->address      = $request->address;
        $driver->city         = $request->city;
        $driver->state        = $request->state;
        $driver->zip          = $request->zip;
        $driver->service_id   = $request->service ?? 0;
        $driver->service_type = $request->service_type ?? 'ride';
        $driver->country_name = @$country;
        $driver->dial_code    = $dialCode;
        $driver->country_code = $countryCode;

        $driver->ev = $request->ev ? Status::VERIFIED : Status::UNVERIFIED;
        $driver->sv = $request->sv ? Status::VERIFIED : Status::UNVERIFIED;
        $driver->ts = $request->ts ? Status::ENABLE : Status::DISABLE;
        $driver->vv = $request->vv ? Status::VERIFIED : Status::UNVERIFIED;
        $driver->dv = $request->dv ? Status::VERIFIED : Status::UNVERIFIED;
        $driver->save();

        $notify[] = ['success', __('Driver details updated successfully')];
        return back()->withNotify($notify);
    }

    public function addSubBalance(Request $request, $id) {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act'    => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        $driver = Driver::findOrFail($id);
        $amount = $request->amount;

        if ($request->act == 'sub' && $driver->balance < $amount) {
            $notify[] = ['error', $driver->username . ' no tiene saldo suficiente.'];
            return back()->withNotify($notify);
        }

        // Fuente de verdad: columna balance + tabla transactions
        $driver->balance += ($request->act == 'add' ? $amount : -$amount);
        $driver->save();

        $trx = new Transaction();
        $trx->driver_id    = $driver->id;
        $trx->amount       = $amount;
        $trx->post_balance = $driver->balance;
        $trx->charge       = 0;
        $trx->trx          = getTrx();
        $trx->trx_type     = $request->act == 'add' ? '+' : '-';
        $trx->remark       = $request->act == 'add' ? 'balance_add' : 'balance_subtract';
        $trx->details      = $request->remark;
        $trx->save();

        // Sync wallet
        $this->ensureWallet($driver);
        $wallet = $driver->wallet;
        if ($request->act == 'add') {
            $wallet->credit($amount, $request->remark, 'Agregado por admin');
        } else {
            $wallet->debit($amount, $request->remark, 'Deducido por admin');
        }

        // FCM al repartidor
        $body = $request->act == 'add'
            ? 'Se ha agregado S/ ' . number_format($amount, 2) . ' a tu billetera. Nuevo saldo: S/ ' . number_format($driver->balance, 2)
            : 'Se ha deducido S/ ' . number_format($amount, 2) . ' de tu billetera. Nuevo saldo: S/ ' . number_format($driver->balance, 2);
        try {
            FcmService::sendToDriver($driver,
                $request->act == 'add' ? 'Saldo agregado' : 'Saldo deducido',
                $body,
                ['type' => $request->act == 'add' ? 'wallet_topup' : 'wallet_deduct', 'amount' => (string) $amount]
            );
        } catch (\Exception $e) {}

        $notify[] = ['success', $request->act == 'add' ? 'Saldo agregado correctamente' : 'Saldo deducido correctamente'];
        return back()->withNotify($notify);
    }

    public function status(Request $request, $id) {
        $driver = Driver::findOrFail($id);
        if ($driver->status == Status::USER_ACTIVE) {
            $request->validate([
                'reason' => 'required|string|max:255',
            ]);
            $driver->status     = Status::USER_BAN;
            $driver->ban_reason = $request->reason;
            $notify[] = ['success', __('Driver banned successfully')];
        } else {
            $driver->status     = Status::USER_ACTIVE;
            $driver->ban_reason = null;
            $notify[] = ['success', __('Driver unbanned successfully')];
        }
        $driver->save();
        return back()->withNotify($notify);
    }

    public function showNotificationSingleForm($id) {
        $driver = Driver::findOrFail($id);
        if (! gs('en') && ! gs('sn') && ! gs('pn')) {
            $notify[] = ['warning', __('Notification options are disabled currently')];
            return to_route('admin.rider.detail', $user->id)->withNotify($notify);
        }
        $pageTitle = 'Send Notification to ' . $driver->username;
        return view('admin.driver.notification_single', compact('pageTitle', 'driver'));
    }

    public function sendNotificationSingle(Request $request, $id) {

        $request->validate([
            'message' => __('required'),
            'via'     => 'required|in:email,sms,push',
            'subject' => 'required_if:via,email,push',
            'image'   => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if (! gs('en') && ! gs('sn') && ! gs('pn')) {
            $notify[] = ['warning', __('Notification options are disabled currently')];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        if ($request->via === 'push') {
            if (!gs('pn')) {
                $notify[] = ['error', 'Las notificaciones push están desactivadas en configuración general.'];
                return back()->withNotify($notify);
            }

            $fbConfig = gs('firebase_config');
            if (!$fbConfig || empty($fbConfig->projectId)) {
                $notify[] = ['error', 'Firebase no está configurado. Ve a Configuración General > Firebase.'];
                return back()->withNotify($notify);
            }

            $pushConfigPath = getFilePath('pushConfig') . '/push_config.json';
            if (!file_exists($pushConfigPath)) {
                $notify[] = ['error', 'Falta el archivo push_config.json en ' . $pushConfigPath . '. Súbelo en Configuración General > Firebase.'];
                return back()->withNotify($notify);
            }

            $driver = Driver::findOrFail($id);
            $tokens = $driver->deviceTokens()->pluck('token')->toArray();
            if (empty($tokens)) {
                $notify[] = ['error', 'El driver no tiene tokens de dispositivo registrados. Asegúrate de que haya iniciado sesión en la app.'];
                return back()->withNotify($notify);
            }
        }

        $notificationSender = new UserNotificationSender(Driver::class);
        return $notificationSender->notificationToSingle($request, $id);
    }

    public function showNotificationAllForm() {
        if (! gs('en') && ! gs('sn') && ! gs('pn')) {
            $notify[] = ['warning', __('Notification options are disabled currently')];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        $notifyToDriver = Driver::notifyToDriver();
        $drivers        = Driver::active()->count();
        $pageTitle = __('Notification to Verified Drivers');
        return view('admin.driver.notification_all', compact('pageTitle', 'drivers', 'notifyToDriver'));
    }

    public function sendNotificationAll(Request $request) {
        $request->validate([
            'via'                            => 'required|in:email,sms,push',
            'message' => __('required'),
            'subject'                        => 'required_if:via,email,push',
            'start'                          => 'required|integer|gte:1',
            'batch'                          => 'required|integer|gte:1',
            'being_sent_to'                  => 'required',
            'cooling_time'                   => 'required|integer|gte:1',
            'number_of_top_deposited_driver' => 'required_if:being_sent_to,topDepositedUsers|integer|gte:0',
            'number_of_days'                 => 'required_if:being_sent_to,notLoginUsers|integer|gte:0',
            'image'                          => ["nullable", 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ], [
            'number_of_days.required_if'                 => "Number of days field is required",
            'number_of_top_deposited_driver.required_if' => "Number of top deposited user field is required",
        ]);

        if (! gs('en') && ! gs('sn') && ! gs('pn')) {
            $notify[] = ['warning', __('Notification options are disabled currently')];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        return (new UserNotificationSender(Driver::class))->notificationToAll($request);
    }

    public function list() {
        $query = Driver::active();

        if (request()->search) {
            $query->where(function ($q) {
                $q->where('email', 'like', '%' . request()->search . '%')->orWhere('username', 'like', '%' . request()->search . '%');
            });
        }

        $drivers = $query->orderBy('id', 'desc')->paginate(getPaginate());
        return response()->json([
            'success' => true,
            'drivers' => $drivers,
            'more'    => $drivers->hasMorePages(),
        ]);
    }

    public function notificationLog($id) {
        $driver    = Driver::findOrFail($id);
        $pageTitle = 'Notifications Sent to ' . $driver->username;
        $logs      = NotificationLog::where('driver_id', $id)->with('driver')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.driver_reports.notification_history', compact('pageTitle', 'logs', 'driver', 'widget'));
    }

    public function deleteAccount($id) {
        $driver = Driver::findOrFail($id);

        if ($driver->is_deleted == Status::NO) {
            $driver->is_deleted = Status::YES;
            $notify[] = ['success', __('Driver account deleted successfully')];
        } else {
            $driver->is_deleted = Status::NO;
            $notify[] = ['success', __('Driver account recovered successfully')];
        }

        $driver->save();
        return back()->withNotify($notify);
    }

    private function callExportData($baseQuery) {
        return exportData($baseQuery, request()->export, "driver", "A4 landscape");
    }

    private function ensureWallet($holder)
    {
        if (!$holder->wallet) {
            $wallet = Wallet::create(['holder_type' => get_class($holder), 'holder_id' => $holder->id]);
            $holder->update(['wallet_id' => $wallet->id]);
            $holder->setRelation('wallet', $wallet);
        } elseif (!$holder->wallet_id) {
            $holder->update(['wallet_id' => $holder->wallet->id]);
        }
        return $holder->wallet;
    }

    public function countBySegment($methodName) {

        return Driver::active()->$methodName()->count();
    }

    public function driversRanking() {
        $pageTitle = __('Drivers Ranking');

        $baseQuery = Driver::withSum(['ride as total_ride_amount' => function ($query) {
            $query->completed();
        }], 'amount')
            ->searchable(['email', 'username', 'firstname', 'lastname'])
            ->dateFilter()
            ->filter(['status'])
            ->orderBy('total_ride_amount', 'desc');

        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $drivers = $baseQuery->paginate(getPaginate());

        return view('admin.driver.ranking', compact('pageTitle', 'drivers'));
    }
}
