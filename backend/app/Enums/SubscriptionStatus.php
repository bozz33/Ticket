<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Draft = 'draft';
    case Trialing = 'trialing';
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Replaced = 'replaced';
    case Expired = 'expired';

    public static function options(): array
    {
        return [
            self::Draft->value => 'Brouillon',
            self::Trialing->value => 'Essai',
            self::Active->value => 'Actif',
            self::Suspended->value => 'Suspendu',
            self::Cancelled->value => 'Annulé',
            self::Replaced->value => 'Remplacé',
            self::Expired->value => 'Expiré',
        ];
    }
}
