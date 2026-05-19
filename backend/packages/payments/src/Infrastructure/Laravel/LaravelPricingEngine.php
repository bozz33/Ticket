<?php

namespace Ticket\Payments\Infrastructure\Laravel;

use App\Models\Offer;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use Ticket\Payments\Application\PricingRuleEngine;
use Ticket\Payments\Contracts\PricingEngine;

class LaravelPricingEngine implements PricingEngine
{
    public function __construct(
        private readonly PricingRuleEngine $pricing,
    ) {}

    public function quote(
        Tenant $tenant,
        Offer $offer,
        int $quantity,
        ?PaymentGateway $gateway = null,
        ?string $paymentMethod = null,
    ): array {
        return $this->pricing->quote($tenant, $offer, $quantity, $gateway, $paymentMethod);
    }

    public function resolveGatewayForCurrency(string $currencyCode, ?string $paymentMethod = null): ?PaymentGateway
    {
        return $this->pricing->resolveGatewayForCurrency($currencyCode, $paymentMethod);
    }

    public function normalizePaymentChannel(?string $paymentMethod): string
    {
        return $this->pricing->normalizePaymentChannel($paymentMethod);
    }
}
