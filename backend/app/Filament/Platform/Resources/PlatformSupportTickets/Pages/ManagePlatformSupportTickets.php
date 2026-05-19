<?php

namespace App\Filament\Platform\Resources\PlatformSupportTickets\Pages;

use App\Filament\Platform\Resources\PlatformSupportTickets\PlatformSupportTicketResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

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
