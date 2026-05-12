<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Refunded = 'refunded';
    case Failed = 'failed';
    case Rejected = 'rejected';

    public static function options(): array
    {
        return [
            self::Pending->value => 'En attente',
            self::Processing->value => 'En cours',
            self::Refunded->value => 'Remboursé',
            self::Failed->value => 'Échec',
            self::Rejected->value => 'Rejeté',
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Refunded => 'success',
            self::Failed => 'danger',
            self::Rejected => 'gray',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Refunded, self::Failed, self::Rejected], true);
    }
}
