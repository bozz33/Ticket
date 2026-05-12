<?php

namespace App\Filament\Platform\Resources\TenantSubscriptions\Pages;

use App\Filament\Platform\Resources\TenantSubscriptions\TenantSubscriptionResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditTenantSubscription extends EditRecordPage
{
    protected static string $resource = TenantSubscriptionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
