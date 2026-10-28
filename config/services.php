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




    'billstack' => [
        'secret_key' => env('BILLSTACK_SECRET_KEY'),
        'base_url' => env('BILLSTACK_BASE_URL', 'https://api.billstack.co/v2'),
    ],

    'korapay' => [
        'public' => env('KORAPAY_PUBLIC_KEY'),
        'secret' => env('KORAPAY_SECRET_KEY'),
    ],

    'gongozconcept' => [
        'api_key' => env('GONGOZ_API_KEY'),
        'api_url' => env('GONGOZ_API_URL', 'https://www.gongozconcept.com/api'),
    ],

    'fadded' => [
        'key' => env('FADDED_API_KEY', env('FADDED_KEY', env('LOGS_API_KEY'))),
        'url' => env('FADDED_API_URL', env('FADDED_URL', env('LOGS_API_URL', 'https://fadded.net/api/v1/reseller'))),
    ],

    'prestmit' => [
        'mode' => env('PRESTMIT_MODE', 'sandbox'),
        'api_key' => env('PRESTMIT_API_KEY'),
        'base_url' => env('PRESTMIT_BASE_URL'),
        'webhook_secret' => env('PRESTMIT_WEBHOOK_SECRET'),
    ],

];
