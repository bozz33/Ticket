<?php

namespace Tests\Unit;

use App\Support\Microservices\DomainEventBridge;
use App\Support\Microservices\MicroserviceClientFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Ticket\Notifications\Contracts\DomainEventPublisher;
use Ticket\Notifications\Domain\DomainEventEnvelope;
use Ticket\Notifications\Domain\DomainEventNames;

class DomainEventBridgeTest extends TestCase
{
    public function test_bridge_publishes_to_outbox_and_enabled_analytics(): void
    {
        config()->set('services.microservices.analytics.enabled', true);
        config()->set('services.microservices.analytics.url', 'http://analytics.test/v1');

        Http::fake([
            'analytics.test/v1/events' => Http::response(['status' => 'accepted'], 202),
        ]);

        $publisher = new class implements DomainEventPublisher
        {
            public function publish(string $type, array $payload = [], ?string $aggregateType = null, ?string $aggregateId = null, array $metadata = []): string
            {
                return 'event-1';
            }

            public function publishEnvelope(DomainEventEnvelope $event): string
            {
                return $event->eventId;
            }
        };

        $bridge = new DomainEventBridge($publisher, app(MicroserviceClientFactory::class));

        $eventId = $bridge->publish(DomainEventNames::ORDER_PAID, ['order_reference' => 'ORD-1'], 'orders', '1', ['tenant_id' => 'tenant-demo']);

        $this->assertSame('event-1', $eventId);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://analytics.test/v1/events'
            && $request['event_id'] === 'event-1'
            && $request['type'] === DomainEventNames::ORDER_PAID
            && $request->header('X-Tenant-ID')[0] === 'tenant-demo');
    }

    public function test_bridge_projects_access_passes_to_checkin_when_enabled(): void
    {
        config()->set('services.microservices.analytics.enabled', false);
        config()->set('services.microservices.access_checkin.enabled', true);
        config()->set('services.microservices.access_checkin.url', 'http://checkin.test/v1');

        Http::fake([
            'checkin.test/v1/projections/passes/upsert' => Http::response(['status' => 'ok']),
        ]);

        $publisher = new class implements DomainEventPublisher
        {
            public function publish(string $type, array $payload = [], ?string $aggregateType = null, ?string $aggregateId = null, array $metadata = []): string
            {
                return 'event-2';
            }

            public function publishEnvelope(DomainEventEnvelope $event): string
            {
                return $event->eventId;
            }
        };

        $bridge = new DomainEventBridge($publisher, app(MicroserviceClientFactory::class));

        $bridge->publish(DomainEventNames::ACCESS_PASS_ISSUED, [
            'access_pass_id' => 10,
            'access_code' => 'ABC123',
            'holder_email' => 'buyer@example.test',
            'holder_name' => 'Buyer',
            'order_reference' => 'ORD-2',
            'status' => 'active',
            'meta' => ['event_ticket_public_id' => 'evt-1', 'event_ticket_title' => 'Concert'],
        ], 'access_passes', '10', ['tenant_id' => 'tenant-demo']);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://checkin.test/v1/projections/passes/upsert'
            && $request['tenant_id'] === 'tenant-demo'
            && $request['pass_id'] === '10'
            && $request['pass_code'] === 'ABC123'
            && $request->header('X-Tenant-ID')[0] === 'tenant-demo');
    }

    public function test_bridge_projects_catalog_events_to_search_when_enabled(): void
    {
        config()->set('services.microservices.analytics.enabled', false);
        config()->set('services.microservices.catalog_search.enabled', true);
        config()->set('services.microservices.catalog_search.url', 'http://catalog.test/v1');

        Http::fake([
            'catalog.test/v1/projections/catalog-items/upsert' => Http::response(['status' => 'ok']),
        ]);

        $publisher = new class implements DomainEventPublisher
        {
            public function publish(string $type, array $payload = [], ?string $aggregateType = null, ?string $aggregateId = null, array $metadata = []): string
            {
                return 'event-3';
            }

            public function publishEnvelope(DomainEventEnvelope $event): string
            {
                return $event->eventId;
            }
        };

        $payload = [
            'tenant_id' => 'tenant-demo',
            'module' => 'events',
            'item_public_id' => 'event-public-id',
            'item_slug' => 'concert-test',
            'title' => 'Concert test',
        ];

        $bridge = new DomainEventBridge($publisher, app(MicroserviceClientFactory::class));

        $bridge->publish(DomainEventNames::EVENT_PUBLISHED, $payload, 'events', '1', ['tenant_id' => 'tenant-demo']);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://catalog.test/v1/projections/catalog-items/upsert'
            && $request['item_public_id'] === 'event-public-id'
            && $request['module'] === 'events'
            && $request->header('X-Tenant-ID')[0] === 'tenant-demo');
    }
}
