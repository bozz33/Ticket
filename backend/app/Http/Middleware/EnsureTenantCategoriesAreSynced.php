<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\SyncCentralCategoriesToTenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantCategoriesAreSynced
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant !== null) {
            app(SyncCentralCategoriesToTenant::class)->handle($tenant);
        }

        return $next($request);
    }
}
