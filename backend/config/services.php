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

    /*
    |--------------------------------------------------------------------------
    | Machine Learning Service (Render-hosted)
    |--------------------------------------------------------------------------
    |
    | Base URL of the FastAPI/Flask ML service running on Render. Used by
    | RiceVarietyController to fetch the feature legend (trained varieties)
    | so the admin UI doesn't wrongly flag every variety as "fallback".
    |
    */

    'ml' => [
        'url'      => env('ML_SERVICE_URL', ''),
        'endpoint' => env('ML_FEATURES_ENDPOINT', '/features'),
        'timeout'  => env('ML_SERVICE_TIMEOUT', 10),
    ],

];