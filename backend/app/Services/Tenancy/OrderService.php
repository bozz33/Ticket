<?php

namespace App\Services\Tenancy;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    public function list(?string $status = null, int $perPage = 50): Collection
    {
        return Order::query()
            ->with(['offer', 'receipt'])
            ->when(
                $status !== null && $status !== '',
                fn ($q) => $q->where('status', $status),
            )
            ->withCount('accessPasses')
            ->latest()
            ->get();
    }

    public function findByIdentifier(string $identifier): ?Order
    {
        return Order::query()
            ->with(['offer', 'receipt', 'accessPasses'])
            ->where('public_id', $identifier)
            ->orWhere('reference', $identifier)
            ->first();
    }

    public function findByTransactionReference(string $transactionReference): ?Order
    {
        return Order::query()
            ->with(['offer', 'receipt', 'accessPasses'])
            ->where('transaction_reference', $transactionReference)
            ->first();
    }

    public function statusOptions(): array
    {
        return OrderStatus::options();
    }
}
