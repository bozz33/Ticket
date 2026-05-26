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

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'proxy' => env('PAYSTACK_HTTP_PROXY'),
        'disable_env_proxy' => env('PAYSTACK_DISABLE_ENV_PROXY', true),
        'connect_timeout' => env('PAYSTACK_CONNECT_TIMEOUT', 10),
        'timeout' => env('PAYSTACK_TIMEOUT', 20),
        'cainfo' => env('PAYSTACK_CAINFO'),
        'verify_tls' => env('PAYSTACK_VERIFY_TLS', true),
    ],

    'microservices' => [
        'enabled' => env('MICROSERVICES_ENABLED', false),
        'timeout' => env('MICROSERVICES_TIMEOUT', 8),
        'connect_timeout' => env('MICROSERVICES_CONNECT_TIMEOUT', 3),
        'retry_times' => env('MICROSERVICES_RETRY_TIMES', 2),
        'retry_sleep_ms' => env('MICROSERVICES_RETRY_SLEEP_MS', 100),
        'internal_token' => env('MICROSERVICES_INTERNAL_TOKEN'),
        'internal_token_header' => env('MICROSERVICES_INTERNAL_TOKEN_HEADER', 'X-Internal-Service-Token'),

        'api_gateway' => [
            'url' => env('API_GATEWAY_URL', 'http://127.0.0.1:4000/v1'),
            'enabled' => env('API_GATEWAY_ENABLED', env('MICROSERVICES_ENABLED', false)),
        ],

        'notifications' => [
            'url' => env('NOTIFICATIONS_SERVICE_URL', 'http://127.0.0.1:4010/v1'),
            'enabled' => env('NOTIFICATIONS_SERVICE_ENABLED', env('MICROSERVICES_ENABLED', false)),
        ],

        'media' => [
            'url' => env('MEDIA_SERVICE_URL', 'http://127.0.0.1:4020/v1'),
            'enabled' => env('MEDIA_SERVICE_ENABLED', env('MICROSERVICES_ENABLED', false)),
        ],

        'catalog_search' => [
            'url' => env('CATALOG_SEARCH_SERVICE_URL', 'http://127.0.0.1:4030/v1'),
            'enabled' => env('CATALOG_SEARCH_SERVICE_ENABLED', env('MICROSERVICES_ENABLED', false)),
        ],

        'analytics' => [
            'url' => env('ANALYTICS_SERVICE_URL', 'http://127.0.0.1:4040/v1'),
            'enabled' => env('ANALYTICS_SERVICE_ENABLED', env('MICROSERVICES_ENABLED', false)),
        ],

        'access_checkin' => [
            'url' => env('ACCESS_CHECKIN_SERVICE_URL', 'http://127.0.0.1:4050/v1'),
            'enabled' => env('ACCESS_CHECKIN_SERVICE_ENABLED', env('MICROSERVICES_ENABLED', false)),
        ],
    ],

];
