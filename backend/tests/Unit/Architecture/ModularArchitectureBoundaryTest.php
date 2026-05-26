<?php

namespace Tests\Unit\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;
use Ticket\AccessControl\Contracts\AccessPassCheckinWorkflow;
use Ticket\Cms\Contracts\FrontCmsContent;
use Ticket\ContentCallsForProjects\Contracts\CallForProjectContentCatalog;
use Ticket\ContentCrowdfunding\Contracts\CrowdfundingContentCatalog;
use Ticket\ContentEvents\Contracts\EventContentCatalog;
use Ticket\ContentStands\Contracts\StandContentCatalog;
use Ticket\ContentTraining\Contracts\TrainingContentCatalog;
use Ticket\Engagement\Contracts\EventEngagementWorkflow;
use Ticket\Engagement\Contracts\OrganizationAudienceWorkflow;
use Ticket\FinanceAccounting\Contracts\FinancePolicyCatalog;
use Ticket\FinanceAccounting\Contracts\PayoutPolicyCatalog;
use Ticket\FormBuilder\Contracts\FormSchemaValidator;
use Ticket\FormBuilder\Contracts\FormSubmissionWriter;
use Ticket\IdentityAccess\Contracts\PlatformTokenIssuer;
use Ticket\IdentityAccess\Contracts\TenantTokenIssuer;
use Ticket\Localization\Contracts\PublicLocalizationCatalog;
use Ticket\MediaDocuments\Contracts\QrCodeRenderer;
use Ticket\Notifications\Contracts\DomainEventPublisher;
use Ticket\Notifications\Contracts\NotificationDispatcher;
use Ticket\Notifications\Contracts\OutboxDispatcher;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Contracts\CheckoutManager;
use Ticket\Payments\Contracts\PaymentWebhookReceiver;
use Ticket\Payments\Contracts\PayoutManager;
use Ticket\Payments\Contracts\PricingEngine;
use Ticket\Payments\Contracts\RefundManager;
use Ticket\Payments\Contracts\SettlementWorkflow;
use Ticket\Payments\Contracts\TenantRefundManager;
use Ticket\PublicCatalog\Contracts\CallForProjectApplications;
use Ticket\PublicCatalog\Contracts\CallForProjectFormBuilder;
use Ticket\PublicCatalog\Contracts\FrontContent;
use Ticket\PublicCatalog\Contracts\PublicContentCatalog;
use Ticket\ReferenceData\Contracts\CityReferenceSearch;
use Ticket\ReferenceData\Contracts\CountryReferenceImport;
use Ticket\Seo\Contracts\SeoMetadataCatalog;
use Ticket\SupportObservability\Contracts\AuditLogger;
use Ticket\Tenancy\Contracts\TenantDestroyer;
use Ticket\Tenancy\Contracts\TenantLifecycleManager;
use Ticket\Tenancy\Contracts\TenantProfileManager;
use Ticket\Tenancy\Contracts\TenantProvisioner;
use Ticket\Tenancy\Contracts\TenantReferenceCatalog;
use Ticket\Tenancy\Contracts\TenantSettingsManager;
use Ticket\Tenancy\Contracts\TenantStorage;
use Ticket\Ticketing\Contracts\AccessPassCatalog;
use Ticket\Ticketing\Contracts\AccessPassCheckin;
use Ticket\Ticketing\Contracts\BuyerRefundRequests;
use Ticket\Ticketing\Contracts\DocumentCatalog;
use Ticket\Ticketing\Contracts\EventCatalog;
use Ticket\Ticketing\Contracts\EventEngagement;
use Ticket\Ticketing\Contracts\EventTicketInventory;
use Ticket\Ticketing\Contracts\EventTicketOfferBridge;
use Ticket\Ticketing\Contracts\OrderCatalog;
use Ticket\Ticketing\Contracts\OrganizationAudience;
use Ticket\Ticketing\Contracts\ReceiptCatalog;

class ModularArchitectureBoundaryTest extends TestCase
{
    public function test_all_module_contracts_are_bound_in_the_container(): void
    {
        foreach ($this->moduleContracts() as $contract) {
            $this->assertInstanceOf($contract, app($contract), sprintf('%s is not bound.', $contract));
        }
    }

