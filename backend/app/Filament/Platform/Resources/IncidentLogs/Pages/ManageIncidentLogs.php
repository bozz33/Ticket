<?php

namespace App\Filament\Platform\Resources\IncidentLogs\Pages;

use App\Filament\Platform\Resources\IncidentLogs\IncidentLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageIncidentLogs extends ManageRecords
{
    protected static string $resource = IncidentLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
