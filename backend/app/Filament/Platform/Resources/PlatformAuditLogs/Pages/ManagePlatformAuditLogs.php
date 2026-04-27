<?php

namespace App\Filament\Platform\Resources\PlatformAuditLogs\Pages;

use App\Filament\Platform\Resources\PlatformAuditLogs\PlatformAuditLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePlatformAuditLogs extends ManageRecords
{
    protected static string $resource = PlatformAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
