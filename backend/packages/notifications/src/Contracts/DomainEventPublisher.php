<?php

namespace Ticket\Notifications\Contracts;

use Ticket\Notifications\Domain\DomainEventEnvelope;

interface DomainEventPublisher
{
    public function publish(
        string $type,
        array $payload = [],
        ?string $aggregateType = null,
        ?string $aggregateId = null,
        array $metadata = [],
    ): string;

    public function publishEnvelope(DomainEventEnvelope $event): string;
}
