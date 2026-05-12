<?php

namespace App\Enums;

enum FeeCalculationMode: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case PercentagePlusFixed = 'percentage_plus_fixed';

    public static function options(): array
    {
        return [
            self::Percentage->value => 'Pourcentage',
            self::Fixed->value => 'Montant fixe',
            self::PercentagePlusFixed->value => 'Pourcentage + fixe',
        ];
    }
}
