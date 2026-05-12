<?php

namespace App\Filament\Platform\Resources\PayoutPolicies\Pages;

use App\Filament\Platform\Resources\PayoutPolicies\PayoutPolicyResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditPayoutPolicy extends EditRecordPage
{
    protected static string $resource = PayoutPolicyResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
