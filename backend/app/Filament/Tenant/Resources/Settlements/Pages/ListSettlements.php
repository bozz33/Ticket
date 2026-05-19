<?php

namespace App\Filament\Tenant\Resources\Settlements\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\Settlements\SettlementResource;
use Filament\Actions\CreateAction;

class ListSettlements extends ListRecordsPage
{
    protected static string $resource = SettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
