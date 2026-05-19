<?php

namespace Ticket\Tenancy;

use Illuminate\Support\ServiceProvider;
use Ticket\Tenancy\Application\ManageTenantLifecycle;
use Ticket\Tenancy\Application\TenantPublicProfileService;
use Ticket\Tenancy\Application\TenantSettingsService;
use Ticket\Tenancy\Contracts\TenantDestroyer;
use Ticket\Tenancy\Contracts\TenantLifecycleManager;
use Ticket\Tenancy\Contracts\TenantProfileManager;
use Ticket\Tenancy\Contracts\TenantProvisioner;
use Ticket\Tenancy\Contracts\TenantReferenceCatalog;
use Ticket\Tenancy\Contracts\TenantSettingsManager;
use Ticket\Tenancy\Contracts\TenantStorage;
use Ticket\Tenancy\Infrastructure\Laravel\LaravelTenantDestroyer;
use Ticket\Tenancy\Infrastructure\Laravel\LaravelTenantLifecycleManager;
use Ticket\Tenancy\Infrastructure\Laravel\LaravelTenantProfileManager;
use Ticket\Tenancy\Infrastructure\Laravel\LaravelTenantProvisioner;
use Ticket\Tenancy\Infrastructure\Laravel\LaravelTenantReferenceCatalog;
use Ticket\Tenancy\Infrastructure\Laravel\LaravelTenantSettingsManager;
use Ticket\Tenancy\Infrastructure\Laravel\LaravelTenantStorage;

class TenancyModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ManageTenantLifecycle::class, fn (): ManageTenantLifecycle => new ManageTenantLifecycle);
        $this->app->singleton(TenantPublicProfileService::class, fn (): TenantPublicProfileService => new TenantPublicProfileService);
        $this->app->singleton(TenantSettingsService::class, fn (): TenantSettingsService => new TenantSettingsService);

        $this->app->bind(TenantProvisioner::class, LaravelTenantProvisioner::class);
        $this->app->bind(TenantLifecycleManager::class, LaravelTenantLifecycleManager::class);
        $this->app->bind(TenantDestroyer::class, LaravelTenantDestroyer::class);
        $this->app->bind(TenantStorage::class, LaravelTenantStorage::class);
        $this->app->bind(TenantReferenceCatalog::class, LaravelTenantReferenceCatalog::class);
        $this->app->bind(TenantProfileManager::class, LaravelTenantProfileManager::class);
        $this->app->bind(TenantSettingsManager::class, LaravelTenantSettingsManager::class);
    }
}
