<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Pages;

use App\Filament\Platform\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditPlatformSetting extends EditRecordPage
{
    protected static string $resource = PlatformSettingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
