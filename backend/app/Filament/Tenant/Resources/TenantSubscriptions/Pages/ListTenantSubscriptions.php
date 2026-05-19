<?php

namespace App\Filament\Tenant\Resources\TenantSubscriptions\Pages;

use App\Filament\Support\Pages\ListRecordsPage;
use App\Filament\Tenant\Resources\TenantSubscriptions\TenantSubscriptionResource;

class ListTenantSubscriptions extends ListRecordsPage
{
    protected static string $resource = TenantSubscriptionResource::class;
}
