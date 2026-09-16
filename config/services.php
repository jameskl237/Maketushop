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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/auth/google/callback'),
    ],

    'cinetpay' => [
        // Back-office CinetPay > Intégration. api_password est indispensable :
        // l'API v1 s'authentifie en OAuth, pas avec la seule clé.
        'api_key' => env('CINETPAY_API_KEY'),
        'api_password' => env('CINETPAY_API_PASSWORD'),
        'site_id' => env('CINETPAY_SITE_ID'),
        'secret_key' => env('CINETPAY_SECRET_KEY'),
        'country' => env('CINETPAY_COUNTRY', 'CI'),

        // Production : https://api.cinetpay.co | Sandbox : https://api.cinetpay.net
        // Sans /v1 : le suffixe est ajouté par le service.
        'base_url' => env('CINETPAY_API_URL', env('CINETPAY_BASE_URL', 'https://api.cinetpay.co')),
    ],

];
