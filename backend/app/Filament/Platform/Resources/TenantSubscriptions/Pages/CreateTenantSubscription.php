<?php

namespace App\Filament\Platform\Resources\TenantSubscriptions\Pages;

use App\Filament\Platform\Resources\TenantSubscriptions\TenantSubscriptionResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateTenantSubscription extends CreateRecordPage
{
    protected static string $resource = TenantSubscriptionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
