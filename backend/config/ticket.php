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
                base_path('packages/cms/database/migrations/central'),
                base_path('packages/finance-accounting/database/migrations/central'),
                base_path('packages/identity-access/database/migrations/central'),
                base_path('packages/localization/database/migrations/central'),
                base_path('packages/media-documents/database/migrations/central'),
                base_path('packages/payments/database/migrations/central'),
                base_path('packages/public-catalog/database/migrations/central'),
                base_path('packages/notifications/database/migrations/central'),
                base_path('packages/reference-data/database/migrations/central'),
                base_path('packages/seo/database/migrations/central'),
                base_path('packages/support-observability/database/migrations/central'),
                base_path('packages/tenancy/database/migrations/central'),
            ],
        ],
        'tenant' => [
            'directories' => [
                database_path('migrations/tenant'),
                base_path('packages/access-control/database/migrations/tenant'),
                base_path('packages/content-calls-for-projects/database/migrations/tenant'),
                base_path('packages/content-crowdfunding/database/migrations/tenant'),
                base_path('packages/content-events/database/migrations/tenant'),
                base_path('packages/content-stands/database/migrations/tenant'),
                base_path('packages/content-training/database/migrations/tenant'),
                base_path('packages/engagement/database/migrations/tenant'),
                base_path('packages/form-builder/database/migrations/tenant'),
                base_path('packages/tenancy/database/migrations/tenant'),
                base_path('packages/ticketing/database/migrations/tenant'),
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
    'ticket_reservations' => [
        'ttl_minutes' => (int) env('TICKET_RESERVATION_TTL_MINUTES', 20),
    ],
    'token_expirations' => [
        'tenant_api_minutes' => (int) env('TENANT_API_TOKEN_TTL_MINUTES', 60 * 24 * 30),
        'platform_api_minutes' => (int) env('PLATFORM_API_TOKEN_TTL_MINUTES', 60 * 24),
    ],
    'email_validation' => [
        'dns_check' => filter_var(env('VERIFY_EMAIL_DNS', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOL),
    ],
    'rate_limits' => [
        'platform_auth_per_minute' => (int) env('RATE_LIMIT_PLATFORM_AUTH_PER_MINUTE', 5),
        'tenant_auth_per_minute' => (int) env('RATE_LIMIT_TENANT_AUTH_PER_MINUTE', 5),
        'tenant_engagement_per_minute' => (int) env('RATE_LIMIT_TENANT_ENGAGEMENT_PER_MINUTE', 120),
        'public_onboarding_per_minute' => (int) env('RATE_LIMIT_PUBLIC_ONBOARDING_PER_MINUTE', 3),
        'public_call_for_project_apply_per_minute' => (int) env('RATE_LIMIT_PUBLIC_CALL_FOR_PROJECT_APPLY_PER_MINUTE', 5),
        'public_payment_initialize_per_minute' => (int) env('RATE_LIMIT_PUBLIC_PAYMENT_INITIALIZE_PER_MINUTE', 10),
        'public_payment_verify_per_minute' => (int) env('RATE_LIMIT_PUBLIC_PAYMENT_VERIFY_PER_MINUTE', 30),
        'public_pass_lookup_per_minute' => (int) env('RATE_LIMIT_PUBLIC_PASS_LOOKUP_PER_MINUTE', 30),
        'payment_webhooks_per_minute' => (int) env('RATE_LIMIT_PAYMENT_WEBHOOKS_PER_MINUTE', 120),
        'tenant_checkin_per_minute' => (int) env('RATE_LIMIT_TENANT_CHECKIN_PER_MINUTE', 60),
    ],
    'logging' => [
        'payments_channel' => env('PAYMENTS_LOG_CHANNEL', 'payments'),
        'security_channel' => env('SECURITY_LOG_CHANNEL', 'security'),
    ],
    'panels' => [
        'platform' => 'platform',
        'tenant' => 'tenant',
    ],
];
