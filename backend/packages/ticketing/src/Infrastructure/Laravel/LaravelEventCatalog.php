<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\Event;
use Ticket\ContentEvents\Contracts\EventContentCatalog;
use Ticket\Ticketing\Contracts\EventCatalog;

class LaravelEventCatalog implements EventCatalog
{
    public function __construct(private readonly EventContentCatalog $events) {}

    public function list(?string $status = null): mixed
    {
        return $this->events->list($status);
    }

    public function create(array $payload): Event
    {
        return $this->events->create($payload);
    }

    public function findByIdentifier(string $identifier): ?Event
    {
        return $this->events->findByIdentifier($identifier);
    }
}
