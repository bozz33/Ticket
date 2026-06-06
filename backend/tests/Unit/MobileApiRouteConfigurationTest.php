<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MobileApiRouteConfigurationTest extends TestCase
{
    public function test_mobile_mvp_routes_are_registered(): void
    {
        $expectedRoutes = [
            'api/v1/mobile/bootstrap',
            'api/v1/tenants/{tenant}/mobile/account/dashboard',
            'api/v1/tenants/{tenant}/mobile/account/applications',
            'api/v1/tenants/{tenant}/mobile/account/contributions',
            'api/v1/tenants/{tenant}/mobile/devices',
            'api/v1/tenants/{tenant}/mobile/devices/{device}',
            'api/v1/tenants/{tenant}/mobile/support/requests',
            'api/v1/tenants/{tenant}/mobile/support/requests/{ticket}',
            'api/v1/tenants/{tenant}/mobile/organizer/dashboard',
            'api/v1/tenants/{tenant}/mobile/organizer/stats',
            'api/v1/tenants/{tenant}/mobile/organizer/events/{event}/participants',
        ];

        $registeredRoutes = collect(Route::getRoutes())
            ->map(fn ($route): string => $route->uri())
            ->all();

        foreach ($expectedRoutes as $expectedRoute) {
            $this->assertContains($expectedRoute, $registeredRoutes);
        }
    }
}
