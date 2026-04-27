<?php

namespace App\Enums;

enum MonetizationMode: string
{
    case Free = 'free';
    case Subscription = 'subscription';
    case Commission = 'commission';
    case Hybrid = 'hybrid';

    public static function options(): array
    {
        return [
            self::Free->value => 'Gratuit',
            self::Subscription->value => 'Souscription',
            self::Commission->value => 'Commission',
            self::Hybrid->value => 'Hybride',
        ];
    }
}
