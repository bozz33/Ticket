<?php

namespace App\Enums;

enum FrontPageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public static function options(): array
    {
        return [
            self::Draft->value => 'Brouillon',
            self::Published->value => 'Publié',
            self::Archived->value => 'Archivé',
        ];
    }
}
