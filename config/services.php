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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Aplikasi Appointment — sumber data registrasi/antrian (live vRegistration).
    // Antrian memanggil GET {base_url}/api/queue/registrations dgn header X-Api-Key.
    'appointment' => [
        'base_url' => rtrim((string) env('APPOINTMENT_API_URL', 'http://localhost/appointment/public'), '/'),
        'api_key'  => env('APPOINTMENT_API_KEY'),
        'timeout'  => (int) env('APPOINTMENT_API_TIMEOUT', 15),
    ],

];
