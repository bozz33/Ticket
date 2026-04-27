<?php

namespace App\Filament\Tenant\Resources\TenantSubscriptions\Pages;

use App\Filament\Tenant\Resources\TenantSubscriptions\TenantSubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListTenantSubscriptions extends ListRecords
{
    protected static string $resource = TenantSubscriptionResource::class;
}
