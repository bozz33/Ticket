<?php

namespace Ticket\Tenancy\Infrastructure\Laravel;

use App\Models\Tenant;
use Ticket\Tenancy\Application\SyncCentralCategoriesToTenant;
use Ticket\Tenancy\Application\SyncCentralTagsToTenant;
use Ticket\Tenancy\Contracts\TenantReferenceCatalog;

class LaravelTenantReferenceCatalog implements TenantReferenceCatalog
{
    public function __construct(
        private readonly SyncCentralCategoriesToTenant $categories,
        private readonly SyncCentralTagsToTenant $tags,
    ) {}

    public function syncCategories(Tenant $tenant): array
    {
        return $this->categories->handle($tenant);
    }

    public function syncTags(Tenant $tenant): array
    {
        return $this->tags->handle($tenant);
    }
}
