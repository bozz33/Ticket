<?php

namespace App\Filament\Tenant\Resources\Settlements\Pages;

use App\Filament\Tenant\Resources\Settlements\SettlementResource;
use Filament\Resources\Pages\ListRecords;

class ListSettlements extends ListRecords
{
    protected static string $resource = SettlementResource::class;
}
