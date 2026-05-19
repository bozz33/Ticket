<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\Document;
use Illuminate\Support\Collection;
use Ticket\Ticketing\Application\DocumentService;
use Ticket\Ticketing\Contracts\DocumentCatalog;

class LaravelDocumentCatalog implements DocumentCatalog
{
    public function __construct(private readonly DocumentService $documents) {}

    public function list(?string $visibility = null, ?string $resourceTypeCode = null): Collection
    {
        return $this->documents->list($visibility, $resourceTypeCode);
    }

    public function listPublic(?string $resourceTypeCode = null): Collection
    {
        return $this->documents->listPublic($resourceTypeCode);
    }

    public function create(array $payload): Document
    {
        return $this->documents->create($payload);
    }

    public function findByIdentifier(string $identifier): ?Document
    {
        return $this->documents->findByIdentifier($identifier);
    }

    public function findPublicByIdentifier(string $identifier): ?Document
    {
        return $this->documents->findPublicByIdentifier($identifier);
    }
}
