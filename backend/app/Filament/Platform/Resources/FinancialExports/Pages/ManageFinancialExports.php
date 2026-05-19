<?php

namespace App\Filament\Platform\Resources\FinancialExports\Pages;

use App\Filament\Platform\Resources\FinancialExports\FinancialExportResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

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
