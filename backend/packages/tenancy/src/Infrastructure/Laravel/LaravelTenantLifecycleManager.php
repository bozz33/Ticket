<?php

namespace Ticket\Tenancy\Infrastructure\Laravel;

use App\Models\Tenant;
use Ticket\Tenancy\Application\ManageTenantLifecycle;
use Ticket\Tenancy\Contracts\TenantLifecycleManager;

class LaravelTenantLifecycleManager implements TenantLifecycleManager
{
    public function __construct(private readonly ManageTenantLifecycle $lifecycle) {}

    public function activate(Tenant $tenant): Tenant
    {
        return $this->lifecycle->activate($tenant);
    }

    public function suspend(Tenant $tenant): Tenant
    {
        return $this->lifecycle->suspend($tenant);
    }

    public function archive(Tenant $tenant): Tenant
    {
        return $this->lifecycle->archive($tenant);
    }
}
