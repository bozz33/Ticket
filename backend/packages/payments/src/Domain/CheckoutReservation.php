<?php

namespace Ticket\Payments\Domain;

use Carbon\CarbonInterface;

class CheckoutReservation
{
    public function __construct(
        public readonly string $type,
        public readonly string $publicId,
        public readonly int $quantity,
        public readonly ?CarbonInterface $expiresAt = null,
        public readonly array $metadata = [],
    ) {}
}
