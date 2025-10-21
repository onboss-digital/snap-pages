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
        'api_public_key' => env('STRIPE_API_PUBLIC_KEY'),
        'api_secret_key' => env('STRIPE_API_SECRET_KEY'),
        'api_url' => env('STRIPE_API_URL', 'https://api.stripe.com/v1'),
    ],

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN', 'TEST-1949014578725661-101900-7f5fd849d0a31ffbe3e5638a242ff8ab-1819882050'),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY', 'TEST-1a5fe0ab-d75a-4dd4-a04b-83f89ef7d8e3'),
    ],

    'streamit' => [
        'api_url' => env('STREAMIT_API_URL'),
    ],

];