<?php

namespace Ticket\Notifications\Infrastructure\Laravel;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Ticket\Notifications\Application\OutboxStatuses;
use Ticket\Notifications\Contracts\DomainEventPublisher;

class LaravelDomainEventPublisher implements DomainEventPublisher
{
    public function publish(
        string $type,
        array $payload = [],
        ?string $aggregateType = null,
        ?string $aggregateId = null,
        array $metadata = [],
    ): string {
        $eventId = (string) Str::uuid();

        if (! $this->tableExists()) {
            return $eventId;
        }

        DomainOutboxMessage::query()->create([
            'event_id' => $eventId,
            'type' => $type,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => $payload,
            'metadata' => array_merge([
                'source' => config('app.name', 'ticket'),
                'occurred_at' => now()->toIso8601String(),
            ], $metadata),
            'status' => OutboxStatuses::Pending,
            'attempts' => 0,
            'available_at' => now(),
        ]);

        return $eventId;
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
