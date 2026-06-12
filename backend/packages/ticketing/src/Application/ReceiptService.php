<?php

namespace Ticket\Ticketing\Application;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ReceiptService
{
    public function list(?string $status = null): Collection
    {
        return Receipt::query()
            ->with(['order.offer'])
            ->when(
                $status !== null && $status !== '',
                fn ($q) => $q->where('status', $status),
            )
            ->latest('issued_at')
            ->get();
    }

    public function listForBuyer(User $user, ?string $status = null): Collection
    {
        $email = Str::lower($user->email);

        return Receipt::query()
            ->with('order')
            ->where(function (Builder $query) use ($user, $email): void {
                $query->where('buyer_user_id', $user->getKey())
                    ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
            })
            ->when(
                $status !== null && $status !== '',
                fn ($q) => $q->where('status', $status),
            )
            ->latest('issued_at')
            ->get();
    }

    public function findByIdentifier(string $identifier): ?Receipt
    {
        return Receipt::query()
            ->with(['order.accessPasses', 'order.offer'])
            ->where(fn (Builder $query) => $this->applyIdentifierLookup($query, $identifier))
            ->first();
    }

    public function findByIdentifierForBuyer(User $user, string $identifier): ?Receipt
    {
        $email = Str::lower($user->email);

        return Receipt::query()
            ->with(['order.accessPasses', 'order.offer'])
            ->where(function (Builder $query) use ($user, $email): void {
                $query->where('buyer_user_id', $user->getKey())
                    ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
            })
            ->where(fn (Builder $query) => $this->applyIdentifierLookup($query, $identifier))
            ->first();
    }

    private function applyIdentifierLookup(Builder $query, string $identifier): void
    {
        $query->where('reference', $identifier)
            ->orWhere('receipt_number', $identifier)
            ->orWhere('meta->transaction_reference', $identifier)
            ->orWhere('meta->gateway_reference', $identifier)
            ->orWhere('meta->gateway_transaction_id', $identifier)
            ->orWhereHas('order', function (Builder $orderQuery) use ($identifier): void {
                $orderQuery->where('reference', $identifier)
                    ->orWhere('transaction_reference', $identifier)
                    ->orWhere('meta->gateway_reference', $identifier)
                    ->orWhere('meta->gateway_transaction_id', $identifier);
            });

        if (Str::isUuid($identifier)) {
            $query->orWhere('public_id', $identifier);
        }
    }
}
