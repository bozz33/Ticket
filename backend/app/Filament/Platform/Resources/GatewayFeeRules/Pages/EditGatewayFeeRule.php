<?php

namespace App\Filament\Platform\Resources\GatewayFeeRules\Pages;

use App\Filament\Platform\Resources\GatewayFeeRules\GatewayFeeRuleResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditGatewayFeeRule extends EditRecordPage
{
    protected static string $resource = GatewayFeeRuleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
