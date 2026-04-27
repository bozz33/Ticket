<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class DeleteTenant
{
    public function handle(Tenant $tenant): void
    {
        DB::connection(config('ticket.central_connection'))->transaction(function () use ($tenant): void {
            $tenant->forceDelete();
        });
    }
}
