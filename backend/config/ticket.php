<?php

return [
    'central_connection' => env('CENTRAL_DB_CONNECTION', 'central'),
    'tenant_connection' => env('TENANT_DB_CONNECTION', 'tenant'),
    'tenant_domain_suffix' => env('TENANT_DOMAIN_SUFFIX'),
    'tenant_database_defaults' => [
        'prefix' => env('TENANT_DB_PREFIX', 'ticket_'),
        'host' => env('TENANT_DB_HOST', '127.0.0.1'),
        'port' => (int) env('TENANT_DB_PORT', 5432),
        'username' => env('TENANT_DB_USERNAME', 'postgres'),
        'password' => env('TENANT_DB_PASSWORD', ''),
    ],
    'migration_paths' => [
        'central' => [
            'directories' => [
                database_path('migrations/central'),
            ],
        ],
        'tenant' => [
            'directories' => [
                database_path('migrations/tenant'),
            ],
        ],
    ],
    'tenant_statuses' => [
        'draft',
        'active',
        'suspended',
        'archived',
    ],
    'public_frontend_url' => env('PUBLIC_FRONTEND_URL', 'http://localhost:3000'),
    'panels' => [
        'platform' => 'platform',
        'tenant' => 'tenant',
    ],
];
