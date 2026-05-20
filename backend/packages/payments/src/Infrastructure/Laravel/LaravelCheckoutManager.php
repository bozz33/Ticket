<?php

namespace Ticket\Payments\Infrastructure\Laravel;

use App\Models\Tenant;
use Ticket\Payments\Application\PublicPaymentService;
use Ticket\Payments\Contracts\CheckoutManager;

class LaravelCheckoutManager implements CheckoutManager
{
    public function __construct(
        private readonly PublicPaymentService $payments,
    ) {}

    public function options(
        Tenant $tenant,
        string $offerIdentifier,
        int $requestedQuantity = 1,
        ?string $paymentMethod = null,
        ?string $checkoutItemType = null,
    ): array {
        return $this->payments->options($tenant, $offerIdentifier, $requestedQuantity, $paymentMethod, $checkoutItemType);
    }

    public function initialize(Tenant $tenant, array $payload): array
    {
        return $this->payments->initialize($tenant, $payload);
    }

    public function verify(Tenant $tenant, string $reference): array
    {
        return $this->payments->verify($tenant, $reference);
    }
}
