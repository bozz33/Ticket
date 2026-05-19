<?php

namespace Ticket\Tenancy\Contracts;

use App\Models\Tenant;

interface TenantLifecycleManager
{
    public function activate(Tenant $tenant): Tenant;

    public function suspend(Tenant $tenant): Tenant;

    public function archive(Tenant $tenant): Tenant;
}
