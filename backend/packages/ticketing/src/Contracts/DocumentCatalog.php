<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\Document;
use Illuminate\Support\Collection;

interface DocumentCatalog
{
    public function list(?string $visibility = null, ?string $resourceTypeCode = null): Collection;

    public function listPublic(?string $resourceTypeCode = null): Collection;

    public function create(array $payload): Document;

    public function findByIdentifier(string $identifier): ?Document;

    public function findPublicByIdentifier(string $identifier): ?Document;
}
