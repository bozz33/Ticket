<?php

namespace App\Filament\Platform\Resources\SeoSettings\Pages;

use App\Filament\Platform\Resources\SeoSettings\SeoSettingResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreateSeoSetting extends CreateRecordPage
{
    protected static string $resource = SeoSettingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
