<?php

namespace App\Http\Controllers\Gateway\MercadoPago;

use App\Constants\Status;
use App\Models\Deposit;
use App\Http\Controllers\Gateway\PaymentController;
use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Http\Request;

class ProcessController extends Controller
{
	public static function process($deposit)
    {
    	$gatewayCurrency = $deposit->gatewayCurrency();
    	$alias = $deposit->gateway->alias;
    	$gatewayAcc = json_decode($gatewayCurrency->gateway_parameter);
        $curl = curl_init();

        // En el flujo de recarga de las apps Driver/Repartidor, el guard activo es
        // "driver" (auth()->guard('driver')->login(...)), por lo que auth()->user()
        // (guard "web") devuelve null y provocaba un fatal error al acceder a
        // $user->username / $user->email -> la app recibía "Not Found".
        // Se resuelve el usuario/payer desde el propio depósito en lugar del guard.
        $payerEmail = '';
        $payerName  = 'Cliente';
        if ($deposit->driver_id) {
            $driver = \App\Models\Driver::find($deposit->driver_id);
            if ($driver) {
                $payerEmail = $driver->email;
                $payerName  = $driver->username ?? $driver->fullname ?? 'Driver';
            }
        } elseif ($deposit->user_id) {
            $user = \App\Models\User::find($deposit->user_id);
            if ($user) {
                $payerEmail = $user->email;
                $payerName  = $user->username;
            }
        }

        $preferenceData = [
            'items' => [
                [
                    'id' => $deposit->trx,
                    'title' => 'Deposit',
                    'description' => 'Deposit from '.$payerName,
                    'quantity' => 1,
                    'currency_id' => $gatewayCurrency->currency,
                    'unit_price' => $deposit->final_amount
                ]
            ],
            'payer' => [
                'email' => $payerEmail,
            ],
            'back_urls' => [
                'success' => route('home').$deposit->success_url,
                'pending' => '',
                'failure' => route('home').$deposit->failed_url,
            ],
            'notification_url' =>  route('ipn.'.$alias),
            'auto_return' =>  'approved',
        ];
        $httpHeader = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $gatewayAcc->access_token
        ];
        $preferenceData['items'][0]['unit_price'] = (float) getAmount($deposit->final_amount, 2);

        $url = "https://api.mercadopago.com/checkout/preferences";
        $opts = [
            CURLOPT_URL             => $url,
            CURLOPT_CUSTOMREQUEST   => "POST",
            CURLOPT_POSTFIELDS      => json_encode($preferenceData),
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_HTTPHEADER      => $httpHeader,
        ];
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        $result = json_decode($response,true);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $send['error'] = true;
            $send['message'] = 'cURL Error: ' . $err;
        } elseif (@$result['init_point']) {
            $send['redirect'] = true;
            $send['redirect_url'] = $result['init_point'];
        } else {
            $send['error'] = true;
            $send['message'] = 'MercadoPago API Error: ' . (@$result['message'] ?: 'Unknown error');
        }

        $send['view'] = '';
        return json_encode($send);
    }

    public function ipn(Request $request)
    {
        $paymentId = $request->data['id'] ?? $request->id;
        if (!$paymentId) return response('Invalid Request', 400);

        $gateway = Gateway::where('alias','MercadoPago')->first();
        $param = json_decode($gateway->gateway_parameters);

        $paymentUrl = "https://api.mercadopago.com/v1/payments/" . $paymentId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paymentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ($param->access_token->value ?? ($param->access_token ?? ''))
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $paymentData = curl_exec($ch);
        curl_close($ch);

        $payment = json_decode($paymentData, true);
        $trx = $payment['additional_info']['items'][0]['id'] ?? null;
        $deposit = Deposit::where('trx', $trx)->where('status',Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->first();

        if ($payment['status'] == 'approved' && $deposit) {
            PaymentController::userDataUpdate($deposit);
            return response('OK', 200);
        }

        return response('Payment not approved', 400);
    }
}
