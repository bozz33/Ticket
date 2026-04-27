<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class FeatureFlagService
{
    public function publicFlags(): Collection
    {
        return FeatureFlag::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('code')
            ->get();
    }

    public function enabledForTenant(Tenant $tenant, string $code): bool
    {
        $flag = FeatureFlag::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($flag === null) {
            return false;
        }

        $override = $tenant->featureOverrides()
            ->where('feature_flag_id', $flag->getKey())
            ->first();

        if ($override !== null) {
            return (bool) $override->is_enabled;
        }

        if ($flag->requires_subscription && ! $tenant->hasActiveSubscription()) {
            return false;
        }

        $subscription = $tenant->activeSubscription();

        if ($subscription !== null) {
            $subscription->loadMissing('plan');
        }

        if ($subscription !== null && $subscription->plan !== null && $subscription->plan->definesFeature($code)) {
            return true;
        }

        return (bool) $flag->default_enabled;
    }
}
