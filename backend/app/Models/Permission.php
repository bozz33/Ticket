<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Stancl\Tenancy\Tenancy;

class Permission extends SpatiePermission
{
    public function getConnectionName(): ?string
    {
        return app(Tenancy::class)->initialized
            ? 'tenant'
            : config('ticket.central_connection');
    }
}
