<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ReceiptCatalog
{
    public function list(?string $status = null): Collection;

    public function listForBuyer(User $user, ?string $status = null): Collection;

    public function findByIdentifier(string $identifier): ?Receipt;

    public function findByIdentifierForBuyer(User $user, string $identifier): ?Receipt;
}
