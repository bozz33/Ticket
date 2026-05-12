<?php

namespace App\Filament\Platform\Resources\PlatformUsers\Pages;

use App\Filament\Platform\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Support\Pages\EditRecordPage;
use Filament\Support\Enums\Width;

class EditPlatformUser extends EditRecordPage
{
    protected static string $resource = PlatformUserResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
