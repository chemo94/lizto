<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppOtpService;
use Throwable;

class AuthorizationController extends Controller
{
    protected function checkCodeValidity($user, $addMin = 2)
    {
        if (!$user->ver_code_send_at) {
            return false;
        }
        if ($user->ver_code_send_at->addMinutes($addMin) < Carbon::now()) {
            return false;
        }
        return true;
    }

    public function authorization()
    {
        $user = auth()->user();
        if (!$user->status) {
            $type = 'ban';
        } elseif (!$user->ev) {
            $type           = 'email';
            $notifyTemplate = 'EVER_CODE';
        } elseif (!$user->sv) {
            $type           = 'sms';
            $notifyTemplate = 'SVER_CODE';
        } elseif (!$user->tv) {
            $type = '2fa';
        } else {
            $notify[] = 'You are already verified';
            return apiResponse("already_verified", "error", $notify);
        }

        $codeValid = $this->checkCodeValidity($user);

        if (!$codeValid && ($type != '2fa') && ($type != 'ban')) {
            $code = verificationCode(6);
            $user->ver_code         = $code;
            $user->ver_code_send_at = Carbon::now();
            $user->save();

            if ($type === 'sms') {
                WhatsAppOtpService::sendOtp(
                    $user->mobile ?? '',
                    $user->dial_code ?? '51',
                    'user'
                );
            } else {
                notify($user, $notifyTemplate, [
                    'code' => $code
                ], [$type, 'push']);
            }
        }

        $notify[] = 'Verify your account';
        return apiResponse("code_sent", "success", $notify, $type === 'sms' ? [
            'phone_number' => '+' . ($user->dial_code ?? '51') . ($user->mobile ?? ''),
            'verification_provider' => 'whatsapp',
        ] : null);
    }


    public function sendVerifyCode($type)
    {
        $user = auth()->user();

        if ($this->checkCodeValidity($user)) {
            $targetTime = $user->ver_code_send_at->addMinutes(2)->timestamp;
            $delay      = $targetTime - time();

            $notify[] = 'Please try after ' . $delay . ' seconds';
            return apiResponse("try_after", "error", $notify);
        }

        $code = verificationCode(6);
        $user->ver_code         = $code;
        $user->ver_code_send_at = Carbon::now();
        $user->save();

        if ($type == 'email') {
            $notifyTemplate = 'EVER_CODE';
            notify($user, $notifyTemplate, [
                'code' => $code
            ], ['email', 'push']);
        } else {
            // SMS / WhatsApp
            WhatsAppOtpService::sendOtp(
                $user->mobile ?? '',
                $user->dial_code ?? '51',
                'user'
            );
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
        $user = auth()->user();

        if ($user->ver_code == $request->code) {
            $user->ev               = Status::VERIFIED;
            $user->ver_code         = null;
            $user->ver_code_send_at = null;
            $user->save();

            $notify[]     = 'Email verified successfully';
            return apiResponse("email_verified", "success", $notify, [
                'user' => $user
            ]);
        }

        $notify[] = 'Verification code doesn\'t match';
        return apiResponse("code_not_match", "error", $notify);
    }

    public function mobileVerification(Request $request)
    {
        $inputCode = trim((string) ($request->code ?? $request->otp_code ?? $request->verification_code ?? ''));

        if (empty($inputCode) && $request->filled('firebase_id_token')) {
            $inputCode = trim((string) $request->firebase_id_token);
        }

        if (empty($inputCode)) {
            return apiResponse("validation_error", "error", ['El código de verificación es obligatorio']);
        }

        $user = auth()->user();

        $waVerify = WhatsAppOtpService::verifyOtp(
            $user->mobile ?? '',
            $inputCode,
            $user->dial_code ?? '51'
        );

        $dbMatch = (!empty($user->ver_code) && (string)$user->ver_code === $inputCode);

        if (($waVerify['valid'] ?? false) || $dbMatch) {
            $user->sv               = Status::VERIFIED;
            $user->ver_code         = null;
            $user->ver_code_send_at = null;
            $user->save();

            $notify[] = 'Teléfono verificado correctamente';
            return apiResponse("mobile_verified", "success", $notify, [
                'user' => $user
            ]);
        }

        return apiResponse('code_not_match', 'error', ['El código de verificación ingresado no es válido o ha expirado']);
    }

}

