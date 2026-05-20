<?php

namespace Ticket\Payments\Contracts;

use Ticket\Payments\Domain\CheckoutItem;
use Ticket\Payments\Domain\CheckoutReservation;

interface CheckoutItemResolver
{
    public function resolve(string $identifier, ?string $type = null): ?CheckoutItem;

    public function quantityBounds(CheckoutItem $item): array;

    public function reserve(CheckoutItem $item, int $quantity, array $context = []): ?CheckoutReservation;

    public function release(array $checkout): bool;

    public function confirm(array $checkout, int $quantity): bool;
}
