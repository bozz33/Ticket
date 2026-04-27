<?php

namespace App\Filament\Platform\Resources\PlatformSupportTickets\Pages;

use App\Filament\Platform\Resources\PlatformSupportTickets\PlatformSupportTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePlatformSupportTickets extends ManageRecords
{
    protected static string $resource = PlatformSupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
