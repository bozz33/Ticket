<?php

namespace Ticket\Payments\Contracts;

use App\Models\Tenant;

interface CheckoutManager
{
    public function options(
        Tenant $tenant,
        string $offerIdentifier,
        int $requestedQuantity = 1,
        ?string $paymentMethod = null,
        ?string $checkoutItemType = null,
    ): array;

    public function initialize(Tenant $tenant, array $payload): array;

    public function verify(Tenant $tenant, string $reference): array;
}
