<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Stancl\Tenancy\Tenancy;

class TenantContext
{
    public function get(): ?Tenant
    {
        $tenant = app(Tenancy::class)->tenant;

        return $tenant instanceof Tenant ? $tenant : null;
    }

    public function hasTenant(): bool
    {
        return $this->get() !== null;
    }
}
