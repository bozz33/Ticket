<?php

namespace App\Filament\Platform\Resources\PlatformUsers\Pages;

use App\Filament\Platform\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Support\Pages\ListRecordsPage;

class ListPlatformUsers extends ListRecordsPage
{
    protected static string $resource = PlatformUserResource::class;
}
