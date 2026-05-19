<?php

namespace App\Filament\Platform\Resources\ReconciliationLogs\Pages;

use App\Filament\Platform\Resources\ReconciliationLogs\ReconciliationLogResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

class ManageReconciliationLogs extends ManageRecordsPage
{
    protected static string $resource = ReconciliationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
