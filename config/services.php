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
        // Récupérées sur https://app.cinetpay.com/marchand (page Intégration)
        'api_key' => env('CINETPAY_API_KEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        // Clé secrète utilisée pour vérifier le HMAC (header x-token) des notifications
        'secret_key' => env('CINETPAY_SECRET_KEY'),

        // API Checkout v2 : même URL en test et en production
        'base_url' => env('CINETPAY_API_URL', 'https://api-checkout.cinetpay.com/v2'),

        // Valeurs par défaut du checkout
        'currency' => env('CINETPAY_CURRENCY', 'XOF'),
        'country' => env('CINETPAY_COUNTRY', 'CI'),
        'channels' => env('CINETPAY_CHANNELS', 'ALL'),
        'lang' => env('CINETPAY_LANG', 'fr'),

        // Garde-fous montant (XOF : multiple de 5 exigé par CinetPay)
        'min_amount' => (int) env('CINETPAY_MIN_AMOUNT', 100),
        'max_amount' => (int) env('CINETPAY_MAX_AMOUNT', 2500000),
    ],

];
