<?php

namespace App\Filament\Tenant\Resources\CallsForProjects\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\CallsForProjects\CallForProjectResource;
use Filament\Actions\CreateAction;

class ListCallForProjects extends ListRecordsPage
{
    protected static string $resource = CallForProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(static::getResource()::getUrl('create')),
        ];
    }
}
