<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'waapi' => [
        'base_url'    => env('WAAPI_BASE_URL', 'http://localhost/waapi'),
        'api_key'     => env('WAAPI_API_KEY', 'wa_secret_key_change_me_12345'),
        'admin_phone' => env('WAAPI_ADMIN_PHONE', '997428341'),
        'enabled'     => env('WAAPI_ENABLED', true),
        'platform'    => env('WAAPI_PLATFORM', 'lizto'),
    ],

];
