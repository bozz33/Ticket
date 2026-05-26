<?php

namespace Ticket\Notifications\Infrastructure\Laravel;

use Illuminate\Support\Facades\Schema;
use Ticket\Notifications\Application\OutboxStatuses;
use Ticket\Notifications\Contracts\DomainEventPublisher;
use Ticket\Notifications\Domain\DomainEventEnvelope;

class LaravelDomainEventPublisher implements DomainEventPublisher
{
    public function publish(
        string $type,
        array $payload = [],
        ?string $aggregateType = null,
        ?string $aggregateId = null,
        array $metadata = [],
    ): string {
        return $this->publishEnvelope(DomainEventEnvelope::make(
            type: $type,
            payload: $payload,
            aggregateType: $aggregateType,
            aggregateId: $aggregateId,
            metadata: $metadata,
        ));
    }

    public function publishEnvelope(DomainEventEnvelope $event): string
    {
        if (! $this->tableExists()) {
            return $event->eventId;
        }

        DomainOutboxMessage::query()->create([
            'event_id' => $event->eventId,
            'type' => $event->type,
            'aggregate_type' => $event->aggregateType,
            'aggregate_id' => $event->aggregateId,
            'payload' => $event->payloadWithEnvelope(),
            'metadata' => $event->metadata,
            'status' => OutboxStatuses::Pending,
            'attempts' => 0,
            'available_at' => now(),
        ]);

        return $event->eventId;
    }

    private function tableExists(): bool
    {
        try {
            return Schema::connection(config('ticket.central_connection', 'central'))
                ->hasTable('domain_outbox_messages');
        } catch (\Throwable) {
            return false;
        }
    }
}
