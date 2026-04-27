<?php

namespace App\Services;

use App\Models\Tenant;

class SubscriptionGateService
{
    public function allowsPanel(Tenant $tenant): bool
    {
        if (! $tenant->hasActiveSubscription()) {
            return false;
        }

        return app(FeatureFlagService::class)->enabledForTenant($tenant, 'tenant.access');
    }

    public function allowsModule(Tenant $tenant, string $featureCode): bool
    {
        if (! $tenant->hasActiveSubscription()) {
            return false;
        }

        return app(FeatureFlagService::class)->enabledForTenant($tenant, $featureCode);
    }
}
