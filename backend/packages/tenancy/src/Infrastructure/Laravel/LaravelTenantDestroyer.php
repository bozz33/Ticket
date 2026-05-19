<?php

namespace Ticket\Tenancy\Infrastructure\Laravel;

use App\Models\Tenant;
use Ticket\Tenancy\Application\DeleteTenant;
use Ticket\Tenancy\Contracts\TenantDestroyer;

class LaravelTenantDestroyer implements TenantDestroyer
{
    public function __construct(private readonly DeleteTenant $destroyer) {}

    public function handle(Tenant $tenant): void
    {
        $this->destroyer->handle($tenant);
    }
}
