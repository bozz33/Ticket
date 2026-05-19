<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\Event;
use Ticket\Ticketing\Application\EventService;
use Ticket\Ticketing\Contracts\EventCatalog;

class LaravelEventCatalog implements EventCatalog
{
    public function __construct(private readonly EventService $events) {}

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
