<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Ticket\Ticketing\Application\ReceiptService;
use Ticket\Ticketing\Contracts\ReceiptCatalog;

class LaravelReceiptCatalog implements ReceiptCatalog
{
    public function __construct(private readonly ReceiptService $receipts) {}

    public function list(?string $status = null): Collection
    {
        return $this->receipts->list($status);
    }

    public function listForBuyer(User $user, ?string $status = null): Collection
    {
        return $this->receipts->listForBuyer($user, $status);
    }

    public function findByIdentifier(string $identifier): ?Receipt
    {
        return $this->receipts->findByIdentifier($identifier);
    }

    public function findByIdentifierForBuyer(User $user, string $identifier): ?Receipt
    {
        return $this->receipts->findByIdentifierForBuyer($user, $identifier);
    }
}
