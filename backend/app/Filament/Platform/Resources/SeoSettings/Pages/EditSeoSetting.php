<?php

namespace App\Filament\Platform\Resources\SeoSettings\Pages;

use App\Filament\Platform\Resources\SeoSettings\SeoSettingResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditSeoSetting extends EditRecordPage
{
    protected static string $resource = SeoSettingResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
