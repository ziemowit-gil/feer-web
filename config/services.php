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

    'unsplash' => [
        'access_key' => env('UNSPLASH_ACCESS_KEY'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        // "common" = dowolne konto MS; ustaw ID tenanta org, aby ograniczyć logowanie do domeny fundacji.
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'browsershot' => [
        // Ścieżka do binarki Chrome/Chromium. Gdy pusta — Browsershot szuka automatycznie.
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
        // Wymagane na większości serwerów Linux (współdzielone środowisko bez sandbox).
        'no_sandbox' => env('BROWSERSHOT_NO_SANDBOX', false),
    ],

    'payu' => [
        'pos_id'     => env('PAYU_POS_ID'),
        'md5_key'    => env('PAYU_MD5_KEY'),
        'second_key' => env('PAYU_SECOND_KEY'),
        'oauth_client_id'     => env('PAYU_OAUTH_CLIENT_ID'),
        'oauth_client_secret' => env('PAYU_OAUTH_CLIENT_SECRET'),
        'sandbox'    => env('PAYU_SANDBOX', true),
    ],

];
