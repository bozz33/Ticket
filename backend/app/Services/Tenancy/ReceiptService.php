<?php

namespace App\Services\Tenancy;

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
        if (Str::isUuid($identifier)) {
            return Receipt::query()
                ->with(['order.accessPasses', 'order.offer'])
                ->where('public_id', $identifier)
                ->first();
        }

        return Receipt::query()
            ->with(['order.accessPasses', 'order.offer'])
            ->where('reference', $identifier)
            ->first();
    }

    public function findByIdentifierForBuyer(User $user, string $identifier): ?Receipt
    {
        $email = Str::lower($user->email);

        if (Str::isUuid($identifier)) {
            return Receipt::query()
                ->with(['order.accessPasses', 'order.offer'])
                ->where(function (Builder $query) use ($user, $email): void {
                    $query->where('buyer_user_id', $user->getKey())
                        ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
                })
                ->where('public_id', $identifier)
                ->first();
        }

        return Receipt::query()
            ->with(['order.accessPasses', 'order.offer'])
            ->where(function (Builder $query) use ($user, $email): void {
                $query->where('buyer_user_id', $user->getKey())
                    ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
            })
            ->where('reference', $identifier)
            ->first();
    }
}
