<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Pages;

use App\Filament\Platform\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;

class CreatePlatformSetting extends CreateRecordPage
{
    protected static string $resource = PlatformSettingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function afterCreate(): void
    {
        Cache::forget('public_platform_configuration');
    }
}
