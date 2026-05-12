<?php

namespace App\Filament\Platform\Resources\PlatformUsers\Pages;

use App\Filament\Platform\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Support\Pages\CreateRecordPage;
use Filament\Support\Enums\Width;

class CreatePlatformUser extends CreateRecordPage
{
    protected static string $resource = PlatformUserResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
