<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Stancl\Tenancy\Tenancy;

class Role extends SpatieRole
{
    public function getConnectionName(): ?string
    {
        return app(Tenancy::class)->initialized
            ? 'tenant'
            : config('ticket.central_connection');
    }
}
