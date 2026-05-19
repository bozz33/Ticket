<?php

namespace App\Filament\Tenant\Resources\EventTickets\Pages;

use App\Filament\Support\Pages\EditRecordPage;
use App\Filament\Tenant\Resources\EventTickets\EventTicketResource;
use Filament\Support\Enums\Width;

class EditEventTicket extends EditRecordPage
{
    protected static string $resource = EventTicketResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
