<?php

namespace Ticket\Ticketing\Infrastructure\Laravel;

use App\Models\Order;
use App\Models\User;
use Ticket\Ticketing\Application\BuyerRefundRequestService;
use Ticket\Ticketing\Contracts\BuyerRefundRequests;

class LaravelBuyerRefundRequests implements BuyerRefundRequests
{
    public function __construct(private readonly BuyerRefundRequestService $refundRequests) {}

    public function request(User $buyer, string $orderIdentifier, array $payload): Order
    {
        return $this->refundRequests->request($buyer, $orderIdentifier, $payload);
    }
}
