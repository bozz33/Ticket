<?php

namespace App\Filament\Platform\Resources\CompliancePolicies\Pages;

use App\Filament\Platform\Resources\CompliancePolicies\CompliancePolicyResource;
use App\Filament\Support\Pages\ManageRecordsPage;
use Filament\Actions\CreateAction;

class ManageCompliancePolicies extends ManageRecordsPage
{
    protected static string $resource = CompliancePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
