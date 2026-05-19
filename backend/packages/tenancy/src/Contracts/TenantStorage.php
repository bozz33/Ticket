<?php

namespace Ticket\Tenancy\Contracts;

use App\Models\Tenant;

interface TenantStorage
{
    public function tenantStoragePath(Tenant $tenant): string;

    public function legacyTenantStoragePath(Tenant $tenant): string;

    public function ensure(Tenant $tenant): void;

    public function migrateLegacyStorage(Tenant $tenant): void;

    public function delete(Tenant $tenant): void;
}
