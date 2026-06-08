<?php

namespace App\Filament\Tenant\Resources\Stands\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Stands\StandResource;
use Filament\Actions\CreateAction;

class ListStands extends ListRecordsPage
{
    protected static string $resource = StandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(static::getResource()::getUrl('create')),
        ];
    }
}
