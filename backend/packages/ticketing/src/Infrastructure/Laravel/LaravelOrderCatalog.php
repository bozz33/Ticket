<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Ticket\Ticketing\Application\OrderService;
use Ticket\Ticketing\Contracts\OrderCatalog;

class LaravelOrderCatalog implements OrderCatalog
{
    public function __construct(private readonly OrderService $orders) {}

    public function list(?string $status = null, int $perPage = 50): Collection
    {
        return $this->orders->list($status, $perPage);
    }

    public function listForBuyer(User $user, ?string $status = null, int $limit = 100): Collection
    {
        return $this->orders->listForBuyer($user, $status, $limit);
    }

    public function findByIdentifier(string $identifier): ?Order
    {
        return $this->orders->findByIdentifier($identifier);
    }

    public function findByIdentifierForBuyer(User $user, string $identifier): ?Order
    {
        return $this->orders->findByIdentifierForBuyer($user, $identifier);
    }

    public function findByTransactionReference(string $transactionReference): ?Order
    {
        return $this->orders->findByTransactionReference($transactionReference);
    }

    public function statusOptions(): array
    {
        return $this->orders->statusOptions();
    }
}
