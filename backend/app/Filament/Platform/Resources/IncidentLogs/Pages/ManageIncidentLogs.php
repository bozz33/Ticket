<?php

namespace App\Filament\Platform\Resources\IncidentLogs\Pages;

use App\Filament\Platform\Resources\IncidentLogs\IncidentLogResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageIncidentLogs extends ManageRecordsPage
{
    protected static string $resource = IncidentLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
