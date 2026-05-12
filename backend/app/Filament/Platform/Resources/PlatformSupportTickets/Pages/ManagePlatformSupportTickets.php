<?php

namespace App\Filament\Platform\Resources\PlatformSupportTickets\Pages;

use App\Filament\Platform\Resources\PlatformSupportTickets\PlatformSupportTicketResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManagePlatformSupportTickets extends ManageRecordsPage
{
    protected static string $resource = PlatformSupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
