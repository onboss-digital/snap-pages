<?php

return [

    'default_payment_gateway' => env('DEFAULT_PAYMENT_GATEWAY', 'tribopay'), // Default to tribopay

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

    'tribopay' => [
        'api_token' => env('TRIBO_PAY_API_TOKEN'),
        'api_url' => env('TRIBO_PAY_API_URL', 'https://api.tribopay.com.br'), // Default API URL
    ],

    'for4payment' => [
        'api_key' => env('FOR4PAYMENT_API_KEY'),
        'api_url' => env('FOR4PAYMENT_API_URL', 'https://api.for4payment.com'), // Example URL
    ],

    'stripe' => [
        'api_secret_key' => env('STRIPE_API_SECRET_KEY'),
        'api_url' => env('STRIPE_API_URL', 'https://api.stripe.com'),
    ],

];
