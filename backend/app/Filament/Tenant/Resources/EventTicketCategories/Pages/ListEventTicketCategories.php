<?php

namespace App\Filament\Tenant\Resources\EventTicketCategories\Pages;

use App\Filament\Tenant\Resources\EventTicketCategories\EventTicketCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEventTicketCategories extends ListRecords
{
    protected static string $resource = EventTicketCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
