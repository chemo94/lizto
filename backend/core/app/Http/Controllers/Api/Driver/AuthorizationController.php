<?php

namespace App\Http\Controllers\Api\Driver;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebasePhoneAuthService;
use Throwable;

class AuthorizationController extends Controller
{
    protected function checkCodeValidity($driver, $addMin = 2)
    {
        if (!$driver->ver_code_send_at) {
            return false;
        }
        if ($driver->ver_code_send_at->addMinutes($addMin) < Carbon::now()) {
            return false;
        }
        return true;
    }

    public function authorization()
    {
        $driver = auth()->user();

        Log::channel('driver_otp')->info('=== DRIVER OTP: authorization() called ===', [
            'driver_id'   => $driver->id,
            'email'       => $driver->email,
            'mobile'      => $driver->mobile,
            'ev'          => $driver->ev,
            'sv'          => $driver->sv,
            'tv'          => $driver->tv,
            'status'      => $driver->status,
            'ver_code'    => $driver->ver_code,
            'last_sent'   => $driver->ver_code_send_at?->toDateTimeString(),
        ]);

        if (!$driver->status) {
            $type = 'ban';
            Log::channel('driver_otp')->warning("Driver {$driver->id} is BAN", ['status' => $driver->status]);
        } elseif (!$driver->ev) {
            $type           = 'email';
            $notifyTemplate = 'EVER_CODE';
        } elseif (!$driver->sv) {
            $type           = 'sms';
            $notifyTemplate = 'SVER_CODE';
        } elseif (!$driver->tv) {
            $type = '2fa';
        } else {
            $notify[] = 'You are already verified';
            Log::channel('driver_otp')->info("Driver {$driver->id} already fully verified");
            return apiResponse("already_verified", "error", $notify);
        }

        Log::channel('driver_otp')->info("Driver {$driver->id} resolved type: {$type}", [
            'template' => $notifyTemplate ?? null,
        ]);

        $codeValid = $this->checkCodeValidity($driver);

        if (!$codeValid && ($type != '2fa') && ($type != 'ban')) {
            $code = verificationCode(6);
            $driver->ver_code         = $code;
            $driver->ver_code_send_at = Carbon::now();
            $driver->save();

            if ($type === 'sms') {
                $waResult = \App\Services\WhatsAppOtpService::sendOtp(
                    $driver->mobile ?? '',
                    $driver->dial_code ?? '51',
                    'driver'
                );
                Log::channel('driver_otp')->info("Driver {$driver->id} WhatsApp OTP dispatch: " . json_encode($waResult));
            } else {
                $sendVia = [$type, 'push'];

                Log::channel('driver_otp')->info("Driver {$driver->id} sending OTP", [
                    'code'      => $code,
                    'send_via'  => $sendVia,
                    'template'  => $notifyTemplate,
                    'email'     => $driver->email,
                    'mobile'    => $driver->mobile,
                ]);

                try {
                    notify($driver, $notifyTemplate, [
                        'code' => $code
                    ], $sendVia);
                    Log::channel('driver_otp')->info("Driver {$driver->id} notify() completed OK");
                } catch (\Exception $e) {
                    Log::channel('driver_otp')->error("Driver {$driver->id} notify() FAILED", [
                        'error' => $e->getMessage(),
                        'file'  => $e->getFile(),
                        'line'  => $e->getLine(),
                    ]);
                }
            }
        } else {
            if ($codeValid) {
                Log::channel('driver_otp')->info("Driver {$driver->id} code still valid, NOT resending", [
                    'last_sent' => $driver->ver_code_send_at?->toDateTimeString(),
                ]);
            }
        }

        $notify[] = 'Verify your account';
        return apiResponse("code_sent", "success", $notify, $type === 'sms' ? [
            'phone_number'          => $driver->mobile,
            'dial_code'             => $driver->dial_code ?? '51',
            'verification_provider' => 'whatsapp',
        ] : null);
    }


