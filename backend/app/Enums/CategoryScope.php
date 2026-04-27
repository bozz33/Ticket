<?php

namespace App\Enums;

enum CategoryScope: string
{
    case Global = 'global';
    case Event = 'event';
    case Training = 'training';
    case Call = 'call';
    case Campaign = 'campaign';
    case Salon = 'salon';
    case Stand = 'stand';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
