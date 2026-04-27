<?php

namespace App\Providers;

use App\Models\CompliancePolicy;
use App\Models\FeatureFlag;
use App\Models\FinancialExport;
use App\Models\GatewayWebhookLog;
use App\Models\IncidentLog;
use App\Models\KpiSnapshot;
use App\Models\Plan;
use App\Models\PaymentGateway;
use App\Models\PaymentIncident;
use App\Models\PlatformSetting;
use App\Models\PlatformSupportTicket;
use App\Models\PlatformUser;
use App\Models\PlatformTransaction;
use App\Models\PayoutBatch;
use App\Models\ReconciliationLog;
use App\Models\Settlement;
use App\Models\Tenant;
use App\Observers\PlatformAuditObserver;
use App\Services\AuditService;
use App\Services\FeatureFlagService;
use App\Services\PlatformSettingsService;
use App\Services\SubscriptionGateService;
use App\Services\Payments\PaymentWebhookService;
use App\Services\Tenancy\DocumentService;
use App\Services\Tenancy\EventService;
use App\Services\Tenancy\ManageTenantLifecycle;
use App\Services\Tenancy\TenantPublicProfileService;
use App\Services\Tenancy\TenantSettingsService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditService::class, fn (): AuditService => new AuditService());
        $this->app->singleton(DocumentService::class, fn (): DocumentService => new DocumentService($this->app->make(TenantPublicProfileService::class)));
        $this->app->singleton(EventService::class, fn (): EventService => new EventService($this->app->make(TenantPublicProfileService::class)));
        $this->app->singleton(FeatureFlagService::class, fn (): FeatureFlagService => new FeatureFlagService());
        $this->app->singleton(ManageTenantLifecycle::class, fn (): ManageTenantLifecycle => new ManageTenantLifecycle());
        $this->app->singleton(PaymentWebhookService::class, fn (): PaymentWebhookService => new PaymentWebhookService());
        $this->app->singleton(PlatformSettingsService::class, fn (): PlatformSettingsService => new PlatformSettingsService());
        $this->app->singleton(SubscriptionGateService::class, fn (): SubscriptionGateService => new SubscriptionGateService());
        $this->app->singleton(TenantPublicProfileService::class, fn (): TenantPublicProfileService => new TenantPublicProfileService());
        $this->app->singleton(TenantContext::class, fn (): TenantContext => new TenantContext());
        $this->app->singleton(TenantSettingsService::class, fn (): TenantSettingsService => new TenantSettingsService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            CompliancePolicy::class,
            FeatureFlag::class,
            FinancialExport::class,
            GatewayWebhookLog::class,
            IncidentLog::class,
            KpiSnapshot::class,
            Plan::class,
            PaymentGateway::class,
            PaymentIncident::class,
            PlatformSetting::class,
            PlatformSupportTicket::class,
            PlatformUser::class,
            PlatformTransaction::class,
            PayoutBatch::class,
            ReconciliationLog::class,
            Settlement::class,
            Tenant::class,
        ] as $model) {
            $model::observe(PlatformAuditObserver::class);
        }
    }
}
