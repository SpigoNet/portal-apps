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
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

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

    'azure' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
    ],

    'treetask' => [
        'url' => env('TREETASK_API_URL', 'https://apps.spigo.net'),
        'version' => env('TREETASK_API_VERSION', 'v1'),
        'user_id' => env('TREETASK_USER_ID', '1'),
        'token' => env('TREETASK_TOKEN', ''),
        'timeout' => env('TREETASK_TIMEOUT', 30),
    ],

    'evolution' => [
        'base_uri' => env('EVOLUTION_API_URL', ''),
        'token' => env('EVOLUTION_API_TOKEN', ''),
        'instance' => env('EVOLUTION_API_INSTANCE', 'Baileys'),
        'timeout' => env('EVOLUTION_API_TIMEOUT', 15),
    ],

];
