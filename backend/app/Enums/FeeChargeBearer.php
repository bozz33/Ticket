<?php

namespace App\Enums;

enum FeeChargeBearer: string
{
    case Buyer = 'buyer';
    case Organizer = 'organizer';
    case Platform = 'platform';

    public static function options(): array
    {
        return [
            self::Buyer->value => 'Acheteur',
            self::Organizer->value => 'Organisateur',
            self::Platform->value => 'Plateforme',
        ];
    }
}
