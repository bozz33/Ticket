<?php

namespace App\Filament\Platform\Resources\PlatformSettings\Pages;

use App\Filament\Platform\Resources\PlatformSettings\PlatformSettingResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListPlatformSettings extends ListRecordsPage
{
    protected static string $resource = PlatformSettingResource::class;
}
