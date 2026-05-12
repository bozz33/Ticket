<?php

namespace App\Enums;

enum RefundFeeBehavior: string
{
    case Refundable = 'refundable';
    case NonRefundable = 'non_refundable';

    public static function options(): array
    {
        return [
            self::Refundable->value => 'Remboursable',
            self::NonRefundable->value => 'Non remboursable',
        ];
    }
}
