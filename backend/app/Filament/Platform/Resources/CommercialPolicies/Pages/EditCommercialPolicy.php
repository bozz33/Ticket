<?php

namespace App\Filament\Platform\Resources\CommercialPolicies\Pages;

use App\Filament\Platform\Resources\CommercialPolicies\CommercialPolicyResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditCommercialPolicy extends EditRecordPage
{
    protected static string $resource = CommercialPolicyResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
