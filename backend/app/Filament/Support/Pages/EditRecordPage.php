<?php

namespace App\Filament\Support\Pages;

use Filament\Resources\Pages\EditRecord as BaseEditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

abstract class EditRecordPage extends BaseEditRecord
{
    protected Width|string|null $maxContentWidth = Width::Full;

    public function defaultForm(Schema $schema): Schema
    {
        if (! $schema->hasCustomColumns()) {
            $schema->columns(1);
        }

        return parent::defaultForm($schema);
    }
}
