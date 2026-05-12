<?php

namespace App\Filament\Tenant\Resources\TenantSubscriptions\Pages;

use App\Filament\Tenant\Resources\TenantSubscriptions\TenantSubscriptionResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListTenantSubscriptions extends ListRecordsPage
{
    protected static string $resource = TenantSubscriptionResource::class;
}
