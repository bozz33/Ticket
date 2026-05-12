<?php

namespace App\Filament\Platform\Resources\PlatformAuditLogs\Pages;

use App\Filament\Platform\Resources\PlatformAuditLogs\PlatformAuditLogResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManagePlatformAuditLogs extends ManageRecordsPage
{
    protected static string $resource = PlatformAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
