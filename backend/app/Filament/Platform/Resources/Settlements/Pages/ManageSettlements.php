<?php

namespace App\Filament\Platform\Resources\Settlements\Pages;

use App\Filament\Platform\Resources\Settlements\SettlementResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageSettlements extends ManageRecordsPage
{
    protected static string $resource = SettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
