<?php

namespace App\Enums;

enum MonetizationMode: string
{
    case Free = 'free';
    case Commission = 'commission';
    case Hybrid = 'hybrid';

    public static function options(): array
    {
        return [
            self::Free->value => 'Gratuit',
            self::Commission->value => 'Commission',
            self::Hybrid->value => 'Hybride',
        ];
    }
}
