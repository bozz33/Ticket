<?php

namespace App\Filament\Platform\Resources\ReconciliationLogs\Pages;

use App\Filament\Platform\Resources\ReconciliationLogs\ReconciliationLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageReconciliationLogs extends ManageRecords
{
    protected static string $resource = ReconciliationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
