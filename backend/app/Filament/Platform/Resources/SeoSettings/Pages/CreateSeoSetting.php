<?php

namespace App\Filament\Platform\Resources\SeoSettings\Pages;

use App\Filament\Platform\Resources\SeoSettings\SeoSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeoSetting extends CreateRecord
{
    protected static string $resource = SeoSettingResource::class;

    protected string|null $maxWidth = '7xl';
}
