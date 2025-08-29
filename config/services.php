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

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    
    'google' => [
        'sheet_id' => env('GOOGLE_SHEET_ID'),
        'sheet_csv_url' => env('GOOGLE_SHEET_CSV_URL'),
        'webapp_url' => env('GOOGLE_SHEET_WEBAPP_URL', 'https://script.google.com/macros/s/AKfycbw8llVpEMT-keUyMiUz1Kav98SBxGMXa1q7aZp6gsCP4bIc_lNlv2-m-WBmrcIEZsI4/exec'),
        'secret'     => env('GOOGLE_SHEET_WEBAPP_SECRET', 'Tapnborrow2025!'),
    ],
];
