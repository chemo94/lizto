<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsAppOtpController extends Controller
{
    /**
     * Send OTP code via WhatsApp to phone number.
     *
     * POST /api/auth/whatsapp/send-otp
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile'    => 'required|string',
            'dial_code' => 'nullable|string',
            'user_type' => 'nullable|in:user,driver,seller',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $mobile   = trim((string) $request->mobile);
        $dialCode = trim((string) ($request->dial_code ?? '51'));
        $userType = (string) ($request->user_type ?? 'user');

        $result = WhatsAppOtpService::sendOtp($mobile, $dialCode, $userType);

        if (!$result['success']) {
            return apiResponse('send_failed', 'error', [$result['message']]);
        }

        return apiResponse('otp_sent', 'success', [$result['message']], $result['data']);
    }

    /**
     * Verify OTP code sent via WhatsApp.
     *
     * POST /api/auth/whatsapp/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile'       => 'required|string',
            'otp_code'     => 'required|string|size:6',
            'dial_code'    => 'nullable|string',
            'user_type'    => 'nullable|in:user,driver,seller',
            'device_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $mobile      = trim((string) $request->mobile);
        $otpCode     = trim((string) $request->otp_code);
        $dialCode    = trim((string) ($request->dial_code ?? '51'));
        $userType    = (string) ($request->user_type ?? 'user');
        $deviceToken = $request->device_token ?? $request->fcm_token;

        $result = WhatsAppOtpService::verifyOtp($mobile, $otpCode, $dialCode, $userType, $deviceToken);

        if (!$result['success']) {
            return apiResponse('verify_failed', 'error', [$result['message']]);
        }

        return apiResponse('verify_success', 'success', [$result['message']], $result['data']);
    }
}
