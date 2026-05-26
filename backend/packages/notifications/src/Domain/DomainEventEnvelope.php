<?php

namespace Ticket\Notifications\Domain;

use Illuminate\Support\Str;

final class DomainEventEnvelope
{
    private function __construct(
        public readonly string $eventId,
        public readonly string $type,
        public readonly int $version,
        public readonly array $payload,
        public readonly ?string $aggregateType,
        public readonly ?string $aggregateId,
        public readonly array $metadata,
    ) {}

    public static function make(
        string $type,
        array $payload = [],
        ?string $aggregateType = null,
        ?string $aggregateId = null,
        array $metadata = [],
        int $version = 1,
        ?string $eventId = null,
    ): self {
        $version = max(1, $version);

        return new self(
            eventId: $eventId ?: (string) Str::uuid(),
            type: $type,
            version: $version,
            payload: $payload,
            aggregateType: $aggregateType,
            aggregateId: $aggregateId,
            metadata: array_merge([
                'source' => config('app.name', 'ticket'),
                'occurred_at' => now()->toIso8601String(),
                'event_version' => $version,
            ], $metadata),
        );
    }

    public function payloadWithEnvelope(): array
    {
        return array_merge($this->payload, [
            '_event' => [
                'event_id' => $this->eventId,
                'type' => $this->type,
                'version' => $this->version,
            ],
        ]);
    }
}
