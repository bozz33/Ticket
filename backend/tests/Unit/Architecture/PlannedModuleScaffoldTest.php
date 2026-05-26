<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\ServiceProvider;
use Tests\TestCase;
use Ticket\AccessControl\AccessControlServiceProvider;
use Ticket\AccessControl\Contracts\AccessPassCheckinWorkflow;
use Ticket\Cms\CmsServiceProvider;
use Ticket\Cms\Contracts\FrontCmsContent;
use Ticket\ContentCallsForProjects\ContentCallsForProjectsServiceProvider;
use Ticket\ContentCallsForProjects\Contracts\CallForProjectContentCatalog;
use Ticket\ContentCrowdfunding\ContentCrowdfundingServiceProvider;
use Ticket\ContentCrowdfunding\Contracts\CrowdfundingContentCatalog;
use Ticket\ContentEvents\ContentEventsServiceProvider;
use Ticket\ContentEvents\Contracts\EventContentCatalog;
use Ticket\ContentStands\ContentStandsServiceProvider;
use Ticket\ContentStands\Contracts\StandContentCatalog;
use Ticket\ContentTraining\ContentTrainingServiceProvider;
use Ticket\ContentTraining\Contracts\TrainingContentCatalog;
use Ticket\Engagement\Contracts\EventEngagementWorkflow;
use Ticket\Engagement\Contracts\OrganizationAudienceWorkflow;
use Ticket\Engagement\EngagementServiceProvider;
use Ticket\FinanceAccounting\Contracts\FinancePolicyCatalog;
use Ticket\FinanceAccounting\Contracts\PayoutPolicyCatalog;
use Ticket\FinanceAccounting\FinanceAccountingServiceProvider;
use Ticket\FormBuilder\Contracts\FormSchemaValidator;
use Ticket\FormBuilder\Contracts\FormSubmissionWriter;
use Ticket\FormBuilder\FormBuilderServiceProvider;
use Ticket\IdentityAccess\Contracts\PlatformTokenIssuer;
use Ticket\IdentityAccess\Contracts\TenantTokenIssuer;
use Ticket\IdentityAccess\IdentityAccessServiceProvider;
use Ticket\Localization\Contracts\PublicLocalizationCatalog;
use Ticket\Localization\LocalizationServiceProvider;
use Ticket\MediaDocuments\Contracts\QrCodeRenderer;
use Ticket\MediaDocuments\MediaDocumentsServiceProvider;
use Ticket\ReferenceData\Contracts\CityReferenceSearch;
use Ticket\ReferenceData\Contracts\CountryReferenceImport;
use Ticket\ReferenceData\ReferenceDataServiceProvider;
use Ticket\Seo\Contracts\SeoMetadataCatalog;
use Ticket\Seo\SeoServiceProvider;
use Ticket\SupportObservability\Contracts\AuditLogger;
use Ticket\SupportObservability\SupportObservabilityServiceProvider;

class PlannedModuleScaffoldTest extends TestCase
{
    public function test_planned_module_providers_are_registered(): void
    {
        $providers = config('modules.providers');

        foreach ($this->plannedProviders() as $provider) {
            $this->assertContains($provider, $providers);
        }
    }

    public function test_planned_module_providers_are_valid_service_providers(): void
    {
        foreach ($this->plannedProviders() as $provider) {
            $this->assertTrue(is_subclass_of($provider, ServiceProvider::class));
        }
    }

    public function test_planned_modules_have_composer_and_readme_files(): void
    {
        foreach ($this->plannedPackages() as $package) {
            $this->assertFileExists(base_path("packages/{$package}/composer.json"));
            $this->assertFileExists(base_path("packages/{$package}/README.md"));
        }
    }

    public function test_identity_access_contracts_are_bound(): void
    {
        $this->assertInstanceOf(PlatformTokenIssuer::class, app(PlatformTokenIssuer::class));
        $this->assertInstanceOf(TenantTokenIssuer::class, app(TenantTokenIssuer::class));
    }

    public function test_reference_data_contracts_are_bound(): void
    {
        $this->assertInstanceOf(CountryReferenceImport::class, app(CountryReferenceImport::class));
        $this->assertInstanceOf(CityReferenceSearch::class, app(CityReferenceSearch::class));
    }

    public function test_cms_contracts_are_bound(): void
    {
        $this->assertInstanceOf(FrontCmsContent::class, app(FrontCmsContent::class));
    }

    public function test_form_builder_contracts_are_bound(): void
    {
        $this->assertInstanceOf(FormSchemaValidator::class, app(FormSchemaValidator::class));
        $this->assertInstanceOf(FormSubmissionWriter::class, app(FormSubmissionWriter::class));
    }

    public function test_localization_contracts_are_bound(): void
    {
        $this->assertInstanceOf(PublicLocalizationCatalog::class, app(PublicLocalizationCatalog::class));
    }

    public function test_access_control_contracts_are_bound(): void
    {
        $this->assertInstanceOf(AccessPassCheckinWorkflow::class, app(AccessPassCheckinWorkflow::class));
    }

    public function test_engagement_contracts_are_bound(): void
    {
        $this->assertInstanceOf(EventEngagementWorkflow::class, app(EventEngagementWorkflow::class));
        $this->assertInstanceOf(OrganizationAudienceWorkflow::class, app(OrganizationAudienceWorkflow::class));
    }

    public function test_finance_accounting_contracts_are_bound(): void
    {
        $this->assertInstanceOf(FinancePolicyCatalog::class, app(FinancePolicyCatalog::class));
        $this->assertInstanceOf(PayoutPolicyCatalog::class, app(PayoutPolicyCatalog::class));
    }

    public function test_support_observability_contracts_are_bound(): void
    {
        $this->assertInstanceOf(AuditLogger::class, app(AuditLogger::class));
    }

    public function test_content_event_contracts_are_bound(): void
    {
        $this->assertInstanceOf(EventContentCatalog::class, app(EventContentCatalog::class));
    }

    public function test_content_vertical_contracts_are_bound(): void
    {
        $this->assertInstanceOf(TrainingContentCatalog::class, app(TrainingContentCatalog::class));
        $this->assertInstanceOf(StandContentCatalog::class, app(StandContentCatalog::class));
        $this->assertInstanceOf(CallForProjectContentCatalog::class, app(CallForProjectContentCatalog::class));
        $this->assertInstanceOf(CrowdfundingContentCatalog::class, app(CrowdfundingContentCatalog::class));
    }

    public function test_media_and_seo_contracts_are_bound(): void
    {
        $this->assertInstanceOf(QrCodeRenderer::class, app(QrCodeRenderer::class));
        $this->assertInstanceOf(SeoMetadataCatalog::class, app(SeoMetadataCatalog::class));
    }

    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    private function plannedProviders(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<int, string>
     */
    private function plannedPackages(): array
    {
        return [
            'identity-access',
            'reference-data',
            'media-documents',
            'cms',
            'seo',
            'localization',
            'engagement',
            'access-control',
            'finance-accounting',
            'support-observability',
            'form-builder',
            'content-events',
            'content-training',
            'content-stands',
            'content-calls-for-projects',
            'content-crowdfunding',
        ];
    }
}
