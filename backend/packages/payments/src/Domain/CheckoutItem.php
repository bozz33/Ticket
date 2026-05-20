<?php

namespace Ticket\Payments\Domain;

use App\Models\Offer;

class CheckoutItem
{
    public function __construct(
        public readonly string $type,
        public readonly string $publicId,
        public readonly string $title,
        public readonly int $unitAmount,
        public readonly ?string $currencyCode,
        public readonly ?Offer $pricingOffer = null,
        public readonly ?string $orderableType = null,
        public readonly ?int $orderableId = null,
        public readonly array $metadata = [],
    ) {}

    public function is(string $type): bool
    {
        return $this->type === $type;
    }
}
