<?php

namespace App\Filament\Platform\Resources\PlatformAuditLogs\Pages;

use App\Filament\Platform\Resources\PlatformAuditLogs\PlatformAuditLogResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

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
