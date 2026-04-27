<?php

namespace App\Filament\Platform\Resources\CommercialPolicies\Pages;

use App\Filament\Platform\Resources\CommercialPolicies\CommercialPolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommercialPolicy extends CreateRecord
{
    protected static string $resource = CommercialPolicyResource::class;

    protected string|null $maxWidth = '7xl';
}
