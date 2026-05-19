<?php

namespace App\Filament\Tenant\Resources\EventTickets\Pages;

use App\Filament\Support\Pages\CreateRecordPage;
use App\Filament\Tenant\Resources\EventTickets\EventTicketResource;
use Filament\Support\Enums\Width;

class CreateEventTicket extends CreateRecordPage
{
    protected static string $resource = EventTicketResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
