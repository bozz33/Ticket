<?php

namespace App\Filament\Platform\Resources\IncidentLogs\Pages;

use App\Filament\Platform\Resources\IncidentLogs\IncidentLogResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

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
