<?php

namespace Ticket\Ticketing\Application;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

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

    public function listForBuyer(User $user, ?string $status = null, int $limit = 100): Collection
    {
        $email = Str::lower($user->email);
        $limit = min(max($limit, 1), 100);

        return Order::query()
            ->with(['offer', 'receipt'])
            ->where(function (Builder $query) use ($user, $email): void {
                $query->where('buyer_user_id', $user->getKey())
                    ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
            })
            ->when(
                $status !== null && $status !== '',
                fn ($q) => $q->where('status', $status),
            )
            ->withCount('accessPasses')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function findByIdentifier(string $identifier): ?Order
    {
        if (Str::isUuid($identifier)) {
            return Order::query()
                ->with(['offer', 'receipt', 'accessPasses'])
                ->where('public_id', $identifier)
                ->first();
        }

        return Order::query()
            ->with(['offer', 'receipt', 'accessPasses'])
            ->where('reference', $identifier)
            ->first();
    }

    public function findByIdentifierForBuyer(User $user, string $identifier): ?Order
    {
        $email = Str::lower($user->email);

        if (Str::isUuid($identifier)) {
            return Order::query()
                ->with(['offer', 'receipt', 'accessPasses'])
                ->where(function (Builder $query) use ($user, $email): void {
                    $query->where('buyer_user_id', $user->getKey())
                        ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
                })
                ->where('public_id', $identifier)
                ->first();
        }

        return Order::query()
            ->with(['offer', 'receipt', 'accessPasses'])
            ->where(function (Builder $query) use ($user, $email): void {
                $query->where('buyer_user_id', $user->getKey())
                    ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
            })
            ->where('reference', $identifier)
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
