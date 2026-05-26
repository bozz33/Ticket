<?php

namespace Ticket\ContentStands\Contracts;

use App\Models\Stand;

interface StandContentCatalog
{
    public function list(?string $status = null): mixed;

    public function create(array $payload): Stand;

    public function findByIdentifier(string $identifier): ?Stand;
}
