<?php

namespace Ticket\Tenancy\Infrastructure\Laravel;

use App\Models\OrganizationProfile;
use Ticket\Tenancy\Application\TenantPublicProfileService;
use Ticket\Tenancy\Contracts\TenantProfileManager;

class LaravelTenantProfileManager implements TenantProfileManager
{
    public function __construct(private readonly TenantPublicProfileService $profiles) {}

    public function getOrCreate(): OrganizationProfile
    {
        return $this->profiles->getOrCreate();
    }

    public function getPublicProjection(): OrganizationProfile
    {
        return $this->profiles->getPublicProjection();
    }

    public function getPublicViewData(?array $tenant = null, array $items = []): array
    {
        return $this->profiles->getPublicViewData($tenant, $items);
    }

    public function update(array $payload): OrganizationProfile
    {
        return $this->profiles->update($payload);
    }
}
