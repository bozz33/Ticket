<?php

namespace App\Enums;

enum BillingInterval: string
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Biannual = 'biannual';
    case Yearly = 'yearly';

    public static function options(): array
    {
        return [
            self::OneTime->value => 'Ponctuel',
            self::Monthly->value => 'Mensuel',
            self::Quarterly->value => 'Trimestriel',
            self::Biannual->value => 'Semestriel',
            self::Yearly->value => 'Annuel',
        ];
    }
}
