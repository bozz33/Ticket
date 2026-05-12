<?php

namespace App\Filament\Platform\Resources\CompliancePolicies\Pages;

use App\Filament\Platform\Resources\CompliancePolicies\CompliancePolicyResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

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
