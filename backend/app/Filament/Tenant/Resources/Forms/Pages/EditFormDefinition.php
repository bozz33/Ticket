<?php

namespace App\Filament\Tenant\Resources\Forms\Pages;

use App\Filament\Tenant\Resources\Forms\FormDefinitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFormDefinition extends EditRecord
{
    protected static string $resource = FormDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
