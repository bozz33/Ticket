<?php

namespace App\Filament\Platform\Resources\Tenants\Pages;

use App\Filament\Platform\Resources\Tenants\TenantResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListTenants extends ListRecordsPage
{
    protected static string $resource = TenantResource::class;
}
