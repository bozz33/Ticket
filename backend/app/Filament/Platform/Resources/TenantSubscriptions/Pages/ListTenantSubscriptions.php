<?php

namespace App\Filament\Platform\Resources\TenantSubscriptions\Pages;

use App\Filament\Platform\Resources\TenantSubscriptions\TenantSubscriptionResource;
use Filament\Actions\CreateAction;
use App\Filament\Support\Pages\ListRecordsPage;

class ListTenantSubscriptions extends ListRecordsPage
{
    protected static string $resource = TenantSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
