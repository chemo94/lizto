<?php

namespace App\Notify;

use App\Notify\NotifyProcess;
use App\Notify\Notifiable;
use Illuminate\Support\Facades\Log;

class Push extends NotifyProcess implements Notifiable
{

    /**
     * Device Id of receiver
     *
     * @var array
     */
    public $deviceId;

    public $redirectUrl;

    public $pushImage;


    /**
     * Assign value to properties
     *
     * @return void
     */
    public function __construct()
    {
        $this->statusField = 'push_status';
        $this->body = 'push_body';
        $this->globalTemplate = 'push_template';
        $this->notifyConfig = 'firebase_config';
    }


    public function redirectForApp($getTemplateName)
    {

        $screens = [];

        foreach ($screens as $screen => $array) {
            if (in_array($getTemplateName, $array)) {
                return $screen;
            }
        }

        return 'HOME';
    }


    /**
     * Send notification
     *
     * @return void|bool
     */
    public function send()
    {

        if (!gs('pn')) {
            Log::channel('driver_otp')->warning('[Push] Notifications disabled globally (pn=false)', ['template' => $this->templateName]);
            return false;
        }

        $message = $this->getMessage();
        if ($message) {
            Log::channel('driver_otp')->info('[Push] Preparing to send', [
                'template'    => $this->templateName,
                'device_ids'  => $this->deviceId,
                'to_count'    => count($this->toAddress ?? []),
            ]);
            try {
                $credentialsFilePath = getFilePath('pushConfig') . '/push_config.json';
                if (!file_exists($credentialsFilePath)) {
                    Log::channel('driver_otp')->error('[Push] push_config.json NOT FOUND', ['path' => $credentialsFilePath]);
                    $this->createErrorLog('push_config.json not found: ' . $credentialsFilePath);
                    return false;
                }
                $client = new \Google_Client();
                $client->setAuthConfig($credentialsFilePath);
                $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
                $client->fetchAccessTokenWithAssertion();
                $token = $client->getAccessToken();
                $access_token = $token['access_token'];
                $headers = [
                    "Authorization: Bearer $access_token",
                    'Content-Type: application/json'
                ];


                $pushData = [
                    'icon'             => siteFavicon(),
                    'click_action'     => $this->redirectUrl,
                    'app_click_action' => $this->redirectForApp($this->templateName),
                    'template_name'    => $this->templateName,
                ];

                if ($this->shortCodes) {
                    foreach ($this->shortCodes as $key => $value) {
                        $pushData[$key] = (string) $value;
                    }
                }

                $data['data'] = $pushData;

                $otpTemplates = ['EVER_CODE', 'SVER_CODE'];

                if (in_array($this->templateName, $otpTemplates)) {
                    $data['android'] = [
                        'priority' => 'high',
                    ];
                } else {
                    if ($this->pushImage) {
                        $data['notification'] = [
                            'body' => $message,
                            'title' => $this->getTitle(),
                            'image' => asset(getFilePath('push')) . '/' . $this->pushImage,
                        ];
                    } else {
                        $data['notification'] = [
                            'body' => $message,
                            'title' => $this->getTitle(),
                        ];
                    }
                    $data['android'] = [
                        'priority'     => 'high',
                        'notification' => [
                            'channel_id' => 'high_importance_channel',
                            'sound'      => 'default',
                        ],
                    ];
                }

                $data['apns'] = [
                    'payload' => [
                        'aps' => [
                            'sound'            => 'default',
                            'content-available' => 1,
                        ],
                    ],
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                ];
                
                foreach ($this->toAddress as $toAddress) {
                    $data['token'] = $toAddress;
                    $payloadData['message'] = $data;
                    $payload = json_encode($payloadData);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/' . gs('firebase_config')->projectId . '/messages:send');
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    $result = curl_exec($ch);
                    if ($result === false) {
                        Log::channel('driver_otp')->error('[Push] curl_exec failed', ['error' => curl_error($ch), 'token' => substr($toAddress, 0, 20) . '...']);
                        $this->createErrorLog(curl_error($ch));
                    } else {
                        $response = json_decode($result);
                        if (isset($response->error)) {
                            Log::channel('driver_otp')->error('[Push] FCM error', ['error' => json_encode($response->error), 'token' => substr($toAddress, 0, 20) . '...']);
                            $this->createErrorLog(json_encode($response->error));
                        } else {
                            Log::channel('driver_otp')->info('[Push] Sent OK', ['token' => substr($toAddress, 0, 20) . '...']);
                        }
                    }
                    curl_close($ch);
                }
                $this->createLog('push');
            } catch (\Exception $e) {
                Log::channel('driver_otp')->error('[Push] Exception', [
                    'error' => $e->getMessage(),
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                ]);
                $this->createErrorLog($e->getMessage());
                session()->flash('firebase_error', $e->getMessage());
            }
        } else {
            Log::channel('driver_otp')->warning('[Push] No message from getMessage()', ['template' => $this->templateName]);
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
            $this->deviceId = $this->user->deviceTokens()->pluck('token')->toArray();
            $this->receiverName = $this->user->fullname;
            Log::channel('driver_otp')->info('[Push::prevConfiguration]', [
                'user_id'    => $this->user->id ?? null,
                'user_type'  => get_class($this->user),
                'tokens'     => count($this->deviceId),
                'token_preview' => !empty($this->deviceId) ? substr($this->deviceId[0], 0, 20) . '...' : 'NONE',
            ]);
        } else {
            Log::channel('driver_otp')->warning('[Push::prevConfiguration] user is null');
        }
        $this->toAddress = $this->deviceId;
    }

    private function getTitle()
    {
        return $this->replaceTemplateShortCode($this->template->push_title ?? gs('push_title'));
    }
}
