<?php

namespace Ticket\Payments\Domain;

use RuntimeException;

/**
 * Integrity guard for confirmed payments. A fulfilled order must have been paid at least
 * the amount the server quoted at checkout, in the quoted currency. Both fulfillment entry
 * points (the gateway webhook and the client-polled verify call) run this guard before
 * creating an order so that a tampered or partial payment cannot mint passes for less than
 * the price.
 *
 * Overpayment is intentionally allowed: paying more than quoted is not a fraud against the
 * organizer, and rejecting it would break legitimate rounding/gateway quirks. A zero
 * expected amount (free checkout) is not checked.
 */
final class PaidAmountGuard
{
    public static function assertNotUnderpaid(
        int $expectedAmount,
        string $expectedCurrency,
        int $paidAmount,
        string $paidCurrency,
        string $reference,
    ): void {
        if ($expectedAmount <= 0) {
            return;
        }

        $expectedCurrency = strtoupper($expectedCurrency);
        $paidCurrency = strtoupper($paidCurrency);

        if ($paidCurrency !== $expectedCurrency) {
            throw new RuntimeException(sprintf(
                'Devise du paiement (%s) différente de la devise attendue (%s) pour la transaction %s.',
                $paidCurrency,
                $expectedCurrency,
                $reference,
            ));
        }

        if ($paidAmount < $expectedAmount) {
            throw new RuntimeException(sprintf(
                'Montant payé (%d %s) inférieur au montant attendu (%d %s) pour la transaction %s.',
                $paidAmount,
                $paidCurrency,
                $expectedAmount,
                $expectedCurrency,
                $reference,
            ));
        }
    }
}
