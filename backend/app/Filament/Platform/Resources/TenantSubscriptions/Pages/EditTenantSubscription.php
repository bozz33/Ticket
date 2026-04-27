<?php

namespace App\Filament\Platform\Resources\TenantSubscriptions\Pages;

use App\Filament\Platform\Resources\TenantSubscriptions\TenantSubscriptionResource;
use Filament\Resources\Pages\EditRecord;

class EditTenantSubscription extends EditRecord
{
    protected static string $resource = TenantSubscriptionResource::class;

    protected string|null $maxWidth = '7xl';
}
