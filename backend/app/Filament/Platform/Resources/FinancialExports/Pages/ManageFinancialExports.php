<?php

namespace App\Filament\Platform\Resources\FinancialExports\Pages;

use App\Filament\Platform\Resources\FinancialExports\FinancialExportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFinancialExports extends ManageRecords
{
    protected static string $resource = FinancialExportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
