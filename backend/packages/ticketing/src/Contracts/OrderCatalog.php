<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface OrderCatalog
{
    public function list(?string $status = null, int $perPage = 50): Collection;

    public function listForBuyer(User $user, ?string $status = null, int $limit = 100): Collection;

    public function findByIdentifier(string $identifier): ?Order;

    public function findByIdentifierForBuyer(User $user, string $identifier): ?Order;

    public function findByTransactionReference(string $transactionReference): ?Order;

    public function statusOptions(): array;
}
