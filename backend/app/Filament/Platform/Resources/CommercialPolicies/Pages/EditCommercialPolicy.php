<?php

namespace App\Filament\Platform\Resources\CommercialPolicies\Pages;

use App\Filament\Platform\Resources\CommercialPolicies\CommercialPolicyResource;
use Filament\Resources\Pages\EditRecord;

class EditCommercialPolicy extends EditRecord
{
    protected static string $resource = CommercialPolicyResource::class;

    protected string|null $maxWidth = '7xl';
}
