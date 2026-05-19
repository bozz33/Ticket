<?php

use Ticket\Notifications\NotificationsServiceProvider;
use Ticket\Payments\PaymentsServiceProvider;
use Ticket\PublicCatalog\PublicCatalogServiceProvider;
use Ticket\Tenancy\TenancyModuleServiceProvider;
use Ticket\Ticketing\TicketingServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Internal Module Providers
    |--------------------------------------------------------------------------
    |
    | Modules are loaded through one narrow provider to keep bootstrap/providers
    | stable as bounded contexts are extracted from the main application.
    |
    */
    'providers' => [
        PaymentsServiceProvider::class,
        TenancyModuleServiceProvider::class,
        TicketingServiceProvider::class,
        PublicCatalogServiceProvider::class,
        NotificationsServiceProvider::class,
    ],
];
