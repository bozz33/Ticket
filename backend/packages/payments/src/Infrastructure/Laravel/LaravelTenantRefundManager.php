<?php

namespace Ticket\Payments\Infrastructure\Laravel;

use App\Models\Order;
use App\Models\Refund;
use Ticket\Payments\Application\TenantRefundService;
use Ticket\Payments\Contracts\TenantRefundManager;

class LaravelTenantRefundManager implements TenantRefundManager
{
    public function __construct(
        private readonly TenantRefundService $refunds,
    ) {}

    public function apply(Refund $refund, Order $order): void
    {
        $this->refunds->apply($refund, $order);
    }

    public function assertOrderCanBeRefunded(Order $order): void
    {
        $this->refunds->assertOrderCanBeRefunded($order);
    }
}
