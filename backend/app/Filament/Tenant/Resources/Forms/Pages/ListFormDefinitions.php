<?php

namespace App\Filament\Tenant\Resources\Forms\Pages;

use App\Filament\Tenant\Resources\Forms\FormDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormDefinitions extends ListRecords
{
    protected static string $resource = FormDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
