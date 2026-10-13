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
    | OpenWeather (current weather for Santiago City)
    |--------------------------------------------------------------------------
    |
    | Used by App\Services\WeatherService to populate planting-time and
    | harvest-time weather snapshots on farm records, and to display the
    | current conditions widget on the dashboard.
    |
    */

    'openweather' => [
        'key'     => env('OPENWEATHER_API_KEY'),
        'city'    => env('OPENWEATHER_CITY', 'Santiago'),
        'country' => env('OPENWEATHER_COUNTRY', 'PH'),
        'units'   => env('OPENWEATHER_UNITS', 'metric'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Machine Learning Service (Render-hosted or local Flask)
    |--------------------------------------------------------------------------
    |
    | Base URL of the ML service. Used by PredictionService to generate
    | yield predictions, and by RiceVarietyController to fetch the
    | feature legend (trained varieties).
    |
    */

    'ml' => [
        'url'      => env('ML_SERVICE_URL', ''),
        'endpoint' => env('ML_FEATURES_ENDPOINT', '/features'),
        'timeout'  => env('ML_SERVICE_TIMEOUT', 60),
    ],

];