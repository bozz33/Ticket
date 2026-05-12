<?php

namespace App\Filament\Platform\Resources\GatewayFeeRules\Pages;

use App\Filament\Platform\Resources\GatewayFeeRules\GatewayFeeRuleResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateGatewayFeeRule extends CreateRecordPage
{
    protected static string $resource = GatewayFeeRuleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
