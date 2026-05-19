<?php

namespace Ticket\Payments\Infrastructure\Laravel;

use App\Models\PlatformTransaction;
use App\Models\PlatformUser;
use App\Models\Refund;
use Ticket\Payments\Application\RefundService;
use Ticket\Payments\Contracts\RefundManager;

class LaravelRefundManager implements RefundManager
{
    public function __construct(
        private readonly RefundService $refunds,
    ) {}

    public function quote(PlatformTransaction $transaction, ?string $reasonCode = null): array
    {
        return $this->refunds->quote($transaction, $reasonCode);
    }

    public function create(PlatformTransaction $transaction, array $payload = [], ?PlatformUser $actor = null): Refund
    {
        return $this->refunds->create($transaction, $payload, $actor);
    }

    public function sync(Refund $refund): Refund
    {
        return $this->refunds->sync($refund);
    }
}
