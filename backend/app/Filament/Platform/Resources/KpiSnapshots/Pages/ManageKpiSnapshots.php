<?php

namespace App\Filament\Platform\Resources\KpiSnapshots\Pages;

use App\Filament\Platform\Resources\KpiSnapshots\KpiSnapshotResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

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
