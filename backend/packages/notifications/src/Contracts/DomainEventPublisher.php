<?php

namespace Ticket\Notifications\Contracts;

interface DomainEventPublisher
{
    public function publish(
        string $type,
        array $payload = [],
        ?string $aggregateType = null,
        ?string $aggregateId = null,
        array $metadata = [],
    ): string;
}
