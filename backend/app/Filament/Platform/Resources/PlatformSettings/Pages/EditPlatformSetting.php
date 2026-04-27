<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Pages;

use App\Filament\Platform\Resources\PlatformSettings\PlatformSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditPlatformSetting extends EditRecord
{
    protected static string $resource = PlatformSettingResource::class;

    protected string|null $maxWidth = '7xl';
}
