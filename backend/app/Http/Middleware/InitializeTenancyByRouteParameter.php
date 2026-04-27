<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\RouteTenantResolver;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByRouteParameter extends IdentificationMiddleware
{
    public static $onFail;

    public function __construct(Tenancy $tenancy, RouteTenantResolver $resolver)
    {
        $this->tenancy = $tenancy;
        $this->resolver = $resolver;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if ($route === null || $route->parameter('tenant') === null) {
            return new JsonResponse([
                'message' => 'Tenant context is required.',
            ], 404);
        }

        try {
            return $this->initializeTenancy($request, $next, $route);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'message' => 'Tenant context is required.',
            ], 404);
        }
    }
}
