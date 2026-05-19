<?php

namespace Ticket\Tenancy\Contracts;

use App\Models\Tenant;

interface TenantReferenceCatalog
{
    public function syncCategories(Tenant $tenant): array;

    public function syncTags(Tenant $tenant): array;
}
