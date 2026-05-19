<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Ticket\Tenancy\Contracts\TenantReferenceCatalog;

class EnsureTenantCategoriesAreSynced
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant !== null) {
            app(TenantReferenceCatalog::class)->syncCategories($tenant);
        }

        return $next($request);
    }
}
