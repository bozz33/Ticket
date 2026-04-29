<?php

namespace App\Services\Tenancy;

use App\Models\AccessPass;
use Illuminate\Database\Eloquent\Collection;

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

    public function findByIdentifier(string $identifier): ?AccessPass
    {
        return AccessPass::query()
            ->with(['order', 'offer', 'scans' => fn ($q) => $q->latest('scanned_at')->limit(20)])
            ->where('public_id', $identifier)
            ->orWhere('access_code', $identifier)
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
