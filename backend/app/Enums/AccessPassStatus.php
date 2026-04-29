<?php

namespace App\Enums;

enum AccessPassStatus: string
{
    case Active = 'active';
    case Used = 'used';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public static function options(): array
    {
        return [
            self::Active->value => 'Actif',
            self::Used->value => 'Utilisé',
            self::Revoked->value => 'Révoqué',
            self::Expired->value => 'Expiré',
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Used => 'info',
            self::Revoked => 'danger',
            self::Expired => 'gray',
        };
    }

    public function isConsumable(): bool
    {
        return $this === self::Active;
    }
}
