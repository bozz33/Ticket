<?php

namespace App\Filament\Tenant\Resources\EventTicketCategories\Pages;

use App\Filament\Tenant\Resources\EventTicketCategories\EventTicketCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditEventTicketCategory extends EditRecord
{
    protected static string $resource = EventTicketCategoryResource::class;
}
