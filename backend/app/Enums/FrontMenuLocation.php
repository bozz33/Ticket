<?php

namespace App\Enums;

enum FrontMenuLocation: string
{
    case HeaderPrimary = 'header_primary';
    case HeaderUtility = 'header_utility';
    case FooterExplore = 'footer_explore';
    case FooterPlatform = 'footer_platform';
    case FooterBottom = 'footer_bottom';

    public static function options(): array
    {
        return [
            self::HeaderPrimary->value => 'Header principal',
            self::HeaderUtility->value => 'Header utilitaire',
            self::FooterExplore->value => 'Footer explorer',
            self::FooterPlatform->value => 'Footer plateforme',
            self::FooterBottom->value => 'Footer bas de page',
        ];
    }
}
