<?php

namespace App\Enums;

enum ScanResult: string
{
    case Granted = 'granted';
    case Denied = 'denied';
    case AlreadyUsed = 'already_used';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public static function options(): array
    {
        return [
            self::Granted->value => 'Accès autorisé',
            self::Denied->value => 'Accès refusé',
            self::AlreadyUsed->value => 'Pass déjà utilisé',
            self::Revoked->value => 'Pass révoqué',
            self::Expired->value => 'Pass expiré',
        ];
    }

    public function isSuccess(): bool
    {
        return $this === self::Granted;
    }
}
