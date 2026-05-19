<?php

namespace Ticket\Tenancy\Infrastructure\Laravel;

use Ticket\Tenancy\Application\ProvisionTenant;
use Ticket\Tenancy\Contracts\TenantProvisioner;

class LaravelTenantProvisioner implements TenantProvisioner
{
    public function __construct(private readonly ProvisionTenant $provisioner) {}

    public function handle(array $payload): array
    {
        return $this->provisioner->handle($payload);
    }
}
