<?php

namespace App\Filament\Platform\Resources\PayoutPolicies\Pages;

use App\Filament\Platform\Resources\PayoutPolicies\PayoutPolicyResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListPayoutPolicies extends ListRecordsPage
{
    protected static string $resource = PayoutPolicyResource::class;
}
