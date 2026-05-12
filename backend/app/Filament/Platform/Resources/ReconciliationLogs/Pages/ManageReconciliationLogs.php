<?php

namespace App\Filament\Platform\Resources\ReconciliationLogs\Pages;

use App\Filament\Platform\Resources\ReconciliationLogs\ReconciliationLogResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

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
