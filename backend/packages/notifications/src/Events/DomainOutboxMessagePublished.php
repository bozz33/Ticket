<?php

namespace Ticket\Notifications\Events;

class DomainOutboxMessagePublished
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $type,
        public readonly array $payload,
        public readonly array $metadata = [],
    ) {}
}
