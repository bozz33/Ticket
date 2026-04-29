<?php

namespace App\Enums;

enum ScanResult: string
{
    case Granted = 'granted';
    case AlreadyUsed = 'already_used';
    case Revoked = 'revoked';
    case Expired = 'expired';
    case NotFound = 'not_found';
    case Denied = 'denied';

    public static function options(): array
    {
        return [
            self::Granted->value => 'Accès accordé',
            self::AlreadyUsed->value => 'Déjà utilisé',
            self::Revoked->value => 'Révoqué',
            self::Expired->value => 'Expiré',
            self::NotFound->value => 'Introuvable',
            self::Denied->value => 'Refusé',
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::Granted => 'success',
            self::AlreadyUsed => 'warning',
            self::Revoked, self::Denied => 'danger',
            self::Expired, self::NotFound => 'gray',
        };
    }

    public function isSuccess(): bool
    {
        return $this === self::Granted;
    }
}
