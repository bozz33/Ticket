<?php

namespace App\Filament\Tenant\Resources\Events\Pages;

use App\Filament\Tenant\Resources\Events\EventResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ListRecordsPage;

class ListEvents extends ListRecordsPage
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(static::getResource()::getUrl('create')),
        ];
    }
}
