<?php

namespace Tests\Unit;

use App\Support\Microservices\MicroserviceClientFactory;
use App\Support\Microservices\MicroserviceNames;
use App\Support\Microservices\MicroserviceRegistry;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MicroserviceClientFactoryTest extends TestCase
{
    public function test_microservice_registry_exposes_configured_services(): void
    {
        $registry = app(MicroserviceRegistry::class);

        $this->assertContains(MicroserviceNames::ApiGateway, $registry->names());
        $this->assertContains(MicroserviceNames::Notifications, $registry->names());
        $this->assertContains(MicroserviceNames::Media, $registry->names());
        $this->assertContains(MicroserviceNames::CatalogSearch, $registry->names());
        $this->assertContains(MicroserviceNames::Analytics, $registry->names());
        $this->assertContains(MicroserviceNames::AccessCheckin, $registry->names());
    }

    public function test_client_factory_applies_base_url_and_context_headers(): void
    {
        config()->set('services.microservices.catalog_search.url', 'http://catalog.test/v1');
        config()->set('services.microservices.internal_token', 'secret-token');

        Http::fake([
            'catalog.test/v1/health' => Http::response(['status' => 'ok']),
        ]);

        app(MicroserviceClientFactory::class)
            ->for(MicroserviceNames::CatalogSearch, 'tenant-demo', 'corr-1')
            ->get('/health')
            ->throw();

        Http::assertSent(fn ($request): bool => $request->url() === 'http://catalog.test/v1/health'
            && $request->header('X-Tenant-ID')[0] === 'tenant-demo'
            && $request->header('X-Correlation-ID')[0] === 'corr-1'
            && $request->header('X-Internal-Service-Token')[0] === 'secret-token');
    }

    public function test_microservices_check_skips_disabled_services(): void
    {
        config()->set('services.microservices.catalog_search.enabled', false);
        config()->set('services.microservices.catalog_search.url', 'http://catalog.test/v1');

        Http::fake();

        $this->artisan('ticket:microservices-check', ['--only' => MicroserviceNames::CatalogSearch])
            ->expectsTable(
                ['Service', 'Enabled', 'Base URL', 'Health', 'Details'],
                [[MicroserviceNames::CatalogSearch, 'no', 'http://catalog.test/v1', 'disabled', 'not checked']],
            )
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_microservices_check_calls_enabled_service_health(): void
    {
        config()->set('services.microservices.catalog_search.enabled', true);
        config()->set('services.microservices.catalog_search.url', 'http://catalog.test/v1');

        Http::fake([
            'catalog.test/v1/health' => Http::response(['status' => 'ok'], 200),
        ]);

        $this->artisan('ticket:microservices-check', [
            '--only' => MicroserviceNames::CatalogSearch,
            '--tenant' => 'tenant-demo',
        ])
            ->expectsTable(
                ['Service', 'Enabled', 'Base URL', 'Health', 'Details'],
                [[MicroserviceNames::CatalogSearch, 'yes', 'http://catalog.test/v1', 'ok', 'HTTP 200']],
            )
            ->assertExitCode(0);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://catalog.test/v1/health'
            && $request->header('X-Tenant-ID')[0] === 'tenant-demo');
    }
}
