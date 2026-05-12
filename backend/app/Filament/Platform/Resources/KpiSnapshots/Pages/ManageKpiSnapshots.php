<?php

namespace App\Filament\Platform\Resources\KpiSnapshots\Pages;

use App\Filament\Platform\Resources\KpiSnapshots\KpiSnapshotResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageKpiSnapshots extends ManageRecordsPage
{
    protected static string $resource = KpiSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
