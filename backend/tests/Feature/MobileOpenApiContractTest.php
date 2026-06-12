<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Keeps docs/api/mobile-openapi.json in lockstep with the registered mobile routes.
 * Every mobile route must be documented and every documented operation must exist as a
 * route, so the published contract cannot silently drift from the implementation.
 */
class MobileOpenApiContractTest extends TestCase
{
    public function test_mobile_routes_and_openapi_operations_match_one_to_one(): void
    {
        $spec = json_decode(
            (string) file_get_contents(base_path('docs/api/mobile-openapi.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $documented = [];
        foreach ($spec['paths'] as $path => $operations) {
            foreach (array_keys($operations) as $method) {
                $documented[] = strtoupper($method).' '.$path;
            }
        }

        $registered = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_contains($uri, 'mobile')) {
                continue;
            }

            $path = '/'.preg_replace('#^api/v1/?#', '', $uri);

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $registered[] = $method.' '.$path;
            }
        }

        sort($documented);
        sort($registered);

        $this->assertSame(
            $registered,
            $documented,
            'Mobile routes and OpenAPI operations are out of sync. '
            .'Routes only: '.implode(', ', array_diff($registered, $documented)).'. '
            .'Spec only: '.implode(', ', array_diff($documented, $registered)).'.',
        );
    }
}
