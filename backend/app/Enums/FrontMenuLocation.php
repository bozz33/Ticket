<?php

namespace App\Enums;

enum FrontMenuLocation: string
{
    case HeaderTopLeft = 'header_top_left';
    case HeaderTopRight = 'header_top_right';
    case HeaderPrimary = 'header_primary';
    case HeaderUtility = 'header_utility';
    case HeaderActions = 'header_actions';
    case FooterExplore = 'footer_explore';
    case FooterPlatform = 'footer_platform';
    case FooterBottom = 'footer_bottom';

    public static function options(): array
    {
        return [
            self::HeaderTopLeft->value => 'Header top gauche',
            self::HeaderTopRight->value => 'Header top droite',
            self::HeaderPrimary->value => 'Header principal',
            self::HeaderUtility->value => 'Header utilitaire legacy',
            self::HeaderActions->value => 'Header actions',
            self::FooterExplore->value => 'Footer explorer',
            self::FooterPlatform->value => 'Footer plateforme',
            self::FooterBottom->value => 'Footer bas de page',
        ];
    }
}
