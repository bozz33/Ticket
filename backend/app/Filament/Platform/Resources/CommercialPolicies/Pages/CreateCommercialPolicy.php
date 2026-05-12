<?php

namespace App\Filament\Platform\Resources\CommercialPolicies\Pages;

use App\Filament\Platform\Resources\CommercialPolicies\CommercialPolicyResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateCommercialPolicy extends CreateRecordPage
{
    protected static string $resource = CommercialPolicyResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
