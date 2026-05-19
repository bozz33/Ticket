<?php

namespace Ticket\Payments\Contracts;

use App\Models\Order;
use App\Models\Refund;

interface TenantRefundManager
{
    public function apply(Refund $refund, Order $order): void;

    public function assertOrderCanBeRefunded(Order $order): void;
}
