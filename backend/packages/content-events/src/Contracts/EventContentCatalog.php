<?php

namespace Ticket\ContentEvents\Contracts;

use App\Models\Event;

interface EventContentCatalog
{
    public function list(?string $status = null): mixed;

    public function create(array $payload): Event;

    public function findByIdentifier(string $identifier): ?Event;
}
