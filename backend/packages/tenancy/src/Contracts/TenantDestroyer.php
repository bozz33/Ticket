<?php

namespace Ticket\Tenancy\Contracts;

use App\Models\Tenant;

interface TenantDestroyer
{
    public function handle(Tenant $tenant): void;
}
