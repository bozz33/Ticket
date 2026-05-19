<?php

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRouteContractTest extends TestCase
{
    public function test_platform_api_routes_are_protected_except_login(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1/platform/')) {
                continue;
            }

            if ($uri === 'api/v1/platform/auth/login') {
                continue;
            }

            if (! in_array('auth.platform.api', $route->middleware(), true)) {
                $violations[] = $uri;
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_tenant_api_routes_initialize_tenancy_and_protect_private_routes(): void
    {
        $missingTenantInitialization = [];
        $missingTenantAuthentication = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1/tenants/{tenant}/')) {
                continue;
            }

            $middleware = $route->middleware();

            if (! in_array('initialize.tenant.route', $middleware, true)) {
                $missingTenantInitialization[] = $uri;
            }

            if ($this->isPublicTenantAuthRoute($uri)) {
                continue;
            }

            if (! in_array('auth.tenant.api', $middleware, true)) {
                $missingTenantAuthentication[] = $uri;
            }
        }

        $this->assertSame([], $missingTenantInitialization);
        $this->assertSame([], $missingTenantAuthentication);
    }

    public function test_public_tenant_routes_initialize_tenancy_without_private_auth(): void
    {
        $missingTenantInitialization = [];
        $unexpectedPrivateAuthentication = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1/public/tenants/{tenant}/')) {
                continue;
            }

            $middleware = $route->middleware();

            if (! in_array('initialize.tenant.route', $middleware, true)) {
                $missingTenantInitialization[] = $uri;
            }

            if (in_array('auth.tenant.api', $middleware, true)) {
                $unexpectedPrivateAuthentication[] = $uri;
            }
        }

        $this->assertSame([], $missingTenantInitialization);
        $this->assertSame([], $unexpectedPrivateAuthentication);
    }

    private function isPublicTenantAuthRoute(string $uri): bool
    {
        return in_array($uri, [
            'api/v1/tenants/{tenant}/auth/login',
            'api/v1/tenants/{tenant}/auth/register',
            'api/v1/tenants/{tenant}/auth/forgot-password',
            'api/v1/tenants/{tenant}/auth/reset-password',
            'api/v1/tenants/{tenant}/auth/email/verify/{id}/{hash}',
        ], true);
    }
}
