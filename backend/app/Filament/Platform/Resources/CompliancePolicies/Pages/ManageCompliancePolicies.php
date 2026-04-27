<?php

namespace App\Filament\Platform\Resources\CompliancePolicies\Pages;

use App\Filament\Platform\Resources\CompliancePolicies\CompliancePolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCompliancePolicies extends ManageRecords
{
    protected static string $resource = CompliancePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
