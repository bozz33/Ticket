<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\AccessPass;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface AccessPassCatalog
{
    public function list(?string $status = null, ?string $type = null): Collection;

    public function listForBuyer(User $user, ?string $status = null, ?string $type = null): Collection;

    public function findByIdentifier(string $identifier): ?AccessPass;

    public function findByIdentifierForBuyer(User $user, string $identifier): ?AccessPass;

    public function findByCode(string $accessCode): ?AccessPass;
}