    public function test_application_entrypoints_do_not_depend_on_legacy_module_services(): void
    {
        $violations = [];

        foreach ($this->consumerFiles() as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            foreach ($this->forbiddenLegacyDependencies() as $dependency) {
                if (str_contains($contents, $dependency)) {
                    $violations[] = sprintf('%s imports %s', $file->getPathname(), $dependency);
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_package_implementations_own_payments_and_public_catalog_application_services(): void
    {
        $violations = [];

        foreach ($this->phpFiles(base_path('packages')) as $file) {
            $contents = (string) file_get_contents($file->getPathname());

            foreach ([
                'App\\Services\\Payments\\',
                'App\\Services\\Public\\',
                'App\\Services\\Tenancy\\',
                'App\\Services\\FrontCmsService',
            ] as $forbiddenNamespace) {
                if (str_contains($contents, $forbiddenNamespace)) {
                    $violations[] = sprintf('%s imports %s', $file->getPathname(), $forbiddenNamespace);
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_legacy_module_service_classes_are_only_compatibility_wrappers(): void
    {
        $violations = [];

        foreach ([
            app_path('Services/Payments'),
            app_path('Services/Public'),
            app_path('Services/Tenancy'),
        ] as $directory) {
            foreach ($this->phpFiles($directory) as $file) {
                $contents = (string) file_get_contents($file->getPathname());
                $lineCount = substr_count($contents, "\n") + 1;

                if ($lineCount > 10 || ! str_contains($contents, 'extends Base')) {
                    $violations[] = $file->getPathname();
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_package_migration_and_test_directories_are_discoverable(): void
    {
        $centralDirectories = config('ticket.migration_paths.central.directories');
        $tenantDirectories = config('ticket.migration_paths.tenant.directories');

        $this->assertContains(base_path('packages/public-catalog/database/migrations/central'), $centralDirectories);
        $this->assertContains(base_path('packages/notifications/database/migrations/central'), $centralDirectories);
        $this->assertContains(base_path('packages/seo/database/migrations/central'), $centralDirectories);
        $this->assertContains(base_path('packages/finance-accounting/database/migrations/central'), $centralDirectories);
        $this->assertContains(base_path('packages/tenancy/database/migrations/tenant'), $tenantDirectories);
        $this->assertContains(base_path('packages/ticketing/database/migrations/tenant'), $tenantDirectories);
        $this->assertContains(base_path('packages/content-events/database/migrations/tenant'), $tenantDirectories);
        $this->assertContains(base_path('packages/form-builder/database/migrations/tenant'), $tenantDirectories);
        $this->assertFileExists(base_path('packages/notifications/tests/Unit/NotificationsOutboxTest.php'));
        $this->assertFileExists(base_path('packages/public-catalog/tests/Unit/PublicCatalogProjectionReaderTest.php'));
    }

    /**
     * @return array<int, class-string>
     */
    private function moduleContracts(): array
    {
        return [
            PlatformTokenIssuer::class,
            TenantTokenIssuer::class,
            CountryReferenceImport::class,
            CityReferenceSearch::class,
            FrontCmsContent::class,
            SeoMetadataCatalog::class,
            PublicLocalizationCatalog::class,
            QrCodeRenderer::class,
            FormSchemaValidator::class,
            FormSubmissionWriter::class,
            FinancePolicyCatalog::class,
            PayoutPolicyCatalog::class,
            AuditLogger::class,
            EventContentCatalog::class,
            TrainingContentCatalog::class,
            StandContentCatalog::class,
            CallForProjectContentCatalog::class,
            CrowdfundingContentCatalog::class,
            EventEngagementWorkflow::class,
            OrganizationAudienceWorkflow::class,
            AccessPassCheckinWorkflow::class,
            CheckoutItemResolver::class,
            CheckoutManager::class,
            PaymentWebhookReceiver::class,
            PricingEngine::class,
            RefundManager::class,
            PayoutManager::class,
            SettlementWorkflow::class,
            TenantRefundManager::class,
            TenantProvisioner::class,
            TenantLifecycleManager::class,
            TenantDestroyer::class,
            TenantStorage::class,
            TenantReferenceCatalog::class,
            TenantProfileManager::class,
            TenantSettingsManager::class,
            EventCatalog::class,
            EventTicketInventory::class,
            EventTicketOfferBridge::class,
            DocumentCatalog::class,
            OrderCatalog::class,
            ReceiptCatalog::class,
            AccessPassCatalog::class,
            AccessPassCheckin::class,
            EventEngagement::class,
            OrganizationAudience::class,
            BuyerRefundRequests::class,
            PublicContentCatalog::class,
            FrontContent::class,
            CallForProjectFormBuilder::class,
            CallForProjectApplications::class,
            NotificationDispatcher::class,
            DomainEventPublisher::class,
            OutboxDispatcher::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function forbiddenLegacyDependencies(): array
    {
        return [
            'App\\Services\\Payments\\PublicPaymentService',
            'App\\Services\\Payments\\PaymentWebhookService',
            'App\\Services\\Payments\\RefundService',
            'App\\Services\\Payments\\PayoutPolicyService',
            'App\\Services\\Payments\\SettlementWorkflowService',
            'App\\Services\\Payments\\TenantRefundService',
            'App\\Services\\Auth\\PlatformTokenService',
            'App\\Services\\Auth\\TenantTokenService',
            'App\\Services\\Tenancy\\ProvisionTenant',
            'App\\Services\\Tenancy\\ManageTenantLifecycle',
            'App\\Services\\Tenancy\\DeleteTenant',
            'App\\Services\\Tenancy\\SyncCentralCategoriesToTenant',
            'App\\Services\\Tenancy\\SyncCentralTagsToTenant',
            'App\\Services\\Tenancy\\TenantSettingsService',
            'App\\Services\\Tenancy\\TenantPublicProfileService',
            'App\\Services\\Tenancy\\TenantStorageManager',
            'App\\Services\\Tenancy\\EventService',
            'App\\Services\\Tenancy\\DocumentService',
            'App\\Services\\Tenancy\\OrderService',
            'App\\Services\\Tenancy\\ReceiptService',
            'App\\Services\\Tenancy\\AccessPassService',
            'App\\Services\\Tenancy\\AccessPassCheckinService',
            'App\\Services\\Tenancy\\EventLikeService',
            'App\\Services\\Tenancy\\OrganizationFollowService',
            'App\\Services\\Tenancy\\BuyerRefundRequestService',
            'App\\Services\\Public\\PublicContentService',
            'App\\Services\\Public\\CallForProjectSubmissionService',
            'App\\Services\\Public\\CallForProjectApplicationFormService',
            'App\\Services\\FrontCmsService',
        ];
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function consumerFiles(): iterable
    {
        foreach ([
            app_path('Http'),
            app_path('Filament'),
            app_path('Providers'),
            app_path('Listeners'),
            app_path('Tenancy'),
            app_path('Services/Payments'),
            app_path('Services/Tenancy'),
        ] as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                    yield $file;
                }
            }
        }
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $directory): iterable
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }
}
