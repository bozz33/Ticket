<?php

namespace App\Filament\Platform\Resources\FinancialExports\Pages;

use App\Filament\Platform\Resources\FinancialExports\FinancialExportResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageFinancialExports extends ManageRecordsPage
{
    protected static string $resource = FinancialExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
