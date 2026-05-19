<?php

namespace Ticket\Tenancy\Infrastructure\Laravel;

use App\Models\Tenant;
use Ticket\Tenancy\Application\TenantStorageManager;
use Ticket\Tenancy\Contracts\TenantStorage;

class LaravelTenantStorage implements TenantStorage
{
    public function __construct(private readonly TenantStorageManager $storage) {}

    public function tenantStoragePath(Tenant $tenant): string
    {
        return $this->storage->tenantStoragePath($tenant);
    }

    public function legacyTenantStoragePath(Tenant $tenant): string
    {
        return $this->storage->legacyTenantStoragePath($tenant);
    }

    public function ensure(Tenant $tenant): void
    {
        $this->storage->ensure($tenant);
    }

    public function migrateLegacyStorage(Tenant $tenant): void
    {
        $this->storage->migrateLegacyStorage($tenant);
    }

    public function delete(Tenant $tenant): void
    {
        $this->storage->delete($tenant);
    }
}
