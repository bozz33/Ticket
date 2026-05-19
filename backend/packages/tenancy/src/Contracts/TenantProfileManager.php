<?php

namespace Ticket\Tenancy\Contracts;

use App\Models\OrganizationProfile;

interface TenantProfileManager
{
    public function getOrCreate(): OrganizationProfile;

    public function getPublicProjection(): OrganizationProfile;

    public function getPublicViewData(?array $tenant = null, array $items = []): array;

    public function update(array $payload): OrganizationProfile;
}
