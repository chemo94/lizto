<?php

namespace App\Notify;

use App\Notify\NotifyProcess;
use App\Notify\SmsGateway;
use App\Notify\Notifiable;
use Illuminate\Support\Facades\Log;


class Sms extends NotifyProcess implements Notifiable
{

    /**
     * Mobile number of receiver
     *
     * @var string
     */
    public $mobile;

    /**
     * Assign value to properties
     *
     * @return void
     */
    public function __construct()
    {

        $this->statusField = 'sms_status';
        $this->body = 'sms_body';
        $this->globalTemplate = 'sms_template';
        $this->notifyConfig = 'sms_config';
    }


    /**
     * Send notification
     *
     * @return void|bool
     */
    public function send()
    {

        if (!gs('sn')) {
            Log::channel('driver_otp')->warning('[Sms] Notifications disabled globally (sn=false)', ['template' => $this->templateName]);
            return false;
        }
        $message = $this->getMessage();
        if ($message) {
            try {
                $gateway = gs('sms_config')->name;
                if ($this->mobile) {
                    Log::channel('driver_otp')->info('[Sms] Sending', [
                        'to'      => $this->mobile,
                        'gateway' => $gateway,
                        'template'=> $this->templateName,
                    ]);
                    $sendSms = new SmsGateway();
                    $sendSms->to = $this->mobile;
                    $sendSms->from = $this->getSmsFrom();
                    $sendSms->message = strip_tags($message);
                    $sendSms->config = gs('sms_config');
                    $result = $sendSms->$gateway();
                    Log::channel('driver_otp')->info('[Sms] Sent OK', [
                        'to'        => $this->mobile,
                        'response'  => is_string($result) ? substr($result, 0, 500) : null,
                    ]);
                    $this->createLog('sms');
                } else {
                    Log::channel('driver_otp')->warning('[Sms] No mobile number', ['template' => $this->templateName]);
                }
            } catch (\Exception $e) {
                Log::channel('driver_otp')->error('[Sms] FAILED', [
                    'to'     => $this->mobile,
                    'error'  => $e->getMessage(),
                    'file'   => $e->getFile(),
                    'line'   => $e->getLine(),
                ]);
                $this->createErrorLog('SMS Error: ' . $e->getMessage());
                session()->flash('sms_error', 'API Error: ' . $e->getMessage());
            }
        } else {
            Log::channel('driver_otp')->warning('[Sms] No message from getMessage()', ['template' => $this->templateName]);
        }
    }

    /**
     * Configure some properties
     *
     * @return void
     */
    public function prevConfiguration()
    {
        if ($this->user) {
            $this->mobile = $this->user->mobileNumber;
            $this->receiverName = $this->user->fullname;
            Log::channel('driver_otp')->info('[Sms::prevConfiguration]', [
                'user_id' => $this->user->id ?? null,
                'mobile'  => $this->mobile,
            ]);
        } else {
            Log::channel('driver_otp')->warning('[Sms::prevConfiguration] user is null');
        }
        $this->toAddress = $this->mobile;
    }

    private function getSmsFrom()
    {
        $this->sentFrom = $this->replaceTemplateShortCode($this->template->sms_sent_from ?? gs('sms_from'));
        return $this->sentFrom;
    }
}
