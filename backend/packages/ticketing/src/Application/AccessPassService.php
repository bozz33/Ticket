<?php

namespace Ticket\Ticketing\Application;

use App\Models\AccessPass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AccessPassService
{
    public function list(?string $status = null, ?string $type = null): Collection
    {
        return AccessPass::query()
            ->with(['order', 'offer'])
            ->when(
                $status !== null && $status !== '',
                fn ($q) => $q->where('status', $status),
            )
            ->when(
                $type !== null && $type !== '',
                fn ($q) => $q->where('type', $type),
            )
            ->withCount('scans')
            ->latest()
            ->get();
    }

    public function listForBuyer(User $user, ?string $status = null, ?string $type = null): Collection
    {
        $email = Str::lower($user->email);

        return AccessPass::query()
            ->with(['order', 'offer'])
            ->where(function (Builder $query) use ($user, $email): void {
                $query->where('holder_user_id', $user->getKey())
                    ->orWhereRaw('LOWER(holder_email) = ?', [$email])
                    ->orWhereHas('order', function (Builder $orderQuery) use ($user, $email): void {
                        $orderQuery->where('buyer_user_id', $user->getKey())
                            ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
                    });
            })
            ->when(
                $status !== null && $status !== '',
                fn ($q) => $q->where('status', $status),
            )
            ->when(
                $type !== null && $type !== '',
                fn ($q) => $q->where('type', $type),
            )
            ->withCount('scans')
            ->latest()
            ->get();
    }

    public function findByIdentifier(string $identifier): ?AccessPass
    {
        if (Str::isUuid($identifier)) {
            return AccessPass::query()
                ->with(['order', 'offer', 'scans' => fn ($q) => $q->latest('scanned_at')->limit(20)])
                ->where('public_id', $identifier)
                ->first();
        }

        return AccessPass::query()
            ->with(['order', 'offer', 'scans' => fn ($q) => $q->latest('scanned_at')->limit(20)])
            ->where('access_code', $identifier)
            ->first();
    }

    public function findByIdentifierForBuyer(User $user, string $identifier): ?AccessPass
    {
        $email = Str::lower($user->email);

        if (Str::isUuid($identifier)) {
            return AccessPass::query()
                ->with(['order', 'offer', 'scans' => fn ($q) => $q->latest('scanned_at')->limit(20)])
                ->where(function (Builder $query) use ($user, $email): void {
                    $query->where('holder_user_id', $user->getKey())
                        ->orWhereRaw('LOWER(holder_email) = ?', [$email])
                        ->orWhereHas('order', function (Builder $orderQuery) use ($user, $email): void {
                            $orderQuery->where('buyer_user_id', $user->getKey())
                                ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
                        });
                })
                ->where('public_id', $identifier)
                ->first();
        }

        return AccessPass::query()
            ->with(['order', 'offer', 'scans' => fn ($q) => $q->latest('scanned_at')->limit(20)])
            ->where(function (Builder $query) use ($user, $email): void {
                $query->where('holder_user_id', $user->getKey())
                    ->orWhereRaw('LOWER(holder_email) = ?', [$email])
                    ->orWhereHas('order', function (Builder $orderQuery) use ($user, $email): void {
                        $orderQuery->where('buyer_user_id', $user->getKey())
                            ->orWhereRaw('LOWER(buyer_email) = ?', [$email]);
                    });
            })
            ->where('access_code', $identifier)
            ->first();
    }

    public function findByCode(string $accessCode): ?AccessPass
    {
        return AccessPass::query()
            ->with(['order', 'offer'])
            ->where('access_code', $accessCode)
            ->first();
    }
}
