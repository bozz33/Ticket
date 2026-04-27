<?php

namespace App\Filament\Platform\Resources\Settlements\Pages;

use App\Filament\Platform\Resources\Settlements\SettlementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSettlements extends ManageRecords
{
    protected static string $resource = SettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
