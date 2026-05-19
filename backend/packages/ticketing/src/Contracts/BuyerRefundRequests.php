<?php

namespace Ticket\Ticketing\Contracts;

use App\Models\Order;
use App\Models\User;

interface BuyerRefundRequests
{
    public function request(User $buyer, string $orderIdentifier, array $payload): Order;
}
