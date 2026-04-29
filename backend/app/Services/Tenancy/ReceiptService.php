<?php

namespace App\Services\Tenancy;

use App\Models\Receipt;
use Illuminate\Database\Eloquent\Collection;

class ReceiptService
{
    public function list(?string $status = null): Collection
    {
        return Receipt::query()
            ->with('order')
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
            ->with(['order.accessPasses'])
            ->where('public_id', $identifier)
            ->orWhere('reference', $identifier)
            ->first();
    }
}
