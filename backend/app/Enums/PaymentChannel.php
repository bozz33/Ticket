<?php

namespace App\Enums;

enum PaymentChannel: string
{
    case Card = 'card';
    case CardLocal = 'card_local';
    case CardInternational = 'card_international';
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Wallet = 'wallet';

    public static function options(): array
    {
        return [
            self::Card->value => 'Carte',
            self::CardLocal->value => 'Carte locale',
            self::CardInternational->value => 'Carte internationale',
            self::MobileMoney->value => 'Mobile Money',
            self::BankTransfer->value => 'Virement bancaire',
            self::Wallet->value => 'Wallet',
        ];
    }
}
