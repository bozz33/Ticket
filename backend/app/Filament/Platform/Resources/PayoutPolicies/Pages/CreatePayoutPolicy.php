<?php

namespace App\Filament\Platform\Resources\PayoutPolicies\Pages;

use App\Filament\Platform\Resources\PayoutPolicies\PayoutPolicyResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreatePayoutPolicy extends CreateRecordPage
{
    protected static string $resource = PayoutPolicyResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
