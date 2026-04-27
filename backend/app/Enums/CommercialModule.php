<?php

namespace App\Enums;

enum CommercialModule: string
{
    case Ticketing = 'ticketing';
    case CallsForProjects = 'calls_for_projects';
    case Training = 'training';
    case Stands = 'stands';
    case Crowdfunding = 'crowdfunding';
    case Salons = 'salons';

    public static function options(): array
    {
        return [
            self::Ticketing->value => 'Billetterie',
            self::CallsForProjects->value => 'Appels à projets',
            self::Training->value => 'Formations',
            self::Stands->value => 'Stands',
            self::Crowdfunding->value => 'Crowdfunding',
            self::Salons->value => 'Salons',
        ];
    }
}
