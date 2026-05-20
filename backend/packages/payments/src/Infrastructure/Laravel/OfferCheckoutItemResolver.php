<?php

namespace Ticket\Payments\Infrastructure\Laravel;

use App\Models\Offer;
use Ticket\Payments\Contracts\CheckoutItemResolver;
use Ticket\Payments\Domain\CheckoutItem;
use Ticket\Payments\Domain\CheckoutReservation;

class OfferCheckoutItemResolver implements CheckoutItemResolver
{
    public function resolve(string $identifier, ?string $type = null): ?CheckoutItem
    {
        if ($type !== null && ! in_array($type, ['offer', 'legacy_offer'], true)) {
            return null;
        }

        $offer = Offer::query()
            ->where('is_active', true)
            ->where(function ($query) use ($identifier): void {
                $query->where('public_id', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (! $offer instanceof Offer) {
            return null;
        }

        return new CheckoutItem(
            type: 'offer',
            publicId: $offer->public_id,
            title: $offer->name,
            unitAmount: $offer->price_amount,
            currencyCode: $offer->currency_code,
            pricingOffer: $offer,
            orderableType: Offer::class,
            orderableId: $offer->getKey(),
        );
    }

    public function quantityBounds(CheckoutItem $item): array
    {
        $offer = $item->pricingOffer;
        $min = max(1, (int) ($offer->min_per_order ?: 1));
        $available = $offer->quantity_total > 0
            ? max(0, (int) $offer->quantity_total - (int) $offer->quantity_sold)
            : null;
        $configuredMax = (int) ($offer->max_per_order ?: 0);
        $max = $configuredMax > 0 ? $configuredMax : ($available ?? max($min, 10));
        $maxPerAccount = (int) ($offer->max_per_account ?: 0);

        if ($available !== null) {
            $max = min($max, $available);
        }

        if ($maxPerAccount > 0) {
            $max = min($max, $maxPerAccount);
        }

        if ($max < $min) {
            throw new \RuntimeException('Cette offre est indisponible pour le moment.');
        }

        return [
            'min' => $min,
            'max' => $max,
            'max_per_account' => $maxPerAccount > 0 ? $maxPerAccount : null,
        ];
    }

    public function reserve(CheckoutItem $item, int $quantity, array $context = []): ?CheckoutReservation
    {
        return null;
    }

    public function release(array $checkout): bool
    {
        return false;
    }

    public function confirm(array $checkout, int $quantity): bool
    {
        return false;
    }
}
