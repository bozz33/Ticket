<?php

namespace App\Filament\Platform\Resources\KpiSnapshots\Pages;

use App\Filament\Platform\Resources\KpiSnapshots\KpiSnapshotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKpiSnapshots extends ManageRecords
{
    protected static string $resource = KpiSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
