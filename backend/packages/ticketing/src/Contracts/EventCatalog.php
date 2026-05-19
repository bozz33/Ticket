<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\Event;

interface EventCatalog
{
    public function list(?string $status = null): mixed;

    public function create(array $payload): Event;

    public function findByIdentifier(string $identifier): ?Event;
}
