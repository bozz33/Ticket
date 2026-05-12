<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Pages;

use App\Filament\Platform\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreatePlatformSetting extends CreateRecordPage
{
    protected static string $resource = PlatformSettingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
