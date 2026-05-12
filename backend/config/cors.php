<?php

return [
    'paths' => [
        'api/*',
        'up',
    ],

    'allowed_methods' => explode(',', (string) env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),

    'allowed_origins' => array_values(array_filter(array_map(
        static fn (string $origin): string => trim($origin),
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://127.0.0.1:3000,http://localhost:3000'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => explode(',', (string) env('CORS_ALLOWED_HEADERS', 'Accept,Authorization,Content-Type,Origin,X-Requested-With')),

    'exposed_headers' => explode(',', (string) env('CORS_EXPOSED_HEADERS', 'Cache-Control,Content-Language,Content-Type,Expires,Last-Modified,Pragma,Retry-After,X-RateLimit-Limit,X-RateLimit-Remaining')),

    'max_age' => (int) env('CORS_MAX_AGE', 3600),

    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', false), FILTER_VALIDATE_BOOL),
];
