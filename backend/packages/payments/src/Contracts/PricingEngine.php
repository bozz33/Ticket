<?php

namespace Ticket\Payments\Contracts;

use App\Models\Offer;
use App\Models\PaymentGateway;
use App\Models\Tenant;

interface PricingEngine
{
    public function quote(
        Tenant $tenant,
        Offer $offer,
        int $quantity,
        ?PaymentGateway $gateway = null,
        ?string $paymentMethod = null,
    ): array;

    public function resolveGatewayForCurrency(string $currencyCode, ?string $paymentMethod = null): ?PaymentGateway;

    public function normalizePaymentChannel(?string $paymentMethod): string;
}