    public function sendVerifyCode($type)
    {
        $driver = auth()->user();

        if ($type === 'mobile' || $type === 'sms') {
            if ($this->checkCodeValidity($driver)) {
                $targetTime = $driver->ver_code_send_at->addMinutes(2)->timestamp;
                $delay      = $targetTime - time();
                $notify[] = 'Por favor espera ' . $delay . ' segundos antes de solicitar otro código';
                return apiResponse("try_after", "error", $notify);
            }

            $driver->ver_code_send_at = Carbon::now();
            $driver->save();

            $res = \App\Services\WhatsAppOtpService::sendOtp(
                $driver->mobile ?? '',
                $driver->dial_code ?? '51',
                'driver'
            );

            if (!$res['success']) {
                return apiResponse('send_failed', 'error', [$res['message']]);
            }

            return apiResponse('code_sent', 'success', ['Código de verificación enviado a tu WhatsApp.']);
        }

        Log::channel('driver_otp')->info('=== DRIVER OTP: sendVerifyCode() called ===', [
            'driver_id'  => $driver->id,
            'type'       => $type,
            'email'      => $driver->email,
            'mobile'     => $driver->mobile,
            'ev'         => $driver->ev,
            'sv'         => $driver->sv,
        ]);

        if ($this->checkCodeValidity($driver)) {
            $targetTime = $driver->ver_code_send_at->addMinutes(2)->timestamp;
            $delay      = $targetTime - time();

            Log::channel('driver_otp')->info("Driver {$driver->id} rate limited, try after {$delay}s");
            $notify[] = 'Please try after ' . $delay . ' seconds';
            return apiResponse("try_after", "error", $notify);
        }

        $code = verificationCode(6);
        $driver->ver_code         = $code;
        $driver->ver_code_send_at = Carbon::now();
        $driver->save();

        if ($type == 'email') {
            $type           = 'email';
            $notifyTemplate = 'EVER_CODE';
        } else {
            $type           = 'sms';
            $notifyTemplate = 'SVER_CODE';
        }

        $sendVia = [$type, 'push'];

        Log::channel('driver_otp')->info("Driver {$driver->id} sending OTP via resend", [
            'code'      => $code,
            'send_via'  => $sendVia,
            'template'  => $notifyTemplate,
        ]);

        try {
            notify($driver, $notifyTemplate, [
                'code' => $code
            ], $sendVia);
            Log::channel('driver_otp')->info("Driver {$driver->id} notify() completed OK");
        } catch (\Exception $e) {
            Log::channel('driver_otp')->error("Driver {$driver->id} notify() FAILED", [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
        }

        $notify[] = 'Verification code sent successfully';
        return apiResponse("code_sent", "success", $notify);
    }

    public function emailVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }
        $driver = auth()->user();

        if ($driver->ver_code == $request->code) {
            $driver->ev               = Status::VERIFIED;
            $driver->ver_code         = null;
            $driver->ver_code_send_at = null;
            $driver->save();

            $notify[]     = 'Email verified successfully';
            return apiResponse("email_verified", "success", $notify, [
                'driver' => $driver
            ]);
        }

        $notify[] = 'Verification code doesn\'t match';
        return apiResponse("code_not_match", "error", $notify);
    }

    public function mobileVerification(Request $request)
    {
        $driver = auth()->user();

        // 1. WhatsApp OTP verification (code / otp_code)
        $code = trim((string) ($request->code ?? $request->otp_code ?? ''));
        if (!empty($code)) {
            $result = \App\Services\WhatsAppOtpService::verifyOtp(
                $driver->mobile ?? '',
                $code,
                $driver->dial_code ?? '51',
                'driver'
            );

            $isDirectMatch = ($driver->ver_code && trim((string)$driver->ver_code) === $code);

            if ($result['success'] || $isDirectMatch) {
                $driver->sv               = Status::VERIFIED;
                $driver->ver_code         = null;
                $driver->ver_code_send_at = null;
                $driver->save();

                $notify[] = 'Teléfono verificado con éxito vía WhatsApp';
                return apiResponse("mobile_verified", "success", $notify, [
                    'driver' => $driver,
                    'user'   => $driver,
                ]);
            }

            return apiResponse('code_not_match', 'error', [$result['message'] ?? 'Código de verificación incorrecto']);
        }

        // 2. Firebase ID token fallback
        if ($request->filled('firebase_id_token')) {
            try {
                $matches = app(FirebasePhoneAuthService::class)->tokenMatchesAccount($request->firebase_id_token, $driver);
            } catch (Throwable $exception) {
                report($exception);
                return apiResponse('firebase_token_invalid', 'error', ['No se pudo validar el teléfono con Firebase']);
            }

            if ($matches) {
                $driver->sv               = Status::VERIFIED;
                $driver->ver_code         = null;
                $driver->ver_code_send_at = null;
                $driver->save();

                $notify[]     = 'Mobile verified successfully';
                return apiResponse("mobile_verified", "success", $notify, [
                    'driver' => $driver,
                    'user'   => $driver,
                ]);
            }
            return apiResponse('phone_not_match', 'error', ['El teléfono verificado no corresponde a esta cuenta']);
        }

        return apiResponse("validation_error", "error", ['El código de verificación es obligatorio']);
    }

    public function g2faVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", "error", $validator->errors()->all());
        }
        $driver     = auth()->user();
        $response = verifyG2fa($driver, $request->code);

        if ($response) {
            $notify[] = 'Verification successful';
            return apiResponse("twofa_verified", "success", $notify, [
                'driver' => $driver
            ]);
        } else {
            $notify[] = 'Wrong verification code';
            return apiResponse("wrong_code", "error", $notify);
        }
    }
}
