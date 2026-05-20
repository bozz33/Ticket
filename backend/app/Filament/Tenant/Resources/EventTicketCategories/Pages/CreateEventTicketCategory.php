<?php

namespace App\Filament\Tenant\Resources\EventTicketCategories\Pages;

use App\Filament\Tenant\Resources\EventTicketCategories\EventTicketCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventTicketCategory extends CreateRecord
{
    protected static string $resource = EventTicketCategoryResource::class;
}
