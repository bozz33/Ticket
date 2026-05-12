<?php

namespace App\Filament\Platform\Resources\PlatformFeeRules\Pages;

use App\Filament\Platform\Resources\PlatformFeeRules\PlatformFeeRuleResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreatePlatformFeeRule extends CreateRecordPage
{
    protected static string $resource = PlatformFeeRuleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
