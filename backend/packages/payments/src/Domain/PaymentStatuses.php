<?php

namespace Ticket\Payments\Domain;

class PaymentStatuses
{
    private const SUCCESSFUL = [
        'success',
        'successful',
        'confirmed',
        'completed',
        'paid',
    ];

    public static function isSuccessful(string $status): bool
    {
        return in_array(strtolower($status), self::SUCCESSFUL, true);
    }

    /**
     * @return array<int, string>
     */
    public static function successful(): array
    {
        return self::SUCCESSFUL;
    }
}
