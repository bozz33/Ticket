<?php

namespace App\Filament\Platform\Resources\TenantSubscriptions\Pages;

use App\Filament\Platform\Resources\TenantSubscriptions\TenantSubscriptionResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ManageRecordsPage;

class ManageTenantSubscriptions extends ManageRecordsPage
{
    protected static string $resource = TenantSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
