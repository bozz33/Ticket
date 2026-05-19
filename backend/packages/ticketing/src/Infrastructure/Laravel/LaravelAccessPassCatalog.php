<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\AccessPass;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Ticket\Ticketing\Application\AccessPassService;
use Ticket\Ticketing\Contracts\AccessPassCatalog;

class LaravelAccessPassCatalog implements AccessPassCatalog
{
    public function __construct(private readonly AccessPassService $passes) {}

    public function list(?string $status = null, ?string $type = null): Collection
    {
        return $this->passes->list($status, $type);
    }

    public function listForBuyer(User $user, ?string $status = null, ?string $type = null): Collection
    {
        return $this->passes->listForBuyer($user, $status, $type);
    }

    public function findByIdentifier(string $identifier): ?AccessPass
    {
        return $this->passes->findByIdentifier($identifier);
    }

    public function findByIdentifierForBuyer(User $user, string $identifier): ?AccessPass
    {
        return $this->passes->findByIdentifierForBuyer($user, $identifier);
    }

    public function findByCode(string $accessCode): ?AccessPass
    {
        return $this->passes->findByCode($accessCode);
    }
}
