<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public static function options(): array
    {
        return [
            self::Pending->value => 'En attente',
            self::Confirmed->value => 'Confirmée',
            self::Cancelled->value => 'Annulée',
            self::Refunded->value => 'Remboursée',
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Cancelled => 'danger',
            self::Refunded => 'gray',
        };
    }
}
