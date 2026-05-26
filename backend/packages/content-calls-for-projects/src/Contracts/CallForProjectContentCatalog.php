<?php

namespace Ticket\ContentCallsForProjects\Contracts;

use App\Models\CallForProject;

interface CallForProjectContentCatalog
{
    public function list(?string $status = null): mixed;

    public function create(array $payload): CallForProject;

    public function findByIdentifier(string $identifier): ?CallForProject;
}
