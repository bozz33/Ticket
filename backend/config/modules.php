<?php

use Ticket\AccessControl\AccessControlServiceProvider;
use Ticket\Cms\CmsServiceProvider;
use Ticket\ContentCallsForProjects\ContentCallsForProjectsServiceProvider;
use Ticket\ContentCrowdfunding\ContentCrowdfundingServiceProvider;
use Ticket\ContentEvents\ContentEventsServiceProvider;
use Ticket\ContentStands\ContentStandsServiceProvider;
use Ticket\ContentTraining\ContentTrainingServiceProvider;
use Ticket\Engagement\EngagementServiceProvider;
use Ticket\FinanceAccounting\FinanceAccountingServiceProvider;
use Ticket\FormBuilder\FormBuilderServiceProvider;
use Ticket\IdentityAccess\IdentityAccessServiceProvider;
use Ticket\Localization\LocalizationServiceProvider;
use Ticket\MediaDocuments\MediaDocumentsServiceProvider;
use Ticket\Notifications\NotificationsServiceProvider;
use Ticket\Payments\PaymentsServiceProvider;
use Ticket\PublicCatalog\PublicCatalogServiceProvider;
use Ticket\ReferenceData\ReferenceDataServiceProvider;
use Ticket\Seo\SeoServiceProvider;
use Ticket\SupportObservability\SupportObservabilityServiceProvider;
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
        IdentityAccessServiceProvider::class,
        ReferenceDataServiceProvider::class,
        MediaDocumentsServiceProvider::class,
        CmsServiceProvider::class,
        SeoServiceProvider::class,
        LocalizationServiceProvider::class,
        EngagementServiceProvider::class,
        AccessControlServiceProvider::class,
        FinanceAccountingServiceProvider::class,
        SupportObservabilityServiceProvider::class,
        FormBuilderServiceProvider::class,
        ContentEventsServiceProvider::class,
        ContentTrainingServiceProvider::class,
        ContentStandsServiceProvider::class,
        ContentCallsForProjectsServiceProvider::class,
        ContentCrowdfundingServiceProvider::class,
        PaymentsServiceProvider::class,
        TenancyModuleServiceProvider::class,
        TicketingServiceProvider::class,
        PublicCatalogServiceProvider::class,
        NotificationsServiceProvider::class,
    ],
];
