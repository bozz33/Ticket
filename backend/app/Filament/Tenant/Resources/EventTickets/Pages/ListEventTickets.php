<?php

namespace App\Filament\Tenant\Resources\EventTickets\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\EventTickets\EventTicketResource;
use Filament\Actions\CreateAction;

class ListEventTickets extends ListRecordsPage
{
    protected static string $resource = EventTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(static::getResource()::getUrl('create')),
        ];
    }
}
