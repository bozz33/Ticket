<?php

namespace App\Support\Microservices;

use Illuminate\Support\Facades\Log;
use Ticket\Notifications\Contracts\DomainEventPublisher;
use Ticket\Notifications\Domain\DomainEventNames;

class DomainEventBridge
{
    public function __construct(
        private readonly DomainEventPublisher $publisher,
        private readonly MicroserviceClientFactory $clientFactory,
    ) {}

    public function publish(
        string $type,
        array $payload = [],
        ?string $aggregateType = null,
        ?string $aggregateId = null,
        array $metadata = [],
    ): string {
        $eventId = $this->publisher->publish($type, $payload, $aggregateType, $aggregateId, $metadata);

        $this->sendToAnalytics($eventId, $type, $payload, $metadata);
        $this->sendToCheckinProjection($type, $payload, $metadata);
        $this->sendToCatalogProjection($type, $payload, $metadata);

        return $eventId;
    }

    private function sendToAnalytics(string $eventId, string $type, array $payload, array $metadata): void
    {
        if (! $this->clientFactory->enabled(MicroserviceNames::Analytics)) {
            return;
        }

        try {
            $tenantId = $metadata['tenant_id'] ?? null;
            $correlationId = $metadata['correlation_id'] ?? null;

            $this->clientFactory
                ->for(MicroserviceNames::Analytics, is_string($tenantId) ? $tenantId : null, is_string($correlationId) ? $correlationId : null)
                ->post('/events', [
                    'event_id' => $eventId,
                    'type' => $type,
                    'payload' => $payload,
                    'metadata' => $metadata,
                    'occurred_at' => now()->toISOString(),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('microservice_analytics_event_failed', [
                'event_id' => $eventId,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendToCheckinProjection(string $type, array $payload, array $metadata): void
    {
        if ($type !== DomainEventNames::ACCESS_PASS_ISSUED || ! $this->clientFactory->enabled(MicroserviceNames::AccessCheckin)) {
            return;
        }

        try {
            $tenantId = $metadata['tenant_id'] ?? null;

            $this->clientFactory
                ->for(MicroserviceNames::AccessCheckin, is_string($tenantId) ? $tenantId : null)
                ->post('/projections/passes/upsert', [
                    'tenant_id' => is_string($tenantId) ? $tenantId : '',
                    'event_id' => data_get($payload, 'meta.event_ticket_public_id') ?: data_get($payload, 'meta.event_ticket_id') ?: '',
                    'event_title' => data_get($payload, 'meta.checkout_item_title') ?: data_get($payload, 'meta.event_ticket_title') ?: '',
                    'pass_id' => (string) ($payload['access_pass_id'] ?? ''),
                    'pass_code' => (string) ($payload['access_code'] ?? ''),
                    'receipt_reference' => (string) ($payload['order_reference'] ?? ''),
                    'holder_name' => (string) ($payload['holder_name'] ?? ''),
                    'holder_email' => (string) ($payload['holder_email'] ?? ''),
                    'ticket_label' => data_get($payload, 'meta.event_ticket_category') ?: data_get($payload, 'meta.checkout_item_title') ?: '',
                    'status' => (string) ($payload['status'] ?? 'issued'),
                    'metadata' => $payload,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('microservice_checkin_projection_failed', [
                'type' => $type,
                'access_pass_id' => $payload['access_pass_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendToCatalogProjection(string $type, array $payload, array $metadata): void
    {
        if (! in_array($type, [
            DomainEventNames::EVENT_PUBLISHED,
            DomainEventNames::EVENT_UPDATED,
            DomainEventNames::TRAINING_SESSION_SCHEDULED,
            DomainEventNames::CALL_FOR_PROJECT_OPENED,
            DomainEventNames::CROWDFUNDING_CAMPAIGN_LAUNCHED,
            DomainEventNames::STAND_RESERVED,
        ], true)
            || ! $this->clientFactory->enabled(MicroserviceNames::CatalogSearch)) {
            return;
        }

        try {
            $tenantId = $metadata['tenant_id'] ?? $payload['tenant_id'] ?? null;

            $this->clientFactory
                ->for(MicroserviceNames::CatalogSearch, is_string($tenantId) ? $tenantId : null)
                ->post('/projections/catalog-items/upsert', $payload);
        } catch (\Throwable $exception) {
            Log::warning('microservice_catalog_projection_failed', [
                'type' => $type,
                'item_public_id' => $payload['item_public_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
