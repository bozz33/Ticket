<?php

namespace App\Filament\Tenant\Resources\FormDefinitions\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\FormDefinitions\FormDefinitionResource;
use Filament\Actions\CreateAction;

class ListFormDefinitions extends ListRecordsPage
{
    protected static string $resource = FormDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->url(static::getResource()::getUrl('create')),
        ];
    }
}
